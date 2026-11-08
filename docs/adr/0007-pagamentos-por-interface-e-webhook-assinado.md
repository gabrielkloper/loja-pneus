# ADR-0007: Pagamentos atrás de uma interface; confirmação só por webhook assinado

- **Status:** Aceita
- **Data:** 2026-11-08

## Contexto

Ainda não escolhemos o gateway (Mercado Pago, Pagar.me, Stripe…), mas o fluxo de compra precisa funcionar e ser testável desde já. E pagamento é o ponto mais atacado de qualquer loja: ninguém pode conseguir marcar uma compra como paga sem pagar.

## Decisão

1. **Interface `App\Payments\PaymentGateway`** com quatro métodos: `name`, `createCharge`, `parseWebhook`, `refund`. O resto do sistema só conhece a interface; o container entrega a implementação definida em `PAYMENTS_DRIVER`.
2. **Driver `FakeGateway`** para desenvolvimento e testes: gera um "Pix copia e cola" fictício e aceita notificações assinadas com HMAC-SHA256. Ele se recusa a rodar em produção.
3. **Confirmação só por webhook assinado.** O endpoint `POST /webhooks/pagamentos` não tem login nem CSRF; a proteção é a assinatura, comparada com `hash_equals`. Assinatura errada → 401.
4. **Idempotência do webhook:** cada notificação é gravada em `payment_webhook_events` com chave única `(provider, event_id)`. Gateways reenviam notificações; cada uma é processada uma vez só.
5. **Valor conferido:** o valor pago precisa ser igual ao total da compra.
6. **Pagamento atrasado:** se o Pix chegar depois de a compra expirar, o valor é reembolsado (o estoque já voltou para a loja).

Em desenvolvimento, o botão "Simular pagamento" monta uma notificação, assina e a envia pelo **mesmo caminho** do webhook real.

## Alternativas consideradas

- **Integrar um gateway real já no primeiro incremento:** exige conta, credenciais e decisões de split antes de o resto existir.
- **Confirmar pagamento consultando o gateway quando o comprador clica "já paguei":** ainda é útil como complemento, mas o webhook é a fonte principal.

## Consequências

- Adicionar um gateway real = criar uma classe que implementa a interface + uma ADR comparando taxas, Pix e split.
- Os testes cobrem assinatura inválida, evento duplicado, valor divergente e pagamento atrasado.
