{{-- Cartão de anúncio usado na vitrine e na página da loja. --}}
@props(['listing'])

<a href="{{ route('listings.show', $listing) }}" wire:navigate
   class="flex flex-col gap-2 rounded-xl border border-zinc-200 bg-white p-4 transition hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800"
   data-test="listing-card">
    <div class="flex items-center justify-between gap-2">
        <flux:badge size="sm" :color="$listing->condition->color()">{{ $listing->condition->label() }}</flux:badge>
        <span class="text-xs text-zinc-500">{{ $listing->location->city }}/{{ $listing->location->state }}</span>
    </div>

    <div>
        <div class="font-semibold">{{ $listing->tireModel->brand }} {{ $listing->tireModel->model }}</div>
        <div class="text-sm text-zinc-600 dark:text-zinc-300">
            {{ $listing->tireModel->sizeLabel() }} · {{ $listing->tireModel->load_index }}{{ $listing->tireModel->speed_index }}
        </div>
    </div>

    <div class="mt-auto text-xl font-bold">{{ \App\Support\Money::format($listing->price_cents) }}</div>

    <div class="flex flex-wrap gap-x-3 text-xs text-zinc-500">
        <span>{{ $listing->seller->trade_name }}</span>
        @if ($listing->seller->offersDelivery())
            <span>Entrega</span>
        @endif
        @if ($listing->location->pickup_enabled)
            <span>Retirada</span>
        @endif
    </div>
</a>
