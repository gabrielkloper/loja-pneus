<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Support\Cart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Botão "Adicionar ao carrinho" da página do anúncio.
 * A edição do carrinho (quantidades, checkout) fica no componente
 * Livewire ShowCart.
 */
class CartController extends Controller
{
    public function store(Request $request, Cart $cart): RedirectResponse
    {
        $data = $request->validate([
            'listing_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1', 'max:'.config('marketplace.max_units_per_item')],
        ]);

        $listing = Listing::query()->visible()->whereKey((int) $data['listing_id'])->first();

        if ($listing === null) {
            return back()->withErrors(['domain' => 'Este anúncio não está mais disponível.']);
        }

        $cart->add($listing->id, (int) $data['quantity']);

        return redirect()->route('cart')->with('status', 'Item adicionado ao carrinho.');
    }
}
