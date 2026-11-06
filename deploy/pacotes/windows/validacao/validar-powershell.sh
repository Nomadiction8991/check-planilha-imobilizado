#!/usr/bin/env bash
# Valida os scripts do Windows no PowerShell 7 do Linux, sem precisar de um Windows:
#   1) a sintaxe do lancador.ps1 e dos scripts de ponto de acesso que o PHP gera;
#   2) o fluxo do lançador com programas falsos (PHP e MySQL viram scripts de teste), numa pasta
#      com espaço no nome, como C:\Program Files. A preferência de erro de programas é a do
#      Windows PowerShell 5.1, que o instalador usa;
#   3) o acesso do administrador do banco: a senha inicial é trocada, a senha nova fica num arquivo
#      de acesso, nenhum comando leva senha na linha de comando e a segunda execução reaproveita o acesso.
# Não prova o comportamento real do Windows: WinRT, MySQL e PHP para Windows só rodam lá.
# A sequência de senhas do MySQL foi conferida com o MySQL 8.4 real (binário do Linux).
#
# Uso: deploy/pacotes/windows/validacao/validar-powershell.sh
#      VER_REGISTRO=1 deploy/pacotes/windows/validacao/validar-powershell.sh   (mostra o registro das chamadas)
set -euo pipefail

pasta_validacao="$(cd "$(dirname "$0")" && pwd)"
pasta_windows="$(cd "$pasta_validacao/.." && pwd)"
raiz="$(cd "$pasta_windows/../../.." && pwd)"
imagem_pwsh="mcr.microsoft.com/powershell:7.4-ubuntu-22.04"
imagem_php="cp-php84-cli"
trabalho="$(mktemp -d)"
trap 'rm -rf "$trabalho"' EXIT
falhas=0

ok() { echo "OK     $*"; }
falha() { echo "FALHA  $*"; falhas=$((falhas + 1)); }
conferir() {
    # conferir <descrição> <comando...>: mostra OK ou FALHA e segue para as próximas conferências.
    local descricao="$1"
    shift
    if "$@" >/dev/null 2>&1; then ok "$descricao"; else falha "$descricao"; fi
}

mkdir -p "$trabalho/ps" "$trabalho/fake" "$trabalho/saida" "$trabalho/instalacao/Check Planilha Igreja"
cp "$pasta_windows/lancador.ps1" "$trabalho/ps/lancador.ps1"

echo "== 1) sintaxe (analisador do PowerShell 7, sem executar nada)"
for metodo in scriptLigar scriptDesligar scriptEstado; do
    docker run --rm --user 33:1000 -v "$raiz:/app" -w /app -e HOME=/tmp "$imagem_php" \
        php -r 'require "vendor/autoload.php"; $a = new App\Support\Connection\WindowsAccessPoint(new App\Support\Connection\ProcessCommandRunner(), sys_get_temp_dir()); echo (new ReflectionMethod($a, $argv[1]))->invoke($a);' \
        "$metodo" >"$trabalho/ps/ponto-$metodo.ps1"
done

cat >"$trabalho/ps/validar-sintaxe.ps1" <<'PS'
# Lê cada script com o analisador do PowerShell, sem executar nada.
$falhas = 0
foreach ($arquivo in Get-ChildItem -Path /ps -Filter '*.ps1' | Where-Object { $_.Name -ne 'validar-sintaxe.ps1' }) {
    $erros = $null
    [void][System.Management.Automation.Language.Parser]::ParseFile($arquivo.FullName, [ref]$null, [ref]$erros)
    if ($erros.Count -gt 0) {
        $falhas++
        $erros | ForEach-Object { Write-Output ("ERRO {0}: {1}" -f $arquivo.Name, $_.Message) }
    }
    else {
        Write-Output ("OK {0}" -f $arquivo.Name)
    }
}
exit $falhas
PS

