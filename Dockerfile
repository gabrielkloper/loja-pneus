# Imagem de DESENVOLVIMENTO da loja (ADR-0012).
#
# Três etapas ("multi-stage"): cada uma usa a imagem mais adequada para a
# sua tarefa, e só o resultado final vai para a imagem que roda.
#
#   1. vendor  → instala os pacotes PHP com o Composer
#   2. assets  → compila CSS e JS com o Node (precisa do vendor por causa do Flux)
#   3. app     → PHP 8.4 com as extensões que o Laravel usa, rodando o servidor

# ---------- 1. Pacotes PHP ----------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock* ./
# --no-scripts: os scripts do Laravel precisam do código, que ainda não foi copiado.
RUN composer install --no-interaction --prefer-dist --no-scripts --no-autoloader --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --no-scripts

# ---------- 2. CSS e JavaScript ----------
FROM node:22 AS assets
WORKDIR /app
COPY --from=vendor /app /app
RUN npm install --no-audit --no-fund && npm run build

# ---------- 3. Aplicação ----------
FROM php:8.4-cli AS app

# intl e zip não vêm na imagem oficial; pdo_sqlite e mbstring já vêm.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libicu-dev libzip-dev unzip \
    && docker-php-ext-install intl zip pcntl \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY --from=assets /app /app
COPY docker/start.sh /usr/local/bin/start-loja
RUN chmod +x /usr/local/bin/start-loja \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

EXPOSE 8000

# CMD (e não ENTRYPOINT) para que "docker compose run app php artisan test"
# rode só o comando pedido, sem subir o servidor.
CMD ["start-loja"]
