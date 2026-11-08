# 8. Testes

## Rodar

```bash
php artisan test                         # todos
php artisan test --filter=PlacePurchase  # só os que têm esse nome
php artisan test tests/Feature/Orders    # uma pasta
```

Os testes usam SQLite em memória (`phpunit.xml`): cada teste começa com o banco vazio (`RefreshDatabase`) e roda em segundos.

O GitHub Actions (`.github/workflows/tests.yml`) roda, a cada push: instalação, seeder, testes, Pint (estilo) e Larastan (tipos).

## Organização

| Arquivo | O que prova |
| --- | --- |
| `tests/Feature/Checkout/PlacePurchaseTest.php` | Divisão por loja, totais, estoque atômico, idempotência, endereço, retirada, limites |
| `tests/Feature/Payments/PaymentWebhookTest.php` | Assinatura, evento duplicado, valor errado, pagamento atrasado, botão de simulação |
| `tests/Feature/Orders/OrderLifecycleTest.php` | Máquina de estados, previsão, cancelamentos, expiração |
| `tests/Feature/AuthorizationTest.php` | Ninguém vê ou altera o que não é seu |
| `tests/Feature/StorefrontTest.php` | Vitrine, filtros, carrinho e checkout pelas telas |
| `tests/Feature/Seller/SellerPanelTest.php` | Cadastro de loja, anúncios, envio de pedido |
| `tests/Feature/Auth/*`, `Settings/*` | Do starter kit: login, cadastro, 2FA |

## Como ler um teste

Todo teste segue três blocos: **prepara**, **age**, **confere**.

```php
public function test_nothing_is_saved_when_one_item_has_no_stock(): void
{
    // prepara: dois anúncios da mesma loja, um com estoque 1
    $plenty = $this->listing(['stock' => 10], $location);
    $scarce = $this->listing(['stock' => 1], $location);

    // age: tenta comprar 4 + 2
    try {
        app(PlacePurchase::class)->handle(...);
        $this->fail('Deveria recusar a compra.');
    } catch (BusinessRuleException $e) { ... }

    // confere: a transação desfez a baixa do primeiro item
    $this->assertSame(10, $plenty->fresh()->stock);
    $this->assertSame(0, Purchase::count());
}
```

## Ferramentas usadas

- **Factories** (`database/factories`): `Listing::factory()->remold()->create()` cria um anúncio remold com loja, local e pneu.
- **`tests/Concerns/BuildsMarketplace.php`**: atalhos `buyer()`, `listing()`, `purchase()` e `sendPaidWebhook()`, que assina a notificação como o gateway faria.
- **`Livewire::test(Componente::class)`**: testa um componente sem navegador: `->set('size', '205/55R16')->assertSee('Pirelli')`.
- **`$this->travel(31)->minutes()`**: avança o relógio para testar expiração.

## Escrevendo um teste novo

1. Ache o arquivo do assunto (ou crie um em `tests/Feature/...`).
2. Use `RefreshDatabase` e `BuildsMarketplace`.
3. Dê um nome que leia como frase: `test_a_pickup_order_is_never_shipped`.
4. Rode `php artisan test --filter=nome_do_teste` até passar.