codigo_sintaxe=0
docker run --rm --user 33:1000 -e HOME=/tmp -v "$trabalho/ps:/ps:ro" "$imagem_pwsh" \
    pwsh -NoProfile -File /ps/validar-sintaxe.ps1 >"$trabalho/saida/sintaxe.txt" 2>&1 || codigo_sintaxe=$?
cat "$trabalho/saida/sintaxe.txt"
conferir "sintaxe do lançador e dos pontos de acesso (código 0)" test "$codigo_sintaxe" -eq 0

echo "== 2) fluxo do lançador com programas falsos (caminho com espaço)"
cat >"$trabalho/fake/php" <<'SH'
#!/bin/bash
# Programa falso do PHP: anota a chamada e cria o arquivo de ambiente quando o lançador pede.
{ printf 'PHP'; printf '\t%s' "$@"; printf '\n'; } >>"$FAKE_LOG"
case " $* " in
    *" -S "*) exec sleep 600 ;;
esac
for argumento in "$@"; do
    case "$argumento" in
        --arquivo=*)
            arquivo="${argumento#--arquivo=}"
            # Como o gerador do sistema: a pasta de destino precisa existir antes.
            if [ ! -d "$(dirname "$arquivo")" ]; then
                echo "Não é possível gravar o .env em $(dirname "$arquivo"): a pasta não existe ou não permite escrita." >&2
                exit 1
            fi
            cat >"$arquivo" <<'AMBIENTE'
APP_NAME="Check Planilha"
APP_KEY=base64:QUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQQ==
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=check_planilha
DB_USERNAME=check_planilha
DB_PASSWORD=SenhaDoSistema2026
MODO_IGREJA_PORTA=8080
AMBIENTE
            ;;
    esac
done
exit 0
SH

cat >"$trabalho/fake/mysqld" <<'SH'
#!/bin/bash
# Programa falso do MySQL 8.4. Na primeira inicialização grava a senha inicial do root no registro,
# como o MySQL de verdade. A senha de teste "TesteInicial#77" tem um caractere especial, para conferir
# que ela sobrevive ao arquivo de acesso. No servidor, fica pronto após 3 s.
{ printf 'MYSQLD'; printf '\t%s' "$@"; printf '\n'; } >>"$FAKE_LOG"
case " $* " in
    *" --initialize-insecure "*) exit 0 ;;
    *" --initialize "*)
        datadir=""
        registro=""
        for argumento in "$@"; do
            case "$argumento" in
                --datadir=*) datadir="${argumento#--datadir=}" ;;
                --log-error=*) registro="${argumento#--log-error=}" ;;
            esac
        done
        # Modos de falha para o teste do lançador: "trava" não termina; "erro" sai com código 3.
        # Os dois deixam a pasta do banco pela metade, como uma inicialização interrompida.
        case "${FAKE_MODO_INICIALIZACAO:-normal}" in
            trava)
                mkdir -p "$datadir/mysql"
                printf 'INIT_PID\t%s\n' "$$" >>"$FAKE_LOG"
                exec sleep 600 ;;
            erro)
                mkdir -p "$datadir/mysql"
                exit 3 ;;
        esac
        mkdir -p "$datadir/mysql" "$(dirname "$registro")"
        printf '%s' 'TesteInicial#77' >"$FAKE_ESTADO/senha-root"
        printf '%s\n' '2026-11-06T00:00:00.000000Z 5 [Note] [MY-010454] [Server] A temporary password is generated for root@localhost: TesteInicial#77' >"$registro"
        exit 0 ;;
esac
( sleep 3; touch "$FAKE_PRONTO" ) &
exec sleep 600
SH

