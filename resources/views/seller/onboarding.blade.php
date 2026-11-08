<x-layouts::shop title="Quero vender">
    <div class="max-w-2xl">
        <flux:heading size="xl">Venda seus pneus aqui</flux:heading>
        <flux:text class="mt-1">
            Cadastre sua loja e o endereço onde os pneus ficam. Você poderá criar anúncios na hora;
            eles aparecem na vitrine assim que a loja for aprovada.
        </flux:text>

        {{-- Formulário HTML comum (sem Livewire): POST tradicional + validação no controller. --}}
        <form method="POST" action="{{ route('seller.onboarding.store') }}" class="mt-8 space-y-8">
            @csrf

            <fieldset class="space-y-4">
                <legend class="mb-2 font-semibold">Dados da loja</legend>
                <flux:input name="trade_name" label="Nome da loja (aparece na vitrine)" :value="old('trade_name')" required />
                <flux:input name="legal_name" label="Razão social ou nome completo" :value="old('legal_name')" required />

                <div class="grid gap-4 sm:grid-cols-[200px_1fr]">
                    <flux:select name="document_type" label="Tipo">
                        @foreach (\App\Enums\DocumentType::cases() as $type)
                            <flux:select.option :value="$type->value" :selected="old('document_type', 'cnpj') === $type->value">{{ $type->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input name="document" label="CPF ou CNPJ" :value="old('document')" required />
                </div>

                <flux:input name="phone" label="Telefone / WhatsApp" :value="old('phone')" required />
            </fieldset>

            <fieldset class="space-y-4">
                <legend class="mb-2 font-semibold">Entrega</legend>
                <input type="hidden" name="delivers" value="0">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="delivers" value="1" @checked(old('delivers', '1'))>
                    Faço entregas com transporte próprio ou contratado
                </label>
                <flux:text size="sm">Se marcar, preencha a taxa e o prazo abaixo. Sem entrega, a loja trabalha só com retirada.</flux:text>
                @error('delivery_fee') <flux:text size="sm" class="text-red-600">{{ $message }}</flux:text> @enderror
                @error('delivery_days') <flux:text size="sm" class="text-red-600">{{ $message }}</flux:text> @enderror
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input name="delivery_fee" label="Taxa de entrega (R$)" placeholder="25,00" :value="old('delivery_fee')" />
                    <flux:input type="number" name="delivery_days" label="Prazo após o pagamento (dias)" min="0" max="60" :value="old('delivery_days', 3)" />
                </div>
            </fieldset>

            <fieldset class="space-y-4">
                <legend class="mb-2 font-semibold">Endereço da loja</legend>
                @include('seller.locations.fields', ['prefix' => 'location', 'location' => null])
            </fieldset>

            <flux:button type="submit" variant="primary">Criar minha loja</flux:button>
        </form>
    </div>
</x-layouts::shop>
