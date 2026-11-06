#!/usr/bin/env bash
# Fase 7 em contêineres separados: um servidor e dois notebooks, cada um num contêiner Debian 12
# com o .deb instalado por dpkg. Os três ficam numa rede Docker interna, sem acesso à internet.
# O roteiro é o mesmo do ensaio-dois-notebooks.sh, que roda tudo num único computador.
# Os registros da execução ficam em build/validacao-contenores (pasta ignorada pelo Git).
#
# Uso: deploy/pacotes/linux/validacao/ensaio-contenores.sh [pasta com o .deb]
set -euo pipefail

pasta_validacao="$(cd "$(dirname "$0")" && pwd)"
raiz="$(cd "$pasta_validacao/../../../.." && pwd)"
pasta_pacotes="$(cd "${1:-$raiz/build/pacotes}" && pwd)"
saida="$raiz/build/validacao-contenores"
imagem="cp-notebook-base:local"
rede="cp-ensaio-rede"
servidor="cp-servidor"
notebook_a="cp-notebook-a"
notebook_b="cp-notebook-b"
todos=("$servidor" "$notebook_a" "$notebook_b")
dados="/home/cp/.local/share/check-planilha"
app="/usr/lib/check-planilha/app"
php="/usr/lib/check-planilha/php/bin/php"

deb_nome="$(basename "$(ls "$pasta_pacotes"/check-planilha-igreja_*_amd64.deb | head -n 1)")"
contexto="$(mktemp -d)"

limpar() {
    docker rm -f "${todos[@]}" >/dev/null 2>&1 || true
    docker network rm "$rede" >/dev/null 2>&1 || true
    rm -rf "$contexto"
}
trap limpar EXIT

passo() { printf '\n== %s\n' "$*"; }

# afirmar <descrição> <comando...>: OK se o comando der certo; senão FALHA e para o ensaio.
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

# afirmar_igual <descrição> <esperado> <obtido>: compara dois textos; se forem diferentes, para o ensaio.
afirmar_igual() {
    local descricao="$1" esperado="$2" obtido="$3"
    if [[ "$esperado" == "$obtido" ]]; then
        echo "OK    $descricao"
    else
        echo "FALHA $descricao"
        echo "      esperado: $esperado"
        echo "      obtido:   $obtido"
        exit 1
    fi
}

# Roda um comando como o usuário cp dentro do contêiner. Sem shell: os argumentos chegam intactos.
no() {
    local contenor="$1"
    shift
    docker exec -u cp -e HOME=/home/cp "$contenor" "$@"
}

# Configuração inicial de um contêiner (o mesmo comando que o lançador usaria na primeira vez).
configurar() {
    local contenor="$1" ativo="$2"
    no "$contenor" bash -c 'mkdir -p /home/cp/.local/share/check-planilha && cd /usr/lib/check-planilha/app && /usr/lib/check-planilha/php/bin/php artisan modo-igreja:configurar --arquivo=/home/cp/.local/share/check-planilha/modo-igreja.env >/dev/null && sed -i "s/^MODO_IGREJA_ATIVO=.*/MODO_IGREJA_ATIVO=$1/" /home/cp/.local/share/check-planilha/modo-igreja.env' bash "$ativo"
}

# Liga o sistema do contêiner como o atalho do menu faria, em segundo plano.
lancador() {
    docker exec -d -u cp -e HOME=/home/cp "$1" bash -c 'exec /usr/bin/check-planilha-igreja >"$HOME/lancador.log" 2>&1'
}

