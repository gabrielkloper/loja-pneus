<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Seller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Página pública de uma loja: dados, endereços e anúncios.
 */
class StoreController extends Controller
{
    public function show(Request $request, Seller $seller): View
    {
        $isOwner = $request->user()?->id === $seller->owner_id;

        abort_unless($seller->isActive() || $isOwner, 404);

        $listings = Listing::query()
            ->visible()
            ->where('seller_id', $seller->id)
            ->with(['tireModel', 'location'])
            ->orderBy('price_cents')
            ->paginate(12);

        return view('stores.show', [
            'seller' => $seller->load('locations'),
            'listings' => $listings,
        ]);
    }
}
