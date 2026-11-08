# ADR-0012: Ambiente local com Docker

- **Status:** Aceita
- **Data:** 2026-11-08

## Contexto

Rodar a loja exige PHP 8.3+ com várias extensões, Composer e Node. No Windows, instalar e manter isso tudo à mão é trabalhoso e varia de máquina para máquina. O autor já usa o Docker Desktop.

## Decisão

O projeto traz uma configuração Docker de **desenvolvimento**:

| Arquivo | Papel |
| --- | --- |
| `Dockerfile` | Três etapas: Composer instala o PHP, Node compila CSS/JS, e a imagem final roda PHP 8.4 |
| `docker/start.sh` | Cria `.env` e chave, migra e popula o banco na primeira vez, sobe o agendador e o servidor |
| `compose.yaml` | Porta 8000, variáveis de ambiente e um volume para o banco SQLite |
| `.dockerignore` | Evita copiar `vendor`, `node_modules`, `.env` e o banco local para a imagem |

Uso:

```bash
docker compose up -d --build   # constrói e sobe (a primeira vez demora alguns minutos)
docker compose logs -f         # acompanha os logs (inclui links de e-mail)
docker compose down            # para (o banco continua salvo no volume)
docker compose down -v         # para e apaga o banco (volta aos dados de demonstração)
```

O CI tem um job `docker` que constrói a imagem, roda os testes dentro dela e sobe o compose fazendo requisições reais, então a configuração não quebra sem ninguém perceber.

## Alternativas consideradas

- **Laravel Sail:** oficial e completo, mas traz MySQL, Redis e outros serviços que ainda não usamos, e o script `sail` pede WSL no Windows.
- **PHP instalado direto no Windows:** funciona, mas cada máquina fica diferente.

## Consequências

- Rodar a loja exige só Docker Desktop e git.
- A imagem é para desenvolvimento (servidor embutido do PHP, `APP_DEBUG` ligado). A imagem de produção (Nginx/FrankenPHP, PostgreSQL) será outra ADR.
- Alterações no código exigem `docker compose up -d --build` para aparecer. Um modo com o código montado em volume, para editar e ver na hora, pode vir depois.
