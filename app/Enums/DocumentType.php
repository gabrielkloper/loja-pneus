<?php

namespace App\Enums;

/**
 * Tipo de documento de quem vende: pessoa física (CPF) ou jurídica (CNPJ).
 */
enum DocumentType: string
{
    case Cpf = 'cpf';
    case Cnpj = 'cnpj';

    public function label(): string
    {
        return match ($this) {
            self::Cpf => 'CPF (pessoa física)',
            self::Cnpj => 'CNPJ (pessoa jurídica)',
        };
    }

    /** Quantidade de dígitos esperada para o documento. */
    public function length(): int
    {
        return match ($this) {
            self::Cpf => 11,
            self::Cnpj => 14,
        };
    }
}
