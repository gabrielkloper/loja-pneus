<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Página pública de um anúncio.
 */
class ListingController extends Controller
{
    public function show(Request $request, Listing $listing): View
    {
        $listing->load(['tireModel', 'seller', 'location']);

        // Anúncio pausado, sem estoque ou de loja não aprovada não existe
        // para o público (404). O dono ainda consegue ver a prévia.
        $isOwner = $request->user()?->seller?->id === $listing->seller_id;

        abort_unless($listing->isVisible() || $isOwner, 404);

        // Outras ofertas do mesmo pneu, de outras lojas (catálogo + ofertas).
        $otherOffers = Listing::query()
            ->visible()
            ->where('tire_model_id', $listing->tire_model_id)
            ->whereKeyNot($listing->id)
            ->with(['seller', 'location'])
            ->orderBy('price_cents')
            ->limit(5)
            ->get();

        return view('listings.show', [
            'listing' => $listing,
            'otherOffers' => $otherOffers,
            'isOwner' => $isOwner,
        ]);
    }
}
