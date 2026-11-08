<x-layouts::shop :title="'Compra '.$purchase->number()">
    <div class="mb-6 flex flex-wrap items-center gap-3">
        <flux:heading size="xl">Compra {{ $purchase->number() }}</flux:heading>
        <flux:badge :color="$purchase->status->color()">{{ $purchase->status->label() }}</flux:badge>
    </div>

    <div class="grid gap-8 lg:grid-cols-[1fr_360px]">
        <div class="space-y-4">
            @foreach ($purchase->orders as $order)
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <a href="{{ route('orders.show', $order) }}" class="font-semibold hover:underline" wire:navigate>
                            Pedido {{ $order->number() }} · {{ $order->seller->trade_name }}
                        </a>
                        <flux:badge size="sm" :color="$order->status->color()">{{ $order->status->label() }}</flux:badge>
                    </div>
                    <ul class="mt-2 text-sm">
                        @foreach ($order->items as $item)
                            <li>{{ $item->quantity }}× {{ $item->title }} — {{ \App\Support\Money::format($item->total_cents) }}</li>
                        @endforeach
                    </ul>
                    <flux:text size="sm" class="mt-2">
                        {{ $order->fulfillment_method->label() }}
                        @if ($order->pickupLocation) — {{ $order->pickupLocation->addressLine() }} @endif
                        · Frete {{ \App\Support\Money::format($order->shipping_cents) }}
                        · Total {{ \App\Support\Money::format($order->total_cents) }}
                    </flux:text>
                </div>
            @endforeach
        </div>

        <aside class="space-y-4">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex justify-between text-lg font-semibold">
                    <span>Total</span>
                    <span>{{ \App\Support\Money::format($purchase->total_cents) }}</span>
                </div>

                @if ($purchase->isPayable())
                    <flux:heading class="mt-4">Pague com Pix</flux:heading>
                    <flux:text size="sm">Copie o código abaixo no app do seu banco até {{ $purchase->expires_at->format('d/m H:i') }}.</flux:text>
                    <textarea readonly rows="4" class="mt-2 w-full rounded-md border border-zinc-300 bg-zinc-50 p-2 font-mono text-xs dark:border-zinc-600 dark:bg-zinc-900">{{ $purchase->pix_code }}</textarea>

                    @if ($canSimulate)
                        {{-- Só aparece em desenvolvimento com o gateway falso. --}}
                        <form method="POST" action="{{ route('purchases.simulate-payment', $purchase) }}" class="mt-3">
                            @csrf
                            <flux:button type="submit" variant="primary" class="w-full" icon="banknotes" data-test="simulate-payment">
                                Simular pagamento (ambiente de teste)
                            </flux:button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('purchases.cancel', $purchase) }}" class="mt-3"
                          onsubmit="return confirm('Cancelar esta compra? Os itens voltam para as lojas.')">
                        @csrf
                        <flux:button type="submit" variant="ghost" size="sm" class="w-full">Cancelar compra</flux:button>
                    </form>
                @elseif ($purchase->paid_at)
                    <flux:text class="mt-3">Pago em {{ $purchase->paid_at->format('d/m/Y H:i') }}. Acompanhe cada pedido pelos links ao lado.</flux:text>
                @else
                    <flux:text class="mt-3">Esta compra não está mais aguardando pagamento.</flux:text>
                @endif
            </div>
        </aside>
    </div>
</x-layouts::shop>
