<?php

namespace App\Payments;

/**
 * Notificação do gateway já autenticada e traduzida para o nosso formato.
 * Cada gateway real tem um JSON diferente; todos viram este objeto.
 */
final readonly class PaymentNotification
{
    /**
     * @param  array<string, mixed>  $payload  JSON original, guardado para auditoria
     */
    public function __construct(
        public string $eventId,
        public PaymentNotificationType $type,
        public string $chargeReference,
        public int $amountCents,
        public array $payload,
    ) {}
}
