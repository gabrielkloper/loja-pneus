# ADR-0011: Compras por agentes de IA — OAuth, mandatos e MCP

- **Status:** Proposta (próximo incremento)
- **Data:** 2026-11-08

## Contexto

O diferencial da loja é permitir que agentes de IA (Claude e outros) comprem pneus em nome do dono da conta. O risco: agentes maliciosos, tokens vazados e *prompt injection* transformarem essa porta em vulnerabilidade.

Princípio: **o agente nunca é a autoridade; o dono é.** O agente age só dentro de uma permissão delegada, limitada e revogável.

## Decisão proposta

1. **Delegação por OAuth 2.1 + PKCE com Laravel Passport.** O dono aprova o agente numa tela da loja; o agente recebe um token com escopos (`catalog:read`, `orders:create`, `orders:read`). Nunca recebe a senha.
2. **Mandatos de compra.** Cada token fica ligado a limites aprovados pelo dono: valor por pedido, valor por mês, unidades, validade, endereços já cadastrados. A ideia segue os *mandates* do protocolo AP2.
3. **O agente nunca toca no dinheiro.** Ele cria a compra; o pagamento continua sendo Pix pago pelo dono no app do banco, ou um cartão já tokenizado, com aprovação humana acima de um limite.
4. **MCP com o pacote oficial `laravel/mcp`.** Dois servidores: `/mcp/catalogo` (público, só leitura) e `/mcp/loja` (protegido por Passport). Cada ferramenta MCP é uma casca fina sobre as Actions existentes (ADR-0010).
5. **Conteúdo de terceiros marcado.** Descrições e avaliações saem num campo separado, para que o agente não as confunda com instruções da loja.

## Consequências esperadas

- `PlacePurchase` já está pronta para isso: recebe só anúncio e quantidade, usa chave de idempotência e recalcula tudo no servidor (ADR-0008).
- Será preciso: tabela `agent_mandates`, tela de consentimento, página "Agentes conectados" com revogação, log de auditoria por agente.
