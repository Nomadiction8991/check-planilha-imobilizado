#!/bin/sh
set -e

cd /var/www/html || exit 0

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

# Copiar .env.example para .env se não existir
if [ ! -f .env ] && [ -f .env.example ]; then
    echo "Copiando .env.example para .env..."
    cp .env.example .env
fi

# Aguardar o banco de dados estar pronto
echo "Aguardando banco de dados..."
until php -r "try { new PDO('mysql:host=db;dbname=checkplanilha', 'checkplanilha', 'checkplanilha123'); echo 'OK'; } catch (Exception \$e) { exit(1); }"; do
    echo "Banco não está pronto, aguardando..."
    sleep 2
done
echo "Banco de dados pronto!"

# Executar migrations do Phinx se disponível
if command -v ./vendor/bin/phinx >/dev/null 2>&1; then
    echo "Executando migrations do Phinx..."
    ./vendor/bin/phinx migrate --environment=development --no-interaction
else
    echo "Phinx não encontrado, tentando instalar dependências..."
    if command -v composer >/dev/null 2>&1; then
        composer update --no-interaction --prefer-dist --no-progress --optimize-autoloader
        if command -v ./vendor/bin/phinx >/dev/null 2>&1; then
            echo "Executando migrations do Phinx..."
            ./vendor/bin/phinx migrate --environment=development --no-interaction
        else
            echo "Falha ao instalar Phinx."
        fi
    else
        echo "Composer não encontrado."
    fi
fi


if [ -x /usr/local/bin/docker-php-entrypoint ]; then
    exec docker-php-entrypoint "$@"
else
    exec "$@"
fi