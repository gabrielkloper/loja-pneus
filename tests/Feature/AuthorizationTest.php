<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsMarketplace;
use Tests\TestCase;

/**
 * Cada usuário só enxerga e altera o que é dele.
 */
class AuthorizationTest extends TestCase
{
    use BuildsMarketplace, RefreshDatabase;

    public function test_a_buyer_cannot_see_someone_elses_order_or_purchase(): void
    {
        $purchase = $this->purchase($this->buyer(), $this->listing());
        $order = $purchase->orders()->firstOrFail();
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->get(route('orders.show', $order))->assertForbidden();
        $this->actingAs($intruder)->get(route('purchases.show', $purchase))->assertForbidden();
        $this->actingAs($intruder)->post(route('purchases.cancel', $purchase))->assertForbidden();
    }

    public function test_buyer_and_seller_can_see_the_order(): void
    {
        $buyer = $this->buyer();
        $order = $this->purchase($buyer, $this->listing())->orders()->firstOrFail();

        $this->actingAs($buyer)->get(route('orders.show', $order))->assertOk()->assertSee($order->number());
        $this->actingAs($order->seller->owner)->get(route('seller.orders.show', $order))->assertOk();
    }

    public function test_a_seller_cannot_manage_another_sellers_order(): void
    {
        $order = $this->purchase($this->buyer(), $this->listing())->orders()->firstOrFail();
        $otherSeller = $this->listing()->seller->owner;

        $this->actingAs($otherSeller)->get(route('seller.orders.show', $order))->assertForbidden();
    }

    public function test_a_seller_cannot_edit_another_sellers_listing(): void
    {
        $listing = $this->listing();
        $otherSeller = $this->listing()->seller->owner;

        $this->actingAs($otherSeller)->get(route('seller.listings.edit', $listing))->assertForbidden();
    }

    public function test_users_without_a_store_are_sent_to_onboarding(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('seller.dashboard'))
            ->assertRedirect(route('seller.onboarding.create'));
    }

    public function test_paused_listings_are_hidden_from_the_public_but_visible_to_the_owner(): void
    {
        $listing = $this->listing(['status' => 'paused']);

        $this->get(route('listings.show', $listing))->assertNotFound();
        $this->actingAs($listing->seller->owner)->get(route('listings.show', $listing))->assertOk();
    }

    public function test_the_webhook_simulation_is_only_for_the_buyer(): void
    {
        $purchase = $this->purchase($this->buyer(), $this->listing());

        $this->actingAs(User::factory()->create())
            ->post(route('purchases.simulate-payment', $purchase))
            ->assertForbidden();
    }
}
