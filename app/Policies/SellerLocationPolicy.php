<?php

namespace App\Policies;

use App\Models\SellerLocation;
use App\Models\User;

class SellerLocationPolicy
{
    public function update(User $user, SellerLocation $location): bool
    {
        return $user->seller !== null && $user->seller->id === $location->seller_id;
    }
}
