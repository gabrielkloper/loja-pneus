# 2. Banco de dados e models

## As tabelas e como se ligam

```
users ─┬─ 1:1 ─ sellers ─┬─ 1:N ─ seller_locations
       │                 ├─ 1:N ─ listings ── N:1 ─ tire_models
       │                 └─ 1:N ─ orders
       ├─ 1:N ─ addresses
       └─ 1:N ─ purchases ── 1:N ─ orders ─┬─ 1:N ─ order_items ── N:1 ─ listings
                                            └─ 1:N ─ order_events
payment_webhook_events (sozinha: registro de notificações do gateway)
```

| Tabela | Guarda | Detalhe que vale estudar |
| --- | --- | --- |
| `sellers` | Lojas | `owner_id` único: uma loja por conta |
| `seller_locations` | Endereços físicos | `latitude`/`longitude` opcionais |
| `tire_models` | Catálogo | Índice único na especificação inteira |
| `listings` | Anúncios | `stock` é baixado atomicamente no checkout |
| `addresses` | Endereços do comprador | Copiados para o pedido na compra |
| `purchases` | Compras | Índice único `(buyer_id, idempotency_key)` |
| `orders` | Pedidos por loja | `shipping_address` é JSON (cópia congelada) |
| `order_items` | Itens | Preço e título congelados |
| `order_events` | Linha do tempo | Só `created_at`; nunca editada |
| `payment_webhook_events` | Webhooks recebidos | Índice único `(provider, event_id)` |

## Anatomia de um model

Trecho de `app/Models/Listing.php`:

```php
/**
 * @property int $price_cents          ← docblock: ajuda o editor e o Larastan
 * @property-read Seller $seller          a saber o tipo de cada campo
 */
#[Fillable(['tire_model_id', 'condition', 'price_cents', ...])]
class Listing extends Model
{
    protected function casts(): array
    {
        return [
            'condition' => TireCondition::class, // texto do banco ↔ enum PHP
            'price_cents' => 'integer',
        ];
    }

    /** @return BelongsTo<Seller, $this> */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function scopeVisible(Builder $query): void { ... }
}
```

**`#[Fillable([...])]`** (novidade do Laravel 13; antes era `protected $fillable`): lista os campos que podem ser preenchidos em massa com `fill()` ou `create()`. É uma defesa contra *mass assignment*: se um formulário malicioso enviar `status=active`, o campo é ignorado porque não está na lista. Repare que em `Seller` os campos `status` e `approved_at` ficam fora de propósito, e há um teste que prova isso (`test_the_status_field_cannot_be_forced_through_the_form`).

`Purchase` e `Order` não têm `#[Fillable]`: só as Actions os criam, com `forceCreate()`/`forceFill()`, preenchendo campo a campo de forma explícita.

**`casts()`**: converte valores ao ler e gravar. `'condition' => TireCondition::class` faz `$listing->condition` ser o enum `TireCondition::New`, não a string `'new'`. Com isso, comparações viram `=== TireCondition::Remold` e o editor autocompleta.

**Relações**: `$listing->seller` busca a loja. Para não fazer uma consulta por item numa lista (o problema "N+1"), carregamos antes: `Listing::with(['seller', 'tireModel'])->get()`.

**Scopes**: `scopeVisible()` vira `Listing::query()->visible()`. Ele junta as três condições para um anúncio aparecer (ativo, com estoque, loja ativa). Toda tela pública usa o mesmo scope, então nenhuma esquece uma condição.

## Enums com comportamento

`app/Enums/OrderStatus.php` não é só uma lista: cada caso sabe seu rótulo, sua cor e para onde pode ir.

```php
enum OrderStatus: string
{
    case Paid = 'paid';
    case Preparing = 'preparing';
    // ...

    public function label(): string        // "Em preparação"
    public function color(): string        // cor do badge na tela
    public function sellerCanMoveTo(FulfillmentMethod $method): array // máquina de estados
}
```

Assim, a tela mostra `$order->status->label()` e a regra fica num lugar só.

## Migrations

Cada arquivo em `database/migrations` cria uma tabela. Alguns detalhes:

- `->constrained()` cria a chave estrangeira; `->cascadeOnDelete()` apaga os filhos junto (ex.: endereços de um usuário apagado).
- `->unique([...])` com várias colunas impede duplicatas da combinação (catálogo, idempotência, webhook).
- `decimal('rim', 4, 1)` porque existem aros 17,5 e 22,5 (caminhões).

Comandos úteis:

```bash
php artisan migrate               # aplica migrations novas
php artisan migrate:fresh --seed  # recria tudo do zero com dados de demonstração
php artisan tinker                # console para brincar com os models
>>> App\Models\Listing::visible()->count()
```
