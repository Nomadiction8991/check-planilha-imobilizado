#!/usr/bin/env bash
# Prepara os binários que o pacote do Linux leva dentro: PHP 8.4 (estático) e MySQL 8.4.
#
# Baixa os arquivos oficiais, confere cada um com a soma SHA-256 fixada neste script e só
# depois monta a pasta usada pelo build-deb.sh. Se a soma não conferir, o arquivo é apagado
# do cache e nada é montado.
#
# Uso: deploy/pacotes/linux/preparar-binarios.sh [pasta-de-destino]
#   padrão de destino: <raiz do projeto>/build/binarios
#
# Requisitos no computador que monta o pacote: curl, tar, xz, sha256sum, ldconfig e as
# bibliotecas libaio e libnuma (pacotes libaio1t64 e libnuma1 no Ubuntu e no Debian).
set -euo pipefail

raiz="$(cd "$(dirname "$0")/../../.." && pwd)"
destino="${1:-$raiz/build/binarios}"
cache="${CP_CACHE:-$HOME/.cache/check-planilha/downloads}"

# PHP 8.4 estático, pacote "common" do static-php-cli. As variáveis CP_* existem para os testes.
PHP_URL="${CP_PHP_URL:-https://dl.static-php.dev/static-php-cli/common/php-8.4.23-cli-linux-x86_64.tar.gz}"
PHP_SHA256="${CP_PHP_SHA256:-1aeed5bc7967977ca5b1da7163acd91bf9ba3ac56037045d4e91ee2ff2712bb7}"

# MySQL 8.4.13, pacote genérico para Linux (glibc 2.28 ou mais novo).
# A MySQL publica o MD5 e a assinatura GPG (.asc) deste pacote. A soma SHA-256 foi conferida
# contra o MD5 publicado e a assinatura GPG da chave oficial da MySQL.
MYSQL_NOME="mysql-8.4.13-linux-glibc2.28-x86_64"
MYSQL_URL="${CP_MYSQL_URL:-https://cdn.mysql.com/Downloads/MySQL-8.4/${MYSQL_NOME}.tar.xz}"
MYSQL_SHA256="${CP_MYSQL_SHA256:-e32f82598aef1384d2bbb8e57a1c684f9b7734409ae8028b363fb913e56b8e92}"

# Extensões do PHP que o sistema usa. Sem alguma delas, o pacote não sobe.
EXTENSOES_OBRIGATORIAS="pdo_mysql mbstring openssl tokenizer ctype fileinfo curl dom xml session json"

erro() {
    echo "Erro: $*" >&2
    exit 1
}

# Baixa o arquivo para o cache, se ainda não estiver lá, e confere a soma antes de usar.
obter() {
    local url="$1" soma="$2" arquivo caminho obtida
    arquivo="$(basename "$url")"
    caminho="$cache/$arquivo"
    mkdir -p "$cache"

    if [[ ! -f "$caminho" ]]; then
        echo "Baixando $arquivo..."
        curl -fsSL --retry 2 -o "$caminho.parcial" "$url" || erro "não consegui baixar $url"
        mv "$caminho.parcial" "$caminho"
    fi

    obtida="$(sha256sum "$caminho" | awk '{print $1}')"

    if [[ "$obtida" != "$soma" ]]; then
        rm -f "$caminho"
        erro "SHA-256 de $arquivo não confere (esperado $soma, obtido $obtida). O arquivo foi apagado do cache e nada foi montado."
    fi

    echo "$caminho"
}

# Caminho de uma biblioteca do sistema, pelo nome que o programa pede.
biblioteca_do_sistema() {
    local nome="$1" caminho
    caminho="$(ldconfig -p | awk -v n="$nome" '$1 == n {print $NF; exit}')"
    [[ -n "$caminho" ]] || erro "a biblioteca $nome não está instalada (instale o pacote correspondente)"
    echo "$caminho"
}

trabalho="$(mktemp -d)"
trap 'rm -rf "$trabalho"' EXIT

