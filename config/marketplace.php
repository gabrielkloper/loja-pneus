<?php

/*
|--------------------------------------------------------------------------
| Regras do marketplace
|--------------------------------------------------------------------------
|
| Valores de negócio ficam aqui, e não espalhados pelo código, para que
| possam mudar por ambiente (via .env) sem alterar classes. Veja
| docs/guia/02-configuracao.md para a explicação de cada item.
|
*/

return [

    // Minutos que o comprador tem para pagar antes de a compra expirar
    // e o estoque voltar para o vendedor (ADR-0008).
    'payment_window_minutes' => (int) env('MARKETPLACE_PAYMENT_WINDOW_MINUTES', 30),

    // Máximo de unidades de um mesmo anúncio numa compra. Um carro usa
    // 4 ou 5 pneus; caminhões usam mais, por isso o padrão é 12.
    'max_units_per_item' => (int) env('MARKETPLACE_MAX_UNITS_PER_ITEM', 12),

    // Máximo de anúncios diferentes num carrinho.
    'max_cart_items' => (int) env('MARKETPLACE_MAX_CART_ITEMS', 20),

    // Tentativas de checkout por minuto por usuário (ADR-0008).
    'checkout_attempts_per_minute' => (int) env('MARKETPLACE_CHECKOUT_PER_MINUTE', 10),

];
