# Loja de Pneus — marketplace de pneus novos e remold

Marketplace em Laravel onde várias lojas (pessoa física ou jurídica) anunciam pneus novos e remoldados. O comprador compara ofertas, paga uma vez só e recebe pelo vendedor ou retira na loja. O sistema está sendo preparado para que **agentes de IA** possam comprar em nome do dono da conta, com segurança (ADR-0011).

> **Incremento 1 (atual):** marketplace funcionando de ponta a ponta com pagamento simulado.
> Próximo: compras por agentes de IA (OAuth + mandatos + MCP).

## O que já funciona

**Comprador**
- Vitrine com busca por medida (`205/55 R16`), marca, condição, estado, cidade e "só com retirada".
- Página do anúncio com especificação, DOT, garantia, registro Inmetro (remold), mapa da loja e "outras lojas com este pneu".
- Carrinho com itens de várias lojas; para cada loja, escolha entre **entrega** ou **retirada**.
- Checkout que gera **uma compra com um Pix** e **um pedido por loja**.
- Acompanhamento do pedido com situação, previsão de entrega, rastreio e linha do tempo.

**Vendedor**
- Cadastro de loja com CPF ou CNPJ validados (inclusive CNPJ alfanumérico), aguardando aprovação.
- Vários endereços por loja, com coordenadas, horário e opção de retirada.
- Anúncios reaproveitando um catálogo compartilhado de pneus.
- Painel de pedidos: preparar → enviar (com previsão e rastreio) → entregue, ou pronto para retirada → retirado; atualizar previsão; cancelar com reembolso.

**Plataforma**
- Pagamento por interface de gateway, com driver falso que assina webhooks (HMAC).
- Estoque baixado atomicamente; compras não pagas expiram em 30 minutos e devolvem o estoque.
- Testes automatizados de todas as regras de negócio e CI no GitHub Actions.

## Rodando localmente

Requisitos: PHP 8.3+, Composer 2 e Node 22+.

```bash
git clone <url-do-repositorio> loja-pneus
cd loja-pneus
composer setup                    # instala dependências, cria .env, migra o banco e compila o front
php artisan migrate:fresh --seed  # dados de demonstração
composer dev                      # servidor + vite + fila + logs
```

Abra http://localhost:8000.

Em outro terminal, deixe o agendador rodando (é ele que expira compras não pagas):

```bash
php artisan schedule:work
```

### Contas de demonstração (senha: `password`)

| E-mail | Papel |
| --- | --- |
| `comprador@exemplo.com` | Comprador com endereço salvo |
| `vendedor@exemplo.com` | Loja "Pneus Curitiba" (entrega e retirada, dois endereços) |
| `remold@exemplo.com` | Loja "Remold Sul" (só retirada) |

### Roteiro para testar o fluxo completo

1. Entre como **comprador**, busque `205/55R16`, adicione 4 pneus ao carrinho.
2. Finalize a compra e clique em **Simular pagamento (ambiente de teste)**.
3. Saia e entre como **vendedor** → Painel da loja → Pedidos → abra o pedido.
4. Avance para "Em preparação" e depois "Enviado", informando previsão e rastreio.
5. Volte como comprador em **Meus pedidos** e veja a linha do tempo atualizada.

### Aprovar uma loja nova

Lojas cadastradas pelo site ficam aguardando aprovação:

```bash
php artisan marketplace:approve-seller nome-da-loja   # o slug aparece em /lojas/{slug}
```

### E-mail de verificação

Comprar e vender exigem e-mail verificado. Em desenvolvimento os e-mails vão para `storage/logs/laravel.log` (`MAIL_MAILER=log`); copie o link de verificação de lá.

## Testes e qualidade

```bash
php artisan test        # testes
vendor/bin/pint         # corrige o estilo do código
vendor/bin/phpstan      # análise estática (Larastan nível 7)
```

## Documentação

- **[Guia do código](docs/guia/README.md)** — explica as partes importantes, com trechos comentados. Comece por aqui para estudar.
- **[ADRs](docs/adr/README.md)** — cada decisão de arquitetura, com alternativas e consequências.

## Configuração

| Variável | Padrão | Para quê |
| --- | --- | --- |
| `PAYMENTS_DRIVER` | `fake` | Gateway de pagamento (só `fake` por enquanto; bloqueado em produção) |
| `PAYMENTS_FAKE_WEBHOOK_SECRET` | — | Segredo da assinatura HMAC do webhook |
| `MARKETPLACE_PAYMENT_WINDOW_MINUTES` | `30` | Prazo para pagar antes de o estoque voltar |
| `MARKETPLACE_MAX_UNITS_PER_ITEM` | `12` | Unidades máximas por anúncio numa compra |
| `MARKETPLACE_MAX_CART_ITEMS` | `20` | Anúncios diferentes no carrinho |
| `MARKETPLACE_CHECKOUT_PER_MINUTE` | `10` | Tentativas de checkout por minuto por usuário |

Em produção use PostgreSQL (`DB_CONNECTION=pgsql`) e configure o cron do agendador:

```
* * * * * cd /caminho/do/projeto && php artisan schedule:run >> /dev/null 2>&1
```

## Roteiro

- [x] Incremento 1 — marketplace, entrega/retirada, acompanhamento, pagamento simulado
- [ ] Incremento 2 — agentes de IA: OAuth (Passport), mandatos de compra, servidor MCP
- [ ] Gateway real com split (Mercado Pago ou Pagar.me)
- [ ] Notificações (e-mail/WhatsApp) a cada evento do pedido
- [ ] Painel de administração (aprovar lojas, moderação)
- [ ] Busca por distância e preenchimento de endereço por CEP
- [ ] Avaliações de lojas e confirmação de recebimento pelo comprador
