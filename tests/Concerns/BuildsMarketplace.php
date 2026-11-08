<?php

namespace Tests\Concerns;

use App\Actions\Checkout\PlacePurchase;
use App\Data\CheckoutItem;
use App\Data\FulfillmentChoice;
use App\Models\Address;
use App\Models\Listing;
use App\Models\Purchase;
use App\Models\Seller;
use App\Models\SellerLocation;
use App\Models\User;
use App\Payments\FakeGateway;
use App\Payments\PaymentGateway;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

/**
 * Atalhos para montar cenários de marketplace nos testes.
 */
trait BuildsMarketplace
{
    protected function buyer(): User
    {
        $user = User::factory()->create();
        Address::factory()->for($user)->create();

        return $user;
    }

    /** Anúncio ativo de uma loja ativa (que entrega e aceita retirada). */
    protected function listing(array $attributes = [], ?SellerLocation $location = null): Listing
    {
        $location ??= SellerLocation::factory()->for(Seller::factory())->create();

        return Listing::factory()->at($location)->create($attributes);
    }

    /**
     * Fecha uma compra de um anúncio com entrega no primeiro endereço do comprador.
     */
    protected function purchase(User $buyer, Listing $listing, int $quantity = 4, ?string $key = null): Purchase
    {
        return app(PlacePurchase::class)->handle(
            $buyer,
            [new CheckoutItem($listing->id, $quantity)],
            [$listing->seller_id => FulfillmentChoice::delivery($buyer->addresses()->firstOrFail()->id)],
            $key ?? (string) Str::uuid(),
        );
    }

    protected function fakeGateway(): FakeGateway
    {
        $gateway = app(PaymentGateway::class);
        $this->assertInstanceOf(FakeGateway::class, $gateway);

        return $gateway;
    }

    /**
     * Envia ao webhook a notificação "pago", assinada como o gateway faria.
     *
     * @param  array<string, mixed>  $overrides
     */
    protected function sendPaidWebhook(Purchase $purchase, array $overrides = [], ?string $signature = null): TestResponse
    {
        $gateway = $this->fakeGateway();
        $body = (string) json_encode(array_merge($gateway->paidPayload($purchase->fresh()), $overrides));

        return $this->call(
            'POST',
            route('webhooks.payments'),
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_FAKE_SIGNATURE' => $signature ?? $gateway->sign($body),
            ],
            content: $body,
        );
    }
}
