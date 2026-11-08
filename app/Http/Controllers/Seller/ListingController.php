<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Lista de anúncios da loja. Criar e editar ficam no componente
 * Livewire App\Livewire\Seller\ListingForm.
 */
class ListingController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->seller !== null, 403);

        $listings = Listing::query()
            ->where('seller_id', $user->seller->id)
            ->with(['tireModel', 'location'])
            ->latest('id')
            ->paginate(20);

        return view('seller.listings.index', ['listings' => $listings]);
    }
}
