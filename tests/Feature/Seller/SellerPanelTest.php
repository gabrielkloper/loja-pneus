<?php

namespace Tests\Feature\Seller;

use App\Enums\OrderStatus;
use App\Enums\SellerStatus;
use App\Livewire\Seller\ListingForm;
use App\Livewire\Seller\ManageOrder;
use App\Models\Listing;
use App\Models\Seller;
use App\Models\SellerLocation;
use App\Models\TireModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsMarketplace;
use Tests\TestCase;

/**
 * Cadastro de loja, anúncios e gestão de pedidos pelo vendedor.
 */
class SellerPanelTest extends TestCase
{
    use BuildsMarketplace, RefreshDatabase;

    /** @return array<string, mixed> */
    private function onboardingPayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'trade_name' => 'Pneus do Zé',
            'legal_name' => 'José da Silva',
            'document_type' => 'cpf',
            'document' => '529.982.247-25',
            'phone' => '41 99999-0000',
            'delivers' => '1',
            'delivery_fee' => '25,00',
            'delivery_days' => '2',
            'location' => [
                'name' => 'Oficina', 'zip_code' => '80010-000', 'street' => 'Rua A', 'number' => '10',
                'district' => 'Centro', 'city' => 'Curitiba', 'state' => 'PR', 'pickup_enabled' => '1',
            ],
        ], $overrides);
    }

    public function test_a_user_registers_a_store_that_starts_pending(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('seller.onboarding.store'), $this->onboardingPayload())
            ->assertRedirect(route('seller.dashboard'));

        $seller = Seller::query()->firstOrFail();
        $this->assertSame(SellerStatus::Pending, $seller->status);
        $this->assertSame('52998224725', $seller->document, 'documento salvo só com dígitos');
        $this->assertSame('pneus-do-ze', $seller->slug);
        $this->assertSame(2500, $seller->delivery_fee_cents);
        $this->assertSame('80010000', $seller->locations()->firstOrFail()->zip_code);

        $this->artisan('marketplace:approve-seller', ['slug' => 'pneus-do-ze'])->assertSuccessful();
        $this->assertSame(SellerStatus::Active, $seller->fresh()->status);
    }

    public function test_an_invalid_document_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('seller.onboarding.store'), $this->onboardingPayload(['document' => '123.456.789-00']))
            ->assertSessionHasErrors('document');

        $this->assertSame(0, Seller::count());
    }

    public function test_the_status_field_cannot_be_forced_through_the_form(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('seller.onboarding.store'), $this->onboardingPayload(['status' => 'active']));

        $this->assertSame(SellerStatus::Pending, Seller::query()->firstOrFail()->status);
    }

    public function test_a_seller_creates_a_listing_reusing_the_catalog(): void
    {
        $location = SellerLocation::factory()->create();
        $existing = TireModel::factory()->create([
            'brand' => 'Pirelli', 'model' => 'Cinturato P7', 'width' => 205, 'aspect_ratio' => 55,
            'rim' => '16.0', 'load_index' => 91, 'speed_index' => 'V',
        ]);

        Livewire::actingAs($location->seller->owner)
            ->test(ListingForm::class)
            ->set('brand', 'pirelli')
            ->set('model', 'Cinturato P7')
            ->set('width', '205')
            ->set('aspect_ratio', '55')
            ->set('rim', '16')
            ->set('load_index', '91')
            ->set('speed_index', 'v')
            ->set('condition', 'new')
            ->set('price', '459,90')
            ->set('stock', '12')
            ->set('seller_location_id', (string) $location->id)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('seller.listings.index'));

        $listing = Listing::query()->firstOrFail();
        $this->assertSame($existing->id, $listing->tire_model_id, 'deve reaproveitar o pneu do catálogo');
        $this->assertSame(45990, $listing->price_cents);
        $this->assertSame(1, TireModel::count());
    }

    public function test_remold_listings_require_inmetro_registration(): void
    {
        $location = SellerLocation::factory()->create();

        Livewire::actingAs($location->seller->owner)
            ->test(ListingForm::class)
            ->set('brand', 'Remold Sul')
            ->set('model', 'R1')
            ->set('width', '175')
            ->set('aspect_ratio', '70')
            ->set('rim', '14')
            ->set('load_index', '84')
            ->set('speed_index', 'T')
            ->set('condition', 'remold')
            ->set('price', '199,90')
            ->set('stock', '5')
            ->call('save')
            ->assertHasErrors('inmetro_registration');
    }

    public function test_the_seller_ships_an_order_from_the_panel(): void
    {
        $purchase = $this->purchase($this->buyer(), $this->listing());
        $this->sendPaidWebhook($purchase)->assertOk();
        $order = $purchase->orders()->firstOrFail();

        Livewire::actingAs($order->seller->owner)
            ->test(ManageOrder::class, ['order' => $order])
            ->assertSet('nextStatus', OrderStatus::Preparing->value)
            ->call('advance')
            ->assertHasNoErrors()
            ->assertSet('nextStatus', OrderStatus::Shipped->value)
            ->set('estimatedDate', now()->addDays(2)->format('Y-m-d'))
            ->set('trackingCode', 'BR999')
            ->call('advance')
            ->assertHasNoErrors();

        $order->refresh();
        $this->assertSame(OrderStatus::Shipped, $order->status);
        $this->assertSame('BR999', $order->tracking_code);

        // O comprador vê a atualização na página do pedido.
        $this->actingAs($order->buyer)
            ->get(route('orders.show', $order))
            ->assertSee('Enviado')
            ->assertSee('BR999');
    }
}
