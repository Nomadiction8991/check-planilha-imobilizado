#!/usr/bin/env bash
# Ensaio de ponta a ponta (fase 7, sem celulares): um servidor e dois notebooks.
#
# Cada computador simulado tem a sua própria instalação do pacote (pasta, banco e portas).
# Como os três ficam no mesmo endereço (127.0.0.1), o "sem internet" é simulado tirando o
# servidor do ar enquanto os notebooks trabalham. Roda como usuário comum, depois do dpkg.
set -euo pipefail

ORIGEM=/usr/lib/check-planilha           # instalação feita pelo dpkg (modelo para as cópias)
LANCADOR=/usr/bin/check-planilha-igreja
RAIZ="$HOME/ensaio"

declare -A PORTA_WEB=([servidor]=8090 [notebook_a]=8081 [notebook_b]=8082)
declare -A PORTA_BANCO=([servidor]=3309 [notebook_a]=3307 [notebook_b]=3308)
declare -A MODO_IGREJA=([servidor]=false [notebook_a]=true [notebook_b]=true)

passo() {
    printf '\n== %s\n' "$*"
}

# Compara dois valores e mostra OK ou FALHA. Se falhar, o ensaio para.
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

dados() { echo "$RAIZ/$1/dados"; }
ambiente() { echo "$RAIZ/$1/dados/modo-igreja.env"; }
app_de() { echo "$RAIZ/$1/instalacao/app"; }
php_de() { echo "$RAIZ/$1/instalacao/php/bin/php"; }
mysql_de() { echo "$RAIZ/$1/instalacao/mysql/bin/mysql"; }
mysqladmin_de() { echo "$RAIZ/$1/instalacao/mysql/bin/mysqladmin"; }

# Cópia da instalação para o computador simulado, com o arquivo de configuração dele.
preparar() {
    local nome="$1"
    mkdir -p "$RAIZ/$nome"
    cp -a "$ORIGEM" "$RAIZ/$nome/instalacao"
    mkdir -p "$(dados "$nome")"
    (
        cd "$(app_de "$nome")"
        MODO_IGREJA_PORTA="${PORTA_WEB[$nome]}" "$(php_de "$nome")" artisan modo-igreja:configurar \
            --arquivo="$(ambiente "$nome")" --socket="$(dados "$nome")/mysql.sock" >/dev/null
    )
    sed -i -e "s/^DB_PORT=.*/DB_PORT=${PORTA_BANCO[$nome]}/" \
        -e "s/^MODO_IGREJA_ATIVO=.*/MODO_IGREJA_ATIVO=${MODO_IGREJA[$nome]}/" "$(ambiente "$nome")"
}

# Código HTTP da tela de login (0 quando o servidor não responde).
status_http() {
    "$ORIGEM/php/bin/php" -r '$h = @get_headers($argv[1]); echo $h ? (int) substr($h[0], 9, 3) : 0;' \
        "http://127.0.0.1:$1/login"
}

# Sobe o computador simulado com o lançador instalado (o mesmo que o atalho do menu roda).
iniciar() {
    local nome="$1"
    # Apaga o PID da execução anterior: sem isso, a espera abaixo leria um processo já encerrado.
    rm -f "$RAIZ/$nome/lancador.pid"
    # bash -x registra cada comando do lançador no log, para uma falha ficar explicada.
    setsid bash -c "echo \$\$ > '$RAIZ/$nome/lancador.pid'; exec env CP_HOME='$RAIZ/$nome/instalacao' \
        CP_APP='$(app_de "$nome")' CP_DADOS='$(dados "$nome")' bash -x '$LANCADOR'" \
        >>"$RAIZ/$nome/log.txt" 2>&1 &
    local tentativa pid
    for tentativa in $(seq 1 200); do
        pid="$(cat "$RAIZ/$nome/lancador.pid" 2>/dev/null || echo 0)"
        if [[ "$pid" -gt 0 ]] && ! kill -0 "$pid" 2>/dev/null; then
            echo "FALHA  $nome parou antes de responder. Fim do log do lançador:"
            tail -60 "$RAIZ/$nome/log.txt"
            echo "Fim do log do banco de $nome:"
            tail -20 "$RAIZ/$nome/dados/mysql.log" 2>/dev/null || true
            exit 1
        fi
        if [[ "$(status_http "${PORTA_WEB[$nome]}")" == "200" ]]; then
            echo "no ar  $nome (porta ${PORTA_WEB[$nome]}, banco ${PORTA_BANCO[$nome]})"
            return 0
        fi
        sleep 3
    done
    echo "FALHA  $nome não subiu. Fim do log:"
    tail -40 "$RAIZ/$nome/log.txt"
    exit 1
}

