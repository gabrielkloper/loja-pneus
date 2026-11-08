<?php

namespace Tests\Feature;

use App\Enums\PurchaseStatus;
use App\Livewire\CatalogSearch;
use App\Livewire\ShowCart;
use App\Models\Purchase;
use App\Models\Seller;
use App\Models\SellerLocation;
use App\Models\TireModel;
use App\Support\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsMarketplace;
use Tests\TestCase;

/**
 * Fluxo do comprador pelas telas: vitrine → anúncio → carrinho → compra.
 */
class StorefrontTest extends TestCase
{
    use BuildsMarketplace, RefreshDatabase;

    public function test_the_catalog_filters_by_size_and_hides_unapproved_stores(): void
    {
        $match = $this->listing(['tire_model_id' => TireModel::factory()->create(['brand' => 'Pirelli', 'width' => 205, 'aspect_ratio' => 55, 'rim' => '16.0'])->id]);
        $otherSize = $this->listing(['tire_model_id' => TireModel::factory()->create(['brand' => 'Michelin', 'width' => 175, 'aspect_ratio' => 70, 'rim' => '14.0'])->id]);
        $pendingStore = $this->listing(
            ['tire_model_id' => TireModel::factory()->create(['brand' => 'Goodyear'])->id],
            SellerLocation::factory()->for(Seller::factory()->pending())->create(),
        );

        Livewire::test(CatalogSearch::class)
            ->assertSee('Pirelli')
            ->assertSee('Michelin')
            ->assertDontSee('Goodyear')
            ->set('size', '205/55R16')
            ->assertSee('Pirelli')
            ->assertDontSee('Michelin');

        $this->get(route('home'))->assertOk();
        $this->assertNotNull($match->id + $otherSize->id + $pendingStore->id);
    }

    public function test_the_catalog_filters_by_state_and_pickup(): void
    {
        $this->listing(['tire_model_id' => TireModel::factory()->create(['brand' => 'Pirelli'])->id]); // PR, com retirada
        $this->listing(
            ['tire_model_id' => TireModel::factory()->create(['brand' => 'Bridgestone'])->id],
            SellerLocation::factory()->withoutPickup()->create(['state' => 'SP', 'city' => 'Campinas']),
        );

        Livewire::test(CatalogSearch::class)
            ->set('state', 'SP')
            ->assertSee('Bridgestone')
            ->assertDontSee('Pirelli')
            ->set('state', '')
            ->set('pickupOnly', true)
            ->assertSee('Pirelli')
            ->assertDontSee('Bridgestone');
    }

    public function test_a_buyer_adds_to_cart_and_checks_out(): void
    {
        $buyer = $this->buyer();
        $listing = $this->listing(['stock' => 10]);

        $this->get(route('listings.show', $listing))->assertOk()->assertSee($listing->tireModel->model);

        $this->actingAs($buyer)
            ->post(route('cart.store'), ['listing_id' => $listing->id, 'quantity' => 4])
            ->assertRedirect(route('cart'));

        $this->assertSame([$listing->id => 4], app(Cart::class)->items());

        Livewire::actingAs($buyer)
            ->test(ShowCart::class)
            ->assertSee($listing->seller->trade_name)
            ->call('placeOrder')
            ->assertHasNoErrors()
            ->assertRedirect();

        $purchase = Purchase::query()->firstOrFail();
        $this->assertSame(PurchaseStatus::Pending, $purchase->status);
        $this->assertSame(6, $listing->fresh()->stock);
        $this->assertSame([], app(Cart::class)->items(), 'o carrinho é esvaziado após a compra');

        $this->actingAs($buyer)->get(route('purchases.show', $purchase))->assertOk()->assertSee('Pague com Pix');
    }

    public function test_checkout_shows_business_errors_instead_of_crashing(): void
    {
        $buyer = $this->buyer();
        $listing = $this->listing(['stock' => 2]);

        app(Cart::class)->set($listing->id, 4);

        Livewire::actingAs($buyer)
            ->test(ShowCart::class)
            ->call('placeOrder')
            ->assertHasErrors('checkout');

        $this->assertSame(0, Purchase::count());
    }

    public function test_guests_are_sent_to_login_when_checking_out(): void
    {
        $listing = $this->listing();
        app(Cart::class)->set($listing->id, 1);

        Livewire::test(ShowCart::class)
            ->assertSee('Entre na sua conta')
            ->call('placeOrder')
            ->assertRedirect(route('login'));
    }

    public function test_the_store_page_lists_its_listings(): void
    {
        $listing = $this->listing();

        $this->get(route('stores.show', $listing->seller))
            ->assertOk()
            ->assertSee($listing->seller->trade_name)
            ->assertSee($listing->tireModel->model);
    }
}
