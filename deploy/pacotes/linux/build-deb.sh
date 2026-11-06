#!/usr/bin/env bash
# Gera o pacote .deb do modo igreja para Linux (Ubuntu e Debian).
#
# Uso:
#   deploy/pacotes/linux/build-deb.sh --versao 1.0.0 \
#     --php <pasta com bin/php> \
#     --mysql <pasta com bin/mysqld e bin/mysql> \
#     --vendor <pasta vendor, sem pacotes de desenvolvimento> \
#     [--saida <pasta de destino>]
#
# O código sai do último commit (git archive HEAD), não da pasta de trabalho. Não entram
# testes, documentação, deploy nem arquivos .env. O PHP e o MySQL precisam ser preparados
# antes: este script só monta o pacote com eles dentro.
set -euo pipefail

raiz="$(cd "$(dirname "$0")/../../.." && pwd)"
pasta_deploy="$(cd "$(dirname "$0")" && pwd)"

versao=""
php_dir=""
mysql_dir=""
vendor_dir=""
saida="$raiz/build"

erro() {
    echo "Erro: $*" >&2
    exit 1
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --versao) versao="${2:-}"; shift 2 ;;
        --php) php_dir="${2:-}"; shift 2 ;;
        --mysql) mysql_dir="${2:-}"; shift 2 ;;
        --vendor) vendor_dir="${2:-}"; shift 2 ;;
        --saida) saida="${2:-}"; shift 2 ;;
        *) erro "opção desconhecida: $1" ;;
    esac
done

[[ "$versao" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] || erro "use --versao no formato 1.2.3"
[[ -x "$php_dir/bin/php" ]] || erro "não encontrei o PHP em $php_dir/bin/php"
[[ -x "$mysql_dir/bin/mysqld" ]] || erro "não encontrei o MySQL em $mysql_dir/bin/mysqld"
[[ -f "$vendor_dir/autoload.php" ]] || erro "não encontrei as dependências em $vendor_dir (falta autoload.php)"
command -v dpkg-deb >/dev/null 2>&1 || erro "instale o dpkg-deb (pacote dpkg)"

pacote="check-planilha-igreja"
trabalho="$(mktemp -d)"
trap 'rm -rf "$trabalho"' EXIT

raiz_pacote="$trabalho/$pacote"
instalacao="$raiz_pacote/usr/lib/check-planilha"

mkdir -p "$raiz_pacote/DEBIAN" "$instalacao/app" "$instalacao/php" "$instalacao/mysql" \
    "$raiz_pacote/usr/bin" "$raiz_pacote/usr/share/applications" "$saida"

# Código do sistema a partir do último commit, sem os arquivos de desenvolvimento.
git -C "$raiz" archive HEAD -- . ':(exclude)tests' ':(exclude)docs' ':(exclude).github' ':(exclude)deploy' ':(exclude).env.testing' \
    | tar -x -C "$instalacao/app"

cp -a "$vendor_dir" "$instalacao/app/vendor"

# Pastas que o Laravel escreve. O pacote cria a estrutura; o postinst libera a escrita.
mkdir -p "$instalacao/app/storage/app" "$instalacao/app/storage/framework/cache" \
    "$instalacao/app/storage/framework/sessions" "$instalacao/app/storage/framework/views" \
    "$instalacao/app/storage/logs" "$instalacao/app/bootstrap/cache"

cp -a "$php_dir/." "$instalacao/php/"
cp -a "$mysql_dir/." "$instalacao/mysql/"

install -m 0755 "$pasta_deploy/lancador.sh" "$raiz_pacote/usr/bin/check-planilha-igreja"
install -m 0644 "$pasta_deploy/check-planilha-igreja.desktop" \
    "$raiz_pacote/usr/share/applications/check-planilha-igreja.desktop"

# Conferência do que foi montado: o pacote só sai se o PHP, o MySQL e o sistema rodarem.
php_pacote="$instalacao/php/bin/php"
mysqld_pacote="$instalacao/mysql/bin/mysqld"
"$php_pacote" -v >/dev/null || erro "o PHP do pacote não roda"
"$mysqld_pacote" --version >/dev/null || erro "o MySQL do pacote não roda"
[[ -f "$instalacao/php/cacert.pem" ]] || erro "faltam os certificados (php/cacert.pem)"

modulos="$("$php_pacote" -m)"
for extensao in pdo_mysql mbstring openssl tokenizer ctype fileinfo curl dom xml session json; do
    grep -qix "$extensao" <<<"$modulos" || erro "o PHP do pacote não tem a extensão $extensao"
done

# Descobre os pacotes do Laravel com o PHP do próprio pacote. Se isso falhar, o sistema não sobe.
"$php_pacote" "$instalacao/app/artisan" package:discover --ansi >/dev/null \
    || erro "o sistema não sobe com o PHP do pacote"
"$php_pacote" "$instalacao/app/artisan" --version >/dev/null \
    || erro "o artisan não roda com o PHP do pacote"

cat > "$raiz_pacote/DEBIAN/postinst" <<'EOF'
#!/bin/sh
set -e

# O sistema roda como o usuário do computador, então as pastas de escrita do Laravel
# precisam ser dele. Sem usuário identificado, libera a escrita para todos.
pasta=/usr/lib/check-planilha/app
usuario="${SUDO_USER:-}"

if [ -n "$usuario" ]; then
    chown -R "$usuario" "$pasta/storage" "$pasta/bootstrap/cache"
else
    chmod -R a+rwX "$pasta/storage" "$pasta/bootstrap/cache"
fi

exit 0
EOF
chmod 0755 "$raiz_pacote/DEBIAN/postinst"

tamanho_kb="$(du -sk "$raiz_pacote" | cut -f1)"
cat > "$raiz_pacote/DEBIAN/control" <<EOF
Package: $pacote
Version: $versao
Section: office
Priority: optional
Architecture: amd64
Installed-Size: $tamanho_kb
Depends: libc6 (>= 2.28), libstdc++6
Maintainer: Ello Tecnologia
Description: Check Planilha para o notebook da igreja (modo offline)
 Cópia local do sistema, com banco de dados e PHP inclusos. Funciona sem
 internet e sincroniza com o servidor quando houver conexão.
EOF

destino="$saida/${pacote}_${versao}_amd64.deb"
dpkg-deb --root-owner-group --build "$raiz_pacote" "$destino"
echo "Pacote criado: $destino"