# Desliga o computador simulado: pede para o banco parar e espera ele terminar de verdade
# (senão um novo start encontra a pasta do banco ainda em uso), depois encerra o lançador.
encerrar() {
    local nome="$1" pid pid_banco tentativa
    pid="$(cat "$RAIZ/$nome/lancador.pid" 2>/dev/null || echo 0)"
    "$(mysqladmin_de "$nome")" --defaults-extra-file="$(dados "$nome")/mysql-administrador.cnf" --socket="$(dados "$nome")/mysql.sock" shutdown >/dev/null 2>&1 || true
    for tentativa in $(seq 1 60); do
        pid_banco="$(cat "$(dados "$nome")/mysql.pid" 2>/dev/null || echo 0)"
        if [[ "$pid_banco" -eq 0 ]] || ! kill -0 "$pid_banco" 2>/dev/null; then
            break
        fi
        sleep 1
    done
    if [[ "$pid" -gt 0 ]]; then
        kill -TERM -- "-$pid" 2>/dev/null || true
    fi
    sleep 2
}

# Roda um comando artisan com a configuração do computador simulado.
artisan_de() {
    local nome="$1"
    shift
    (
        set -a
        # shellcheck disable=SC1090
        . "$(ambiente "$nome")"
        set +a
        cd "$(app_de "$nome")"
        "$(php_de "$nome")" artisan "$@"
    )
}

# Sincroniza um computador simulado. Se a sincronização agendada estiver rodando agora, espera e tenta de novo.
sincroniza() {
    local nome="$1" saida tentativa
    for tentativa in $(seq 1 60); do
        # Uma sincronização que termina com erro para o ensaio e mostra a saída, em vez de sair em silêncio.
        saida="$(artisan_de "$nome" sincronizacao:executar 2>&1)" || {
            printf '%s\n' "$saida" >&2
            echo "FALHA  $nome: a sincronização terminou com erro" >&2
            exit 1
        }
        if ! grep -q 'já está rodando' <<<"$saida"; then
            printf '%s\n' "$saida"
            return 0
        fi
        sleep 5
    done
    echo "FALHA  $nome: a sincronização ficou presa em outra execução" >&2
    return 1
}

# Consulta SQL no banco do computador simulado (saída em colunas separadas por tab).
sql_de() {
    local nome="$1"
    "$(mysql_de "$nome")" --defaults-extra-file="$(dados "$nome")/mysql-administrador.cnf" --socket="$(dados "$nome")/mysql.sock" -N -B --default-character-set=utf8mb4 \
        check_planilha -e "$2"
}

rm -rf "$RAIZ"
mkdir -p "$RAIZ"

passo "1. três computadores simulados, cada um com a sua instalação do pacote"
for nome in servidor notebook_a notebook_b; do
    preparar "$nome"
    echo "pronto $nome (web ${PORTA_WEB[$nome]}, banco ${PORTA_BANCO[$nome]})"
done

passo "2. servidor no ar e cadastro dos dois notebooks"
iniciar servidor
token_a="$(artisan_de servidor sincronizacao:aparelho 'Notebook A' 1 | awk -F': ' '/guarde-o/ {print $NF}')"
token_b="$(artisan_de servidor sincronizacao:aparelho 'Notebook B' 1 | awk -F': ' '/guarde-o/ {print $NF}')"
afirmar_igual "código de acesso do notebook A com formato válido" "sim" \
    "$([[ "$token_a" =~ ^[A-Za-z0-9_-]{30,}$ ]] && echo sim || echo nao)"
afirmar_igual "código de acesso do notebook B com formato válido" "sim" \
    "$([[ "$token_b" =~ ^[A-Za-z0-9_-]{30,}$ ]] && echo sim || echo nao)"
