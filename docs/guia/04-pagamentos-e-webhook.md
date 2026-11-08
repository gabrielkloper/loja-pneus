# 4. Pagamentos e webhook

Arquivos: `app/Payments/*`, `app/Actions/Payments/*`, `app/Http/Controllers/PaymentWebhookController.php` · Decisão: ADR-0007

## O fluxo

```
Comprador        Nossa loja                        Gateway (Pix)            Banco do comprador
    │  finaliza       │                                  │                         │
    │────────────────▶│ PlacePurchase                    │                         │
    │                 │── createCharge() ───────────────▶│                         │
    │◀── Pix copia ───│◀─────────── código Pix ──────────│                         │
    │    e cola       │                                  │                         │
    │─────────────────────────── paga no app ─────────────────────────────────────▶│
    │                 │                                  │◀──── confirma ──────────│
    │                 │◀── POST /webhooks/pagamentos ────│                         │
    │                 │    (assinado com HMAC)           │                         │
    │                 │ parseWebhook() confere assinatura│                         │
    │                 │ ProcessPaymentNotification       │                         │
    │                 │ ConfirmPayment → pedidos "pago"  │                         │
```

## A interface

```php
interface PaymentGateway
{
    public function name(): string;
    public function createCharge(Purchase $purchase): Charge;
    public function parseWebhook(Request $request): PaymentNotification;
    public function refund(Purchase $purchase, int $amountCents, string $reason): void;
}
```

O resto do código só conhece esses quatro métodos. Em `AppServiceProvider::register()` o container decide qual classe entregar:

```php
$this->app->singleton(PaymentGateway::class, function () {
    return match (config('services.payments.driver')) {
        'fake' => new FakeGateway(config('services.payments.fake.webhook_secret')),
        // 'mercadopago' => new MercadoPagoGateway(...),   ← um dia
    };
});
```

Isso é **inversão de dependência**: as regras de negócio dependem de um contrato, não de um fornecedor.

## Assinatura HMAC — por que o webhook é seguro sem login

O endpoint do webhook é público: o gateway não faz login na nossa loja. Então qualquer pessoa poderia enviar `{"type":"paid", ...}`. A defesa é a assinatura:

```php
public function sign(string $body): string
{
    return hash_hmac('sha256', $body, $this->webhookSecret);
}

public function parseWebhook(Request $request): PaymentNotification
{
    if (! hash_equals($this->sign($request->getContent()), $request->header('X-Fake-Signature'))) {
        throw new InvalidWebhookSignature(...);   // → 401
    }
    ...
}
```

- Só nós e o gateway conhecemos o segredo. Sem ele, é impossível calcular a assinatura certa para um corpo qualquer.
- Assinamos o **corpo cru** (`getContent()`), não o JSON reinterpretado: um espaço a mais mudaria a assinatura.
- `hash_equals` compara em tempo constante. Uma comparação comum (`===`) para no primeiro caractere diferente, e um atacante poderia descobrir a assinatura medindo o tempo de resposta.

## Processar uma vez só

Gateways reais reenviam a mesma notificação até receber 200. `ProcessPaymentNotification` usa a tabela `payment_webhook_events`:

```php
$record = PaymentWebhookEvent::createOrFirst(
    ['provider' => 'fake', 'event_id' => $notification->eventId],
    ['type' => ..., 'payload' => ...],
);

if ($record->processed_at !== null) {
    return; // já processado
}
```

`createOrFirst` tenta inserir; se o índice único acusar duplicata, devolve o registro existente. Funciona mesmo com duas entregas simultâneas.

## Confirmar o pagamento

`ConfirmPayment::handle()`:

1. Relê a compra com `lockForUpdate()` (trava a linha até o fim da transação).
2. Se já está paga → não faz nada (segunda camada de idempotência).
3. Confere se o **valor pago é igual ao total**.
4. Se a compra já expirou → devolve o dinheiro (`refund`), porque o estoque já voltou para a loja.
5. Marca compra e pedidos como pagos, calcula a primeira previsão de entrega (hoje + prazo da loja) e registra o evento.

## O botão "Simular pagamento"

Em `PurchaseController::simulatePayment()`: só existe com o `FakeGateway` e fora de produção. Ele monta o corpo da notificação, **assina** e passa pelo mesmo `parseWebhook()` e `ProcessPaymentNotification`. Assim, simular em desenvolvimento exercita o código real.

## Como plugar um gateway real (roteiro)

1. Escrever uma ADR comparando gateways (taxas, Pix, split para marketplace).
2. Criar `app/Payments/MercadoPagoGateway.php` (por exemplo) implementando a interface.
3. Registrar o driver no `match` do `AppServiceProvider` e as credenciais em `config/services.php`.
4. Copiar os testes de `PaymentWebhookTest` adaptando a assinatura do gateway.
