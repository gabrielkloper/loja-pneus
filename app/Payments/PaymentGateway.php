<?php

namespace App\Payments;

use App\Models\Purchase;
use Illuminate\Http\Request;

/**
 * Contrato que todo gateway de pagamento precisa cumprir (ADR-0007).
 *
 * O resto do sistema só conhece esta interface. Trocar o driver falso por
 * Mercado Pago, Pagar.me ou outro significa escrever uma nova classe que
 * implementa estes quatro métodos — nenhuma Action muda.
 */
interface PaymentGateway
{
    /** Nome curto do provedor, gravado na compra: "fake", "mercadopago"… */
    public function name(): string;

    /** Cria a cobrança (Pix) no provedor para o total da compra. */
    public function createCharge(Purchase $purchase): Charge;

    /**
     * Lê e AUTENTICA a notificação que o provedor enviou ao nosso webhook.
     *
     * @throws InvalidWebhookSignature quando a assinatura não confere
     */
    public function parseWebhook(Request $request): PaymentNotification;

    /** Devolve dinheiro ao comprador (total ou parcial). */
    public function refund(Purchase $purchase, int $amountCents, string $reason): void;
}