cat >"$trabalho/fake/mysqladmin" <<'SH'
#!/bin/bash
# Programa falso do mysqladmin. O ping responde 0 quando o servidor está no ar, mesmo com a senha
# recusada, como o de verdade. O shutdown exige a senha correta do administrador.
{ printf 'MYSQLADMIN'; printf '\t%s' "$@"; printf '\n'; } >>"$FAKE_LOG"
confere_senha() {
    if [ ! -f "$1" ]; then return 1; fi
    local enviada esperada
    enviada="$(tr -d '\r' <"$1" | sed -n 's/^password="\(.*\)"$/\1/p')"
    esperada="$(cat "$FAKE_ESTADO/senha-root" 2>/dev/null)"
    [ -n "$enviada" ] && [ "$enviada" = "$esperada" ]
}
acesso=""
for argumento in "$@"; do
    case "$argumento" in
        --defaults-extra-file=*) acesso="${argumento#--defaults-extra-file=}" ;;
    esac
done
case " $* " in
    *" ping "*)
        if [ -f "$FAKE_PRONTO" ]; then exit 0; fi
        exit 1 ;;
    *" shutdown "*)
        if ! confere_senha "$acesso"; then exit 1; fi
        rm -f "$FAKE_PRONTO"
        exit 0 ;;
esac
exit 0
SH

cat >"$trabalho/fake/mysql" <<'SH'
#!/bin/bash
# Programa falso do cliente MySQL. Confere a senha do arquivo de acesso, anota os comandos SQL que
# chegam pela entrada padrão e troca a senha do root quando o SQL manda, como o servidor faria.
{ printf 'MYSQL'; printf '\t%s' "$@"; printf '\n'; } >>"$FAKE_LOG"
confere_senha() {
    if [ ! -f "$1" ]; then return 1; fi
    local enviada esperada
    enviada="$(tr -d '\r' <"$1" | sed -n 's/^password="\(.*\)"$/\1/p')"
    esperada="$(cat "$FAKE_ESTADO/senha-root" 2>/dev/null)"
    [ -n "$enviada" ] && [ "$enviada" = "$esperada" ]
}
acesso=""
for argumento in "$@"; do
    case "$argumento" in
        --defaults-extra-file=*) acesso="${argumento#--defaults-extra-file=}" ;;
    esac
done
sql="$(cat)"
if ! confere_senha "$acesso"; then
    echo "ERROR 1045 (28000): Access denied for user 'root'@'localhost' (using password: YES)" >&2
    printf 'ERRO_ACESSO\n' >>"$FAKE_LOG"
    exit 1
fi
printf 'AUTENTICADO\n' >>"$FAKE_LOG"
while IFS= read -r linha; do
    if [ -n "$linha" ]; then printf 'SQL\t%s\n' "$linha" >>"$FAKE_LOG"; fi
done <<<"$sql"
nova="$(sed -n "s/.*ALTER USER 'root'@'localhost' IDENTIFIED BY '\([^']*\)'.*/\1/p" <<<"$sql")"
if [ -n "$nova" ]; then printf '%s' "$nova" >"$FAKE_ESTADO/senha-root"; fi
exit 0
SH

cat >"$trabalho/fake/xdg-open" <<'SH'
#!/bin/bash
{ printf 'XDG-OPEN'; printf '\t%s' "$@"; printf '\n'; } >>"$FAKE_LOG"
exit 0
SH

cat >"$trabalho/driver.ps1" <<'PS'
# Roda o lançador como o instalador faz, com a preferência que transforma texto de erro de programa
# em erro do script (comportamento do Windows PowerShell 5.1).
param([Parameter(Mandatory)][string]$Lancador, [switch]$Simular, [int]$Limite = 0)
$PSNativeCommandUseErrorActionPreference = $true
$opcoes = @{}
if ($Simular) { $opcoes['Simular'] = $true }
if ($Limite -gt 0) { $opcoes['LimiteInicializacaoSegundos'] = $Limite }
& $Lancador @opcoes
PS

