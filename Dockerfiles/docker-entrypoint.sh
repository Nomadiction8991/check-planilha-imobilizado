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

# Garantir diretórios de storage com permissão de escrita
echo "Preparando diretórios de storage..."
mkdir -p storage/importacao storage/tmp storage/logs

# Ajustar dono/grupo quando possível (container normalmente sobe como root)
if id -u www-data >/dev/null 2>&1; then
    chown -R www-data:www-data storage 2>/dev/null || true
fi

# Garantir permissão de escrita para aplicação
chmod -R 775 storage 2>/dev/null || true

# Aguardar o banco de dados estar pronto (com melhor timeout e debug)
echo "Aguardando banco de dados..."
MAX_ATTEMPTS=60
ATTEMPT=1
until php -r "
    try {
        \$opts = [
            PDO::ATTR_TIMEOUT => 5,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ];
        \$pdo = new PDO('mysql:host=db;port=3306;dbname=checkplanilha', 'checkplanilha', 'checkplanilha123', \$opts);
        echo 'OK';
        exit(0);
    } catch (Throwable \$e) {
        echo 'DB Error: ' . \$e->getMessage();
        exit(1);
    }
" 2>&1; do
    ATTEMPT=$((ATTEMPT + 1))
    if [ $ATTEMPT -gt $MAX_ATTEMPTS ]; then
        echo "Falha ao conectar ao banco após $MAX_ATTEMPTS tentativas."
        exit 1
    fi
    echo "Banco não está pronto (tentativa $ATTEMPT/$MAX_ATTEMPTS), aguardando..."
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