<div>
    @include('partials.seller-nav')

    <div class="mb-6 flex flex-wrap items-center gap-3">
        <flux:heading size="xl">Pedido {{ $order->number() }}</flux:heading>
        <flux:badge :color="$order->status->color()">{{ $order->status->label() }}</flux:badge>
    </div>

    <div class="grid gap-8 lg:grid-cols-[1fr_380px]">
        <div class="space-y-6">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                <div class="font-semibold">Comprador: {{ $order->buyer->name }}</div>
                <div class="mt-1">{{ $order->fulfillment_method->label() }}</div>
                @if ($order->shipping_address)
                    <div class="mt-1">{{ $order->shippingAddressLine() }}</div>
                @elseif ($order->pickupLocation)
                    <div class="mt-1">Retirada em: {{ $order->pickupLocation->addressLine() }}</div>
                @endif

                <ul class="mt-4 space-y-1">
                    @foreach ($order->items as $item)
                        <li class="flex justify-between gap-3">
                            <span>{{ $item->quantity }}× {{ $item->title }}</span>
                            <span>{{ \App\Support\Money::format($item->total_cents) }}</span>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-3 flex justify-between border-t border-zinc-200 pt-3 font-semibold dark:border-zinc-700">
                    <span>Total (com frete de {{ \App\Support\Money::format($order->shipping_cents) }})</span>
                    <span>{{ \App\Support\Money::format($order->total_cents) }}</span>
                </div>
            </div>

            <div>
                <flux:heading class="mb-3">Linha do tempo</flux:heading>
                @include('partials.order-timeline', ['order' => $order])
            </div>
        </div>

        <aside class="space-y-4">
            @if ($order->status === \App\Enums\OrderStatus::AwaitingPayment)
                <flux:callout icon="clock" heading="Aguardando pagamento. Não separe os pneus ainda." />
            @endif

            {{-- 1. Avançar a situação: só aparecem as opções que a máquina de estados permite. --}}
            @if (count($nextOptions) > 0)
                <form wire:submit="advance" class="space-y-3 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:heading>Atualizar situação</flux:heading>

                    <flux:select wire:model.live="nextStatus" label="Nova situação">
                        @foreach ($nextOptions as $option)
                            <flux:select.option :value="$option->value">{{ $option->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    @if ($nextStatus === \App\Enums\OrderStatus::Shipped->value)
                        <flux:input wire:model="estimatedDate" type="date" label="Previsão de entrega" />
                        <flux:input wire:model="carrier" label="Transportadora (opcional)" placeholder="Entrega própria" />
                        <flux:input wire:model="trackingCode" label="Código de rastreio (opcional)" />
                    @endif

                    <flux:textarea wire:model="message" label="Recado ao comprador (opcional)" rows="2" />

                    @error('advance')
                        <flux:callout variant="danger" :heading="$message" />
                    @enderror

                    <flux:button type="submit" variant="primary" class="w-full" data-test="advance-order">Salvar situação</flux:button>
                </form>
            @endif

            {{-- 2. Mudar só a previsão de entrega. --}}
            @if ($order->fulfillment_method === \App\Enums\FulfillmentMethod::Delivery && $order->status->acceptsDeliveryEstimate())
                <form wire:submit="updateEstimate" class="space-y-3 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:heading>Previsão de entrega</flux:heading>
                    <flux:text size="sm">
                        Atual: {{ $order->estimated_delivery_at?->format('d/m/Y') ?? 'não definida' }}
                    </flux:text>
                    <flux:input wire:model="newEstimate" type="date" label="Nova previsão" />
                    <flux:button type="submit" class="w-full">Atualizar previsão</flux:button>
                </form>
            @endif

            {{-- 3. Cancelar (devolve estoque e reembolsa). --}}
            @if ($order->status->sellerCanCancel())
                <form wire:submit="cancel" class="space-y-3 rounded-xl border border-red-200 bg-white p-5 dark:border-red-900 dark:bg-zinc-800"
                      wire:confirm="Cancelar este pedido? O comprador será reembolsado.">
                    <flux:heading>Cancelar pedido</flux:heading>
                    <flux:input wire:model="cancelReason" label="Motivo (o comprador verá)" />
                    <flux:button type="submit" variant="danger" class="w-full">Cancelar e reembolsar</flux:button>
                </form>
            @endif
        </aside>
    </div>
</div>
