<?php

namespace Tests\Feature\Checkout;

use App\Actions\Checkout\PlacePurchase;
use App\Data\CheckoutItem;
use App\Data\FulfillmentChoice;
use App\Enums\OrderStatus;
use App\Enums\PurchaseStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Address;
use App\Models\Listing;
use App\Models\Purchase;
use App\Models\Seller;
use App\Models\SellerLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsMarketplace;
use Tests\TestCase;

/**
 * Regras do checkout (ADR-0004 e ADR-0008).
 */
class PlacePurchaseTest extends TestCase
{
    use BuildsMarketplace, RefreshDatabase;

    public function test_it_creates_one_order_per_seller_with_server_side_totals(): void
    {
        $buyer = $this->buyer();
        $address = $buyer->addresses()->firstOrFail();

        $a = $this->listing(['price_cents' => 40000, 'stock' => 10]); // loja A, frete 30,00
        $b = $this->listing(['price_cents' => 25000, 'stock' => 10]); // loja B, frete 30,00

        $purchase = app(PlacePurchase::class)->handle(
            $buyer,
            [new CheckoutItem($a->id, 4), new CheckoutItem($b->id, 2)],
            [
                $a->seller_id => FulfillmentChoice::delivery($address->id),
                $b->seller_id => FulfillmentChoice::pickup(),
            ],
            'chave-1',
        );

        $this->assertSame(PurchaseStatus::Pending, $purchase->status);
        $this->assertCount(2, $purchase->orders);

        $orderA = $purchase->orders->firstWhere('seller_id', $a->seller_id);
        $orderB = $purchase->orders->firstWhere('seller_id', $b->seller_id);

        // A: 4 × 400,00 + frete 30,00
        $this->assertSame(160000, $orderA->subtotal_cents);
        $this->assertSame(3000, $orderA->shipping_cents);
        $this->assertSame('Avenida Paulista', $orderA->shipping_address['street']);

        // B: 2 × 250,00, retirada sem frete, no local onde o pneu está
        $this->assertSame(50000, $orderB->total_cents);
        $this->assertSame($b->seller_location_id, $orderB->pickup_location_id);

        $this->assertSame(163000 + 50000, $purchase->total_cents);
        $this->assertSame(OrderStatus::AwaitingPayment, $orderA->status);
        $this->assertNotNull($purchase->payment_reference);
        $this->assertNotNull($purchase->pix_code);

        // Estoque baixado na hora da compra
        $this->assertSame(6, $a->fresh()->stock);
        $this->assertSame(8, $b->fresh()->stock);

        // Primeiro evento da linha do tempo
        $this->assertSame(OrderStatus::AwaitingPayment, $orderA->events()->first()->status);
    }

    public function test_the_item_price_is_frozen_at_purchase_time(): void
    {
        $buyer = $this->buyer();
        $listing = $this->listing(['price_cents' => 40000]);

        $order = $this->purchase($buyer, $listing, 1)->orders()->firstOrFail();

        $listing->update(['price_cents' => 99900]);

        $this->assertSame(40000, $order->items()->firstOrFail()->unit_price_cents);
    }

    public function test_the_same_idempotency_key_returns_the_same_purchase(): void
    {
        $buyer = $this->buyer();
        $listing = $this->listing(['stock' => 10]);

        $first = $this->purchase($buyer, $listing, 4, 'mesma-chave');
        $second = $this->purchase($buyer, $listing, 4, 'mesma-chave');

        $this->assertTrue($first->is($second));
        $this->assertSame(1, Purchase::count());
        $this->assertSame(6, $listing->fresh()->stock, 'o estoque só pode baixar uma vez');
    }

    public function test_nothing_is_saved_when_one_item_has_no_stock(): void
    {
        $buyer = $this->buyer();
        $location = SellerLocation::factory()->create();
        $plenty = $this->listing(['stock' => 10], $location);
        $scarce = $this->listing(['stock' => 1], $location);

        try {
            app(PlacePurchase::class)->handle(
                $buyer,
                [new CheckoutItem($plenty->id, 4), new CheckoutItem($scarce->id, 2)],
                [$location->seller_id => FulfillmentChoice::pickup()],
                'chave-sem-estoque',
            );
            $this->fail('Deveria recusar a compra.');
        } catch (BusinessRuleException $e) {
            $this->assertStringContainsString('Estoque insuficiente', $e->getMessage());
        }

        // A transação desfez a baixa do primeiro item.
        $this->assertSame(10, $plenty->fresh()->stock);
        $this->assertSame(0, Purchase::count());
    }

    public function test_a_buyer_cannot_buy_from_their_own_store(): void
    {
        $listing = $this->listing();
        $owner = $listing->seller->owner;
        Address::factory()->for($owner)->create();

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('sua própria loja');

        $this->purchase($owner, $listing);
    }

    public function test_paused_listings_and_unapproved_stores_cannot_be_bought(): void
    {
        $buyer = $this->buyer();

        $paused = $this->listing(['status' => 'paused']);
        $pendingStore = Listing::factory()
            ->at(SellerLocation::factory()->for(Seller::factory()->pending())->create())
            ->create();

        foreach ([$paused, $pendingStore] as $listing) {
            try {
                $this->purchase($buyer, $listing);
                $this->fail('Deveria recusar a compra.');
            } catch (BusinessRuleException $e) {
                $this->assertStringContainsString('não está mais disponível', $e->getMessage());
            }
        }
    }

    public function test_delivery_must_use_an_address_of_the_buyer(): void
    {
        $buyer = $this->buyer();
        $someoneElsesAddress = Address::factory()->create();
        $listing = $this->listing();

        $this->expectException(BusinessRuleException::class);

        app(PlacePurchase::class)->handle(
            $buyer,
            [new CheckoutItem($listing->id, 1)],
            [$listing->seller_id => FulfillmentChoice::delivery($someoneElsesAddress->id)],
            'chave-endereco',
        );
    }

    public function test_delivery_is_refused_when_the_store_does_not_deliver(): void
    {
        $buyer = $this->buyer();
        $listing = $this->listing([], SellerLocation::factory()->for(Seller::factory()->pickupOnly())->create());

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('não faz entregas');

        $this->purchase($buyer, $listing);
    }

    public function test_pickup_is_refused_when_the_location_does_not_allow_it(): void
    {
        $buyer = $this->buyer();
        $listing = $this->listing([], SellerLocation::factory()->withoutPickup()->create());

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('não oferece retirada');

        app(PlacePurchase::class)->handle(
            $buyer,
            [new CheckoutItem($listing->id, 1)],
            [$listing->seller_id => FulfillmentChoice::pickup()],
            'chave-retirada',
        );
    }

    public function test_the_quantity_limit_per_item_is_enforced(): void
    {
        config(['marketplace.max_units_per_item' => 5]);
        $buyer = $this->buyer();
        $listing = $this->listing(['stock' => 50]);

        $this->expectException(BusinessRuleException::class);

        // Mesmo dividido em duas linhas, o total do anúncio passa do limite.
        app(PlacePurchase::class)->handle(
            $buyer,
            [new CheckoutItem($listing->id, 3), new CheckoutItem($listing->id, 3)],
            [$listing->seller_id => FulfillmentChoice::pickup()],
            'chave-limite',
        );
    }
}
