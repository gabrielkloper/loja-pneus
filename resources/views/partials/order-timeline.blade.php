{{-- Linha do tempo de um pedido (ADR-0005). Recebe $order com 'events' carregado. --}}
<ol class="relative space-y-4 border-s border-zinc-200 ps-5 dark:border-zinc-700">
    @foreach ($order->events->reverse() as $event)
        <li class="relative">
            <span class="absolute -start-[1.6rem] top-1 size-3 rounded-full {{ $loop->first ? 'bg-blue-600' : 'bg-zinc-300 dark:bg-zinc-600' }}"></span>
            <div class="flex flex-wrap items-center gap-2 text-sm">
                @if ($event->status)
                    <flux:badge size="sm" :color="$event->status->color()">{{ $event->status->label() }}</flux:badge>
                @endif
                <span class="text-zinc-500">{{ $event->created_at->format('d/m/Y H:i') }} · {{ $event->actor->label() }}</span>
            </div>
            @if ($event->message)
                <p class="mt-1 text-sm">{{ $event->message }}</p>
            @endif
            @if ($event->estimated_delivery_at)
                <p class="text-sm text-zinc-500">Previsão de entrega: {{ $event->estimated_delivery_at->format('d/m/Y') }}</p>
            @endif
        </li>
    @endforeach
</ol>
