<div>
    @include('partials.seller-nav')

    <div class="max-w-3xl">
        <flux:heading size="xl" class="mb-6">{{ $listingId ? 'Editar anúncio' : 'Novo anúncio' }}</flux:heading>

        @if ($locations->isEmpty())
            <flux:callout variant="warning" heading="Cadastre um endereço da loja antes de anunciar." />
        @endif

        @error('form')
            <flux:callout variant="danger" class="mb-4" :heading="$message" />
        @enderror

        {{-- wire:submit chama o método save() da classe; wire:model liga cada campo a uma propriedade. --}}
        <form wire:submit="save" class="space-y-8">
            <fieldset class="space-y-4">
                <legend class="mb-2 font-semibold">Pneu</legend>
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input wire:model="brand" label="Marca" placeholder="Pirelli" />
                    <flux:input wire:model="model" label="Modelo" placeholder="Cinturato P7" />
                </div>
                <div class="grid gap-4 sm:grid-cols-5">
                    <flux:input wire:model="width" type="number" label="Largura" placeholder="205" />
                    <flux:input wire:model="aspect_ratio" type="number" label="Perfil" placeholder="55" />
                    <flux:input wire:model="rim" label="Aro" placeholder="16" />
                    <flux:input wire:model="load_index" type="number" label="Carga" placeholder="91" />
                    <flux:input wire:model="speed_index" label="Velocidade" placeholder="V" />
                </div>
                <flux:text size="sm">Na lateral do pneu: 205/55 R16 91V → largura 205, perfil 55, aro 16, carga 91, velocidade V.</flux:text>
            </fieldset>

            <fieldset class="space-y-4">
                <legend class="mb-2 font-semibold">Oferta</legend>
                <div class="grid gap-4 sm:grid-cols-3">
                    <flux:select wire:model.live="condition" label="Condição">
                        @foreach (\App\Enums\TireCondition::cases() as $case)
                            <flux:select.option :value="$case->value">{{ $case->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="price" label="Preço por unidade (R$)" placeholder="459,90" />
                    <flux:input wire:model="stock" type="number" min="0" label="Estoque" />
                </div>

                {{-- Campo que só aparece para remold: wire:model.live acima re-renderiza ao trocar a condição. --}}
                @if ($condition === 'remold')
                    <flux:input wire:model="inmetro_registration" label="Registro no Inmetro" description="Obrigatório para pneus remoldados." />
                @endif

                <div class="grid gap-4 sm:grid-cols-3">
                    <flux:input wire:model="dot_code" label="DOT (semana e ano)" placeholder="2425" maxlength="4" />
                    <flux:input wire:model="warranty_months" type="number" min="0" label="Garantia (meses)" />
                    <flux:select wire:model="seller_location_id" label="Onde o pneu está">
                        @foreach ($locations as $location)
                            <flux:select.option :value="$location->id">{{ $location->name }} — {{ $location->city }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:textarea wire:model="description" label="Descrição" rows="4" placeholder="Detalhes, condições de troca, etc." />

                <flux:select wire:model="status" label="Situação">
                    @foreach (\App\Enums\ListingStatus::cases() as $case)
                        <flux:select.option :value="$case->value">{{ $case->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
            </fieldset>

            <div class="flex gap-3">
                <flux:button type="submit" variant="primary" data-test="save-listing">Salvar anúncio</flux:button>
                <flux:button :href="route('seller.listings.index')" variant="ghost" wire:navigate>Cancelar</flux:button>
            </div>
        </form>
    </div>
</div>
