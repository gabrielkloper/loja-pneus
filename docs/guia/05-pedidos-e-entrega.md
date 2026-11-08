# 5. Pedidos e entrega

Arquivos: `app/Enums/OrderStatus.php`, `app/Actions/Orders/*`, `app/Livewire/Seller/ManageOrder.php` · Decisão: ADR-0005

## A máquina de estados

```php
public function sellerCanMoveTo(FulfillmentMethod $method): array
{
    return match ($this) {
        self::Paid => [self::Preparing],
        self::Preparing => $method === FulfillmentMethod::Delivery
            ? [self::Shipped]
            : [self::ReadyForPickup],
        self::Shipped => [self::OutForDelivery, self::Delivered],
        self::OutForDelivery => [self::Delivered],
        self::ReadyForPickup => [self::PickedUp],
        default => [],
    };
}
```

- `match` devolve a lista de próximos passos permitidos a partir da situação atual.
- `default => []` cobre as situações finais e as que o vendedor não controla (aguardando pagamento).
- A mesma função alimenta a tela (o select só mostra opções válidas) e a regra (a Action recusa o resto). A tela não é a proteção; a Action é.

## As três Actions do vendedor

### `AdvanceOrderStatus::handle($order, $to, $seller, $eta, $carrier, $tracking, $message)`

1. Confere se `$to` está em `sellerCanMoveTo()`. Se não → `BusinessRuleException`.
2. Se for **enviado**: exige previsão de entrega, que não pode estar no passado.
3. Grava a situação (e transportadora, rastreio, previsão quando informados).
4. Registra um evento com a mensagem do vendedor ou uma mensagem padrão ("Pedido enviado. Rastreio: BR123.").

### `UpdateDeliveryEstimate::handle($order, $eta, $seller, $message)`

Muda só a previsão. Vale para pedidos de entrega em `pago`, `em preparação`, `enviado` ou `saiu para entrega`. O evento guarda a nova data, então o comprador vê o histórico de previsões.

### `CancelOrderBySeller::handle($order, $seller, $reason)`

1. Relê o pedido com `lockForUpdate()`: dois cliques não reembolsam duas vezes.
2. Só cancela se `sellerCanCancel()` (pago, em preparação ou pronto para retirada).
3. Devolve cada item ao estoque, marca `refunded_cents` e registra o evento.
4. Chama `refund()` no gateway **depois** da transação.

## A linha do tempo

`Order::recordEvent()` é o único jeito de escrever em `order_events`:

```php
$order->recordEvent(
    OrderEventActor::Seller,     // quem
    'Pedido enviado.',           // mensagem
    OrderStatus::Shipped,        // nova situação (ou null se não mudou)
    $seller,                     // usuário
    $eta,                        // previsão (opcional)
);
```

A view `resources/views/partials/order-timeline.blade.php` é usada tanto pelo comprador (`orders/show`) quanto pelo vendedor (`livewire/seller/manage-order`), então os dois veem exatamente a mesma história.

## O que o comprador vê

`resources/views/orders/show.blade.php` mostra primeiro o que mais importa:

- **Entrega:** endereço, previsão em destaque, transportadora e rastreio.
- **Retirada:** endereço da loja, horário, link do mapa e o aviso "pronto para retirada".
- Depois, a linha do tempo completa.
