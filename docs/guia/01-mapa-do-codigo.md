# 1. Mapa do código

## Onde fica cada coisa

```
app/
├── Actions/            ← REGRAS DE NEGÓCIO (o coração; ADR-0010)
│   ├── Checkout/       PlacePurchase, CancelPurchase
│   ├── Payments/       ProcessPaymentNotification, ConfirmPayment
│   ├── Orders/         AdvanceOrderStatus, UpdateDeliveryEstimate, CancelOrderBySeller
│   ├── Sellers/        RegisterSeller, ApproveSeller
│   └── Listings/       SaveListing
├── Console/Commands/   purchases:expire, marketplace:approve-seller
├── Data/               Objetos simples que carregam dados (CheckoutItem, FulfillmentChoice)
├── Enums/              Listas fechadas de valores (OrderStatus, TireCondition…)
├── Exceptions/         BusinessRuleException
├── Http/
│   ├── Controllers/    Páginas comuns (Blade) e o webhook
│   └── Middleware/     EnsureUserHasSeller ("seller")
├── Livewire/           Telas interativas (vitrine, carrinho, anúncio, pedido do vendedor)
├── Models/             Tabelas do banco (Eloquent)
├── Payments/           Interface de gateway + FakeGateway
├── Policies/           Quem pode ver/alterar o quê
├── Rules/              Regras de validação próprias (CpfCnpj)
└── Support/            Utilitários (Money, TireSize, Cart, AddressRules)

config/marketplace.php  Limites de negócio (prazo de pagamento, unidades…)
database/migrations/    Estrutura das tabelas
database/factories/     Dados falsos para testes
database/seeders/       Dados de demonstração
resources/views/        Blade (páginas) e views dos componentes Livewire
routes/web.php          Todas as URLs
routes/console.php      Tarefas agendadas
tests/                  Testes automatizados
docs/                   ADRs e este guia
```

## O caminho de uma requisição

Exemplo: o vendedor clica em "Salvar situação" num pedido.

```
1. Navegador ── POST /livewire/update ──▶ Livewire
2. Livewire recria o componente App\Livewire\Seller\ManageOrder
   e chama o método advance()
3. advance():
   ├─ $this->authorize('manage', $order)   → OrderPolicy::manage()  (quem pode)
   ├─ $this->validate([...])               → campos do formulário    (formato)
   └─ $advance->handle($order, $to, ...)   → AdvanceOrderStatus      (regra)
4. AdvanceOrderStatus:
   ├─ confere a máquina de estados (OrderStatus::sellerCanMoveTo)
   ├─ grava o pedido numa transação
   └─ registra o evento na linha do tempo (Order::recordEvent)
5. Livewire renderiza a view de novo e devolve só o HTML que mudou
```

Para uma página comum (ex.: `GET /pedidos/{order}`) o caminho é igual, trocando o componente Livewire por um método de controller que devolve uma view Blade.

## Três peças do Laravel que aparecem o tempo todo

**Container e injeção de dependência.** Quando um construtor ou método pede uma classe, o Laravel cria e entrega:

```php
final class PlacePurchase
{
    // Ninguém faz "new FakeGateway" aqui: o container decide qual gateway
    // entregar, conforme AppServiceProvider::register().
    public function __construct(private readonly PaymentGateway $gateway) {}
}
```

**Route model binding.** Em `Route::get('pedidos/{order}', ...)`, o Laravel lê o número da URL e busca o `Order` correspondente (404 se não existir). Em `lojas/{seller:slug}` a busca é pela coluna `slug`.

**Policies.** `OrderPolicy::view()` devolve `true` ou `false`. `Gate::authorize('view', $order)` lança 403 quando é `false`. O Laravel encontra a policy pelo nome: `Order` → `OrderPolicy`.

## Por onde começar a ler

1. `routes/web.php` — todas as URLs e quem atende cada uma.
2. `app/Actions/Checkout/PlacePurchase.php` — a regra mais importante.
3. `tests/Feature/Checkout/PlacePurchaseTest.php` — a mesma regra descrita como exemplos.
