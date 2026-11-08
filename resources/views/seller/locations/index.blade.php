<x-layouts::shop title="Endereços da loja">
    @include('partials.seller-nav')

    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">Endereços</flux:heading>
        <flux:button :href="route('seller.locations.create')" icon="plus" variant="primary">Novo endereço</flux:button>
    </div>

    <div class="grid gap-3 sm:grid-cols-2">
        @foreach ($locations as $location)
            <div class="rounded-xl border border-zinc-200 bg-white p-4 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex items-center justify-between">
                    <span class="font-semibold">{{ $location->name }}</span>
                    <a href="{{ route('seller.locations.edit', $location) }}" class="text-blue-600 hover:underline">Editar</a>
                </div>
                <div class="mt-1">{{ $location->addressLine() }}</div>
                <div class="mt-1 text-zinc-500">{{ $location->pickup_enabled ? 'Aceita retirada' : 'Sem retirada' }}</div>
            </div>
        @endforeach
    </div>
</x-layouts::shop>
