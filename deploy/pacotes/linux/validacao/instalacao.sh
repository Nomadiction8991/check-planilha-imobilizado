#!/usr/bin/env bash
# Passo 1 do ensaio. Roda como root dentro de um Debian 12 limpo: instala o .deb com dpkg,
# confere o que o pacote faz (postinst, atalho, remoção e reinstalação) e depois chama o
# ensaio de ponta a ponta como usuário comum.
set -euo pipefail

pacote="$1"

passo() {
    printf '\n== %s\n' "$*"
}

# Roda o comando e mostra OK ou FALHA. Se falhar, o ensaio para.
afirmar() {
    local descricao="$1"
    shift
    if "$@" >/dev/null 2>&1; then
        echo "OK    $descricao"
    else
        echo "FALHA $descricao"
        exit 1
    fi
}

passo "usuário comum que vai rodar o sistema"
if ! id cp >/dev/null 2>&1; then
    useradd -m -u 1000 cp
fi
echo "usuário: $(id cp)"

passo "1. dpkg -i, como no sudo dpkg -i (SUDO_USER=cp)"
set +e
SUDO_USER=cp dpkg -i "$pacote" >/tmp/dpkg-1.txt 2>&1
codigo_dpkg=$?
set -e
cat /tmp/dpkg-1.txt
afirmar "dpkg recusa a configuração sem a dependência libnuma1 declarada" \
    bash -c "test $codigo_dpkg -ne 0 && grep -q libnuma1 /tmp/dpkg-1.txt"

passo "1b. apt-get -f resolve a dependência e termina a configuração (com SUDO_USER=cp)"
if ! apt-get update -qq >/dev/null 2>&1; then
    echo "FALHA apt-get update (sem acesso aos repositórios do Debian)"
    exit 1
fi
SUDO_USER=cp apt-get -f install -y -qq >/tmp/apt-1.txt 2>&1 || { tail -20 /tmp/apt-1.txt; echo "FALHA apt-get -f"; exit 1; }
dpkg -s check-planilha-igreja | grep -E '^(Package|Version|Status|Depends):'
afirmar "pacote instalado" dpkg -s check-planilha-igreja
afirmar "libnuma1 instalada pelo apt" dpkg -s libnuma1
afirmar "launcher em /usr/bin" test -x /usr/bin/check-planilha-igreja
afirmar "atalho do menu em /usr/share/applications" test -f /usr/share/applications/check-planilha-igreja.desktop
afirmar "PHP 8.4 do pacote" test -x /usr/lib/check-planilha/php/bin/php
afirmar "MySQL 8.4 do pacote" test -x /usr/lib/check-planilha/mysql/bin/mysqld
afirmar "certificados do pacote" test -f /usr/lib/check-planilha/php/cacert.pem
afirmar "descoberta de pacotes do Laravel feita na montagem" test -f /usr/lib/check-planilha/app/bootstrap/cache/packages.php

passo "2. postinst: as pastas de escrita do Laravel ficam com quem instalou"
stat -c '%A %U:%G %n' /usr/lib/check-planilha/app/storage /usr/lib/check-planilha/app/bootstrap/cache
afirmar "storage pertence ao usuário do sudo (cp)" test "$(stat -c %U /usr/lib/check-planilha/app/storage)" = cp
afirmar "bootstrap/cache pertence ao usuário do sudo (cp)" test "$(stat -c %U /usr/lib/check-planilha/app/bootstrap/cache)" = cp

passo "3. atalho do menu aponta para o lançador instalado e roda como usuário comum"
grep -E '^(Type|Name|Exec|Terminal)=' /usr/share/applications/check-planilha-igreja.desktop
afirmar "Exec aponta para /usr/bin/check-planilha-igreja" grep -qx 'Exec=/usr/bin/check-planilha-igreja' /usr/share/applications/check-planilha-igreja.desktop
saida_simulacao="$(runuser -u cp -- env HOME=/home/cp /usr/bin/check-planilha-igreja --simular)"
afirmar "lançador (modo simulação) sobe o servidor na porta 8080" grep -q -- '-S 0.0.0.0:8080' <<<"$saida_simulacao"

passo "4. reinstalação sem SUDO_USER (postinst pelo caminho de permissão ampla)"
afirmar "reinstalação concluída" bash -c "env -u SUDO_USER dpkg -i '$pacote' >/dev/null 2>&1"
stat -c '%A %U:%G %n' /usr/lib/check-planilha/app/storage
afirmar "storage com escrita para todos (chmod a+rwX)" test "$(stat -c %a /usr/lib/check-planilha/app/storage)" = 777

passo "5. remoção e instalação de novo para o ensaio"
afirmar "remoção deixa o sistema sem o launcher" bash -c "dpkg -r check-planilha-igreja >/dev/null 2>&1 && test ! -e /usr/bin/check-planilha-igreja"
afirmar "instalação de novo" bash -c "dpkg -i '$pacote' >/dev/null 2>&1"

passo "6. ensaio de ponta a ponta: um servidor e dois notebooks, como usuário comum"
runuser -u cp -- env HOME=/home/cp bash /validacao/ensaio-dois-notebooks.sh
