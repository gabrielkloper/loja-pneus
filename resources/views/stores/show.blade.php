<x-layouts::shop :title="$seller->trade_name">
    <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ $seller->trade_name }}</flux:heading>
            <flux:text class="mt-1">
                {{ $seller->document_type->value === 'cpf' ? 'Vendedor pessoa física' : 'Empresa' }}
                · na plataforma desde {{ $seller->created_at?->format('m/Y') }}
            </flux:text>
            @unless ($seller->isActive())
                <flux:badge color="amber" class="mt-2">{{ $seller->status->label() }}</flux:badge>
            @endunless
        </div>
        <div class="text-sm">
            @if ($seller->offersDelivery())
                Entrega própria: {{ \App\Support\Money::format((int) $seller->delivery_fee_cents) }}, até {{ $seller->delivery_days }} dia(s)
            @else
                Somente retirada na loja
            @endif
        </div>
    </div>

    <flux:heading class="mb-3">Endereços</flux:heading>
    <div class="mb-8 grid gap-3 sm:grid-cols-2">
        @foreach ($seller->locations as $location)
            <div class="rounded-xl border border-zinc-200 bg-white p-4 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                <div class="font-medium">{{ $location->name }}</div>
                <div>{{ $location->addressLine() }}</div>
                @if ($location->opening_hours)
                    <div class="text-zinc-500">{{ $location->opening_hours }}</div>
                @endif
                <div class="mt-1 flex gap-3">
                    <span>{{ $location->pickup_enabled ? 'Aceita retirada' : 'Sem retirada' }}</span>
                    <a href="{{ $location->mapUrl() }}" target="_blank" rel="noopener" class="text-blue-600 hover:underline">Ver no mapa</a>
                </div>
            </div>
        @endforeach
    </div>

    <flux:heading class="mb-3">Pneus à venda</flux:heading>
    @if ($listings->isEmpty())
        <flux:text>Nenhum anúncio ativo no momento.</flux:text>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($listings as $listing)
                <x-listing-card :listing="$listing->setRelation('seller', $seller)" />
            @endforeach
        </div>
        <div class="mt-6">{{ $listings->links() }}</div>
    @endif
</x-layouts::shop>
