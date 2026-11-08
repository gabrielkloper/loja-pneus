# ADR-0006: Endereços das lojas e retirada presencial

- **Status:** Aceita
- **Data:** 2026-11-08

## Contexto

Pneu é pesado e muitas vezes o comprador prefere buscar (e até montar) na loja. Lojas também podem ter mais de um endereço.

## Decisão

- Cada loja tem um ou mais endereços (`seller_locations`), com coordenadas opcionais, horário de atendimento e a opção "aceita retirada".
- Cada anúncio diz **em qual endereço** o pneu está.
- No carrinho, o comprador escolhe, por loja, **entrega** (se a loja entrega) ou **retirada** (se o endereço aceita).
- Retirada exige que todos os itens daquela loja estejam no mesmo endereço; se não estiverem, o comprador escolhe entrega ou faz compras separadas.
- A vitrine filtra por estado, cidade e "só lojas com retirada". Links "Ver no mapa" abrem o Google Maps.

## Alternativas consideradas

- **Busca por distância (raio em km) já agora:** exige geocodificação de CEP e cálculo geográfico; fica para um incremento com PostGIS ou API de CEP.
- **Endereço único por loja:** mais simples, mas força lojas com filiais a criar contas separadas.

## Consequências

- A busca "perto de mim" é o próximo passo natural: as coordenadas já são guardadas.
- Preenchimento automático de endereço por CEP (ViaCEP) melhora o cadastro e fica para depois.
