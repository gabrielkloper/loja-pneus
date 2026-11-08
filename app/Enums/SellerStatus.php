<?php

namespace App\Enums;

/**
 * Situação de uma loja no marketplace.
 *
 * Só lojas "Active" aparecem na vitrine. Uma loja nova nasce "Pending" e
 * precisa ser aprovada (comando marketplace:approve-seller) — ADR-0003.
 */
enum SellerStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Aguardando aprovação',
            self::Active => 'Ativa',
            self::Suspended => 'Suspensa',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Active => 'green',
            self::Suspended => 'red',
        };
    }
}
