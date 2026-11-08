<?php

namespace App\Actions\Payments;

use App\Exceptions\BusinessRuleException;
use App\Models\PaymentWebhookEvent;
use App\Models\Purchase;
use App\Payments\PaymentGateway;
use App\Payments\PaymentNotification;
use App\Payments\PaymentNotificationType;
use Illuminate\Support\Facades\Log;

/**
 * Processa uma notificação de pagamento já autenticada.
 *
 * Gateways reais reenviam a mesma notificação várias vezes (é assim que
 * garantem a entrega). Por isso cada evento é registrado com uma chave
 * única e processado no máximo uma vez.
 */
final class ProcessPaymentNotification
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly ConfirmPayment $confirmPayment,
    ) {}

    public function handle(PaymentNotification $notification): void
    {
        // createOrFirst tenta inserir; se o índice único (provider, event_id)
        // acusar duplicata, devolve o registro existente. Funciona mesmo com
        // duas entregas simultâneas do mesmo evento.
        $record = PaymentWebhookEvent::createOrFirst(
            ['provider' => $this->gateway->name(), 'event_id' => $notification->eventId],
            ['type' => $notification->type->value, 'payload' => $notification->payload],
        );

        if ($record->processed_at !== null) {
            return; // já processado antes
        }

        if ($notification->type === PaymentNotificationType::Paid) {
            $purchase = Purchase::query()
                ->where('payment_reference', $notification->chargeReference)
                ->first();

            if ($purchase === null) {
                Log::warning('Notificação de pagamento para cobrança desconhecida.', [
                    'reference' => $notification->chargeReference,
                ]);
            } else {
                try {
                    $this->confirmPayment->handle($purchase, $notification->amountCents);
                } catch (BusinessRuleException $e) {
                    // Valor divergente não se resolve tentando de novo: registra
                    // para análise humana e marca como processado.
                    Log::error('Falha ao confirmar pagamento: '.$e->getMessage(), [
                        'purchase_id' => $purchase->id,
                    ]);
                }
            }
        }

        $record->forceFill(['processed_at' => now()])->save();
    }
}
