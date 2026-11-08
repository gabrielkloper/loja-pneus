# ADR-0001: Registrar decisões em ADRs

- **Status:** Aceita
- **Data:** 2026-11-08

## Contexto

O sistema vai crescer aos poucos, com pausas e com ajuda de agentes de IA. Sem registro, o motivo de cada escolha se perde: daqui a seis meses ninguém lembra por que o pagamento só é confirmado por webhook ou por que o estoque baixa na hora da compra.

## Decisão

Toda decisão cara de reverter vira uma ADR em `docs/adr/NNNN-titulo.md`, no formato do [`template.md`](template.md): Status, Contexto, Decisão, Alternativas e Consequências.

Junto com as ADRs, o guia em [`docs/guia`](../guia/README.md) explica o código das partes mais importantes.

## Alternativas consideradas

- **Wiki externa:** fica fora do repositório e desatualiza.
- **Só comentários no código:** explicam o *como*, mas não guardam as opções descartadas.

## Consequências

- Todo PR que muda arquitetura cria ou atualiza uma ADR.
- Agentes de IA que trabalharem no código ganham contexto apontando para `docs/`.
- Decisões revertidas ganham uma ADR nova; a antiga muda para "Substituída".
