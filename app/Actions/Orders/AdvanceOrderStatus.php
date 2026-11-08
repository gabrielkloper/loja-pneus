<?php

namespace App\Actions\Orders;

use App\Enums\OrderEventActor;
use App\Enums\OrderStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * O vendedor avança o pedido na máquina de estados (ADR-0005):
 * em preparação → enviado → saiu para entrega → entregue, ou
 * em preparação → pronto para retirada → retirado.
 *
 * Quem chama (tela ou API) já verificou, pela OrderPolicy, que o usuário
 * é o vendedor deste pedido. Esta classe cuida só das regras do pedido.
 */
final class AdvanceOrderStatus
{
    public function handle(
        Order $order,
        OrderStatus $to,
        User $seller,
        ?CarbonInterface $estimatedDeliveryAt = null,
        ?string $carrier = null,
        ?string $trackingCode = null,
        ?string $message = null,
    ): Order {
        $allowed = $order->status->sellerCanMoveTo($order->fulfillment_method);

        if (! in_array($to, $allowed, true)) {
            throw new BusinessRuleException(
                "Não é possível mudar o pedido de \"{$order->status->label()}\" para \"{$to->label()}\".",
            );
        }

        if ($to === OrderStatus::Shipped) {
            // Ao enviar, o comprador precisa saber quando chega.
            if ($estimatedDeliveryAt === null) {
                throw new BusinessRuleException('Informe a previsão de entrega ao enviar o pedido.');
            }
            if ($estimatedDeliveryAt->lt(now()->startOfDay())) {
                throw new BusinessRuleException('A previsão de entrega não pode estar no passado.');
            }
        }

        return DB::transaction(function () use ($order, $to, $seller, $estimatedDeliveryAt, $carrier, $trackingCode, $message): Order {
            $order->status = $to;

            if ($estimatedDeliveryAt !== null) {
                $order->estimated_delivery_at = $estimatedDeliveryAt;
            }
            if (filled($carrier)) {
                $order->carrier = $carrier;
            }
            if (filled($trackingCode)) {
                $order->tracking_code = $trackingCode;
            }

            $order->save();

            $order->recordEvent(
                OrderEventActor::Seller,
                filled($message) ? $message : $this->defaultMessage($to, $order),
                $to,
                $seller,
                $estimatedDeliveryAt,
            );

            return $order;
        });
    }

    private function defaultMessage(OrderStatus $to, Order $order): string
    {
        return match ($to) {
            OrderStatus::Preparing => 'A loja está separando os pneus do seu pedido.',
            OrderStatus::Shipped => trim('Pedido enviado. '.($order->carrier ? "Transportadora: {$order->carrier}. " : '').($order->tracking_code ? "Rastreio: {$order->tracking_code}." : '')),
            OrderStatus::OutForDelivery => 'O pedido saiu para entrega.',
            OrderStatus::Delivered => 'Pedido entregue.',
            OrderStatus::ReadyForPickup => 'Pedido pronto para retirada na loja.',
            OrderStatus::PickedUp => 'Pedido retirado na loja.',
            default => $to->label(),
        };
    }
}
