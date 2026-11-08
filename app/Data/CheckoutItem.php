<?php

namespace App\Data;

/**
 * Um item que o comprador quer levar: qual anúncio e quantas unidades.
 *
 * Repare que NÃO existe preço aqui. Quem decide o preço é o banco de
 * dados, nunca quem chama o checkout (ADR-0008).
 */
final readonly class CheckoutItem
{
    public function __construct(
        public int $listingId,
        public int $quantity,
    ) {}
}
