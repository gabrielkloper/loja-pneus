<?php

namespace App\Http\Controllers\Seller;

use App\Enums\ListingStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Seller;
use App\Models\User;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Página inicial do painel do vendedor e ajustes da loja.
 */
class DashboardController extends Controller
{
    public function show(Request $request): View
    {
        $seller = $this->seller($request);

        return view('seller.dashboard', [
            'seller' => $seller,
            'activeListings' => $seller->listings()->where('status', ListingStatus::Active)->count(),
            // Pedidos que esperam alguma ação da loja.
            'ordersToHandle' => $seller->orders()
                ->whereIn('status', [OrderStatus::Paid, OrderStatus::Preparing, OrderStatus::ReadyForPickup])
                ->count(),
            'ordersInTransit' => $seller->orders()
                ->whereIn('status', [OrderStatus::Shipped, OrderStatus::OutForDelivery])
                ->count(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $seller = $this->seller($request);

        $data = $request->validate([
            'trade_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:20'],
            'delivers' => ['boolean'],
            'delivery_fee' => ['nullable', 'required_if:delivers,1', 'string', 'max:15'],
            'delivery_days' => ['nullable', 'required_if:delivers,1', 'integer', 'min:0', 'max:60'],
        ]);

        $delivers = $request->boolean('delivers');
        $fee = $delivers ? Money::parse($data['delivery_fee'] ?? null) : null;

        if ($delivers && $fee === null) {
            throw ValidationException::withMessages(['delivery_fee' => 'Informe a taxa de entrega, ex.: 25,00.']);
        }

        // O slug não muda com o nome, para não quebrar links já divulgados.
        $seller->fill([
            'trade_name' => $data['trade_name'],
            'phone' => $data['phone'],
            'delivery_fee_cents' => $fee,
            'delivery_days' => $delivers ? (int) $data['delivery_days'] : null,
        ])->save();

        return back()->with('status', 'Dados da loja atualizados.');
    }

    private function seller(Request $request): Seller
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->seller !== null, 403);

        return $user->seller;
    }
}
