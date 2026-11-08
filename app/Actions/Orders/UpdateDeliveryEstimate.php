<?php

namespace App\Actions\Orders;

use App\Enums\FulfillmentMethod;
use App\Enums\OrderEventActor;
use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Models\User;
use Carbon\CarbonInterface;

/**
 * O vendedor muda a previsão de entrega sem mudar a situação do pedido
 * (ex.: "a transportadora atrasou, chega sexta"). Fica registrado na
 * linha do tempo, então o comprador vê o histórico de previsões.
 */
final class UpdateDeliveryEstimate
{
    public function handle(Order $order, CarbonInterface $estimatedDeliveryAt, User $seller, ?string $message = null): Order
    {
        if ($order->fulfillment_method !== FulfillmentMethod::Delivery) {
            throw new BusinessRuleException('Pedidos de retirada não têm previsão de entrega.');
        }

        if (! $order->status->acceptsDeliveryEstimate()) {
            throw new BusinessRuleException('Este pedido não aceita mais mudança de previsão.');
        }

        if ($estimatedDeliveryAt->lt(now()->startOfDay())) {
            throw new BusinessRuleException('A previsão de entrega não pode estar no passado.');
        }

        $order->estimated_delivery_at = $estimatedDeliveryAt;
        $order->save();

        $order->recordEvent(
            OrderEventActor::Seller,
            filled($message) ? $message : 'Previsão de entrega atualizada para '.$estimatedDeliveryAt->format('d/m/Y').'.',
            user: $seller,
            estimatedDeliveryAt: $estimatedDeliveryAt,
        );

        return $order;
    }
}
