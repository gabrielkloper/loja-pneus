<?php

namespace App\Enums;

/**
 * Quem gerou um evento na linha do tempo do pedido.
 */
enum OrderEventActor: string
{
    case System = 'system';
    case Buyer = 'buyer';
    case Seller = 'seller';

    public function label(): string
    {
        return match ($this) {
            self::System => 'Sistema',
            self::Buyer => 'Comprador',
            self::Seller => 'Vendedor',
        };
    }
}