# Espera a tela de login responder 200 dentro do contêiner.
esperar_sistema() {
    local contenor="$1" tentativa
    for tentativa in $(seq 1 120); do
        if [[ "$(no "$contenor" "$php" -r '$h = @get_headers("http://127.0.0.1:8080/login"); echo $h ? (int) substr($h[0], 9, 3) : 0;' 2>/dev/null)" == "200" ]]; then
            return 0
        fi
        sleep 2
    done
    return 1
}

# Roda um comando do artisan com a configuração do contêiner.
artisan_de() {
    local contenor="$1"
    shift
    no "$contenor" bash -c 'set -a; . /home/cp/.local/share/check-planilha/modo-igreja.env; set +a; cd /usr/lib/check-planilha/app && exec /usr/lib/check-planilha/php/bin/php artisan "$@"' artisan "$@"
}

# Consulta SQL no banco do contêiner (colunas separadas por tab).
sql_de() {
    local contenor="$1" sql="$2"
    no "$contenor" bash -c 'exec /usr/lib/check-planilha/mysql/bin/mysql --socket=/home/cp/.local/share/check-planilha/mysql.sock -uroot -N -B --default-character-set=utf8mb4 check_planilha -e "$1"' sql "$sql"
}

# Grava um arquivo dentro do contêiner, criando as pastas que faltarem.
escrever_em() {
    local contenor="$1" caminho="$2" texto="$3"
    printf '%s' "$texto" | docker exec -i -u cp -e HOME=/home/cp "$contenor" bash -c 'mkdir -p "$(dirname "$1")" && cat >"$1"' escrita "$caminho"
}

# Acrescenta texto ao fim de um arquivo dentro do contêiner.
anexar_em() {
    local contenor="$1" caminho="$2" texto="$3"
    printf '%s' "$texto" | docker exec -i -u cp -e HOME=/home/cp "$contenor" bash -c 'cat >>"$1"' escrita "$caminho"
}

# SHA-256 de um arquivo dentro do contêiner.
sha_de() {
    no "$1" sha256sum "$2" | awk '{print $1}'
}

# alcancavel <de> <para> <porta>: o contêiner "de" consegue abrir uma conexão TCP com "para:porta"?
alcancavel() {
    no "$1" timeout 5 bash -c 'exec 3<>/dev/tcp/$1/$2' bash "$2" "$3" >/dev/null 2>&1
}

# Sincroniza um notebook. Se a sincronização agendada estiver rodando, espera e tenta de novo.
sincroniza() {
    local contenor="$1" saida_sync tentativa
    for tentativa in $(seq 1 60); do
        if ! saida_sync="$(artisan_de "$contenor" sincronizacao:executar 2>&1)"; then
            printf '%s\n' "$saida_sync" >&2
            echo "FALHA  $contenor: a sincronização terminou com erro" >&2
            return 1
        fi
        if ! grep -q 'já está rodando' <<<"$saida_sync"; then
            printf '%s\n' "$saida_sync"
            return 0
        fi
        sleep 5
    done
    echo "FALHA  $contenor: a sincronização ficou presa em outra execução" >&2
    return 1
}

rm -rf "$saida"
mkdir -p "$saida"

echo "== preparo: imagem base Debian 12 com as bibliotecas que o .deb declara"
cat >"$contexto/Dockerfile" <<'DOCKERFILE'
FROM debian:bookworm-slim
# Dependências declaradas pelo .deb. O próprio .deb entra depois, com dpkg -i, em cada contêiner.
RUN apt-get update -qq \
 && apt-get install -y -qq --no-install-recommends libnuma1 libncurses6 \
 && rm -rf /var/lib/apt/lists/*
RUN useradd -m -u 1000 cp
DOCKERFILE
docker build -q -t "$imagem" "$contexto" >"$saida/build-imagem.txt"
echo "OK    imagem base $imagem criada"

docker network rm "$rede" >/dev/null 2>&1 || true
docker network create --internal "$rede" >/dev/null
for contenor in "${todos[@]}"; do
    docker run -d --init --name "$contenor" --hostname "$contenor" --network "$rede" \
        -v "$pasta_pacotes:/pacotes:ro" "$imagem" sleep infinity >/dev/null
done

passo "1. três contêineres Debian separados, cada um com o .deb instalado por dpkg"
afirmar_igual "rede Docker interna (sem rota para a internet)" "true" \
    "$(docker network inspect "$rede" --format '{{.Internal}}')"
for contenor in "${todos[@]}"; do
    if docker exec -e SUDO_USER=cp "$contenor" dpkg -i "/pacotes/$deb_nome" >"$saida/dpkg-$contenor.txt" 2>&1; then
        echo "OK    $contenor: dpkg -i de $deb_nome concluído"
    else
        cat "$saida/dpkg-$contenor.txt"
        echo "FALHA $contenor: dpkg -i"
        exit 1
    fi
    estado="$(docker exec "$contenor" dpkg -s check-planilha-igreja)"
    afirmar_igual "$contenor: pacote com status 'install ok installed'" "sim" \
        "$(grep -q 'Status: install ok installed' <<<"$estado" && echo sim || echo nao)"
done
for contenor in "${todos[@]}"; do
    if no "$contenor" timeout 5 bash -c 'exec 3<>/dev/tcp/1.1.1.1/80' >/dev/null 2>&1; then
        echo "FALHA $contenor alcança a internet"
        exit 1
    fi
    if no "$contenor" getent hosts example.com >/dev/null 2>&1; then
        echo "FALHA $contenor resolve nomes externos"
        exit 1
    fi
    echo "OK    $contenor não alcança a internet (1.1.1.1:80 inalcançável e example.com sem resolução)"
done
no "$notebook_a" touch /home/cp/so-do-notebook-a
if no "$notebook_b" test -e /home/cp/so-do-notebook-a; then
    echo "FALHA arquivo criado no notebook A aparece no notebook B"
    exit 1
fi
echo "OK    arquivo do notebook A não aparece no notebook B (sistemas de arquivos separados)"

configurar "$servidor" false
configurar "$notebook_a" true
configurar "$notebook_b" true

passo "2. servidor no ar e cadastro dos dois notebooks"
lancador "$servidor"
afirmar "servidor respondeu HTTP 200 na tela de login" esperar_sistema "$servidor"
token_a="$(artisan_de "$servidor" sincronizacao:aparelho 'Notebook A' 1 | awk -F': ' '/guarde-o/ {print $NF}')"
token_b="$(artisan_de "$servidor" sincronizacao:aparelho 'Notebook B' 1 | awk -F': ' '/guarde-o/ {print $NF}')"
afirmar_igual "código de acesso do notebook A com formato válido" "sim" \
    "$([[ "$token_a" =~ ^[A-Za-z0-9_-]{30,}$ ]] && echo sim || echo nao)"
afirmar_igual "código de acesso do notebook B com formato válido" "sim" \
    "$([[ "$token_b" =~ ^[A-Za-z0-9_-]{30,}$ ]] && echo sim || echo nao)"
anexar_em "$notebook_a" "$dados/modo-igreja.env" \
    "$(printf 'MODO_IGREJA_SERVIDOR_URL=http://%s:8080\nMODO_IGREJA_APARELHO_TOKEN=%s\n' "$servidor" "$token_a")"
anexar_em "$notebook_b" "$dados/modo-igreja.env" \
    "$(printf 'MODO_IGREJA_SERVIDOR_URL=http://%s:8080\nMODO_IGREJA_APARELHO_TOKEN=%s\n' "$servidor" "$token_b")"

passo "3. dados do servidor antes da cópia"
sql_de "$servidor" "INSERT INTO comums (codigo, descricao) VALUES ('C-100', 'Igreja do ensaio');"
comum_id="$(sql_de "$servidor" "SELECT id FROM comums WHERE codigo='C-100';")"
sql_de "$servidor" "INSERT INTO produtos (codigo, comum_id, bem) VALUES ('P-1', $comum_id, 'Cadeira');"
afirmar_igual "servidor tem 1 produto antes da cópia" "1" "$(sql_de "$servidor" "SELECT COUNT(*) FROM produtos;")"

passo "4. notebooks no ar e cópia inicial"
lancador "$notebook_a"
lancador "$notebook_b"
afirmar "notebook A respondeu HTTP 200 na tela de login" esperar_sistema "$notebook_a"
afirmar "notebook B respondeu HTTP 200 na tela de login" esperar_sistema "$notebook_b"
artisan_de "$notebook_a" sincronizacao:copia
artisan_de "$notebook_b" sincronizacao:copia
afirmar_igual "notebook A recebeu o produto P-1" "P-1" "$(sql_de "$notebook_a" "SELECT codigo FROM produtos;")"
afirmar_igual "notebook B recebeu o produto P-1" "P-1" "$(sql_de "$notebook_b" "SELECT codigo FROM produtos;")"

passo "5. sem internet: o servidor sai da rede"
docker network disconnect "$rede" "$servidor"
if alcancavel "$notebook_a" "$servidor" 8080; then
    echo "FALHA notebook A ainda alcança o servidor"
    exit 1
fi
echo "OK    notebook A não alcança o servidor (o servidor saiu da rede)"

passo "6. os dois notebooks trabalham sem internet"
sql_de "$notebook_a" "UPDATE produtos SET bem='Mesa' WHERE codigo='P-1';"
sql_de "$notebook_a" "INSERT INTO produtos (codigo, comum_id, bem) VALUES ('P-A', $comum_id, 'Armário');"
sql_de "$notebook_b" "INSERT INTO produtos (codigo, comum_id, bem) VALUES ('P-B', $comum_id, 'Estante');"
escrever_em "$notebook_a" "$app/storage/importacao/importacao_1_1.csv" $'codigo;bem\nP-A;Armario\n'
escrever_em "$notebook_a" "$app/storage/app/private/audits/audit-log.jsonl" \
    $'{"acao":"importacao","usuario":1,"descricao":"planilha preparada no notebook A"}\n'
echo "notebook A: P-1 virou Mesa, criou P-A, guardou uma planilha e uma linha de auditoria"
echo "notebook B: criou P-B"

passo "7. o servidor volta à rede e recebe uma mudança feita no escritório"
docker network connect "$rede" "$servidor"
voltou=0
for tentativa in $(seq 1 30); do
    if alcancavel "$notebook_a" "$servidor" 8080; then
        voltou=1
        break
    fi
    sleep 2
done
afirmar_igual "notebook A volta a alcançar o servidor" "1" "$voltou"
sql_de "$servidor" "UPDATE produtos SET bem='Sofá' WHERE codigo='P-1';"
echo "servidor: P-1 virou Sofá (mudado nos dois lados: conflito esperado)"

passo "8. sincronização: primeiro o notebook A, depois o B"
sincroniza "$notebook_a"
sincroniza "$notebook_b"

passo "9. o conflito de P-1 aparece no servidor"
echo "conflitos registrados no servidor:"
sql_de "$servidor" "SELECT tabela, chave, operacao, motivo, status FROM sincronizacao_conflitos;" | sed 's/^/    /'
afirmar_igual "um conflito pendente no servidor" "1" \
    "$(sql_de "$servidor" "SELECT COUNT(*) FROM sincronizacao_conflitos WHERE status='pendente';")"
conflito_id="$(sql_de "$servidor" "SELECT id FROM sincronizacao_conflitos WHERE status='pendente' LIMIT 1;")"

passo "10. a pessoa escolhe o valor do notebook (mesmo serviço da tela de conflitos)"
resultado="$(artisan_de "$servidor" tinker --execute="echo app(App\\Sync\\ConflictResolver::class)->manterDoNotebook($conflito_id) ? 'resolvido' : 'nao';")"
afirmar_igual "conflito resolvido com o valor do notebook" "resolvido" \
    "$(grep -o 'resolvido\|nao' <<<"$resultado" | tail -n 1)"

passo "11. nova sincronização dos dois notebooks"
sincroniza "$notebook_a"
sincroniza "$notebook_b"

passo "12. conferência: os três bancos ficaram iguais"
for contenor in "${todos[@]}"; do
    sql_de "$contenor" "SELECT codigo, bem FROM produtos ORDER BY codigo;" >"$saida/produtos-$contenor.txt"
done
echo "produtos no servidor:"
sed 's/^/    /' "$saida/produtos-$servidor.txt"
afirmar "produtos iguais no servidor e no notebook A" cmp -s "$saida/produtos-$servidor.txt" "$saida/produtos-$notebook_a.txt"
afirmar "produtos iguais no servidor e no notebook B" cmp -s "$saida/produtos-$servidor.txt" "$saida/produtos-$notebook_b.txt"
afirmar_igual "observação vazia de P-A chegou ao servidor como texto vazio, não nulo" "" \
    "$(sql_de "$servidor" "SELECT IFNULL(observacao,'<NULL>') FROM produtos WHERE codigo='P-A';")"
afirmar_igual "produtos finais: P-1 Mesa, P-A Armário, P-B Estante" \
    "$(printf 'P-1\tMesa\nP-A\tArmário\nP-B\tEstante')" "$(cat "$saida/produtos-$servidor.txt")"

passo "13. planilha e auditoria chegaram nos dois sentidos"
planilha="$app/storage/importacao/importacao_1_1.csv"
auditoria="$app/storage/app/private/audits/audit-log.jsonl"
afirmar_igual "planilha de A chegou ao notebook B com o mesmo conteúdo" \
    "$(sha_de "$notebook_a" "$planilha")" "$(sha_de "$notebook_b" "$planilha")"
afirmar_igual "planilha de A está no servidor com o mesmo conteúdo" \
    "$(sha_de "$notebook_a" "$planilha")" "$(sha_de "$servidor" "$planilha")"
afirmar_igual "auditoria igual no servidor e no notebook A" \
    "$(sha_de "$servidor" "$auditoria")" "$(sha_de "$notebook_a" "$auditoria")"
afirmar_igual "auditoria igual no servidor e no notebook B" \
    "$(sha_de "$servidor" "$auditoria")" "$(sha_de "$notebook_b" "$auditoria")"

passo "14. nenhum eco: cada notebook registrou só as alterações que fez"
afirmar_igual "notebook A registrou só as duas alterações dele (P-1 e P-A)" "2" \
    "$(sql_de "$notebook_a" "SELECT COUNT(*) FROM sincronizacao_alteracoes WHERE origem='local';")"
afirmar_igual "notebook B registrou só a inserção de P-B" "1" \
    "$(sql_de "$notebook_b" "SELECT COUNT(*) FROM sincronizacao_alteracoes WHERE origem='local';")"

passo "15. segunda rodada: nada novo deve trafegar"
saida_a="$(sincroniza "$notebook_a")"
saida_b="$(sincroniza "$notebook_b")"
resumo_esperado="$(printf 'Registros: enviados 0, recebidos 0.\nPlanilhas: enviadas 0, recebidas 0, iguais 1.\nAuditoria: enviadas 0, recebidas 0.')"
afirmar_igual "segunda rodada do notebook A sem transferências" "$resumo_esperado" \
    "$(grep -E '^(Registros|Planilhas|Auditoria):' <<<"$saida_a")"
afirmar_igual "segunda rodada do notebook B sem transferências" "$resumo_esperado" \
    "$(grep -E '^(Registros|Planilhas|Auditoria):' <<<"$saida_b")"

passo "16. registros dos lançadores e parada dos contêineres"
for contenor in "${todos[@]}"; do
    docker cp "$contenor:/home/cp/lancador.log" "$saida/lancador-$contenor.log"
done
docker stop -t 10 "${todos[@]}" >/dev/null
for contenor in "${todos[@]}"; do
    afirmar_igual "$contenor parado" "false" "$(docker inspect -f '{{.State.Running}}' "$contenor")"
done
echo "ENSAIO EM CONTÊINERES CONCLUÍDO: todas as conferências passaram."
