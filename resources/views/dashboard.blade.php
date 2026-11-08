{{-- "Minha conta": para onde o usuário vai depois do login (config/fortify.php → home). --}}
@php
    $user = auth()->user();
    $recentOrders = $user->orders()->with('seller')->latest('id')->limit(5)->get();
    $pendingPurchases = $user->purchases()->where('status', \App\Enums\PurchaseStatus::Pending)->latest('id')->get();
@endphp

<x-layouts::shop title="Minha conta">
    <flux:heading size="xl" class="mb-6">Olá, {{ $user->name }}</flux:heading>

    @foreach ($pendingPurchases as $purchase)
        <flux:callout icon="clock" variant="warning" class="mb-4">
            <flux:callout.heading>Compra {{ $purchase->number() }} aguardando pagamento</flux:callout.heading>
            <flux:callout.text>
                <a href="{{ route('purchases.show', $purchase) }}" class="underline">Pagar ou cancelar</a>
            </flux:callout.text>
        </flux:callout>
    @endforeach

    <div class="grid gap-6 md:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-3 flex items-center justify-between">
                <flux:heading>Últimos pedidos</flux:heading>
                <a href="{{ route('orders.index') }}" class="text-sm text-blue-600 hover:underline">Ver todos</a>
            </div>
            @forelse ($recentOrders as $order)
                <a href="{{ route('orders.show', $order) }}" class="flex justify-between gap-2 py-1 text-sm hover:underline">
                    <span>{{ $order->number() }} · {{ $order->seller->trade_name }}</span>
                    <flux:badge size="sm" :color="$order->status->color()">{{ $order->status->label() }}</flux:badge>
                </a>
            @empty
                <flux:text>Nenhum pedido ainda. <a href="{{ route('home') }}" class="underline">Ver pneus</a></flux:text>
            @endforelse
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading class="mb-3">Vender no marketplace</flux:heading>
            @if ($user->seller)
                <flux:text>Sua loja: <strong>{{ $user->seller->trade_name }}</strong> ({{ $user->seller->status->label() }})</flux:text>
                <flux:button :href="route('seller.dashboard')" class="mt-3">Abrir painel da loja</flux:button>
            @else
                <flux:text>Tem pneus novos ou remold para vender? Cadastre sua loja em poucos minutos.</flux:text>
                <flux:button :href="route('seller.onboarding.create')" class="mt-3">Quero vender</flux:button>
            @endif
        </div>
    </div>

    <div class="mt-6 text-sm">
        <a href="{{ route('profile.edit') }}" class="text-blue-600 hover:underline">Configurações da conta (perfil, senha, 2FA)</a>
    </div>
</x-layouts::shop>
