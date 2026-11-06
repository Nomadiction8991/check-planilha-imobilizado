#!/usr/bin/env bash
# Compila o instalador do Windows (deploy/pacotes/windows/instalador.iss) num contêiner Linux, com o
# Inno Setup 6.7 sobre o Wine. Gera build/windows/CheckPlanilhaIgreja-<versão>-setup.exe.
#
# Pré-requisito: a pasta build/windows pronta (deploy/pacotes/windows/preparar-binarios-windows.sh).
# Compilar não é testar: o instalador ainda precisa ser instalado e testado numa máquina Windows.
#
# Uso: deploy/pacotes/windows/compilar/compilar-instalador.sh
set -euo pipefail

pasta="$(cd "$(dirname "$0")" && pwd)"
raiz="$(cd "$pasta/../../../.." && pwd)"
imagem="cp-innosetup:local"

if [[ ! -f "$raiz/build/windows/app/artisan" ]]; then
    echo "Erro: a pasta build/windows não está pronta. Rode antes deploy/pacotes/windows/preparar-binarios-windows.sh." >&2
    exit 1
fi

if ! docker image inspect "$imagem" >/dev/null 2>&1; then
    docker build -t "$imagem" "$pasta"
fi

# O contêiner roda com o usuário 1000 (cp), dono do prefixo do Wine. A pasta build/windows precisa ser
# gravável por esse usuário (no computador de uma pessoa só, normalmente é o usuário 1000).
# /tmp em memória: o Wine não pode reaproveitar diretórios temporários criados por outro usuário
# (por exemplo, os que sobram da montagem da imagem).
docker run --rm --user 1000:1000 --tmpfs /tmp:exec,mode=1777 \
    -e HOME=/tmp -e WINEPREFIX=/opt/wineprefix -e WINEDEBUG=-all \
    -v "$raiz:/work" -w /work "$imagem" \
    sh -c 'iscc="$(find /opt/wineprefix/drive_c -name ISCC.exe | head -n 1)"; xvfb-run -a wine "$iscc" Z:/work/deploy/pacotes/windows/instalador.iss'
