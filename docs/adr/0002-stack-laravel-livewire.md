# ADR-0002: Stack — Laravel 13, Livewire 4, Flux e PostgreSQL

- **Status:** Aceita
- **Data:** 2026-11-08

## Contexto

Precisamos de um framework maduro, com autenticação, filas, agendador e um caminho oficial para OAuth e servidores MCP (incremento de agentes). O autor já conhece Laravel e quer reforçar fundamentos, então menos JavaScript no início é uma vantagem.

## Decisão

| Peça | Escolha | Por quê |
| --- | --- | --- |
| Framework | Laravel 13 (PHP 8.3+) | Lançado em março de 2026, com correções de segurança até 2028 |
| Ponto de partida | Starter kit oficial de Livewire | Login, cadastro, 2FA e passkeys prontos (Laravel Fortify) |
| Interface | Blade + Livewire 4 + Flux (gratuito) | Telas reativas escritas em PHP; Flux dá componentes prontos |
| Banco em desenvolvimento | SQLite | Zero instalação: `composer setup` e pronto |
| Banco em produção | PostgreSQL | Travas de linha confiáveis (`SELECT … FOR UPDATE`) e tipos ricos |
| Testes | PHPUnit (o que o starter kit traz) | Menos dependências; os testes leem como especificação |
| Qualidade | Pint (estilo) e Larastan nível 7 (tipos) | Rodam no CI a cada push |

## Alternativas consideradas

- **Inertia + Vue/React:** ótima opção, mas exige manter um front-end JavaScript separado. Fica para quando houver necessidade (ex.: app móvel).
- **MySQL:** funcionaria; o Postgres foi escolhido pelas travas previsíveis e por recursos futuros (busca geográfica com PostGIS).
- **Pest em vez de PHPUnit:** mais enxuto, mas é mais uma dependência. Pode ser adotado depois sem reescrever testes.

## Consequências

- Todo o código de banco precisa funcionar em SQLite (testes) e PostgreSQL (produção). Por isso evitamos SQL específico de um banco; onde é necessário (busca sem diferenciar maiúsculas), usamos `LOWER(...) LIKE ?`, que funciona nos dois.
- Componentes do Flux Pro (pagos) não são usados.
