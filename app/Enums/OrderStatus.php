<?php

namespace App\Enums;

/**
 * Ciclo de vida de um pedido (um pedido = uma loja) — ADR-0005.
 *
 * A regra mais importante deste arquivo é sellerCanMoveTo(): ela é a
 * "máquina de estados" que diz para quais situações o vendedor pode levar
 * o pedido a partir da situação atual. Qualquer tentativa fora dessa lista
 * é recusada pela Action AdvanceOrderStatus.
 *
 *   Entrega:  awaiting_payment → paid → preparing → shipped → out_for_delivery → delivered
 *   Retirada: awaiting_payment → paid → preparing → ready_for_pickup → picked_up
 *
 * "paid" só é alcançado pelo sistema (confirmação de pagamento) e
 * "cancelled" só pelas Actions de cancelamento, que devolvem estoque.
 */
enum OrderStatus: string
{
    case AwaitingPayment = 'awaiting_payment';
    case Paid = 'paid';
    case Preparing = 'preparing';
    case Shipped = 'shipped';
    case OutForDelivery = 'out_for_delivery';
    case Delivered = 'delivered';
    case ReadyForPickup = 'ready_for_pickup';
    case PickedUp = 'picked_up';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::AwaitingPayment => 'Aguardando pagamento',
            self::Paid => 'Pago',
            self::Preparing => 'Em preparação',
            self::Shipped => 'Enviado',
            self::OutForDelivery => 'Saiu para entrega',
            self::Delivered => 'Entregue',
            self::ReadyForPickup => 'Pronto para retirada',
            self::PickedUp => 'Retirado',
            self::Cancelled => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::AwaitingPayment => 'amber',
            self::Paid, self::Preparing => 'blue',
            self::Shipped, self::OutForDelivery, self::ReadyForPickup => 'indigo',
            self::Delivered, self::PickedUp => 'green',
            self::Cancelled => 'zinc',
        };
    }

    /**
     * Situações finais: nada mais acontece com o pedido.
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::Delivered, self::PickedUp, self::Cancelled], true);
    }

    /**
     * Para onde o VENDEDOR pode mover o pedido a partir daqui.
     *
     * O caminho depende do método de entrega: um pedido de retirada nunca
     * passa por "enviado", e um de entrega nunca fica "pronto para retirada".
     *
     * @return list<self>
     */
    public function sellerCanMoveTo(FulfillmentMethod $method): array
    {
        return match ($this) {
            self::Paid => [self::Preparing],
            self::Preparing => $method === FulfillmentMethod::Delivery
                ? [self::Shipped]
                : [self::ReadyForPickup],
            self::Shipped => [self::OutForDelivery, self::Delivered],
            self::OutForDelivery => [self::Delivered],
            self::ReadyForPickup => [self::PickedUp],
            default => [],
        };
    }

    /**
     * O vendedor pode cancelar um pedido já pago enquanto ele não saiu da
     * loja. Antes do pagamento, quem cancela é o comprador (a compra toda).
     */
    public function sellerCanCancel(): bool
    {
        return in_array($this, [self::Paid, self::Preparing, self::ReadyForPickup], true);
    }

    /**
     * Situações em que ainda faz sentido mudar a previsão de entrega.
     */
    public function acceptsDeliveryEstimate(): bool
    {
        return in_array($this, [self::Paid, self::Preparing, self::Shipped, self::OutForDelivery], true);
    }
}
