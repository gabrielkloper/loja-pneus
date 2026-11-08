{{--
    Layout público da loja (vitrine, carrinho, pedidos, painel do vendedor).

    Usado de dois jeitos:
      - em views comuns:       <x-layouts::shop :title="'Meus pedidos'"> ... </x-layouts::shop>
      - em componentes Livewire: #[Layout('layouts::shop')] na classe
    Em ambos, o conteúdo da página chega em $slot.
--}}
@props(['title' => null])
@php($cartCount = app(\App\Support\Cart::class)->count())
<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 text-zinc-900 dark:bg-zinc-900 dark:text-zinc-100">
        <header class="border-b border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-x-6 gap-y-2 px-4 py-3">
                <a href="{{ route('home') }}" class="flex items-center gap-2 font-semibold" wire:navigate>
                    <span class="flex size-8 items-center justify-center rounded-md bg-zinc-900 text-white dark:bg-white dark:text-zinc-900">
                        <x-app-logo-icon class="size-5 fill-current" />
                    </span>
                    {{ config('app.name') }}
                </a>

                <nav class="flex flex-1 flex-wrap items-center gap-4 text-sm">
                    <a href="{{ route('home') }}" class="hover:underline" wire:navigate>Vitrine</a>
                    @auth
                        <a href="{{ route('orders.index') }}" class="hover:underline" wire:navigate>Meus pedidos</a>
                        <a href="{{ route('seller.dashboard') }}" class="hover:underline" wire:navigate>
                            {{ auth()->user()->seller ? 'Painel da loja' : 'Quero vender' }}
                        </a>
                    @endauth
                </nav>

                <div class="flex items-center gap-4 text-sm">
                    <a href="{{ route('cart') }}" class="inline-flex items-center gap-1 hover:underline" wire:navigate data-test="cart-link">
                        <flux:icon.shopping-cart variant="micro" />
                        Carrinho
                        @if ($cartCount > 0)
                            <flux:badge size="sm" color="blue">{{ $cartCount }}</flux:badge>
                        @endif
                    </a>

                    @auth
                        <a href="{{ route('dashboard') }}" class="hover:underline" wire:navigate>{{ auth()->user()->name }}</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="cursor-pointer text-zinc-500 hover:underline">Sair</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="hover:underline">Entrar</a>
                        <a href="{{ route('register') }}" class="hover:underline">Criar conta</a>
                    @endauth
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-8">
            @if (session('status'))
                <flux:callout variant="success" icon="check-circle" class="mb-6" :heading="session('status')" />
            @endif

            @error('domain')
                <flux:callout variant="danger" icon="exclamation-triangle" class="mb-6" :heading="$message" />
            @enderror

            {{ $slot }}
        </main>

        <footer class="mx-auto max-w-6xl px-4 pb-10 text-xs text-zinc-500">
            Marketplace de pneus novos e remold. Entregas feitas pelos próprios vendedores.
        </footer>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        {{-- Carrega Livewire (e Alpine) em todas as páginas da loja, inclusive
             nas que são views comuns, para o Flux e o wire:navigate funcionarem. --}}
        @livewireScripts
        @fluxScripts
    </body>
</html>
