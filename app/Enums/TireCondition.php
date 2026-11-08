<?php

namespace App\Enums;

/**
 * Condição do pneu anunciado.
 *
 * Remold é um pneu reconstruído sobre uma carcaça usada; no Brasil ele
 * precisa de registro no Inmetro, por isso o anúncio exige esse número.
 */
enum TireCondition: string
{
    case New = 'new';
    case Remold = 'remold';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Novo',
            self::Remold => 'Remold',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'blue',
            self::Remold => 'purple',
        };
    }
}
