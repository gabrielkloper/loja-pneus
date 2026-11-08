<div>
    <flux:heading size="xl" class="mb-6">Carrinho</flux:heading>

    @if ($groups->isEmpty())
        <div class="rounded-xl border border-dashed border-zinc-300 p-10 text-center dark:border-zinc-600">
            <flux:text>Seu carrinho está vazio.</flux:text>
            <flux:button :href="route('home')" class="mt-4" wire:navigate>Ver pneus</flux:button>
        </div>
    @else
        <div class="grid gap-8 lg:grid-cols-[1fr_340px]">
            <div class="space-y-6">
                {{-- Um bloco por loja: cada loja vira um pedido separado. --}}
                @foreach ($groups as $sellerId => $group)
                    <section wire:key="seller-{{ $sellerId }}" class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                        <div class="mb-3 flex items-center justify-between">
                            <flux:heading>{{ $group['seller']->trade_name }}</flux:heading>
                            <flux:text size="sm">Pedido separado</flux:text>
                        </div>

                        <ul class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach ($group['listings'] as $listing)
                                <li wire:key="item-{{ $listing->id }}" class="flex flex-wrap items-center gap-4 py-3">
                                    <div class="min-w-0 flex-1">
                                        <a href="{{ route('listings.show', $listing) }}" class="font-medium hover:underline" wire:navigate>{{ $listing->title() }}</a>
                                        <div class="text-sm text-zinc-500">{{ \App\Support\Money::format($listing->price_cents) }} cada</div>
                                        @unless ($listing->isVisible())
                                            <div class="text-sm text-red-600">Indisponível no momento — remova para continuar.</div>
                                        @endunless
                                        @if ($listing->isVisible() && $quantities[$listing->id] > $listing->stock)
                                            <div class="text-sm text-amber-600">Só há {{ $listing->stock }} em estoque.</div>
                                        @endif
                                    </div>

                                    <select wire:change="updateQuantity({{ $listing->id }}, $event.target.value)"
                                        class="rounded-md border border-zinc-300 bg-white px-2 py-1 text-sm dark:border-zinc-600 dark:bg-zinc-800"
                                        aria-label="Quantidade">
                                        @for ($q = 1; $q <= $maxUnits; $q++)
                                            <option value="{{ $q }}" @selected($q === $quantities[$listing->id])>{{ $q }}</option>
                                        @endfor
                                    </select>

                                    <div class="w-28 text-right font-semibold">
                                        {{ \App\Support\Money::format($listing->price_cents * $quantities[$listing->id]) }}
                                    </div>

                                    <flux:button size="sm" variant="ghost" wire:click="remove({{ $listing->id }})">Remover</flux:button>
                                </li>
                            @endforeach
                        </ul>

                        {{-- Escolha de entrega ou retirada PARA ESTA LOJA --}}
                        <fieldset class="mt-4 space-y-2 text-sm">
                            <legend class="mb-1 font-medium">Como receber</legend>

                            <label class="flex items-start gap-2 {{ $group['seller']->offersDelivery() ? '' : 'opacity-50' }}">
                                <input type="radio" value="delivery" wire:model.live="fulfillment.{{ $sellerId }}"
                                    @disabled(! $group['seller']->offersDelivery())>
                                <span>
                                    Entrega pelo vendedor
                                    @if ($group['seller']->offersDelivery())
                                        — {{ \App\Support\Money::format((int) $group['seller']->delivery_fee_cents) }}, até {{ $group['seller']->delivery_days }} dia(s) após o pagamento
                                    @else
                                        — esta loja não entrega
                                    @endif
                                </span>
                            </label>

                            <label class="flex items-start gap-2 {{ $group['pickupLocation']?->pickup_enabled ? '' : 'opacity-50' }}">
                                <input type="radio" value="pickup" wire:model.live="fulfillment.{{ $sellerId }}"
                                    @disabled(! $group['pickupLocation']?->pickup_enabled)>
                                <span>
                                    Retirar na loja (grátis)
                                    @if ($group['pickupLocation'])
                                        — {{ $group['pickupLocation']->addressLine() }}
                                    @endif
                                </span>
                            </label>
                        </fieldset>

                        <div class="mt-4 flex justify-end gap-6 text-sm">
                            <span>Produtos: {{ \App\Support\Money::format($group['subtotal']) }}</span>
                            <span>Frete: {{ \App\Support\Money::format($group['shipping']) }}</span>
                            <span class="font-semibold">Total da loja: {{ \App\Support\Money::format($group['total']) }}</span>
                        </div>
                    </section>
                @endforeach
            </div>

            <aside class="space-y-4">
                @guest
                    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                        <flux:text>Entre na sua conta para finalizar a compra. Seu carrinho fica guardado.</flux:text>
                        <flux:button :href="route('login')" variant="primary" class="mt-3 w-full">Entrar</flux:button>
                    </div>
                @else
                    @if ($needsAddress)
                        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                            <flux:heading class="mb-2">Endereço de entrega</flux:heading>

                            @foreach ($addresses as $address)
                                <label wire:key="address-{{ $address->id }}" class="mb-2 flex items-start gap-2 text-sm">
                                    <input type="radio" value="{{ $address->id }}" wire:model.live="addressId">
                                    <span>
                                        @if ($address->label)<strong>{{ $address->label }}:</strong>@endif
                                        {{ $address->line() }}
                                    </span>
                                </label>
                            @endforeach

                            @if ($showAddressForm)
                                <form wire:submit="saveAddress" class="mt-3 space-y-3">
                                    <flux:input wire:model="newAddress.label" label="Apelido (opcional)" placeholder="Casa" />
                                    <flux:input wire:model="newAddress.recipient_name" label="Quem recebe" />
                                    <flux:input wire:model="newAddress.zip_code" label="CEP" placeholder="80010-000" />
                                    <flux:input wire:model="newAddress.street" label="Rua" />
                                    <div class="grid grid-cols-2 gap-2">
                                        <flux:input wire:model="newAddress.number" label="Número" />
                                        <flux:input wire:model="newAddress.complement" label="Complemento" />
                                    </div>
                                    <flux:input wire:model="newAddress.district" label="Bairro" />
                                    <div class="grid grid-cols-[1fr_90px] gap-2">
                                        <flux:input wire:model="newAddress.city" label="Cidade" />
                                        <flux:select wire:model="newAddress.state" label="UF">
                                            <flux:select.option value="">--</flux:select.option>
                                            @foreach ($states as $code => $name)
                                                <flux:select.option :value="$code">{{ $code }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                    </div>
                                    <flux:button type="submit" size="sm">Salvar endereço</flux:button>
                                </form>
                            @else
                                <flux:button size="sm" variant="ghost" wire:click="$set('showAddressForm', true)" icon="plus">Novo endereço</flux:button>
                            @endif
                        </div>
                    @endif

                    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                        <div class="flex justify-between text-lg font-semibold">
                            <span>Total</span>
                            <span>{{ \App\Support\Money::format($grandTotal) }}</span>
                        </div>
                        <flux:text size="sm" class="mt-1">Pagamento via Pix. Você terá {{ config('marketplace.payment_window_minutes') }} minutos para pagar.</flux:text>

                        @error('checkout')
                            <flux:callout variant="danger" class="mt-3" :heading="$message" />
                        @enderror

                        {{-- wire:loading.attr="disabled" evita clique duplo; a chave de idempotência garante. --}}
                        <flux:button wire:click="placeOrder" wire:loading.attr="disabled" variant="primary" class="mt-4 w-full" data-test="place-order">
                            Finalizar compra
                        </flux:button>
                    </div>
                @endguest
            </aside>
        </div>
    @endif
</div>