cat >"$trabalho/roteiro.sh" <<'SH'
#!/bin/bash
# Roda dentro do contêiner do PowerShell 7: monta a instalação falsa e roda o lançador três vezes
# (simulação, primeira execução e segunda execução, que deve reaproveitar o acesso guardado).
set -u
BASE='/work/instalacao/Check Planilha Igreja'
export CP_DADOS='/work/instalacao/Dados Locais/CheckPlanilha'
export FAKE_LOG=/work/saida/programas.txt
export FAKE_PRONTO=/work/saida/pronto
export FAKE_ESTADO=/work/saida/estado
mkdir -p "$BASE/php" "$BASE/mysql/bin" "$BASE/app" "$(dirname "$CP_DADOS")" /work/bin "$FAKE_ESTADO"
cp /work/ps/lancador.ps1 "$BASE/lancador.ps1"
cp /work/fake/php "$BASE/php/php.exe"
cp /work/fake/mysqld "$BASE/mysql/bin/mysqld.exe"
cp /work/fake/mysqladmin "$BASE/mysql/bin/mysqladmin.exe"
cp /work/fake/mysql "$BASE/mysql/bin/mysql.exe"
cp /work/fake/xdg-open /work/bin/xdg-open
chmod +x "$BASE/php/php.exe" "$BASE/mysql/bin/mysqld.exe" "$BASE/mysql/bin/mysqladmin.exe" "$BASE/mysql/bin/mysql.exe" /work/bin/xdg-open
export PATH="/work/bin:$PATH"
: >"$FAKE_LOG"
pwsh -NoProfile -File /work/driver.ps1 -Lancador "$BASE/lancador.ps1" -Simular >/work/saida/simulacao.txt 2>&1
echo "codigo_simulacao=$?"
echo EXECUCAO >>"$FAKE_LOG"
timeout 40 pwsh -NoProfile -File /work/driver.ps1 -Lancador "$BASE/lancador.ps1" >/work/saida/execucao.txt 2>&1
echo "codigo_execucao=$?"
echo EXECUCAO >>"$FAKE_LOG"
timeout 40 pwsh -NoProfile -File /work/driver.ps1 -Lancador "$BASE/lancador.ps1" >/work/saida/execucao2.txt 2>&1
echo "codigo_execucao2=$?"
# Cópia da pasta de dados depois das duas execuções normais: as conferências olham essa cópia.
cp -a "$CP_DADOS" /work/saida/dados-apos-execucao2
# Inicialização travada: a pasta de dados é apagada antes, para o lançador chamar a inicialização de novo.
rm -rf "$CP_DADOS"
echo EXECUCAO >>"$FAKE_LOG"
FAKE_MODO_INICIALIZACAO=trava timeout 60 pwsh -NoProfile -File /work/driver.ps1 -Lancador "$BASE/lancador.ps1" -Limite 5 >/work/saida/travada.txt 2>&1
echo "codigo_travada=$?"
pid="$(awk -F'\t' '$1 == "INIT_PID" { p = $2 } END { print p }' "$FAKE_LOG")"
echo "processo_travado_pid=${pid:-nenhum}"
estado="$(awk '/^State/ { print $2 }' "/proc/$pid/status" 2>/dev/null || echo X)"
if [ -n "$pid" ] && [ -e "/proc/$pid" ] && [ "$estado" != "Z" ]; then echo "processo_travado_vivo=sim"; else echo "processo_travado_vivo=nao"; fi
if [ -e "$CP_DADOS/mysql" ]; then echo "banco_parcial_travada=sim"; else echo "banco_parcial_travada=nao"; fi
# Inicialização com erro do programa: código de saída 3.
rm -rf "$CP_DADOS"
echo EXECUCAO >>"$FAKE_LOG"
FAKE_MODO_INICIALIZACAO=erro timeout 60 pwsh -NoProfile -File /work/driver.ps1 -Lancador "$BASE/lancador.ps1" -Limite 5 >/work/saida/erro.txt 2>&1
echo "codigo_erro=$?"
if [ -e "$CP_DADOS/mysql" ]; then echo "banco_parcial_erro=sim"; else echo "banco_parcial_erro=nao"; fi
SH
chmod +x "$trabalho/fake/"* "$trabalho/roteiro.sh"

