<x-layouts::shop :title="$location->exists ? 'Editar endereço' : 'Novo endereço'">
    @include('partials.seller-nav')

    <div class="max-w-2xl">
        <flux:heading size="xl" class="mb-6">{{ $location->exists ? 'Editar endereço' : 'Novo endereço' }}</flux:heading>

        <form method="POST"
              action="{{ $location->exists ? route('seller.locations.update', $location) : route('seller.locations.store') }}"
              class="space-y-4">
            @csrf
            @if ($location->exists)
                @method('PUT')
            @endif

            @include('seller.locations.fields', ['prefix' => 'location', 'location' => $location])

            <div class="flex gap-3">
                <flux:button type="submit" variant="primary">Salvar</flux:button>
                <flux:button :href="route('seller.locations.index')" variant="ghost">Voltar</flux:button>
            </div>
        </form>
    </div>
</x-layouts::shop>
