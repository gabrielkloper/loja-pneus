<?php

namespace App\Data;

use App\Enums\FulfillmentMethod;

/**
 * Como o comprador quer receber os itens de UMA loja.
 *
 * Para entrega, informa qual endereço salvo usar. Para retirada, não
 * precisa de mais nada: o local é onde os pneus estão.
 */
final readonly class FulfillmentChoice
{
    public function __construct(
        public FulfillmentMethod $method,
        public ?int $addressId = null,
    ) {}

    public static function delivery(int $addressId): self
    {
        return new self(FulfillmentMethod::Delivery, $addressId);
    }

    public static function pickup(): self
    {
        return new self(FulfillmentMethod::Pickup);
    }
}
