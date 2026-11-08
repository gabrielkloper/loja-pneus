# Guia do código

Este guia explica **o que cada parte importante do código faz e por quê**. Ele complementa as [ADRs](../adr/README.md): as ADRs registram decisões; o guia mostra como elas viraram código.

Sugestão de leitura, na ordem em que uma compra acontece:

| # | Capítulo | Você vai entender |
| --- | --- | --- |
| 1 | [Mapa do código](01-mapa-do-codigo.md) | Onde fica cada coisa e o caminho de uma requisição |
| 2 | [Banco de dados e models](02-banco-e-models.md) | Tabelas, relações, casts, enums, `#[Fillable]` |
| 3 | [Checkout](03-checkout.md) | `PlacePurchase` linha a linha: divisão por loja, estoque atômico, idempotência |
| 4 | [Pagamentos e webhook](04-pagamentos-e-webhook.md) | Interface de gateway, HMAC, processamento único |
| 5 | [Pedidos e entrega](05-pedidos-e-entrega.md) | Máquina de estados, previsão, linha do tempo, cancelamentos |
| 6 | [Telas com Livewire](06-telas-com-livewire.md) | Como os componentes funcionam: `wire:model`, `#[Url]`, `#[Locked]` |
| 7 | [Segurança](07-seguranca.md) | Cada proteção e o arquivo onde ela mora |
| 8 | [Testes](08-testes.md) | Como rodar, como ler e como escrever novos testes |

## Glossário rápido

| Termo | Significado aqui |
| --- | --- |
| **Loja / Seller** | Quem vende. Pessoa física (CPF) ou jurídica (CNPJ). |
| **Anúncio / Listing** | A oferta de uma loja para um pneu: preço, estoque, condição. |
| **Pneu do catálogo / TireModel** | O pneu em si (marca, modelo, medida). Compartilhado entre lojas. |
| **Compra / Purchase** | Um checkout com um único Pix. Contém um pedido por loja. |
| **Pedido / Order** | A parte da compra que cabe a uma loja. É o que a loja envia. |
| **Action** | Classe em `app/Actions` com uma regra de negócio e um método `handle()`. |
| **Idempotência** | Repetir a mesma operação não muda o resultado (ex.: clique duplo não cria duas compras). |
| **Webhook** | Chamada HTTP que o gateway faz para o nosso sistema avisando algo (ex.: "pago"). |
| **HMAC** | Assinatura de uma mensagem com um segredo compartilhado; prova quem enviou. |
| **Remold** | Pneu reconstruído sobre carcaça usada; exige registro no Inmetro. |
| **DOT** | Código na lateral do pneu; os 4 últimos dígitos são semana e ano de fabricação. |
