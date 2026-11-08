<?php

namespace App\Actions\Payments;

use App\Enums\FulfillmentMethod;
use App\Enums\OrderEventActor;
use App\Enums\OrderStatus;
use App\Enums\PurchaseStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Purchase;
use App\Payments\PaymentGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Marca uma compra como paga e libera os pedidos para os vendedores.
 *
 * Só é chamada depois que o gateway confirmou o pagamento por um webhook
 * assinado (ADR-0007). Nunca a partir de algo que o comprador ou um agente
 * diz ("já paguei").
 */
final class ConfirmPayment
{
    public function __construct(private readonly PaymentGateway $gateway) {}

    public function handle(Purchase $purchase, int $amountCents): void
    {
        $lateStatus = DB::transaction(function () use ($purchase, $amountCents): ?PurchaseStatus {
            // lockForUpdate trava a linha até o fim da transação: se o mesmo
            // pagamento for confirmado duas vezes ao mesmo tempo, a segunda
            // espera a primeira terminar e então vê a compra já paga.
            $purchase = Purchase::query()->whereKey($purchase->id)->lockForUpdate()->firstOrFail();

            if ($purchase->status === PurchaseStatus::Paid) {
                return null; // já processado: não faz nada (idempotente)
            }

            if ($amountCents !== $purchase->total_cents) {
                throw new BusinessRuleException(
                    "Valor pago ({$amountCents}) diferente do total da compra {$purchase->number()} ({$purchase->total_cents}).",
                );
            }

            if ($purchase->status !== PurchaseStatus::Pending) {
                // Pagou depois de a compra expirar: o estoque já voltou para
                // o vendedor, então devolvemos o dinheiro (fora da transação).
                return $purchase->status;
            }

            $purchase->forceFill(['status' => PurchaseStatus::Paid, 'paid_at' => now()])->save();

            foreach ($purchase->orders()->with('seller')->get() as $order) {
                if ($order->status !== OrderStatus::AwaitingPayment) {
                    continue;
                }

                // Primeira previsão de entrega: hoje + prazo informado pela loja.
                $estimate = $order->fulfillment_method === FulfillmentMethod::Delivery
                    ? now()->addDays((int) $order->seller->delivery_days)->endOfDay()
                    : null;

                $order->forceFill([
                    'status' => OrderStatus::Paid,
                    'estimated_delivery_at' => $estimate,
                ])->save();

                $order->recordEvent(
                    OrderEventActor::System,
                    'Pagamento confirmado. A loja já pode preparar o pedido.',
                    OrderStatus::Paid,
                    estimatedDeliveryAt: $estimate,
                );
            }

            return null;
        });

        if ($lateStatus !== null) {
            Log::warning('Pagamento recebido para compra não pendente; reembolsando.', [
                'purchase_id' => $purchase->id,
                'status' => $lateStatus->value,
            ]);

            $this->gateway->refund($purchase, $amountCents, 'Pagamento recebido após o prazo da compra.');
        }
    }
}
