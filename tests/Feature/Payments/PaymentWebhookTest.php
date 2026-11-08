<?php

namespace Tests\Feature\Payments;

use App\Enums\OrderStatus;
use App\Enums\PurchaseStatus;
use App\Models\OrderEvent;
use App\Models\PaymentWebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsMarketplace;
use Tests\TestCase;

/**
 * Confirmação de pagamento por webhook assinado (ADR-0007).
 */
class PaymentWebhookTest extends TestCase
{
    use BuildsMarketplace, RefreshDatabase;

    public function test_a_signed_notification_marks_the_purchase_and_orders_as_paid(): void
    {
        $purchase = $this->purchase($this->buyer(), $this->listing());

        $this->sendPaidWebhook($purchase)->assertOk();

        $purchase->refresh();
        $order = $purchase->orders()->firstOrFail();

        $this->assertSame(PurchaseStatus::Paid, $purchase->status);
        $this->assertNotNull($purchase->paid_at);
        $this->assertSame(OrderStatus::Paid, $order->status);
        // Loja da factory entrega em 3 dias: previsão calculada no pagamento.
        $this->assertTrue($order->estimated_delivery_at->isSameDay(now()->addDays(3)));
    }

    public function test_an_unsigned_or_forged_notification_is_rejected(): void
    {
        $purchase = $this->purchase($this->buyer(), $this->listing());

        $this->sendPaidWebhook($purchase, signature: 'assinatura-falsa')->assertStatus(401);

        $this->assertSame(PurchaseStatus::Pending, $purchase->fresh()->status);
        $this->assertSame(0, PaymentWebhookEvent::count());
    }

    public function test_the_same_event_delivered_twice_is_processed_once(): void
    {
        $purchase = $this->purchase($this->buyer(), $this->listing());

        $this->sendPaidWebhook($purchase, ['id' => 'evt_repetido'])->assertOk();
        $this->sendPaidWebhook($purchase, ['id' => 'evt_repetido'])->assertOk();

        $this->assertSame(1, PaymentWebhookEvent::count());
        $this->assertSame(1, OrderEvent::query()->where('status', OrderStatus::Paid)->count());
    }

    public function test_a_payment_with_the_wrong_amount_does_not_confirm_the_purchase(): void
    {
        $purchase = $this->purchase($this->buyer(), $this->listing());

        $this->sendPaidWebhook($purchase, ['amount_cents' => 100])->assertOk();

        $this->assertSame(PurchaseStatus::Pending, $purchase->fresh()->status);
    }

    public function test_a_payment_after_expiry_does_not_revive_the_purchase(): void
    {
        $purchase = $this->purchase($this->buyer(), $this->listing());

        $this->travel(31)->minutes();
        $this->artisan('purchases:expire')->assertSuccessful();

        $this->sendPaidWebhook($purchase)->assertOk();

        // Continua expirada; o valor é reembolsado (FakeGateway registra no log).
        $this->assertSame(PurchaseStatus::Expired, $purchase->fresh()->status);
    }

    public function test_the_simulate_button_goes_through_the_same_signed_flow(): void
    {
        $buyer = $this->buyer();
        $purchase = $this->purchase($buyer, $this->listing());

        $this->actingAs($buyer)
            ->post(route('purchases.simulate-payment', $purchase))
            ->assertRedirect(route('purchases.show', $purchase));

        $this->assertSame(PurchaseStatus::Paid, $purchase->fresh()->status);
        $this->assertSame(1, PaymentWebhookEvent::count());
    }
}
