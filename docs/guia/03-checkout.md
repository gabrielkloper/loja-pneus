# 3. Checkout — `PlacePurchase` linha a linha

Arquivo: `app/Actions/Checkout/PlacePurchase.php` · Decisões: ADR-0004 e ADR-0008

É a **única** forma de criar pedidos no sistema. Hoje quem chama é o carrinho (`App\Livewire\ShowCart::placeOrder`); no incremento de agentes, a API e o MCP chamarão a mesma classe.

## Entrada e saída

```php
public function handle(
    User $buyer,
    array $items,          // list<CheckoutItem>: qual anúncio e quantas unidades
    array $fulfillment,    // [id da loja => FulfillmentChoice]: entrega ou retirada
    string $idempotencyKey // UUID desta tentativa
): Purchase
```

- **Não existe preço na entrada.** `CheckoutItem` tem só `listingId` e `quantity`.
- **Saída:** a `Purchase` criada (ou a já existente, se a chave se repetir).
- **Erros:** `BusinessRuleException` com mensagem pronta para o usuário.

## Passo a passo

### 1. Idempotência

```php
if ($existing = $this->findExisting($buyer, $idempotencyKey)) {
    return $existing;
}
```

O carrinho gera a chave quando a tela abre (`$checkoutKey = Str::uuid()`). Clique duplo, F5 ou um agente repetindo a chamada mandam a mesma chave e recebem a mesma compra.

### 2. Juntar quantidades

`mergeQuantities()` soma linhas repetidas do mesmo anúncio e aplica o limite de `config('marketplace.max_units_per_item')`. Somar antes de validar fecha a brecha "3 + 3 para burlar um limite de 5".

### 3. Transação

```php
$purchase = DB::transaction(fn () => $this->createPurchase(...));
```

Tudo dentro da função roda numa **transação**: se qualquer linha lançar exceção, o banco desfaz **todas** as alterações daquela função. É isso que garante que, se o segundo item não tiver estoque, a baixa do primeiro volta.

### 4. Carregar e conferir os anúncios

`loadAndCheckListings()` busca os anúncios **no banco** e recusa se algum:

- não existe, está pausado, sem estoque ou é de loja não aprovada (`isVisible()`);
- pertence à loja do próprio comprador.

### 5. Gravar a compra primeiro

```php
$purchase = Purchase::forceCreate([... 'idempotency_key' => $idempotencyKey, 'total_cents' => 0 ...]);
```

Por que primeiro? O índice único `(buyer_id, idempotency_key)` dispara aqui se outra requisição com a mesma chave já gravou. Isso acontece **antes** de mexermos no estoque. O `catch (UniqueConstraintViolationException)` lá em cima devolve a compra vencedora.

### 6. Um pedido por loja

```php
foreach ($listings->groupBy('seller_id')->sortKeys() as $sellerId => $sellerListings) {
    $choice = $fulfillment[$sellerId] ?? throw new BusinessRuleException(...);
    $order = $this->createOrder(...);
}
```

`groupBy('seller_id')` transforma a lista de anúncios em grupos por loja. Cada grupo vira um `Order`.

### 7. Entrega ou retirada (`resolveFulfillment`)

- **Entrega:** a loja precisa entregar, e o endereço precisa ser **do comprador** (`$buyer->addresses()->whereKey(...)`). Sem essa checagem, bastaria mandar o id do endereço de outra pessoa.
- **Retirada:** todos os itens da loja precisam estar no mesmo endereço, e ele precisa aceitar retirada.

### 8. Baixa de estoque atômica — o trecho mais importante

```php
$updated = Listing::query()
    ->whereKey($listing->id)
    ->where('stock', '>=', $quantity)
    ->decrement('stock', $quantity);

if ($updated === 0) {
    throw new BusinessRuleException("Estoque insuficiente para ...");
}
```

Vira um único comando SQL:

```sql
UPDATE listings SET stock = stock - 4 WHERE id = 7 AND stock >= 4
```

Imagine duas pessoas comprando os últimos 4 pneus no mesmo instante. Ler o estoque, conferir e depois gravar abriria uma janela em que as duas leem "4". Com o `UPDATE` condicional, o banco executa um de cada vez: o primeiro deixa 0, o segundo afeta 0 linhas e cai no erro. **Nunca vendemos mais do que existe.**

### 9. Preço congelado

```php
$lineTotal = $listing->price_cents * $quantity;     // preço do BANCO
$itemRows[] = ['unit_price_cents' => $listing->price_cents, 'title' => $listing->title(), ...];
```

O item guarda preço e título do momento. Se o vendedor mudar o anúncio amanhã, o pedido de hoje não muda.

### 10. Evento inicial e total

Cada pedido nasce `AwaitingPayment` com um evento na linha do tempo. A soma dos pedidos vira o `total_cents` da compra.

### 11. Cobrança fora da transação

```php
$charge = $this->gateway->createCharge($purchase);
```

Chamadas de rede podem demorar; não devem acontecer com o banco "segurando" a transação. Se o gateway falhar, a compra fica sem cobrança e expira sozinha em 30 minutos, devolvendo o estoque.

## E se o comprador não pagar?

`app/Console/Commands/ExpireUnpaidPurchases.php` roda a cada minuto (agendado em `routes/console.php`). Ele procura compras pendentes com prazo vencido e chama `CancelPurchase` com status `Expired`, que devolve cada unidade ao estoque com `increment('stock', ...)`.

## Teste que descreve tudo isso

`tests/Feature/Checkout/PlacePurchaseTest.php` tem um teste para cada regra acima. Comece por `test_it_creates_one_order_per_seller_with_server_side_totals` e `test_nothing_is_saved_when_one_item_has_no_stock`.
