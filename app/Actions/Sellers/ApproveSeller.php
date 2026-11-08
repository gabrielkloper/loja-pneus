<?php

namespace App\Actions\Sellers;

use App\Enums\SellerStatus;
use App\Models\Seller;

/**
 * Libera a loja para aparecer na vitrine. Por enquanto é chamada pelo
 * comando "php artisan marketplace:approve-seller {slug}"; um painel de
 * administração virá num incremento futuro.
 */
final class ApproveSeller
{
    public function handle(Seller $seller): Seller
    {
        $seller->status = SellerStatus::Active;
        $seller->approved_at = now();
        $seller->save();

        return $seller;
    }
}
