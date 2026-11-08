<?php

namespace App\Payments;

use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Gateway falso para desenvolvimento e testes (ADR-0007).
 *
 * Ele se comporta como um gateway real nos pontos que importam para a
 * segurança: as notificações chegam assinadas com HMAC-SHA256 e são
 * rejeitadas se a assinatura não conferir. Só não movimenta dinheiro.
 */
final class FakeGateway implements PaymentGateway
{
    public const SIGNATURE_HEADER = 'X-Fake-Signature';

    public function __construct(private readonly string $webhookSecret) {}

    public function name(): string
    {
        return 'fake';
    }

    public function createCharge(Purchase $purchase): Charge
    {
        $reference = 'fake_'.Str::lower((string) Str::ulid());

        // Um texto parecido com um Pix copia-e-cola, só para exibição.
        $pixCode = '00020126FAKEPIX'.strtoupper($reference).'5204000053039865406'.number_format($purchase->total_cents / 100, 2, '.', '');

        return new Charge($reference, $pixCode);
    }

    public function parseWebhook(Request $request): PaymentNotification
    {
        $body = $request->getContent();
        $signature = (string) $request->header(self::SIGNATURE_HEADER, '');

        // hash_equals compara em tempo constante: evita que um atacante
        // descubra a assinatura certa medindo o tempo de resposta.
        if (! hash_equals($this->sign($body), $signature)) {
            throw new InvalidWebhookSignature('Assinatura do webhook inválida.');
        }

        /** @var array{id?: string, type?: string, charge_id?: string, amount_cents?: int} $data */
        $data = json_decode($body, true) ?: [];

        $type = PaymentNotificationType::tryFrom((string) ($data['type'] ?? ''));

        if (! isset($data['id'], $data['charge_id'], $data['amount_cents']) || $type === null) {
            throw new InvalidWebhookSignature('Notificação com formato inesperado.');
        }

        return new PaymentNotification(
            eventId: (string) $data['id'],
            type: $type,
            chargeReference: (string) $data['charge_id'],
            amountCents: (int) $data['amount_cents'],
            payload: $data,
        );
    }

    public function refund(Purchase $purchase, int $amountCents, string $reason): void
    {
        Log::info('[pagamento falso] reembolso', [
            'purchase_id' => $purchase->id,
            'amount_cents' => $amountCents,
            'reason' => $reason,
        ]);
    }

    /** HMAC-SHA256 do corpo cru da requisição com o segredo compartilhado. */
    public function sign(string $body): string
    {
        return hash_hmac('sha256', $body, $this->webhookSecret);
    }

    /**
     * Monta o corpo da notificação "pago" que um gateway real enviaria.
     * Usado pelo botão "Simular pagamento" e pelos testes.
     *
     * @return array{id: string, type: string, charge_id: string, amount_cents: int}
     */
    public function paidPayload(Purchase $purchase): array
    {
        return [
            'id' => 'evt_'.Str::lower((string) Str::ulid()),
            'type' => PaymentNotificationType::Paid->value,
            'charge_id' => (string) $purchase->payment_reference,
            'amount_cents' => $purchase->total_cents,
        ];
    }
}
