<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * "Meus pedidos" do ponto de vista do COMPRADOR.
 */
class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = Order::query()
            ->where('buyer_id', $request->user()?->id)
            ->with(['seller', 'items'])
            ->latest('id')
            ->paginate(10);

        return view('orders.index', ['orders' => $orders]);
    }

    public function show(Order $order): View
    {
        Gate::authorize('view', $order);

        $order->load(['seller', 'items', 'events', 'pickupLocation', 'purchase']);

        return view('orders.show', ['order' => $order]);
    }
}