printf 'MODO_IGREJA_SERVIDOR_URL=%s\nMODO_IGREJA_APARELHO_TOKEN=%s\n' \
    "http://127.0.0.1:${PORTA_WEB[servidor]}" "$token_a" >>"$(ambiente notebook_a)"
printf 'MODO_IGREJA_SERVIDOR_URL=%s\nMODO_IGREJA_APARELHO_TOKEN=%s\n' \
    "http://127.0.0.1:${PORTA_WEB[servidor]}" "$token_b" >>"$(ambiente notebook_b)"

passo "3. dados do servidor antes da cópia"
sql_de servidor "INSERT INTO comums (codigo, descricao) VALUES ('C-100', 'Igreja do ensaio');"
comum_id="$(sql_de servidor "SELECT id FROM comums WHERE codigo='C-100';")"
sql_de servidor "INSERT INTO produtos (codigo, comum_id, bem) VALUES ('P-1', $comum_id, 'Cadeira');"
afirmar_igual "servidor tem 1 produto antes da cópia" "1" "$(sql_de servidor "SELECT COUNT(*) FROM produtos;")"

passo "4. notebooks no ar e cópia inicial"
iniciar notebook_a
iniciar notebook_b
artisan_de notebook_a sincronizacao:copia
artisan_de notebook_b sincronizacao:copia
afirmar_igual "notebook A recebeu o produto P-1" "P-1" "$(sql_de notebook_a "SELECT codigo FROM produtos;")"
afirmar_igual "notebook B recebeu o produto P-1" "P-1" "$(sql_de notebook_b "SELECT codigo FROM produtos;")"

passo "5. sem internet: o servidor sai do ar"
encerrar servidor
afirmar_igual "servidor fora do ar" "0" "$(status_http "${PORTA_WEB[servidor]}")"

passo "6. os dois notebooks trabalham sem internet"
sql_de notebook_a "UPDATE produtos SET bem='Mesa' WHERE codigo='P-1';"
sql_de notebook_a "INSERT INTO produtos (codigo, comum_id, bem) VALUES ('P-A', $comum_id, 'Armário');"
sql_de notebook_b "INSERT INTO produtos (codigo, comum_id, bem) VALUES ('P-B', $comum_id, 'Estante');"
mkdir -p "$(app_de notebook_a)/storage/importacao"
printf 'codigo;bem\nP-A;Armario\n' >"$(app_de notebook_a)/storage/importacao/importacao_1_1.csv"
mkdir -p "$(app_de notebook_a)/storage/app/private/audits"
printf '%s\n' '{"acao":"importacao","usuario":1,"descricao":"planilha preparada no notebook A"}' \
    >>"$(app_de notebook_a)/storage/app/private/audits/audit-log.jsonl"
echo "notebook A: P-1 virou Mesa, criou P-A, guardou uma planilha e uma linha de auditoria"
echo "notebook B: criou P-B"

passo "7. o servidor volta e recebe uma mudança feita no escritório"
iniciar servidor
sql_de servidor "UPDATE produtos SET bem='Sofá' WHERE codigo='P-1';"
echo "servidor: P-1 virou Sofá (mudado nos dois lados: conflito esperado)"

passo "8. sincronização: primeiro o notebook A, depois o B"
sincroniza notebook_a
sincroniza notebook_b

passo "9. o conflito de P-1 aparece no servidor"
echo "conflitos registrados no servidor:"
sql_de servidor "SELECT tabela, chave, operacao, motivo, status FROM sincronizacao_conflitos;" | sed 's/^/    /'
afirmar_igual "um conflito pendente no servidor" "1" \
    "$(sql_de servidor "SELECT COUNT(*) FROM sincronizacao_conflitos WHERE status='pendente';")"
conflito_id="$(sql_de servidor "SELECT id FROM sincronizacao_conflitos WHERE status='pendente' LIMIT 1;")"

