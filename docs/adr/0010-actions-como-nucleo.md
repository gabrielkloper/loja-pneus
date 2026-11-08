# ADR-0010: Actions como núcleo das regras de negócio

- **Status:** Aceita
- **Data:** 2026-11-08

## Contexto

O mesmo "fechar compra" será chamado pela tela do carrinho (Livewire), por uma API REST e por ferramentas MCP para agentes de IA. Se a regra morar dentro de um controller ou componente, ela será copiada — e as cópias divergem.

## Decisão

Cada operação de negócio é uma classe em `app/Actions`, com um método `handle()`:

| Action | O que faz |
| --- | --- |
| `Checkout\PlacePurchase` | Fecha a compra e divide em pedidos por loja |
| `Checkout\CancelPurchase` | Cancela ou expira compra não paga e devolve estoque |
| `Payments\ProcessPaymentNotification` | Registra o webhook e chama a confirmação uma única vez |
| `Payments\ConfirmPayment` | Marca compra e pedidos como pagos |
| `Orders\AdvanceOrderStatus` | Avança o pedido na máquina de estados |
| `Orders\UpdateDeliveryEstimate` | Muda a previsão de entrega |
| `Orders\CancelOrderBySeller` | Loja cancela pedido pago e reembolsa |
| `Sellers\RegisterSeller` / `ApproveSeller` | Cria e aprova lojas |
| `Listings\SaveListing` | Cria/edita anúncio reaproveitando o catálogo |

Divisão de responsabilidades:

```
Rota → Controller ou componente Livewire → Action → Models/banco
          │                                  │
          ├─ autenticação e autorização       ├─ regras de negócio
          ├─ validação de formulário          ├─ transações
          └─ resposta (view, redirect)        └─ BusinessRuleException
```

- Regra quebrada lança `BusinessRuleException`, com mensagem para o usuário final. Em páginas comuns ela volta como erro do formulário; em JSON, como 422.
- Autorização (quem pode) fica nas Policies e é checada na borda; a Action cuida do que pode acontecer com o pedido.

## Alternativas consideradas

- **Regras nos Models ("fat models"):** models ficam enormes e difíceis de testar isoladamente.
- **Services genéricos (`OrderService` com 20 métodos):** tende a virar um arquivo-tudo.

## Consequências

- Os testes mais importantes chamam as Actions diretamente, sem passar por telas.
- O incremento de agentes vai expor as mesmas Actions por OAuth + MCP, sem duplicar regras.
