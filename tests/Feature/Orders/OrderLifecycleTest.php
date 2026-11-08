<?php

namespace Tests\Feature\Orders;

use App\Actions\Checkout\CancelPurchase;
use App\Actions\Checkout\PlacePurchase;
use App\Actions\Orders\AdvanceOrderStatus;
use App\Actions\Orders\CancelOrderBySeller;
use App\Actions\Orders\UpdateDeliveryEstimate;
use App\Data\CheckoutItem;
use App\Data\FulfillmentChoice;
use App\Enums\OrderStatus;
use App\Enums\PurchaseStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsMarketplace;
use Tests\TestCase;

/**
 * Máquina de estados do pedido e linha do tempo (ADR-0005).
 */
class OrderLifecycleTest extends TestCase
{
    use BuildsMarketplace, RefreshDatabase;

    private function paidDeliveryOrder(): Order
    {
        $purchase = $this->purchase($this->buyer(), $this->listing(['stock' => 10]));
        $this->sendPaidWebhook($purchase)->assertOk();

        return $purchase->orders()->firstOrFail();
    }

    private function sellerOf(Order $order): User
    {
        return $order->seller->owner;
    }

    public function test_a_delivery_order_follows_the_full_path_with_a_timeline(): void
    {
        $order = $this->paidDeliveryOrder();
        $seller = $this->sellerOf($order);
        $advance = app(AdvanceOrderStatus::class);

        $advance->handle($order, OrderStatus::Preparing, $seller);
        $advance->handle($order, OrderStatus::Shipped, $seller, now()->addDays(2), 'Transportadora X', 'BR123');
        $advance->handle($order, OrderStatus::OutForDelivery, $seller);
        $advance->handle($order, OrderStatus::Delivered, $seller);

        $order->refresh();
        $this->assertSame(OrderStatus::Delivered, $order->status);
        $this->assertSame('BR123', $order->tracking_code);

        // criado, pago, preparando, enviado, saiu para entrega, entregue
        $this->assertSame(
            ['awaiting_payment', 'paid', 'preparing', 'shipped', 'out_for_delivery', 'delivered'],
            $order->events->pluck('status')->map->value->all(),
        );
    }

    public function test_shipping_requires_a_delivery_estimate(): void
    {
        $order = $this->paidDeliveryOrder();
        $seller = $this->sellerOf($order);

        app(AdvanceOrderStatus::class)->handle($order, OrderStatus::Preparing, $seller);

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('previsão de entrega');

        app(AdvanceOrderStatus::class)->handle($order, OrderStatus::Shipped, $seller);
    }

    public function test_steps_cannot_be_skipped(): void
    {
        $order = $this->paidDeliveryOrder();

        $this->expectException(BusinessRuleException::class);

        app(AdvanceOrderStatus::class)->handle($order, OrderStatus::Delivered, $this->sellerOf($order));
    }

    public function test_a_pickup_order_is_never_shipped(): void
    {
        $buyer = $this->buyer();
        $listing = $this->listing();
        $purchase = app(PlacePurchase::class)->handle(
            $buyer,
            [new CheckoutItem($listing->id, 2)],
            [$listing->seller_id => FulfillmentChoice::pickup()],
            'retirada',
        );
        $this->sendPaidWebhook($purchase)->assertOk();

        $order = $purchase->orders()->firstOrFail();
        $seller = $this->sellerOf($order);

        app(AdvanceOrderStatus::class)->handle($order, OrderStatus::Preparing, $seller);

        $this->assertSame([OrderStatus::ReadyForPickup], $order->status->sellerCanMoveTo($order->fulfillment_method));

        app(AdvanceOrderStatus::class)->handle($order, OrderStatus::ReadyForPickup, $seller);
        app(AdvanceOrderStatus::class)->handle($order, OrderStatus::PickedUp, $seller);

        $this->assertSame(OrderStatus::PickedUp, $order->fresh()->status);
    }

    public function test_the_seller_can_change_the_delivery_estimate_and_it_is_logged(): void
    {
        $order = $this->paidDeliveryOrder();
        $newDate = now()->addDays(7)->endOfDay();

        app(UpdateDeliveryEstimate::class)->handle($order, $newDate, $this->sellerOf($order));

        $this->assertTrue($order->fresh()->estimated_delivery_at->isSameDay($newDate));
        $this->assertStringContainsString($newDate->format('d/m/Y'), (string) $order->events()->get()->last()->message);
    }

    public function test_seller_cancellation_restores_stock_and_records_the_refund(): void
    {
        $order = $this->paidDeliveryOrder();
        $listing = $order->items()->firstOrFail()->listing;
        $this->assertSame(6, $listing->stock);

        app(CancelOrderBySeller::class)->handle($order, $this->sellerOf($order), 'Pneu avariado no estoque');

        $order->refresh();
        $this->assertSame(OrderStatus::Cancelled, $order->status);
        $this->assertSame($order->total_cents, $order->refunded_cents);
        $this->assertSame(10, $listing->fresh()->stock);
    }

    public function test_a_shipped_order_cannot_be_cancelled_by_the_seller(): void
    {
        $order = $this->paidDeliveryOrder();
        $seller = $this->sellerOf($order);
        app(AdvanceOrderStatus::class)->handle($order, OrderStatus::Preparing, $seller);
        app(AdvanceOrderStatus::class)->handle($order, OrderStatus::Shipped, $seller, now()->addDay());

        $this->expectException(BusinessRuleException::class);

        app(CancelOrderBySeller::class)->handle($order, $seller, 'Desisti');
    }

    public function test_unpaid_purchases_expire_and_return_the_stock(): void
    {
        $listing = $this->listing(['stock' => 10]);
        $purchase = $this->purchase($this->buyer(), $listing, 4);
        $this->assertSame(6, $listing->fresh()->stock);

        // Ainda dentro do prazo: nada acontece.
        $this->artisan('purchases:expire');
        $this->assertSame(PurchaseStatus::Pending, $purchase->fresh()->status);

        $this->travel(31)->minutes();
        $this->artisan('purchases:expire');

        $this->assertSame(PurchaseStatus::Expired, $purchase->fresh()->status);
        $this->assertSame(OrderStatus::Cancelled, $purchase->orders()->firstOrFail()->status);
        $this->assertSame(10, $listing->fresh()->stock);
    }

    public function test_the_buyer_can_cancel_before_paying(): void
    {
        $buyer = $this->buyer();
        $listing = $this->listing(['stock' => 10]);
        $purchase = $this->purchase($buyer, $listing, 4);

        app(CancelPurchase::class)->handle($purchase, PurchaseStatus::Cancelled, $buyer);

        $this->assertSame(PurchaseStatus::Cancelled, $purchase->fresh()->status);
        $this->assertSame(10, $listing->fresh()->stock);

        // Depois de cancelada, não dá para cancelar de novo (nem pagar).
        $this->expectException(BusinessRuleException::class);
        app(CancelPurchase::class)->handle($purchase, PurchaseStatus::Cancelled, $buyer);
    }
}
