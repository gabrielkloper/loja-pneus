<?php

namespace App\Enums;

/**
 * Situação de um anúncio. Anúncios não são apagados depois de vendidos
 * (os pedidos apontam para eles); o vendedor apenas pausa.
 */
enum ListingStatus: string
{
    case Active = 'active';
    case Paused = 'paused';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Ativo',
            self::Paused => 'Pausado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'green',
            self::Paused => 'zinc',
        };
    }
}
