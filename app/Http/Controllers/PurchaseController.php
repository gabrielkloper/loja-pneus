<?php

namespace App\Http\Controllers;

use App\Actions\Checkout\CancelPurchase;
use App\Actions\Payments\ProcessPaymentNotification;
use App\Enums\PurchaseStatus;
use App\Models\Purchase;
use App\Payments\FakeGateway;
use App\Payments\PaymentGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Tela de pagamento de uma compra (Pix) e ações do comprador sobre ela.
 */
class PurchaseController extends Controller
{
    public function show(Purchase $purchase, PaymentGateway $gateway): View
    {
        Gate::authorize('view', $purchase);

        $purchase->load(['orders.seller', 'orders.items', 'orders.pickupLocation']);

        return view('purchases.show', [
            'purchase' => $purchase,
            'canSimulate' => $this->canSimulate($gateway),
        ]);
    }

    public function cancel(Request $request, Purchase $purchase, CancelPurchase $cancelPurchase): RedirectResponse
    {
        Gate::authorize('cancel', $purchase);

        $cancelPurchase->handle($purchase, PurchaseStatus::Cancelled, $request->user());

        return redirect()->route('purchases.show', $purchase)->with('status', 'Compra cancelada. Nada foi cobrado.');
    }

    /**
     * Só em desenvolvimento, com o gateway falso: faz o papel do banco e
     * do gateway, enviando a notificação "pago" pelo MESMO caminho de um
     * webhook real — inclusive a checagem de assinatura.
     */
    public function simulatePayment(
        Purchase $purchase,
        PaymentGateway $gateway,
        ProcessPaymentNotification $process,
    ): RedirectResponse {
        abort_unless($this->canSimulate($gateway), 404);
        Gate::authorize('view', $purchase);

        if (! $purchase->isPayable()) {
            return back()->withErrors(['domain' => 'Esta compra não está aguardando pagamento.']);
        }

        /** @var FakeGateway $gateway */
        $body = (string) json_encode($gateway->paidPayload($purchase));

        $webhook = Request::create(
            uri: route('webhooks.payments'),
            method: 'POST',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_'.str_replace('-', '_', strtoupper(FakeGateway::SIGNATURE_HEADER)) => $gateway->sign($body),
            ],
            content: $body,
        );

        $process->handle($gateway->parseWebhook($webhook));

        return redirect()->route('purchases.show', $purchase)->with('status', 'Pagamento simulado com sucesso.');
    }

    private function canSimulate(PaymentGateway $gateway): bool
    {
        return $gateway instanceof FakeGateway && ! app()->isProduction();
    }
}
