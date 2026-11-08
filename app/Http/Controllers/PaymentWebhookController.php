<?php

namespace App\Http\Controllers;

use App\Actions\Payments\ProcessPaymentNotification;
use App\Payments\PaymentGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoint que o gateway chama para avisar que um pagamento aconteceu.
 *
 * É público (o gateway não faz login), por isso a segurança está toda na
 * assinatura: parseWebhook() lança InvalidWebhookSignature, que vira 401
 * (configurado em bootstrap/app.php), se o corpo não foi assinado com o
 * nosso segredo.
 */
class PaymentWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentGateway $gateway, ProcessPaymentNotification $process): JsonResponse
    {
        $notification = $gateway->parseWebhook($request);

        $process->handle($notification);

        // 200 diz ao gateway "recebido, não precisa reenviar".
        return response()->json(['received' => true]);
    }
}
