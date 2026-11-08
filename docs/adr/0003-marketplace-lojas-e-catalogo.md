# ADR-0003: Marketplace com lojas aprovadas e catálogo compartilhado

- **Status:** Aceita
- **Data:** 2026-11-08

## Contexto

A plataforma não vende pneus próprios: várias lojas (pessoas físicas ou jurídicas) anunciam. Isso traz dois problemas clássicos de marketplace:

1. **Lojas falsas** aplicando golpes.
2. **Catálogo bagunçado:** dez lojas cadastram o mesmo pneu de dez jeitos, e o comprador não consegue comparar.

## Decisão

**Lojas (`sellers`)**

- Qualquer usuário com e-mail verificado pode abrir uma loja, informando CPF ou CNPJ válido (inclusive o CNPJ alfanumérico, emitido desde julho de 2026).
- A loja nasce **aguardando aprovação** (`pending`). Ela já pode cadastrar endereços e anúncios, mas nada aparece na vitrine até a aprovação (`active`).
- Aprovação por enquanto é feita no terminal: `php artisan marketplace:approve-seller {slug}`.
- Uma conta tem no máximo uma loja neste incremento.

**Catálogo + ofertas (modelo do Mercado Livre)**

- `tire_models` guarda o pneu em si: marca, modelo, medida (largura/perfil/aro), índice de carga e de velocidade. Cada combinação existe **uma vez** (índice único).
- `listings` é a oferta de uma loja para um pneu do catálogo: condição (novo ou remold), preço, estoque, DOT, garantia, local.
- Ao salvar um anúncio, `SaveListing` procura o pneu no catálogo e só cria um novo se não existir.
- Remold exige número de registro no Inmetro.

## Alternativas consideradas

- **Aprovação automática:** mais simples, mas abre espaço para golpes desde o primeiro dia.
- **Anúncio com especificação livre (modelo OLX):** cadastro mais fácil, mas impede "outras lojas com este pneu" e a busca por medida fica imprecisa.

## Consequências

- A página do anúncio mostra "Outras lojas com este pneu", ordenadas por preço.
- Será preciso um painel de administração (aprovar lojas, moderar anúncios) num incremento futuro.
- Variações de escrita do modelo ("Cinturato P7" vs "Cinturato-P7") ainda criam pneus diferentes. Uma lista de modelos sugeridos resolve isso depois.
