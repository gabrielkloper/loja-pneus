{{--
    Campos de endereço de loja, reaproveitados no cadastro da loja e no
    cadastro de novos endereços.

    $prefix   = 'location' gera name="location[street]" (validado como "location.street")
    $location = SellerLocation sendo editado, ou null

    Os erros são exibidos manualmente com @error porque o nome do campo no
    HTML (location[street]) é diferente da chave do erro (location.street).
--}}
@php
    $name = fn (string $field) => $prefix ? "{$prefix}[{$field}]" : $field;
    $key = fn (string $field) => $prefix ? "{$prefix}.{$field}" : $field;
    $val = fn (string $field, $default = null) => old($key($field), $location?->{$field} ?? $default);
@endphp

<flux:input :name="$name('name')" label="Nome do local" placeholder="Matriz" :value="$val('name')" required />
@error($key('name')) <flux:text size="sm" class="text-red-600">{{ $message }}</flux:text> @enderror

<div class="grid gap-4 sm:grid-cols-[160px_1fr_120px]">
    <flux:input :name="$name('zip_code')" label="CEP" placeholder="80010-000" :value="$val('zip_code')" required />
    <flux:input :name="$name('street')" label="Rua" :value="$val('street')" required />
    <flux:input :name="$name('number')" label="Número" :value="$val('number')" required />
</div>
@foreach (['zip_code', 'street', 'number'] as $field)
    @error($key($field)) <flux:text size="sm" class="text-red-600">{{ $message }}</flux:text> @enderror
@endforeach

<div class="grid gap-4 sm:grid-cols-2">
    <flux:input :name="$name('complement')" label="Complemento" :value="$val('complement')" />
    <flux:input :name="$name('district')" label="Bairro" :value="$val('district')" required />
</div>

<div class="grid gap-4 sm:grid-cols-[1fr_200px]">
    <flux:input :name="$name('city')" label="Cidade" :value="$val('city')" required />
    <flux:select :name="$name('state')" label="Estado">
        <flux:select.option value="">Selecione</flux:select.option>
        @foreach (\App\Support\BrazilianStates::all() as $code => $stateName)
            <flux:select.option :value="$code" :selected="$val('state') === $code">{{ $stateName }}</flux:select.option>
        @endforeach
    </flux:select>
</div>
@foreach (['district', 'city', 'state'] as $field)
    @error($key($field)) <flux:text size="sm" class="text-red-600">{{ $message }}</flux:text> @enderror
@endforeach

<div class="grid gap-4 sm:grid-cols-2">
    <flux:input :name="$name('latitude')" label="Latitude (opcional)" placeholder="-25.4284" :value="$val('latitude')" />
    <flux:input :name="$name('longitude')" label="Longitude (opcional)" placeholder="-49.2733" :value="$val('longitude')" />
</div>
<flux:text size="sm">Dica: no Google Maps, clique com o botão direito no local para copiar as coordenadas.</flux:text>

<flux:input :name="$name('opening_hours')" label="Horário de atendimento" placeholder="Seg a sex, 8h às 18h; sáb, 8h às 12h" :value="$val('opening_hours')" />

{{-- Checkbox HTML nativo: o hidden com 0 garante que "desmarcado" também seja enviado. --}}
<input type="hidden" name="{{ $name('pickup_enabled') }}" value="0">
<label class="flex items-center gap-2 text-sm">
    <input type="checkbox" name="{{ $name('pickup_enabled') }}" value="1" @checked($val('pickup_enabled', true))>
    Clientes podem retirar pneus neste endereço
</label>
@foreach (['latitude', 'longitude', 'opening_hours'] as $field)
    @error($key($field)) <flux:text size="sm" class="text-red-600">{{ $message }}</flux:text> @enderror
@endforeach
