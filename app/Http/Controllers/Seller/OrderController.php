<?php

namespace App\Http\Controllers\Seller;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Lista de pedidos recebidos pela loja, com filtro por situação.
 * O detalhe (e as ações) ficam no componente Livewire Seller\ManageOrder.
 */
class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->seller !== null, 403);

        $status = OrderStatus::tryFrom((string) $request->query('status'));

        $orders = Order::query()
            ->where('seller_id', $user->seller->id)
            // Pedidos não pagos ficam fora: a loja não deve separar nada antes do pagamento.
            ->where('status', '!=', OrderStatus::AwaitingPayment)
            ->when($status, fn ($query) => $query->where('status', $status))
            ->with(['buyer', 'items'])
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('seller.orders.index', [
            'orders' => $orders,
            'currentStatus' => $status,
        ]);
    }
}
