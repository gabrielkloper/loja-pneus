# 7. Segurança: cada proteção e onde ela mora

| Ameaça | Proteção | Onde |
| --- | --- | --- |
| Marcar compra como paga sem pagar | Confirmação só por webhook com assinatura HMAC; `hash_equals` | `FakeGateway::parseWebhook`, ADR-0007 |
| Webhook reenviado processado duas vezes | Registro único por `(provider, event_id)` + compra relida com trava | `ProcessPaymentNotification`, `ConfirmPayment` |
| Pagar valor menor | Valor pago comparado ao total | `ConfirmPayment` |
| Manipular preço no navegador | Checkout não recebe preço; tudo vem do banco | `CheckoutItem`, `PlacePurchase` |
| Vender mais do que existe | `UPDATE … WHERE stock >= N` atômico | `PlacePurchase::createOrder` |
| Compra duplicada (clique duplo, retry) | Chave de idempotência + índice único | `purchases`, `ShowCart::$checkoutKey` |
| Bot travando estoque com compras não pagas | Prazo de 30 min + expiração agendada + limite de tentativas | `purchases:expire`, `ShowCart::placeOrder` |
| Ver ou alterar pedido de outra pessoa | Policies checadas em cada controller e ação Livewire | `OrderPolicy`, `PurchasePolicy`, `ListingPolicy` |
| Usar endereço de outro comprador | Endereço buscado dentro de `$buyer->addresses()` | `PlacePurchase::resolveFulfillment` |
| Anunciar em endereço de outra loja | Local conferido dentro de `$seller->locations()` | `SaveListing` |
| Aprovar a própria loja pelo formulário | `status` fora do `#[Fillable]` | `Seller`, teste dedicado |
| Lojas falsas | Aprovação manual antes de aparecer na vitrine | `SellerStatus::Pending`, ADR-0003 |
| Documento inválido | Dígitos verificadores de CPF/CNPJ (inclui CNPJ alfanumérico) | `App\Rules\CpfCnpj` |
| Contas descartáveis comprando | Checkout e painel exigem e-mail verificado | `routes/web.php` (`verified`), `ShowCart` |
| XSS em descrições | Blade escapa `{{ }}`; descrição usa `nl2br(e(...))` | `listings/show.blade.php` |
| `%` virando curinga na busca | `escapeLike()` + `ESCAPE` | `CatalogSearch` |
| CSRF em formulários | Token `@csrf` (padrão do Laravel); webhook fica de fora só por ter assinatura | `bootstrap/app.php` |
| Excesso de requisições | Rate limit no carrinho e no webhook | `AppServiceProvider::configureRateLimits` |
| Gateway falso em produção | O container recusa o driver `fake` em produção | `AppServiceProvider::register` |
| Contas invadidas | 2FA e passkeys do starter kit (Fortify) | Configurações da conta |

## O que ainda falta (próximos incrementos)

- Painel de administração com auditoria (aprovar lojas, suspender, moderar anúncios).
- Notificações ao comprador a cada evento do pedido.
- Para agentes de IA: OAuth com mandatos e log de auditoria por agente (ADR-0011).
- Cabeçalhos de segurança (CSP, HSTS) no servidor de produção.
