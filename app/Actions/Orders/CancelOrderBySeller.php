<?php

namespace App\Actions\Orders;

use App\Enums\OrderEventActor;
use App\Enums\OrderStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Listing;
use App\Models\Order;
use App\Models\User;
use App\Payments\PaymentGateway;
use Illuminate\Support\Facades\DB;

/**
 * O vendedor cancela um pedido JÁ PAGO (ex.: pneu avariado no estoque).
 * Devolve o estoque e reembolsa o valor deste pedido ao comprador.
 */
final class CancelOrderBySeller
{
    public function __construct(private readonly PaymentGateway $gateway) {}

    public function handle(Order $order, User $seller, string $reason): Order
    {
        if (trim($reason) === '') {
            throw new BusinessRuleException('Informe o motivo do cancelamento.');
        }

        $order = DB::transaction(function () use ($order, $seller, $reason): Order {
            // Relê o pedido travado: dois cliques em "cancelar" não podem
            // devolver estoque nem reembolsar duas vezes.
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! $order->status->sellerCanCancel()) {
                throw new BusinessRuleException("Pedidos \"{$order->status->label()}\" não podem ser cancelados pela loja.");
            }

            foreach ($order->items as $item) {
                Listing::query()->whereKey($item->listing_id)->increment('stock', $item->quantity);
            }

            $order->forceFill([
                'status' => OrderStatus::Cancelled,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
                'refunded_cents' => $order->total_cents,
            ])->save();

            $order->recordEvent(
                OrderEventActor::Seller,
                "Pedido cancelado pela loja: {$reason}. O valor será reembolsado.",
                OrderStatus::Cancelled,
                $seller,
            );

            return $order;
        });

        $this->gateway->refund($order->purchase, $order->total_cents, $reason);

        return $order;
    }
}
