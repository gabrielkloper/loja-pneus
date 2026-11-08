# Registro de decisões de arquitetura (ADRs)

Cada arquivo desta pasta registra **uma** decisão importante: o problema, o que foi decidido, as alternativas descartadas e as consequências. Decisões não são apagadas; quando mudam, uma ADR nova substitui a antiga (ADR-0001).

| ADR | Decisão | Status |
| --- | --- | --- |
| [0001](0001-registrar-decisoes-em-adrs.md) | Registrar decisões em ADRs | Aceita |
| [0002](0002-stack-laravel-livewire.md) | Stack: Laravel 13, Livewire 4, Flux, PostgreSQL | Aceita |
| [0003](0003-marketplace-lojas-e-catalogo.md) | Marketplace com lojas aprovadas e catálogo compartilhado | Aceita |
| [0004](0004-compra-dividida-em-pedidos-por-loja.md) | Uma compra, um pagamento, um pedido por loja | Aceita |
| [0005](0005-entrega-pelo-vendedor-e-linha-do-tempo.md) | Entrega pelo vendedor, máquina de estados e linha do tempo | Aceita |
| [0006](0006-enderecos-das-lojas-e-retirada.md) | Endereços das lojas e retirada presencial | Aceita |
| [0007](0007-pagamentos-por-interface-e-webhook-assinado.md) | Pagamentos atrás de uma interface; confirmação só por webhook assinado | Aceita |
| [0008](0008-servidor-e-a-fonte-da-verdade.md) | O servidor é a fonte da verdade | Aceita |
| [0009](0009-dinheiro-em-centavos.md) | Dinheiro em centavos inteiros | Aceita |
| [0010](0010-actions-como-nucleo.md) | Actions como núcleo das regras de negócio | Aceita |
| [0011](0011-compras-por-agentes-de-ia.md) | Compras por agentes de IA: OAuth, mandatos e MCP | Proposta |
| [0012](0012-ambiente-local-com-docker.md) | Ambiente local com Docker | Aceita |

Modelo para uma ADR nova: copie [`template.md`](template.md).
