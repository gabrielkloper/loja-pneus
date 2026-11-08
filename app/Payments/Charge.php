<?php

namespace App\Payments;

/**
 * Resultado de criar uma cobrança no gateway.
 */
final readonly class Charge
{
    public function __construct(
        public string $reference, // id da cobrança no provedor
        public string $pixCode,   // "Pix copia e cola" que o comprador paga no banco
    ) {}
}
