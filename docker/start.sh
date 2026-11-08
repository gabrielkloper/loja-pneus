#!/bin/sh
# Prepara e sobe a loja dentro do container (ADR-0012).
set -e
cd /app

# 1. Arquivo .env e chave de criptografia (gerados uma vez por container).
[ -f .env ] || cp .env.example .env
grep -q '^APP_KEY=base64' .env || php artisan key:generate --force

# 2. Banco SQLite num volume (/data), para os dados sobreviverem a um rebuild.
#    Primeira vez: cria as tabelas e os dados de demonstração.
mkdir -p /data
if [ ! -s /data/database.sqlite ]; then
    touch /data/database.sqlite
    php artisan migrate --seed --force
else
    php artisan migrate --force
fi

# 3. Agendador em segundo plano: expira compras não pagas a cada minuto.
php artisan schedule:work > /dev/null 2>&1 &

# 4. Servidor web. "exec" faz o PHP virar o processo principal do container,
#    então "docker compose stop" o encerra corretamente.
echo "Loja no ar: http://localhost:8000"
# --no-reload permite usar PHP_CLI_SERVER_WORKERS (vários processos).
exec php artisan serve --host=0.0.0.0 --port=8000 --no-reload
