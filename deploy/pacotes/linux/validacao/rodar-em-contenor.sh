#!/usr/bin/env bash
# Valida o .deb num Debian 12 limpo, em contêiner descartável:
#   1) instala com dpkg e confere o postinst e o atalho do menu;
#   2) roda o ensaio de ponta a ponta: um servidor e dois notebooks, cada um com a sua instalação.
#
# Uso: deploy/pacotes/linux/validacao/rodar-em-contenor.sh [caminho do .deb]
set -euo pipefail

pasta_validacao="$(cd "$(dirname "$0")" && pwd)"
raiz="$(cd "$pasta_validacao/../../../.." && pwd)"
deb="${1:-$raiz/build/pacotes/check-planilha-igreja_0.1.0_amd64.deb}"

if [[ ! -f "$deb" ]]; then
    echo "Erro: não encontrei o pacote $deb" >&2
    exit 1
fi

docker run --rm \
    -v "$(dirname "$deb"):/pacotes:ro" \
    -v "$pasta_validacao:/validacao:ro" \
    debian:bookworm-slim \
    bash /validacao/instalacao.sh "/pacotes/$(basename "$deb")"
