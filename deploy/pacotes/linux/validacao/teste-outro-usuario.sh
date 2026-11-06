#!/usr/bin/env bash
# Teste do segundo usuário: outro usuário do computador consegue ler ou alterar o banco do sistema?
#
# Roda num contêiner sem rede (--network none), com o .deb instalado por dpkg e o sistema ligado pelo
# lançador como o usuário cp. Depois tenta entrar no banco como outro usuário (intruso), pelo socket e
# pela porta local. O dono (cp) precisa continuar entrando pelo socket, senão um "bloqueado" não
# provaria nada (poderia ser só o banco parado). Antes do ataque, confere também que o sistema responde
# HTTP 200 na tela de login: um servidor que morreu na subida não pode parecer protegido.
#
# Resultado: PROTEGIDO (código 0) ou VULNERAVEL (código 1). Precisa da imagem cp-notebook-base:local,
# criada por ensaio-contenores.sh.
#
# Uso: deploy/pacotes/linux/validacao/teste-outro-usuario.sh [pasta com o .deb]
set -euo pipefail

pasta_validacao="$(cd "$(dirname "$0")" && pwd)"
raiz="$(cd "$pasta_validacao/../../../.." && pwd)"
pasta_pacotes="$(cd "${1:-$raiz/build/pacotes}" && pwd)"
imagem="cp-notebook-base:local"
contenor="cp-teste-seguranca"
deb_nome="$(basename "$(ls "$pasta_pacotes"/check-planilha-igreja_*_amd64.deb | head -n 1)")"
dados="/home/cp/.local/share/check-planilha"
mysql="/usr/lib/check-planilha/mysql/bin/mysql"
mysqladmin="/usr/lib/check-planilha/mysql/bin/mysqladmin"
php="/usr/lib/check-planilha/php/bin/php"

trap 'docker rm -f "$contenor" >/dev/null 2>&1 || true' EXIT

if ! docker image inspect "$imagem" >/dev/null 2>&1; then
    echo "Erro: a imagem $imagem não existe. Rode antes deploy/pacotes/linux/validacao/ensaio-contenores.sh." >&2
    exit 1
fi

docker rm -f "$contenor" >/dev/null 2>&1 || true
docker run -d --init --name "$contenor" --network none -v "$pasta_pacotes:/pacotes:ro" "$imagem" sleep infinity >/dev/null
docker exec -e SUDO_USER=cp "$contenor" dpkg -i "/pacotes/$deb_nome" >/dev/null 2>&1
echo "OK    pacote instalado por dpkg num contêiner sem rede"

docker exec -d -u cp -e HOME=/home/cp "$contenor" bash -c 'exec /usr/bin/check-planilha-igreja >"$HOME/lancador.log" 2>&1'

# Espera o banco aceitar o dono (cp) pelo socket. Se nunca aceitar, o teste para aqui.
pronto=0
for _ in $(seq 1 120); do
    if docker exec -u cp -e HOME=/home/cp "$contenor" "$mysqladmin" --socket="$dados/mysql.sock" -uroot ping >/dev/null 2>&1; then
        pronto=1
        break
    fi
    sleep 2
done
if [[ "$pronto" -ne 1 ]]; then
    echo "FALHA o banco não ficou pronto para o dono (cp). Fim do registro do lançador:"
    docker exec "$contenor" tail -n 30 /home/cp/lancador.log || true
    exit 1
fi
echo "OK    banco pronto e acessível pelo dono (cp) pelo socket"

dono="$(docker exec -u cp -e HOME=/home/cp "$contenor" "$mysql" --socket="$dados/mysql.sock" -uroot -N -e 'select current_user();')"
echo "OK    dono (cp) entra no banco pelo socket: $dono"

# O sistema precisa responder na tela de login. Sem isso, o teste não prova nada.
no_ar=0
for _ in $(seq 1 120); do
    if [[ "$(docker exec -u cp -e HOME=/home/cp "$contenor" "$php" -r '$h = @get_headers("http://127.0.0.1:8080/login"); echo $h ? (int) substr($h[0], 9, 3) : 0;' 2>/dev/null)" == "200" ]]; then
        no_ar=1
        break
    fi
    sleep 2
done
if [[ "$no_ar" -ne 1 ]]; then
    echo "FALHA o sistema não respondeu HTTP 200 na tela de login: o teste não conclui nada. Fim do registro do lançador:"
    docker exec "$contenor" tail -n 30 /home/cp/lancador.log || true
    exit 1
fi
echo "OK    sistema respondeu HTTP 200 na tela de login"

docker exec "$contenor" useradd -m intruso

# tentar <descrição> <comando...>: mostra o resultado; devolve 0 só se o acesso foi concedido.
tentar() {
    local descricao="$1" saida
    shift
    if saida="$("$@" 2>&1)"; then
        echo "ACESSO CONCEDIDO: $descricao -> $(tail -n 1 <<<"$saida")"
        return 0
    fi
    echo "negado: $descricao ($(tail -n 1 <<<"$saida" | cut -c1-140))"
    return 1
}

acessos=0
if tentar "outro usuário (intruso) pelo socket" \
    docker exec -u intruso -e HOME=/home/intruso "$contenor" "$mysql" --socket="$dados/mysql.sock" -uroot -N -e 'select current_user();'; then
    acessos=$((acessos + 1))
fi
if tentar "outro usuário (intruso) pela porta 127.0.0.1:3307, como root sem senha" \
    docker exec -u intruso -e HOME=/home/intruso "$contenor" "$mysql" -h127.0.0.1 -P3307 -uroot -N -e 'select current_user();'; then
    acessos=$((acessos + 1))
fi

echo "--- o que existe no contêiner (visto como root):"
if docker exec "$contenor" bash -c 'exec 3<>/dev/tcp/127.0.0.1/3307' 2>/dev/null; then
    echo "porta 127.0.0.1:3307 ABERTA"
else
    echo "porta 127.0.0.1:3307 fechada (nenhum servidor escuta por TCP)"
fi
docker exec "$contenor" stat -c 'pasta de dados: %a %U:%G' "$dados"
docker exec "$contenor" stat -c 'socket: %a %U:%G' "$dados/mysql.sock" 2>/dev/null || echo "socket: ausente"

echo
if (( acessos == 0 )); then
    echo "PROTEGIDO: nenhum outro usuário conseguiu entrar no banco (socket nem porta local)."
    exit 0
fi
echo "VULNERAVEL: $acessos acesso(s) de outro usuário foram concedidos."
exit 1