docker run --rm --user "$(id -u):$(id -g)" -e HOME=/tmp -v "$trabalho:/work" -w /work \
    "$imagem_pwsh" bash /work/roteiro.sh >"$trabalho/saida/roteiro.txt" 2>&1 || true
cat "$trabalho/saida/roteiro.txt"

echo
log="$trabalho/saida/programas.txt"
dados_no_host="$trabalho/saida/dados-apos-execucao2"
acesso_admin="$dados_no_host/mysql-administrador.cnf"
estado="$trabalho/saida/estado/senha-root"

conferir "simulação termina com código 0" grep -q 'codigo_simulacao=0' "$trabalho/saida/roteiro.txt"
conferir "simulação mostra a pasta de extensões do PHP" grep -q 'extension_dir=' "$trabalho/saida/simulacao.txt"
conferir "a simulação não mostra senhas" bash -c "! grep -q 'IDENTIFIED' '$trabalho/saida/simulacao.txt'"
php_total="$(awk -F'\t' '$1 == "PHP"' "$log" 2>/dev/null | wc -l || true)"
php_com_extensoes="$(awk -F'\t' '$1 == "PHP" && $2 == "-d" && $3 ~ /^extension_dir=/' "$log" 2>/dev/null | wc -l || true)"
conferir "todo PHP recebe a pasta de extensões em caminho absoluto" test "$php_total" -gt 0 -a "$php_total" -eq "$php_com_extensoes"
conferir "pasta de dados com espaço chega inteira ao MySQL (inicialização)" \
    awk -F'\t' '$1 == "MYSQLD" && $2 == "--initialize" && $4 ~ /Dados Locais/ { found = 1 } END { exit !found }' "$log"
conferir "MySQL sobe com os 7 argumentos inteiros (caminho com espaço)" \
    awk -F'\t' '$1 == "MYSQLD" && $2 ~ /^--basedir=/ && NF == 8 { found = 1 } END { exit !found }' "$log"
conferir "a configuração do sistema é criada na primeira execução" test -f "$dados_no_host/modo-igreja.env"
conferir "o banco é criado uma vez só nas duas primeiras execuções" \
    awk -F'\t' '/^EXECUCAO/ { m++ } $1 == "MYSQLD" && $2 == "--initialize" && m >= 1 && m <= 2 { n++ } END { exit !(n == 1) }' "$log"
conferir "a inicialização não usa mais o modo sem senha" \
    awk -F'\t' '$1 == "MYSQLD" && $2 == "--initialize-insecure" { bad = 1 } END { exit bad }' "$log"
conferir "a senha inicial é trocada pela senha do administrador (ALTER USER)" \
    awk -F'\t' '$1 == "SQL" && $2 ~ /^ALTER USER .root.@.localhost. IDENTIFIED BY/ { found = 1 } END { exit !found }' "$log"
conferir "a troca é feita com o acesso inicial, que o banco aceita" \
    awk -F'\t' '$0 == "AUTENTICADO" && !auth { auth = NR } $1 == "SQL" && $2 ~ /^ALTER USER/ && !alter { alter = NR } END { exit !(auth && alter && auth < alter) }' "$log"
conferir "nenhuma tentativa de acesso foi recusada" bash -c "! grep -q '^ERRO_ACESSO' '$log'"
conferir "nenhum comando do MySQL leva senha na linha de comando" \
    awk -F'\t' '$1 ~ /^MYSQL/ && $0 ~ /IDENTIFIED|\t-p/ { bad = 1 } END { exit bad }' "$log"
