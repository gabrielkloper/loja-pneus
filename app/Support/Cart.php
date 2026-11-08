<?php

namespace App\Support;

/**
 * Carrinho guardado na sessão do navegador.
 *
 * Guardamos só "id do anúncio => quantidade". Preço, título e estoque são
 * sempre lidos do banco na hora de exibir e de fechar a compra (ADR-0008):
 * o carrinho nunca é fonte de verdade sobre valores.
 */
final class Cart
{
    private const SESSION_KEY = 'cart.items';

    /**
     * @return array<int, int> id do anúncio => quantidade
     */
    public function items(): array
    {
        /** @var array<int, int> $items */
        $items = session()->get(self::SESSION_KEY, []);

        return $items;
    }

    /**
     * Soma quantidade a um item (ou cria). Os limites vêm de config/marketplace.php.
     */
    public function add(int $listingId, int $quantity): void
    {
        $items = $this->items();

        if (! isset($items[$listingId]) && count($items) >= (int) config('marketplace.max_cart_items')) {
            return; // carrinho cheio: ignora novos anúncios
        }

        $this->set($listingId, ($items[$listingId] ?? 0) + $quantity);
    }

    /**
     * Define a quantidade exata. Zero ou menos remove o item.
     */
    public function set(int $listingId, int $quantity): void
    {
        $items = $this->items();
        $max = (int) config('marketplace.max_units_per_item');

        if ($quantity <= 0) {
            unset($items[$listingId]);
        } else {
            $items[$listingId] = min($quantity, $max);
        }

        session()->put(self::SESSION_KEY, $items);
    }

    public function remove(int $listingId): void
    {
        $this->set($listingId, 0);
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /** Total de unidades, para o contador no topo da página. */
    public function count(): int
    {
        return array_sum($this->items());
    }
}
