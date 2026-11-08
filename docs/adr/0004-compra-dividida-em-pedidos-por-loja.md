# ADR-0004: Uma compra, um pagamento, um pedido por loja

- **Status:** Aceita
- **Data:** 2026-11-08

## Contexto

O comprador pode levar pneus de lojas diferentes no mesmo carrinho. Cada loja prepara, entrega e atualiza o próprio envio; o comprador quer pagar uma vez só.

## Decisão

Dois níveis, como na Amazon e no Mercado Livre:

- **`purchases` (compra):** o checkout. Tem **um** pagamento (um Pix), prazo para pagar e chave de idempotência.
- **`orders` (pedido):** uma por loja dentro da compra. Tem situação, forma de entrega, frete, previsão e linha do tempo próprias.

```
Compra C-000042 (Pix de R$ 2.130,00)
├── Pedido P-000101 · Pneus Curitiba · entrega · R$ 1.630,00
└── Pedido P-000102 · Remold Sul · retirada · R$ 500,00
```

Regras:

- Antes do pagamento, quem cancela é o **comprador**, e cancela a **compra inteira** (o Pix é do valor total).
- Depois do pagamento, cada **loja** pode cancelar o **próprio pedido**, com reembolso só daquele valor.

## Alternativas consideradas

- **Um pedido só com itens de várias lojas:** quebra quando uma loja envia e a outra não; o status vira uma mistura.
- **Um pagamento por loja:** o comprador pagaria vários Pix seguidos; péssima experiência.

## Consequências

- O vendedor só enxerga os seus pedidos, nunca a compra inteira de outro vendedor.
- O repasse do dinheiro para cada loja (split) depende do gateway real e entra numa ADR própria quando o gateway for escolhido.