# 1) PHP
php_arquivo="$(obter "$PHP_URL" "$PHP_SHA256")"
tar -xzf "$php_arquivo" -C "$trabalho"
[[ -f "$trabalho/php" ]] || erro "o arquivo do PHP não trouxe o programa php"

rm -rf "$destino/php"
mkdir -p "$destino/php/bin"
install -m 0755 "$trabalho/php" "$destino/php/bin/php"

# Certificados para o HTTPS da sincronização. O PHP estático não acha os do sistema sozinho.
certificados="/etc/ssl/certs/ca-certificates.crt"
[[ -f "$certificados" ]] || erro "não encontrei $certificados (instale o pacote ca-certificates)"
install -m 0644 "$certificados" "$destino/php/cacert.pem"

# 2) MySQL
mysql_arquivo="$(obter "$MYSQL_URL" "$MYSQL_SHA256")"
tar -xJf "$mysql_arquivo" -C "$trabalho"
[[ -d "$trabalho/$MYSQL_NOME" ]] || erro "o arquivo do MySQL não trouxe a pasta $MYSQL_NOME"

rm -rf "$destino/mysql"
mkdir -p "$destino/mysql"
cp -a "$trabalho/$MYSQL_NOME/bin" "$trabalho/$MYSQL_NOME/lib" "$trabalho/$MYSQL_NOME/share" "$destino/mysql/"

# Só o necessário para o servidor. O restante (depuração, componentes extras, clientes de
# replicação) só deixaria o pacote maior. Fica o plugin auth_socket, que o root usa no primeiro acesso.
for programa in "$destino"/mysql/bin/*; do
    case "$(basename "$programa")" in
        mysqld | mysql | mysqladmin | mysqldump) ;;
        *) rm -f "$programa" ;;
    esac
done
rm -rf "$destino/mysql/lib/mecab" "$destino/mysql/lib/libmysqlclient.a"
find "$destino/mysql/lib/plugin" -type f ! -name auth_socket.so -delete

# Os símbolos de depuração ocupam quase metade do mysqld e não são usados em produção.
if command -v strip >/dev/null 2>&1; then
    strip --strip-debug "$destino/mysql/bin/mysqld" "$destino/mysql/bin/mysql" \
        "$destino/mysql/bin/mysqladmin" "$destino/mysql/bin/mysqldump"
fi

# O mysqld pede libaio.so.1 e libnuma.so.1. Versões novas do Ubuntu e do Debian trazem
# libaio.so.1t64, então a biblioteca instalada entra com o nome que o mysqld espera.
cp -L "$(biblioteca_do_sistema libaio.so.1t64)" "$destino/mysql/lib/private/libaio.so.1"
cp -L "$(biblioteca_do_sistema libnuma.so.1)" "$destino/mysql/lib/private/libnuma.so.1"

# 3) Conferência: os dois programas precisam rodar aqui, antes de entrar no pacote.
"$destino/mysql/bin/mysqld" --version >/dev/null || erro "o mysqld não roda neste computador"
"$destino/mysql/bin/mysql" --version >/dev/null || erro "o cliente mysql não roda neste computador"
"$destino/php/bin/php" -v >/dev/null || erro "o php não roda neste computador"

for programa in "$destino/mysql/bin/mysqld" "$destino/mysql/bin/mysql"; do
    if ldd "$programa" | grep -q 'not found'; then
        erro "faltam bibliotecas para $(basename "$programa"): $(ldd "$programa" | grep 'not found' | awk '{print $1}' | tr '\n' ' ')"
    fi
done

modulos="$("$destino/php/bin/php" -m)"
for extensao in $EXTENSOES_OBRIGATORIAS; do
    grep -qix "$extensao" <<<"$modulos" || erro "o PHP estático não tem a extensão $extensao"
done

sha256sum "$destino/php/bin/php" "$destino/mysql/bin/mysqld" > "$destino/SHA256SUMS"

echo "Binários prontos em $destino"
echo "  PHP   : $("$destino/php/bin/php" -r 'echo PHP_VERSION;')"
echo "  MySQL : $("$destino/mysql/bin/mysqld" --version | awk '{print $3}')"
