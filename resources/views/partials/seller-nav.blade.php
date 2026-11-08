{{-- Abas do painel do vendedor. --}}
<nav class="mb-6 flex flex-wrap gap-2 border-b border-zinc-200 pb-3 text-sm dark:border-zinc-700">
    @foreach ([
        'seller.dashboard' => 'Resumo',
        'seller.orders.index' => 'Pedidos',
        'seller.listings.index' => 'Anúncios',
        'seller.locations.index' => 'Endereços',
    ] as $routeName => $label)
        <a href="{{ route($routeName) }}" wire:navigate
           class="rounded-md px-3 py-1.5 {{ request()->routeIs(str_replace('.index', '.*', $routeName)) ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' : 'hover:bg-zinc-100 dark:hover:bg-zinc-700' }}">
            {{ $label }}
        </a>
    @endforeach
</nav>
