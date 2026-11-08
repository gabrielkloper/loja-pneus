<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

/**
 * Quem pode ver e mexer em um pedido.
 *
 * O Laravel encontra esta classe sozinho pelo nome (Order → OrderPolicy).
 * Em controllers usamos $this->authorize('view', $order) ou
 * Gate::authorize(...); se a resposta for false, o usuário recebe 403.
 */
class OrderPolicy
{
    /** Comprador e vendedor do pedido podem ver. */
    public function view(User $user, Order $order): bool
    {
        return $order->buyer_id === $user->id || $this->isSellerOf($user, $order);
    }

    /** Só o vendedor muda situação, previsão ou cancela. */
    public function manage(User $user, Order $order): bool
    {
        return $this->isSellerOf($user, $order);
    }

    private function isSellerOf(User $user, Order $order): bool
    {
        return $user->seller !== null && $user->seller->id === $order->seller_id;
    }
}
