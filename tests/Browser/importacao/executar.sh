#!/usr/bin/env bash
set -Eeuo pipefail

raiz=$(cd "$(dirname "$0")/../../.." && pwd)
tmp=$(mktemp -d /tmp/opencode/importacao-e2e.XXXXXXXX)
nome="importacao-e2e-$(basename "$tmp")"
rede="$nome-rede"
mysql="$nome-mysql"
app="$nome-app"
limpar() {
    docker rm -fv "$app" "$mysql" >/dev/null 2>&1 || true
    docker network rm "$rede" >/dev/null 2>&1 || true
    rm -rf -- "$tmp"
}
trap limpar EXIT

: "${NODE_PATH:?Informe NODE_PATH com o diretório que contém o módulo playwright}"
mkdir -p "$tmp/app/storage/framework/"{cache/data,sessions,views} "$tmp/app/storage/logs" "$tmp/app/bootstrap/cache"
tar -C "$raiz" --exclude='./.git' --exclude='./.env*' --exclude='./vendor' --exclude='./node_modules' --exclude='./storage' --exclude='./bootstrap/cache' -cf - . | tar -C "$tmp/app" -xf -
printf '%s\n' 'APP_NAME=Teste' 'APP_ENV=testing' 'APP_KEY=base64:MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3ODlhYmNkZWY=' 'APP_DEBUG=true' 'APP_URL=http://localhost' 'DB_CONNECTION=mysql' 'DB_HOST=db' 'DB_PORT=3306' 'DB_DATABASE=teste' 'DB_NAME=teste' 'DB_USERNAME=teste' 'DB_USER=teste' 'DB_PASSWORD=senha-teste' 'DB_PASS=senha-teste' 'SESSION_DRIVER=file' 'CACHE_STORE=file' 'LOG_CHANNEL=stderr' > "$tmp/app/.env"
chmod -R a+rwX "$tmp/app/storage" "$tmp/app/bootstrap/cache"
docker network create "$rede" >/dev/null
docker run -d --name "$mysql" --network "$rede" --network-alias db --tmpfs /var/lib/mysql:rw -e MYSQL_ROOT_PASSWORD=raiz-teste -e MYSQL_DATABASE=teste -e MYSQL_USER=teste -e MYSQL_PASSWORD=senha-teste mysql:8.0 >/dev/null
for i in {1..90}; do
    if docker exec "$mysql" mysqladmin ping -h 127.0.0.1 -uroot -praiz-teste --silent >/dev/null 2>&1; then break; fi
    if (( i == 90 )); then docker logs "$mysql"; exit 1; fi
    sleep 1
done
docker exec -i "$mysql" mysql -uteste -psenha-teste teste < "$raiz/tests/Browser/importacao/schema.sql"
docker run -d --name "$app" --user "$(id -u):$(id -g)" --network "$rede" -p 127.0.0.1::8000 -v "$tmp/app:/app" -v "$raiz/vendor:/app/vendor:ro" -w /app check-planilha-php84:test php artisan serve --host=0.0.0.0 --port=8000 >/dev/null
porta=$(docker port "$app" 8000/tcp | cut -d: -f2)
for i in {1..30}; do
    if node -e 'fetch(process.argv[1]).then(() => process.exit(0)).catch(() => process.exit(1))' "http://127.0.0.1:$porta/login"; then break; fi
    if (( i == 30 )); then docker logs "$app"; exit 1; fi
    sleep 1
done
if ! BASE_URL="http://127.0.0.1:$porta" node "$raiz/tests/Browser/importacao/fluxo.cjs"; then
    if [[ "${INVESTIGAR_IMPORTAR_TUDO:-}" == 1 ]]; then
        docker exec "$mysql" mysql -N -uteste -psenha-teste teste -e "SELECT CONCAT(status, ':', linhas_processadas, ':', linhas_sucesso, ':', linhas_erro) FROM importacoes; SELECT COUNT(*) FROM produtos;" 2>/dev/null
    fi
    exit 1
fi
resultado=$(docker exec "$mysql" mysql -N -uteste -psenha-teste teste -e "SELECT CONCAT(status, ':', linhas_sucesso, ':', linhas_erro) FROM importacoes; SELECT GROUP_CONCAT(codigo ORDER BY codigo SEPARATOR '|') FROM produtos;" 2>/dev/null)
esperado=$'concluida:2:0\n99-9999 / 0001|99-9999 / 0002'
if [[ "$resultado" != "$esperado" ]]; then printf 'Resultado inesperado no banco: %s\n' "$resultado"; exit 1; fi
printf 'Banco isolado: importação concluída e dois produtos conferidos.\n'
