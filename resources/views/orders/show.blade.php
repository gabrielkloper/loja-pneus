<x-layouts::shop :title="'Pedido '.$order->number()">
    <div class="mb-6 flex flex-wrap items-center gap-3">
        <flux:heading size="xl">Pedido {{ $order->number() }}</flux:heading>
        <flux:badge :color="$order->status->color()">{{ $order->status->label() }}</flux:badge>
    </div>

    <div class="grid gap-8 lg:grid-cols-[1fr_360px]">
        <div class="space-y-6">
            {{-- Resumo de entrega: a informação que o comprador mais procura. --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-5 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                @if ($order->fulfillment_method === \App\Enums\FulfillmentMethod::Delivery)
                    <div class="flex items-center gap-2 font-medium"><flux:icon.truck variant="micro" /> Entrega pelo vendedor</div>
                    <div class="mt-1">{{ $order->shippingAddressLine() }}</div>
                    @if ($order->estimated_delivery_at && ! $order->status->isFinal())
                        <div class="mt-2 text-base font-semibold">Previsão: {{ $order->estimated_delivery_at->format('d/m/Y') }}</div>
                    @endif
                    @if ($order->carrier || $order->tracking_code)
                        <div class="mt-1">{{ $order->carrier }} {{ $order->tracking_code ? '· código '.$order->tracking_code : '' }}</div>
                    @endif
                @else
                    <div class="flex items-center gap-2 font-medium"><flux:icon.map-pin variant="micro" /> Retirada na loja</div>
                    @if ($order->pickupLocation)
                        <div class="mt-1">{{ $order->pickupLocation->addressLine() }}</div>
                        @if ($order->pickupLocation->opening_hours)
                            <div class="text-zinc-500">{{ $order->pickupLocation->opening_hours }}</div>
                        @endif
                        <a href="{{ $order->pickupLocation->mapUrl() }}" target="_blank" rel="noopener" class="text-blue-600 hover:underline">Ver no mapa</a>
                    @endif
                    @if ($order->status === \App\Enums\OrderStatus::ReadyForPickup)
                        <div class="mt-2 text-base font-semibold">Seu pedido está pronto para retirada.</div>
                    @endif
                @endif

                @if ($order->cancellation_reason)
                    <div class="mt-2 text-red-600">Cancelado: {{ $order->cancellation_reason }}</div>
                @endif
            </div>

            <div>
                <flux:heading class="mb-3">Acompanhamento</flux:heading>
                @include('partials.order-timeline', ['order' => $order])
            </div>
        </div>

        <aside class="space-y-4">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                <div class="mb-2 font-semibold">
                    <a href="{{ route('stores.show', $order->seller) }}" class="hover:underline" wire:navigate>{{ $order->seller->trade_name }}</a>
                </div>
                <ul class="space-y-1">
                    @foreach ($order->items as $item)
                        <li class="flex justify-between gap-3">
                            <span>{{ $item->quantity }}× {{ $item->title }}</span>
                            <span>{{ \App\Support\Money::format($item->total_cents) }}</span>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-3 space-y-1 border-t border-zinc-200 pt-3 dark:border-zinc-700">
                    <div class="flex justify-between"><span>Frete</span><span>{{ \App\Support\Money::format($order->shipping_cents) }}</span></div>
                    <div class="flex justify-between font-semibold"><span>Total</span><span>{{ \App\Support\Money::format($order->total_cents) }}</span></div>
                    @if ($order->refunded_cents > 0)
                        <div class="flex justify-between text-green-700"><span>Reembolsado</span><span>{{ \App\Support\Money::format($order->refunded_cents) }}</span></div>
                    @endif
                </div>
                <a href="{{ route('purchases.show', $order->purchase) }}" class="mt-3 block text-blue-600 hover:underline" wire:navigate>
                    Ver compra {{ $order->purchase->number() }}
                </a>
            </div>
        </aside>
    </div>
</x-layouts::shop>
