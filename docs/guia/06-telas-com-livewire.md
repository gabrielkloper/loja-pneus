# 6. Telas com Livewire

O projeto usa dois estilos de tela, de propósito, para você comparar:

| Estilo | Exemplos | Quando usar |
| --- | --- | --- |
| Controller + Blade + formulário HTML | cadastro de loja, endereços, páginas de pedido | Páginas que só mostram dados ou enviam um formulário |
| Componente Livewire | vitrine, carrinho, formulário de anúncio, pedido do vendedor | Telas que reagem enquanto o usuário mexe |

## Como um componente Livewire funciona

Um componente é uma **classe PHP** (estado + ações) e uma **view Blade** (aparência).

```php
#[Layout('layouts::shop')]          // layout em volta da página
#[Title('Pneus novos e remold')]    // <title> da aba
class CatalogSearch extends Component
{
    #[Url(as: 'medida', except: '')] // espelha na URL: /?medida=205/55R16
    public string $size = '';

    public function updated(): void  // chamado sozinho após qualquer mudança
    {
        $this->resetPage();
    }

    public function render(): View   // roda a cada interação
    {
        return view('livewire.catalog-search', ['listings' => ...]);
    }
}
```

```blade
<flux:input wire:model.live.debounce.400ms="size" label="Medida" />
```

O ciclo:

1. O usuário digita "205/55R16".
2. Depois de 400 ms parado (`debounce`), o navegador envia o novo valor.
3. Livewire atualiza `$size` na classe, chama `updated()` e `render()`.
4. O HTML novo volta e só o que mudou é trocado na página.

**Propriedades públicas são estado enviado ao navegador.** Por isso:

- nunca guarde nelas algo secreto;
- trate qualquer valor delas como vindo do usuário (validar sempre);
- use `#[Locked]` quando o navegador não pode alterar o valor (ex.: `$checkoutKey` e `$listingId`).

Models em propriedades públicas (`public Order $order` em `ManageOrder`) são guardados só pelo id e recarregados do banco a cada ação; o Livewire impede trocar o id pelo navegador. Mesmo assim, cada ação chama `$this->authorize()` de novo.

## Os quatro componentes

### `CatalogSearch` (vitrine)

Filtros ligados à URL; a busca usa `Listing::visible()` mais um `when()` por filtro:

```php
->when($size, fn ($q) => $q->whereHas('tireModel', fn ($t) => $t->where('width', ...)))
```

`when(condição, função)` só aplica o filtro se a condição for verdadeira. `whereHas` filtra pelo relacionamento (anúncios cujo pneu tem aquela medida).

`escapeLike()` impede que um `%` digitado vire curinga do `LIKE`.

### `ShowCart` (carrinho e checkout)

- O carrinho em si é a sessão (`App\Support\Cart`): só `id do anúncio => quantidade`.
- `render()` busca os anúncios e agrupa por loja, calculando subtotais no servidor.
- `$fulfillment[idDaLoja]` guarda a escolha de entrega/retirada de cada loja (radio com `wire:model.live`).
- `placeOrder()`: confere login e e-mail verificado, aplica limite de tentativas (`RateLimiter`), monta `CheckoutItem` e `FulfillmentChoice`, chama `PlacePurchase` e trata `BusinessRuleException` mostrando a mensagem com `addError()`.

### `Seller\ListingForm` (anúncio)

O mesmo componente cria e edita: em `mount(?Listing $listing = null)` o Laravel entrega o anúncio da URL ou `null`. O campo do Inmetro só aparece para remold porque o select de condição usa `wire:model.live`, que re-renderiza na hora.

Campos numéricos são `string` na classe: um `<input>` vazio envia `""`, que não cabe numa propriedade `?int`. Convertemos com `(int)` depois de validar.

### `Seller\ManageOrder` (pedido do vendedor)

Três formulários independentes (`advance`, `updateEstimate`, `cancel`), cada um chamando sua Action. `Flux::toast()` mostra a confirmação no canto da tela.

## Layout

`resources/views/layouts/shop.blade.php` é usado pelas duas formas:

- views comuns: `<x-layouts::shop :title="'Meus pedidos'">…</x-layouts::shop>`
- componentes: `#[Layout('layouts::shop')]`

As telas de configurações da conta (perfil, senha, 2FA) vêm do starter kit e usam o layout com barra lateral (`layouts::app`).
