#!/usr/bin/env bash
# Prepara a pasta que o instalador do Windows (Inno Setup) empacota: PHP 8.4 (NTS, x64),
# MySQL 8.4 (x64), o php.ini do sistema, os certificados e o próprio sistema.
#
# Cada download é conferido antes de usar: o PHP pela soma SHA-256 publicada em
# windows.php.net, e o MySQL pelo MD5 publicado pela MySQL. Os .exe não podem rodar neste
# Linux. A execução real só acontece numa máquina Windows.
#
# Uso: deploy/pacotes/windows/preparar-binarios-windows.sh --vendor <pasta vendor sem dev> [--saida <pasta>]
#   padrão de saída: <raiz do projeto>/build/windows
set -euo pipefail

raiz="$(cd "$(dirname "$0")/../../.." && pwd)"
saida="$raiz/build/windows"
vendor_dir=""

erro() {
    echo "Erro: $*" >&2
    exit 1
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --vendor) vendor_dir="${2:-}"; shift 2 ;;
        --saida) saida="${2:-}"; shift 2 ;;
        *) erro "opção desconhecida: $1" ;;
    esac
done

[[ -f "$vendor_dir/autoload.php" ]] || erro "informe --vendor com as dependências de produção (falta autoload.php)"

for ferramenta in curl unzip sha256sum md5sum git; do
    command -v "$ferramenta" >/dev/null 2>&1 || erro "instale $ferramenta"
done

cache="${CP_CACHE:-$HOME/.cache/check-planilha/downloads}"
mkdir -p "$cache"

# PHP 8.4.27 NTS x64 (VS17), oficial. Soma publicada em windows.php.net/downloads/releases/sha256sum.txt
PHP_ZIP="php-8.4.27-1-nts-Win32-vs17-x64.zip"
PHP_URL="${CP_PHP_WIN_URL:-https://windows.php.net/downloads/releases/$PHP_ZIP}"
PHP_SHA256="${CP_PHP_WIN_SHA256:-dbae154a0a89791f36c3d48e68bae797878333e7d6cb5721e74617de59b63ab7}"

# MySQL 8.4.13 Windows x64 (zip). MD5 publicado pela MySQL junto do pacote.
MYSQL_PASTA="mysql-8.4.13-winx64"
MYSQL_URL="${CP_MYSQL_WIN_URL:-https://cdn.mysql.com/Downloads/MySQL-8.4/${MYSQL_PASTA}.zip}"
MYSQL_MD5="${CP_MYSQL_WIN_MD5:-7b94489d5524dea9b27097068bea25a8}"

# Baixa para o cache (se preciso) e confere a soma antes de devolver o caminho.
baixar_conferido() {
    local url="$1" algoritmo="$2" esperado="$3" arquivo caminho obtido
    arquivo="$(basename "$url")"
    caminho="$cache/$arquivo"

    if [[ ! -f "$caminho" ]]; then
        echo "Baixando $arquivo..."
        curl -fsSL --retry 2 -o "$caminho.parcial" "$url" || erro "não consegui baixar $url"
        mv "$caminho.parcial" "$caminho"
    fi

    if [[ "$algoritmo" == "sha256" ]]; then
        obtido="$(sha256sum "$caminho" | awk '{print $1}')"
    else
        obtido="$(md5sum "$caminho" | awk '{print $1}')"
    fi

    if [[ "$obtido" != "$esperado" ]]; then
        rm -f "$caminho"
        erro "$algoritmo de $arquivo não confere (esperado $esperado, obtido $obtido). O arquivo foi apagado."
    fi

    echo "$caminho"
}

php_zip="$(baixar_conferido "$PHP_URL" sha256 "$PHP_SHA256")"
mysql_zip="$(baixar_conferido "$MYSQL_URL" md5 "$MYSQL_MD5")"

trabalho="$(mktemp -d)"
trap 'rm -rf "$trabalho"' EXIT

rm -rf "$saida"
mkdir -p "$saida/php" "$saida/mysql" "$saida/app"

