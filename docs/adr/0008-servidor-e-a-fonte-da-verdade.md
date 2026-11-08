# ADR-0008: O servidor é a fonte da verdade

- **Status:** Aceita
- **Data:** 2026-11-08

## Contexto

Navegadores podem ser manipulados, usuários clicam duas vezes, redes caem no meio da requisição e, no futuro, agentes de IA vão repetir chamadas e podem ser enganados por textos que leem na web. Qualquer valor vindo de fora é uma sugestão, não um fato.

## Decisão

Cinco regras para toda operação de compra:

1. **Preço vem do banco.** O checkout recebe só *qual anúncio* e *quantas unidades* (`CheckoutItem`). Preço, frete e total são calculados no servidor e congelados no item do pedido.
2. **Chave de idempotência.** Cada tentativa de checkout tem uma chave (UUID). Repetir a chave devolve a mesma compra. Um índice único `(buyer_id, idempotency_key)` garante isso até com requisições simultâneas.
3. **Baixa de estoque atômica.** Um único `UPDATE … SET stock = stock - N WHERE id = ? AND stock >= N`. Se afetar 0 linhas, não há estoque e a transação inteira é desfeita. Nunca vendemos mais do que existe.
4. **Prazo para pagar.** O estoque fica reservado por 30 minutos (configurável). O comando agendado `purchases:expire` devolve o estoque de compras não pagas. Isso impede bots de "sequestrar" o estoque.
5. **Limites de uso:** máximo de unidades por anúncio, de itens no carrinho e de tentativas de checkout por minuto (`config/marketplace.php`).

## Alternativas consideradas

- **Reservar estoque só no pagamento:** duas pessoas pagariam pelo último pneu.
- **Travar a linha com `lockForUpdate` antes de baixar:** funciona no PostgreSQL, mas o `UPDATE` condicional é mais simples, mais rápido e funciona igual em SQLite.

## Consequências

- O web e a futura API para agentes usam exatamente a mesma Action (`PlacePurchase`), então as regras valem para todos os canais.
- O agendador (`php artisan schedule:run` no cron) é obrigatório em produção.
