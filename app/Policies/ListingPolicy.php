<?php

namespace App\Policies;

use App\Models\Listing;
use App\Models\User;

class ListingPolicy
{
    /** Só a loja dona do anúncio pode editá-lo. */
    public function update(User $user, Listing $listing): bool
    {
        return $user->seller !== null && $user->seller->id === $listing->seller_id;
    }
}
