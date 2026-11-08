<?php

namespace App\Enums;

/**
 * Como o pedido chega ao comprador (ADR-0005 e ADR-0006).
 *
 * Delivery: o próprio vendedor entrega no endereço do comprador.
 * Pickup:   o comprador retira na loja física do vendedor.
 */
enum FulfillmentMethod: string
{
    case Delivery = 'delivery';
    case Pickup = 'pickup';

    public function label(): string
    {
        return match ($this) {
            self::Delivery => 'Entrega pelo vendedor',
            self::Pickup => 'Retirada na loja',
        };
    }
}