# 1) PHP: programa, extensões e php.ini próprio
unzip -q "$php_zip" -d "$saida/php"
[[ -f "$saida/php/php.exe" ]] || erro "o zip do PHP não trouxe php.exe"
[[ -f "$saida/php/php.ini-production" ]] || erro "o zip do PHP não trouxe php.ini-production"

ini="$saida/php/php.ini"
cp "$saida/php/php.ini-production" "$ini"

# Extensões que o sistema usa e que vêm como DLL no zip: ativa cada uma no php.ini.
for extensao in pdo_mysql mbstring openssl curl fileinfo; do
    [[ -f "$saida/php/ext/php_${extensao}.dll" ]] || erro "o zip do PHP não tem a extensão $extensao (php_${extensao}.dll)"
    sed -i "s/^;extension=${extensao}\$/extension=${extensao}/" "$ini"
    grep -qx "extension=${extensao}" "$ini" || echo "extension=${extensao}" >> "$ini"
done

# ctype, dom, xml, session, tokenizer e json vêm embutidos no PHP para Windows (não têm DLL).
# Não dá para confirmar isso sem rodar o php.exe: o teste numa máquina Windows precisa checar.

cat >> "$ini" <<'EOF'

; Ajustes do Check Planilha (valem por último, então sobrescrevem os padrões acima).
extension_dir = "ext"
date.timezone = "America/Sao_Paulo"
memory_limit = 512M
upload_max_filesize = 64M
post_max_size = 72M
EOF

# Certificados para o HTTPS da sincronização. O lançador aponta SSL_CERT_FILE para este arquivo.
[[ -f /etc/ssl/certs/ca-certificates.crt ]] || erro "instale o pacote ca-certificates para gerar o cacert.pem"
install -m 0644 /etc/ssl/certs/ca-certificates.crt "$saida/php/cacert.pem"

# 2) MySQL: só o servidor e os clientes que o sistema usa. O resto só deixaria o pacote maior.
unzip -q "$mysql_zip" -d "$trabalho/mysql"
origem="$trabalho/mysql/$MYSQL_PASTA"
[[ -f "$origem/bin/mysqld.exe" ]] || erro "o zip do MySQL não trouxe bin\\mysqld.exe"

cp -a "$origem/bin" "$origem/lib" "$origem/share" "$saida/mysql/"

for programa in "$saida/mysql/bin"/*.exe; do
    case "$(basename "$programa")" in
        mysqld.exe | mysql.exe | mysqladmin.exe | mysqldump.exe) ;;
        *) rm -f "$programa" ;;
    esac
done
find "$saida/mysql" -name '*.pdb' -delete
find "$saida/mysql/lib" -name '*.lib' -delete
rm -rf "$saida/mysql/lib/mecab"

# 3) Sistema: o mesmo código do Linux (último commit), com as dependências de produção.
# A pasta storage sai toda: ela guarda dados de execução, e um arquivo de teste versionado não pode ir para a instalação.
git -C "$raiz" archive HEAD -- . ':(exclude)tests' ':(exclude)docs' ':(exclude).github' ':(exclude)deploy' ':(exclude).env.testing' ':(exclude)storage' \
    | tar -x -C "$saida/app"
cp -a "$vendor_dir" "$saida/app/vendor"
mkdir -p "$saida/app/storage/app" "$saida/app/storage/framework/cache" \
    "$saida/app/storage/framework/sessions" "$saida/app/storage/framework/views" \
    "$saida/app/storage/logs" "$saida/app/storage/importacao" "$saida/app/bootstrap/cache"

# 4) Conferência do que foi montado (os .exe só rodam numa máquina Windows)
for arquivo in php/php.exe php/php.ini php/cacert.pem mysql/bin/mysqld.exe mysql/bin/mysql.exe app/artisan app/vendor/autoload.php; do
    [[ -e "$saida/$arquivo" ]] || erro "faltou $arquivo na pasta do Windows"
done

(cd "$saida" && sha256sum php/php.exe mysql/bin/mysqld.exe > SHA256SUMS)

echo "Pasta do Windows pronta em $saida"
echo "  PHP   : 8.4.27 (NTS x64), extensões conferidas pelos arquivos DLL"
echo "  MySQL : 8.4.13 (x64)"
echo "  Os .exe ainda não foram executados: falta o teste numa máquina Windows."
