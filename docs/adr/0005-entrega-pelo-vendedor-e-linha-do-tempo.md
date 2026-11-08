# ADR-0005: Entrega pelo vendedor, máquina de estados e linha do tempo

- **Status:** Aceita
- **Data:** 2026-11-08

## Contexto

A plataforma não opera logística: cada loja entrega com transporte próprio ou contratado. Mesmo assim, o comprador precisa saber em que pé está o pedido e quando ele chega, como no Mercado Livre.

## Decisão

**Máquina de estados** em `App\Enums\OrderStatus::sellerCanMoveTo()`:

```
Entrega:  aguardando pagamento → pago → em preparação → enviado → saiu para entrega → entregue
Retirada: aguardando pagamento → pago → em preparação → pronto para retirada → retirado
                                   └──────── cancelado (pela loja, antes de sair) ────────┘
```

- "Pago" só é alcançado pelo sistema, após confirmação do gateway (ADR-0007).
- O vendedor não pode pular etapas nem voltar.
- Ao marcar **enviado**, a previsão de entrega é obrigatória; transportadora e código de rastreio são opcionais.
- A previsão pode ser alterada depois, sem mudar a situação ("a transportadora atrasou").
- A primeira previsão é calculada no pagamento: hoje + prazo informado pela loja.

**Linha do tempo** (`order_events`): toda mudança vira uma linha nova, com quem fez, quando, mensagem e previsão. Nada é sobrescrito.

## Alternativas consideradas

- **Status livre (o vendedor escolhe qualquer um):** gera pedidos "entregues" que nunca foram enviados.
- **Integração com transportadoras/Correios já no início:** valiosa, mas grande. Fica para depois; os campos `carrier` e `tracking_code` já existem.

## Consequências

- Comprador e vendedor veem o mesmo histórico, o que ajuda o suporte em disputas.
- Próximos passos possíveis: notificações por e-mail/WhatsApp a cada evento, confirmação de recebimento pelo comprador, alerta de pedido parado.
