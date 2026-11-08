<?php

namespace App\Policies;

use App\Models\Purchase;
use App\Models\User;

/**
 * Uma compra é assunto só de quem comprou.
 */
class PurchasePolicy
{
    public function view(User $user, Purchase $purchase): bool
    {
        return $purchase->buyer_id === $user->id;
    }

    public function cancel(User $user, Purchase $purchase): bool
    {
        return $purchase->buyer_id === $user->id;
    }
}
