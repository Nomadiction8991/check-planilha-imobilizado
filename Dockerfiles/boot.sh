#!/bin/sh
set -e

cd /var/www/html || exit 0

if [ -n "$DB_HOST" ] && [ -n "$DB_PORT" ] && [ -n "$DB_DATABASE" ] && [ -n "$DB_USERNAME" ] && command -v mysql >/dev/null 2>&1; then
    echo "Aguardando o banco em ${DB_HOST}:${DB_PORT}..."

    attempt=1
    while ! MYSQL_PWD="${DB_PASSWORD:-}" mysql \
        -h"$DB_HOST" \
        -P"$DB_PORT" \
        -u"$DB_USERNAME" \
        -e "SELECT 1" \
        "$DB_DATABASE" >/dev/null 2>&1; do
        if [ "$attempt" -ge 30 ]; then
            echo "Banco não ficou pronto a tempo."
            exit 1
        fi

        echo "Banco ainda indisponível, tentativa ${attempt}/30..."
        attempt=$((attempt + 1))
        sleep 2
    done
else
    echo "Variáveis do banco ausentes ou cliente MySQL indisponível, pulando espera."
fi

echo "Preparando diretórios de storage..."
if [ ! -d storage ]; then
    echo "Diretório storage ausente, criando..."
    mkdir -p storage
fi

mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    storage/logs \
    storage/app/private \
    storage/app/public \
    storage/importacao \
    storage/tmp

if id -u www-data >/dev/null 2>&1; then
    chown -R www-data:www-data storage 2>/dev/null || true
fi

chmod -R ug+rwX,o+rX storage 2>/dev/null || true
chmod -R 777 storage/framework storage/logs storage/importacao storage/tmp 2>/dev/null || true

if [ ! -w storage ]; then
    echo "storage não está gravável para o usuário atual ($(id -u):$(id -g))."
    echo "O volume precisa permitir escrita no host ou via SELinux para subir o container."
    exit 1
fi

if command -v composer >/dev/null 2>&1; then
    if [ ! -d vendor ] || [ ! -f vendor/composer/installed.json ] || [ composer.lock -nt vendor/composer/installed.json ]; then
        echo "Executando composer install..."
        composer install --no-interaction --prefer-dist --no-progress --optimize-autoloader
    else
        echo "Dependências já instaladas, pulando composer install."
    fi
else
    echo "Composer não encontrado, pulando composer install."
fi

if [ -f .env ] && grep -q '^APP_KEY=$' .env 2>/dev/null; then
    echo "APP_KEY ausente, gerando chave da aplicação..."
    php artisan key:generate --force --no-interaction
fi

if [ -f artisan ] && command -v php >/dev/null 2>&1; then
    echo "Arquivo artisan encontrado, executando migrations..."
    php artisan migrate --force
else
    echo "Artisan não encontrado, pulando migrations."
fi

if [ -x /usr/local/bin/docker-php-entrypoint ]; then
    exec docker-php-entrypoint "$@"
else
    exec "$@"
fi
