<?php

namespace App\Rules;

use App\Enums\DocumentType;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Regra de validação de CPF e CNPJ pelos dígitos verificadores.
 *
 * Uso num formulário:
 *     'document' => ['required', new CpfCnpj(DocumentType::Cnpj)]
 *
 * Aceita o CNPJ alfanumérico (letras nas 12 primeiras posições), emitido
 * pela Receita Federal desde julho de 2026 (IN RFB 2.229/2024). Nele cada
 * caractere vale "código ASCII − 48": dígitos mantêm o valor, A = 17 … Z = 42.
 * Os dois dígitos verificadores continuam sendo números.
 */
class CpfCnpj implements ValidationRule
{
    public function __construct(private readonly ?DocumentType $type = null) {}

    /**
     * Remove pontuação e deixa letras maiúsculas: "12.ABC.345/01DE-35" → "12ABC34501DE35".
     * Use também antes de salvar, para guardar o documento normalizado.
     */
    public static function normalize(string $value): string
    {
        return strtoupper((string) preg_replace('/[^0-9A-Za-z]/', '', $value));
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $document = self::normalize((string) $value);

        // Se o tipo não foi informado, deduz pelo tamanho.
        $type = $this->type ?? (strlen($document) === 11 ? DocumentType::Cpf : DocumentType::Cnpj);

        $valid = $type === DocumentType::Cpf
            ? self::isValidCpf($document)
            : self::isValidCnpj($document);

        if (! $valid) {
            $fail($type === DocumentType::Cpf ? 'O CPF informado não é válido.' : 'O CNPJ informado não é válido.');
        }
    }

    public static function isValidCpf(string $cpf): bool
    {
        // 11 dígitos e não todos iguais (111.111.111-11 passa no cálculo, mas é inválido).
        if (! preg_match('/^\d{11}$/', $cpf) || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        // Cada dígito verificador é calculado sobre os dígitos anteriores,
        // com pesos decrescentes (10..2 para o primeiro, 11..2 para o segundo).
        for ($position = 9; $position < 11; $position++) {
            $sum = 0;
            for ($i = 0; $i < $position; $i++) {
                $sum += (int) $cpf[$i] * ($position + 1 - $i);
            }

            $digit = ($sum * 10) % 11 % 10; // resto 10 vira 0

            if ($digit !== (int) $cpf[$position]) {
                return false;
            }
        }

        return true;
    }

    public static function isValidCnpj(string $cnpj): bool
    {
        // 12 caracteres alfanuméricos + 2 dígitos verificadores numéricos.
        if (! preg_match('/^[0-9A-Z]{12}\d{2}$/', $cnpj) || preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        $firstWeights = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $secondWeights = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        return self::cnpjDigit(substr($cnpj, 0, 12), $firstWeights) === (int) $cnpj[12]
            && self::cnpjDigit(substr($cnpj, 0, 13), $secondWeights) === (int) $cnpj[13];
    }

    /**
     * @param  list<int>  $weights
     */
    private static function cnpjDigit(string $base, array $weights): int
    {
        $sum = 0;
        foreach ($weights as $i => $weight) {
            $sum += (ord($base[$i]) - 48) * $weight; // ASCII − 48: '7' → 7, 'A' → 17
        }

        $remainder = $sum % 11;

        return $remainder < 2 ? 0 : 11 - $remainder;
    }
}
