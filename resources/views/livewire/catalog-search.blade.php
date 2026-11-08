<div>
    <div class="mb-6">
        <flux:heading size="xl">Encontre pneus novos e remold</flux:heading>
        <flux:text class="mt-1">Compare ofertas de várias lojas, receba em casa ou retire na loja mais perto.</flux:text>
    </div>

    <div class="grid gap-6 lg:grid-cols-[260px_1fr]">
        {{-- Filtros. wire:model.live.debounce espera o usuário parar de digitar. --}}
        <aside class="space-y-4">
            <flux:input wire:model.live.debounce.400ms="size" label="Medida" placeholder="205/55 R16" />
            @if ($sizeNotUnderstood)
                <flux:text size="sm" class="text-amber-600">Não entendi a medida. Exemplo: 205/55 R16.</flux:text>
            @endif

            <flux:input wire:model.live.debounce.400ms="term" label="Marca ou modelo" placeholder="Pirelli" />

            <flux:select wire:model.live="condition" label="Condição">
                <flux:select.option value="">Todas</flux:select.option>
                @foreach (\App\Enums\TireCondition::cases() as $case)
                    <flux:select.option :value="$case->value">{{ $case->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="state" label="Estado">
                <flux:select.option value="">Todos</flux:select.option>
                @foreach ($states as $code => $name)
                    <flux:select.option :value="$code">{{ $name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model.live.debounce.400ms="city" label="Cidade" />

            <flux:checkbox wire:model.live="pickupOnly" label="Só lojas com retirada" />

            <flux:select wire:model.live="sort" label="Ordenar por">
                <flux:select.option value="menor-preco">Menor preço</flux:select.option>
                <flux:select.option value="maior-preco">Maior preço</flux:select.option>
                <flux:select.option value="recentes">Mais recentes</flux:select.option>
            </flux:select>

            <flux:button wire:click="clearFilters" variant="ghost" size="sm">Limpar filtros</flux:button>
        </aside>

        <section>
            <div wire:loading.delay class="mb-3 text-sm text-zinc-500">Buscando…</div>

            @if ($listings->isEmpty())
                <div class="rounded-xl border border-dashed border-zinc-300 p-10 text-center text-zinc-500 dark:border-zinc-600">
                    Nenhum pneu encontrado com esses filtros.
                </div>
            @else
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($listings as $listing)
                        <div wire:key="listing-{{ $listing->id }}">
                            <x-listing-card :listing="$listing" />
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">{{ $listings->links() }}</div>
            @endif
        </section>
    </div>
</div>