passo "10. a pessoa escolhe o valor do notebook (mesmo serviço da tela de conflitos)"
resultado="$(artisan_de servidor tinker --execute="echo app(App\\Sync\\ConflictResolver::class)->manterDoNotebook($conflito_id) ? 'resolvido' : 'nao';")"
afirmar_igual "conflito resolvido com o valor do notebook" "resolvido" "$(grep -o 'resolvido\|nao' <<<"$resultado" | tail -1)"

passo "11. nova sincronização dos dois notebooks"
sincroniza notebook_a
sincroniza notebook_b

passo "12. conferência: os três bancos ficaram iguais"
for nome in servidor notebook_a notebook_b; do
    sql_de "$nome" "SELECT codigo, bem FROM produtos ORDER BY codigo;" >"$RAIZ/produtos-$nome.txt"
done
echo "produtos no servidor:"
sed 's/^/    /' "$RAIZ/produtos-servidor.txt"
afirmar "produtos iguais no servidor e no notebook A" cmp -s "$RAIZ/produtos-servidor.txt" "$RAIZ/produtos-notebook_a.txt"
afirmar "produtos iguais no servidor e no notebook B" cmp -s "$RAIZ/produtos-servidor.txt" "$RAIZ/produtos-notebook_b.txt"
# Regressão: texto vazio chega ao servidor como texto vazio (antes virava nulo e recusava o registro).
afirmar_igual "observação vazia de P-A chegou ao servidor como texto vazio, não nulo" "" \
    "$(sql_de servidor "SELECT IFNULL(observacao,'<NULL>') FROM produtos WHERE codigo='P-A';")"
afirmar_igual "produtos finais: P-1 Mesa, P-A Armário, P-B Estante" \
    "$(printf 'P-1\tMesa\nP-A\tArmário\nP-B\tEstante')" "$(cat "$RAIZ/produtos-servidor.txt")"

passo "13. planilha e auditoria chegaram nos dois sentidos"
afirmar "planilha de A chegou ao servidor e ao notebook B, com o mesmo conteúdo" \
    cmp -s "$(app_de notebook_a)/storage/importacao/importacao_1_1.csv" "$(app_de notebook_b)/storage/importacao/importacao_1_1.csv"
afirmar "planilha de A está no servidor com o mesmo conteúdo" \
    cmp -s "$(app_de notebook_a)/storage/importacao/importacao_1_1.csv" "$(app_de servidor)/storage/importacao/importacao_1_1.csv"
afirmar "auditoria igual no servidor e nos dois notebooks" bash -c "
    diff -q '$(app_de servidor)/storage/app/private/audits/audit-log.jsonl' '$(app_de notebook_a)/storage/app/private/audits/audit-log.jsonl' &&
    diff -q '$(app_de servidor)/storage/app/private/audits/audit-log.jsonl' '$(app_de notebook_b)/storage/app/private/audits/audit-log.jsonl'"

passo "14. nenhum eco: cada notebook registrou só as alterações que fez"
afirmar_igual "notebook A registrou só as duas alterações dele (P-1 e P-A)" "2" \
    "$(sql_de notebook_a "SELECT COUNT(*) FROM sincronizacao_alteracoes WHERE origem='local';")"
afirmar_igual "notebook B registrou só a inserção de P-B" "1" \
    "$(sql_de notebook_b "SELECT COUNT(*) FROM sincronizacao_alteracoes WHERE origem='local';")"

passo "15. segunda rodada: nada novo deve trafegar"
saida_a="$(sincroniza notebook_a)"
saida_b="$(sincroniza notebook_b)"
resumo_esperado="$(printf 'Registros: enviados 0, recebidos 0.\nPlanilhas: enviadas 0, recebidas 0, iguais 1.\nAuditoria: enviadas 0, recebidas 0.')"
afirmar_igual "segunda rodada do notebook A sem transferências" "$resumo_esperado" \
    "$(grep -E '^(Registros|Planilhas|Auditoria):' <<<"$saida_a")"
afirmar_igual "segunda rodada do notebook B sem transferências" "$resumo_esperado" \
    "$(grep -E '^(Registros|Planilhas|Auditoria):' <<<"$saida_b")"

passo "16. desligamento dos três computadores simulados"
encerrar notebook_a
encerrar notebook_b
encerrar servidor
echo "ENSAIO CONCLUÍDO: todas as conferências passaram."
