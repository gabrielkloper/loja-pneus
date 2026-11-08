# ADR-0009: Dinheiro em centavos inteiros

- **Status:** Aceita
- **Data:** 2026-11-08

## Contexto

Números com casas decimais (`float`) não representam valores como 0,10 com exatidão. Em PHP, `0.1 + 0.2 == 0.3` é `false`. Em dinheiro, isso vira centavos sumindo em somas, descontos e reembolsos.

## Decisão

- Todo valor monetário é um **inteiro em centavos**, no banco e no código: R$ 459,90 é `45990`. As colunas terminam em `_cents`.
- Conversões ficam em `App\Support\Money`:
  - `Money::format(45990)` → `"R$ 459,90"` (exibição);
  - `Money::parse("1.459,90")` → `145990` (entrada do usuário, sem usar `float`);
  - `Money::toInput(3000)` → `"30,00"` (preencher formulários).

## Alternativas consideradas

- **Coluna `decimal(10,2)`:** exata no banco, mas o PHP a lê como string e é fácil cair em `float` no meio do caminho.
- **Biblioteca de dinheiro (ex.: brick/money):** robusta, mas desnecessária enquanto só existe uma moeda.

## Consequências

- Nada de `float` em cálculos de preço. Multiplicação de centavos por quantidade é sempre inteira.
- Se surgir desconto percentual, o arredondamento precisa de uma regra explícita (e de uma ADR).
