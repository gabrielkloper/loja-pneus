<x-layouts::shop :title="$listing->tireModel->fullName()">
    @php($tire = $listing->tireModel)
    @php($location = $listing->location)

    <a href="{{ route('home') }}" class="mb-4 inline-flex items-center gap-1 text-sm text-zinc-500 hover:underline" wire:navigate>
        <flux:icon.arrow-left variant="micro" /> Voltar para a vitrine
    </a>

    @if ($isOwner && ! $listing->isVisible())
        <flux:callout variant="warning" icon="exclamation-triangle" class="mb-6"
            heading="Prévia: este anúncio não aparece na vitrine (pausado, sem estoque ou loja aguardando aprovação)." />
    @endif

    <div class="grid gap-8 lg:grid-cols-[1fr_340px]">
        <div class="space-y-6">
            <div>
                <flux:badge :color="$listing->condition->color()">{{ $listing->condition->label() }}</flux:badge>
                <flux:heading size="xl" class="mt-2">{{ $tire->brand }} {{ $tire->model }}</flux:heading>
                <flux:text class="mt-1">{{ $tire->sizeLabel() }} · índice de carga {{ $tire->load_index }} · velocidade {{ $tire->speed_index }}</flux:text>
            </div>

            <dl class="grid grid-cols-2 gap-4 rounded-xl border border-zinc-200 bg-white p-4 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                <div><dt class="text-zinc-500">Medida</dt><dd class="font-medium">{{ $tire->sizeLabel() }}</dd></div>
                <div><dt class="text-zinc-500">Carga / velocidade</dt><dd class="font-medium">{{ $tire->load_index }}{{ $tire->speed_index }}</dd></div>
                <div><dt class="text-zinc-500">Fabricação (DOT)</dt><dd class="font-medium">{{ $listing->dot_code ? 'semana '.substr($listing->dot_code, 0, 2).' de 20'.substr($listing->dot_code, 2, 2) : 'Não informado' }}</dd></div>
                <div><dt class="text-zinc-500">Garantia</dt><dd class="font-medium">{{ $listing->warranty_months ? $listing->warranty_months.' meses' : 'Não informada' }}</dd></div>
                @if ($listing->inmetro_registration)
                    <div class="col-span-2"><dt class="text-zinc-500">Registro Inmetro (remold)</dt><dd class="font-medium">{{ $listing->inmetro_registration }}</dd></div>
                @endif
            </dl>

            @if ($listing->description)
                <div>
                    <flux:heading>Descrição do vendedor</flux:heading>
                    {{-- e() escapa HTML antes do nl2br: o texto do vendedor nunca vira código na página. --}}
                    <div class="mt-2 text-sm leading-relaxed">{!! nl2br(e($listing->description)) !!}</div>
                </div>
            @endif

            @if ($otherOffers->isNotEmpty())
                <div>
                    <flux:heading>Outras lojas com este pneu</flux:heading>
                    <ul class="mt-2 divide-y divide-zinc-200 rounded-xl border border-zinc-200 bg-white text-sm dark:divide-zinc-700 dark:border-zinc-700 dark:bg-zinc-800">
                        @foreach ($otherOffers as $offer)
                            <li>
                                <a href="{{ route('listings.show', $offer) }}" class="flex justify-between gap-4 p-3 hover:bg-zinc-50 dark:hover:bg-zinc-700" wire:navigate>
                                    <span>{{ $offer->seller->trade_name }} · {{ $offer->condition->label() }} · {{ $offer->location->city }}/{{ $offer->location->state }}</span>
                                    <span class="font-semibold">{{ \App\Support\Money::format($offer->price_cents) }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <aside class="space-y-4">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="text-3xl font-bold">{{ \App\Support\Money::format($listing->price_cents) }}</div>
                <flux:text size="sm">por unidade · {{ $listing->stock }} em estoque</flux:text>

                @if ($listing->isVisible() && ! $isOwner)
                    <form method="POST" action="{{ route('cart.store') }}" class="mt-4 flex items-end gap-2">
                        @csrf
                        <input type="hidden" name="listing_id" value="{{ $listing->id }}">
                        <flux:input type="number" name="quantity" label="Quantidade" value="4" min="1"
                            :max="min($listing->stock, config('marketplace.max_units_per_item'))" class="w-24" />
                        <flux:button type="submit" variant="primary" icon="shopping-cart" data-test="add-to-cart">Adicionar</flux:button>
                    </form>
                @endif
            </div>

            <div class="space-y-2 rounded-xl border border-zinc-200 bg-white p-5 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                <a href="{{ route('stores.show', $listing->seller) }}" class="font-semibold hover:underline" wire:navigate>
                    {{ $listing->seller->trade_name }}
                </a>

                <div class="flex items-start gap-2">
                    <flux:icon.truck variant="micro" class="mt-0.5 shrink-0" />
                    @if ($listing->seller->offersDelivery())
                        <span>Entrega pelo vendedor: {{ \App\Support\Money::format((int) $listing->seller->delivery_fee_cents) }}, em até {{ $listing->seller->delivery_days }} dia(s) após o pagamento.</span>
                    @else
                        <span>Esta loja não faz entregas.</span>
                    @endif
                </div>

                <div class="flex items-start gap-2">
                    <flux:icon.map-pin variant="micro" class="mt-0.5 shrink-0" />
                    <span>
                        @if ($location->pickup_enabled)
                            Retirada grátis em {{ $location->addressLine() }}
                        @else
                            Pneu localizado em {{ $location->city }}/{{ $location->state }} (sem retirada)
                        @endif
                        <a href="{{ $location->mapUrl() }}" target="_blank" rel="noopener" class="block text-blue-600 hover:underline">Ver no mapa</a>
                    </span>
                </div>

                @if ($location->pickup_enabled && $location->opening_hours)
                    <div class="flex items-start gap-2">
                        <flux:icon.clock variant="micro" class="mt-0.5 shrink-0" />
                        <span>{{ $location->opening_hours }}</span>
                    </div>
                @endif
            </div>
        </aside>
    </div>
</x-layouts::shop>
