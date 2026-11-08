<?php

namespace App\Enums;

/**
 * Situação do pagamento de uma compra.
 *
 * Uma compra (Purchase) agrupa os pedidos de várias lojas que o comprador
 * pagou de uma vez — ADR-0004.
 */
enum PurchaseStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Aguardando pagamento',
            self::Paid => 'Paga',
            self::Expired => 'Expirada',
            self::Cancelled => 'Cancelada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Paid => 'green',
            self::Expired, self::Cancelled => 'zinc',
        };
    }
}
