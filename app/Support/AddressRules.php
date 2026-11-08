<?php

namespace App\Support;

use Illuminate\Validation\Rule;

/**
 * Regras de validação de endereço reutilizadas por loja (local físico) e
 * comprador (endereço de entrega). Um lugar só para mudar se o formato do
 * CEP ou a lista de UFs mudar.
 */
final class AddressRules
{
    /**
     * @param  string  $prefix  ex.: "location." para campos location[street]
     * @return array<string, array<int, mixed>>
     */
    public static function rules(string $prefix = ''): array
    {
        return [
            "{$prefix}zip_code" => ['required', 'string', 'regex:/^\d{5}-?\d{3}$/'],
            "{$prefix}street" => ['required', 'string', 'max:160'],
            "{$prefix}number" => ['required', 'string', 'max:20'],
            "{$prefix}complement" => ['nullable', 'string', 'max:80'],
            "{$prefix}district" => ['required', 'string', 'max:80'],
            "{$prefix}city" => ['required', 'string', 'max:80'],
            "{$prefix}state" => ['required', 'string', Rule::in(BrazilianStates::codes())],
        ];
    }

    /** "80010-000" → "80010000" */
    public static function normalizeZip(string $zip): string
    {
        return (string) preg_replace('/\D/', '', $zip);
    }
}
