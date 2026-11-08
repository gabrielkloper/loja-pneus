<x-layouts::shop title="Meus pedidos">
    <flux:heading size="xl" class="mb-6">Meus pedidos</flux:heading>

    @if ($orders->isEmpty())
        <flux:text>Você ainda não fez nenhum pedido.</flux:text>
    @else
        <div class="space-y-3">
            @foreach ($orders as $order)
                <a href="{{ route('orders.show', $order) }}" wire:navigate
                   class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-zinc-200 bg-white p-4 hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                    <div>
                        <div class="font-semibold">{{ $order->number() }} · {{ $order->seller->trade_name }}</div>
                        <div class="text-sm text-zinc-500">
                            {{ $order->items->sum('quantity') }} pneu(s) · {{ $order->created_at?->format('d/m/Y') }}
                            @if ($order->estimated_delivery_at && ! $order->status->isFinal())
                                · previsão {{ $order->estimated_delivery_at->format('d/m') }}
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="font-semibold">{{ \App\Support\Money::format($order->total_cents) }}</span>
                        <flux:badge size="sm" :color="$order->status->color()">{{ $order->status->label() }}</flux:badge>
                    </div>
                </a>
            @endforeach
        </div>
        <div class="mt-6">{{ $orders->links() }}</div>
    @endif
</x-layouts::shop>