conferir "a senha inicial não aparece no registro de comandos" bash -c "! grep -q 'TesteInicial' '$log'"
conferir "o acesso do administrador fica guardado no arquivo" test -f "$acesso_admin"
senha_guardada="$(tr -d '\r' <"$acesso_admin" 2>/dev/null | sed -n 's/^password="\(.*\)"$/\1/p' || true)"
senha_do_banco="$(cat "$estado" 2>/dev/null || true)"
conferir "o acesso guardado tem a senha que o banco aceitou (e não a inicial)" \
    test -n "$senha_guardada" -a "$senha_guardada" = "$senha_do_banco" -a "$senha_guardada" != 'TesteInicial#77'
conferir "o acesso inicial é apagado ao fim da configuração" test ! -e "$dados_no_host/mysql-inicial.cnf"
conferir "o registro da inicialização é apagado ao fim da configuração" test ! -e "$dados_no_host/mysql-inicializacao.log"
conferir "o usuário do sistema é criado" \
    awk -F'\t' '$1 == "SQL" && $2 ~ /CREATE USER/ { found = 1 } END { exit !found }' "$log"
conferir "a segunda execução entra no banco com o acesso guardado" \
    awk -F'\t' '/^EXECUCAO/ { n++ } n == 2 && $0 == "AUTENTICADO" { ok = 1 } END { exit !ok }' "$log"
conferir "migrações são executadas" \
    awk -F'\t' '$1 == "PHP" && $0 ~ /\tmigrate\t/ { found = 1 } END { exit !found }' "$log"
conferir "gatilhos são recriados" \
    awk -F'\t' '$1 == "PHP" && $0 ~ /sincronizacao:gatilhos/ { found = 1 } END { exit !found }' "$log"
conferir "servidor do PHP é iniciado com -S" \
    awk -F'\t' '$1 == "PHP" && $0 ~ /\t-S\t/ { found = 1 } END { exit !found }' "$log"
conferir "agendador de sincronização roda ao menos uma vez" grep -q 'schedule:run' "$log"
conferir "primeira execução segue até o laço do agendador (encerrada pelo tempo do teste)" \
    grep -q 'codigo_execucao=124' "$trabalho/saida/roteiro.txt"
conferir "segunda execução segue até o laço do agendador (encerrada pelo tempo do teste)" \
    grep -q 'codigo_execucao2=124' "$trabalho/saida/roteiro.txt"
conferir "inicialização travada é encerrada e o lançador para com erro claro" \
    bash -c "grep -q 'codigo_travada=1' '$trabalho/saida/roteiro.txt' && grep -q 'não terminou em 5 segundos' '$trabalho/saida/travada.txt'"
conferir "o processo da inicialização travada é encontrado e não fica vivo depois do erro" \
    bash -c "grep -q 'processo_travado_pid=[0-9]' '$trabalho/saida/roteiro.txt' && grep -q 'processo_travado_vivo=nao' '$trabalho/saida/roteiro.txt'"
conferir "a pasta do banco pela metade é apagada quando a inicialização trava" grep -q 'banco_parcial_travada=nao' "$trabalho/saida/roteiro.txt"
conferir "inicialização com erro do programa informa o código de saída" \
    bash -c "grep -q 'codigo_erro=1' '$trabalho/saida/roteiro.txt' && grep -q 'falhou (código 3)' '$trabalho/saida/erro.txt'"
conferir "a pasta do banco pela metade é apagada quando a inicialização falha" grep -q 'banco_parcial_erro=nao' "$trabalho/saida/roteiro.txt"

echo
if [ "${VER_REGISTRO:-0}" = "1" ]; then
    echo "-- registro das chamadas dos programas falsos (uma linha por chamada, campos separados por tab):"
    sed 's/\t/ | /g' "$log" 2>/dev/null | cut -c1-240 || true
    echo "--"
fi
if [ "$falhas" -eq 0 ]; then
    echo "TODAS AS CONFERÊNCIAS PASSARAM (PowerShell 7 no Linux; não é o Windows)."
else
    echo "$falhas conferência(s) falharam. Fim do log do lançador da primeira execução:"
    tail -30 "$trabalho/saida/execucao.txt" 2>/dev/null || true
fi
exit "$falhas"
