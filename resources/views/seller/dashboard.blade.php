<x-layouts::shop title="Painel da loja">
    @include('partials.seller-nav')

    <div class="mb-6 flex flex-wrap items-center gap-3">
        <flux:heading size="xl">{{ $seller->trade_name }}</flux:heading>
        <flux:badge :color="$seller->status->color()">{{ $seller->status->label() }}</flux:badge>
        <a href="{{ route('stores.show', $seller) }}" class="text-sm text-blue-600 hover:underline">Ver página pública</a>
    </div>

    @if ($seller->status === \App\Enums\SellerStatus::Pending)
        <flux:callout icon="clock" class="mb-6" variant="warning"
            heading="Sua loja está em análise. Você já pode cadastrar anúncios; eles aparecem na vitrine após a aprovação." />
    @endif

    <div class="mb-8 grid gap-4 sm:grid-cols-3">
        <a href="{{ route('seller.orders.index') }}" class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-3xl font-bold">{{ $ordersToHandle }}</div>
            <div class="text-sm text-zinc-500">pedidos aguardando ação da loja</div>
        </a>
        <a href="{{ route('seller.orders.index', ['status' => 'shipped']) }}" class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-3xl font-bold">{{ $ordersInTransit }}</div>
            <div class="text-sm text-zinc-500">pedidos em trânsito</div>
        </a>
        <a href="{{ route('seller.listings.index') }}" class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-3xl font-bold">{{ $activeListings }}</div>
            <div class="text-sm text-zinc-500">anúncios ativos</div>
        </a>
    </div>

    <div class="max-w-2xl">
        <flux:heading class="mb-4">Dados da loja e entrega</flux:heading>

        <form method="POST" action="{{ route('seller.settings.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <flux:input name="trade_name" label="Nome da loja" :value="old('trade_name', $seller->trade_name)" required />
            <flux:input name="phone" label="Telefone / WhatsApp" :value="old('phone', $seller->phone)" required />

            <input type="hidden" name="delivers" value="0">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="delivers" value="1" @checked(old('delivers', $seller->offersDelivery()))>
                Faço entregas
            </label>
            @error('delivery_fee') <flux:text size="sm" class="text-red-600">{{ $message }}</flux:text> @enderror

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input name="delivery_fee" label="Taxa de entrega (R$)"
                    :value="old('delivery_fee', \App\Support\Money::toInput($seller->delivery_fee_cents))" />
                <flux:input type="number" name="delivery_days" label="Prazo após o pagamento (dias)" min="0" max="60"
                    :value="old('delivery_days', $seller->delivery_days)" />
            </div>

            <flux:text size="sm">Documento: {{ $seller->formattedDocument() }} · Razão social: {{ $seller->legal_name }} (para alterar, fale com o suporte).</flux:text>

            <flux:button type="submit" variant="primary">Salvar</flux:button>
        </form>
    </div>
</x-layouts::shop>
