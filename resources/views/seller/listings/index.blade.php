<x-layouts::shop title="Meus anúncios">
    @include('partials.seller-nav')

    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">Anúncios</flux:heading>
        <flux:button :href="route('seller.listings.create')" icon="plus" variant="primary" wire:navigate>Novo anúncio</flux:button>
    </div>

    @if ($listings->isEmpty())
        <flux:text>Nenhum anúncio ainda. Crie o primeiro!</flux:text>
    @else
        <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <table class="w-full text-sm">
                <thead class="text-left text-zinc-500">
                    <tr>
                        <th class="p-3">Pneu</th>
                        <th class="p-3">Condição</th>
                        <th class="p-3">Preço</th>
                        <th class="p-3">Estoque</th>
                        <th class="p-3">Local</th>
                        <th class="p-3">Situação</th>
                        <th class="p-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($listings as $listing)
                        <tr>
                            <td class="p-3">
                                <a href="{{ route('listings.show', $listing) }}" class="hover:underline">{{ $listing->tireModel->fullName() }}</a>
                            </td>
                            <td class="p-3"><flux:badge size="sm" :color="$listing->condition->color()">{{ $listing->condition->label() }}</flux:badge></td>
                            <td class="p-3">{{ \App\Support\Money::format($listing->price_cents) }}</td>
                            <td class="p-3 {{ $listing->stock === 0 ? 'text-red-600' : '' }}">{{ $listing->stock }}</td>
                            <td class="p-3">{{ $listing->location->name }}</td>
                            <td class="p-3"><flux:badge size="sm" :color="$listing->status->color()">{{ $listing->status->label() }}</flux:badge></td>
                            <td class="p-3 text-right">
                                <a href="{{ route('seller.listings.edit', $listing) }}" class="text-blue-600 hover:underline" wire:navigate>Editar</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $listings->links() }}</div>
    @endif
</x-layouts::shop>
