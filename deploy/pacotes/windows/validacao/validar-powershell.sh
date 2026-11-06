#!/usr/bin/env bash
# Valida os scripts do Windows no PowerShell 7 do Linux, sem precisar de um Windows:
#   1) a sintaxe do lancador.ps1 e dos scripts de ponto de acesso que o PHP gera;
#   2) o fluxo do lancador com programas falsos (PHP e MySQL viram scripts de teste), numa pasta
#      com espaço no nome, como C:\Program Files. A preferência de erro de programas é a do
#      Windows PowerShell 5.1, que o instalador usa.
# Não prova o comportamento real do Windows: WinRT, MySQL e PHP para Windows só rodam lá.
#
# Uso: deploy/pacotes/windows/validacao/validar-powershell.sh
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
            mkdir -p "$(dirname "$arquivo")"
            cat >"$arquivo" <<'AMBIENTE'
APP_NAME="Check Planilha"
APP_KEY=base64:QUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQQ==
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=check_planilha
DB_USERNAME=check_planilha
DB_PASSWORD=senha-do-teste-123
MODO_IGREJA_PORTA=8080
AMBIENTE
            ;;
    esac
done
exit 0
SH

cat >"$trabalho/fake/mysqld" <<'SH'
#!/bin/bash
# Programa falso do MySQL: na criação, cria a pasta de dados; no servidor, fica pronto após 3 s.
{ printf 'MYSQLD'; printf '\t%s' "$@"; printf '\n'; } >>"$FAKE_LOG"
case " $* " in
    *" --initialize-insecure "*)
        for argumento in "$@"; do
            case "$argumento" in
                --datadir=*) mkdir -p "${argumento#--datadir=}/mysql" ;;
            esac
        done
        exit 0 ;;
esac
( sleep 3; touch "$FAKE_PRONTO" ) &
exec sleep 600
SH

cat >"$trabalho/fake/mysqladmin" <<'SH'
#!/bin/bash
# Programa falso do mysqladmin: o ping falha, com texto de erro, até o servidor falso ficar pronto.
{ printf 'MYSQLADMIN'; printf '\t%s' "$@"; printf '\n'; } >>"$FAKE_LOG"
case " $* " in
    *" ping "*)
        if [ -f "$FAKE_PRONTO" ]; then exit 0; fi
        echo "mysqladmin: connect to server at '127.0.0.1' failed" >&2
        exit 1 ;;
    *" shutdown "*) rm -f "$FAKE_PRONTO"; exit 0 ;;
esac
exit 0
SH

cat >"$trabalho/fake/mysql" <<'SH'
#!/bin/bash
# Programa falso do cliente MySQL: só anota a chamada (inclui o SQL do -e).
{ printf 'MYSQL'; printf '\t%s' "$@"; printf '\n'; } >>"$FAKE_LOG"
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
param([Parameter(Mandatory)][string]$Lancador, [switch]$Simular)
$PSNativeCommandUseErrorActionPreference = $true
$opcoes = @{}
if ($Simular) { $opcoes['Simular'] = $true }
& $Lancador @opcoes
PS

cat >"$trabalho/roteiro.sh" <<'SH'
#!/bin/bash
# Roda dentro do contêiner do PowerShell 7: monta a instalação falsa e roda o lançador.
set -u
BASE='/work/instalacao/Check Planilha Igreja'
export CP_DADOS='/work/instalacao/Dados Locais/CheckPlanilha'
export FAKE_LOG=/work/saida/programas.txt
export FAKE_PRONTO=/work/saida/pronto
mkdir -p "$BASE/app" "$(dirname "$CP_DADOS")" /work/bin
cp /work/ps/lancador.ps1 "$BASE/lancador.ps1"
# No Linux, o PowerShell troca a barra invertida por barra normal nos caminhos (Join-Path),
# então a estrutura de pastas falsa é a mesma do Windows, com barras normais.
mkdir -p "$BASE/php" "$BASE/mysql/bin"
cp /work/fake/php "$BASE/php/php.exe"
cp /work/fake/mysqld "$BASE/mysql/bin/mysqld.exe"
cp /work/fake/mysqladmin "$BASE/mysql/bin/mysqladmin.exe"
cp /work/fake/mysql "$BASE/mysql/bin/mysql.exe"
cp /work/fake/xdg-open /work/bin/xdg-open
export PATH="/work/bin:$PATH"
: >"$FAKE_LOG"
pwsh -NoProfile -File /work/driver.ps1 -Lancador "$BASE/lancador.ps1" -Simular >/work/saida/simulacao.txt 2>&1
echo "codigo_simulacao=$?"
timeout 40 pwsh -NoProfile -File /work/driver.ps1 -Lancador "$BASE/lancador.ps1" >/work/saida/execucao.txt 2>&1
echo "codigo_execucao=$?"
SH

chmod +x "$trabalho/fake/"*
docker run --rm --user "$(id -u):$(id -g)" -e HOME=/tmp -v "$trabalho:/work" -w /work \
    "$imagem_pwsh" bash /work/roteiro.sh >"$trabalho/saida/roteiro.txt" 2>&1 || true
cat "$trabalho/saida/roteiro.txt"

log="$trabalho/saida/programas.txt"
conferir "simulação termina com código 0" grep -q 'codigo_simulacao=0' "$trabalho/saida/roteiro.txt"
conferir "simulação mostra a pasta de extensões do PHP" grep -q 'extension_dir=' "$trabalho/saida/simulacao.txt"
php_total=$(awk -F'\t' '$1 == "PHP"' "$log" | wc -l)
php_com_extensoes=$(awk -F'\t' '$1 == "PHP" && $2 == "-d" && $3 ~ /^extension_dir=/' "$log" | wc -l)
conferir "todo PHP recebe a pasta de extensões em caminho absoluto" test "$php_total" -gt 0 -a "$php_total" -eq "$php_com_extensoes"
conferir "pasta de dados com espaço chega inteira ao MySQL (criação)" \
    awk -F'\t' '$1 == "MYSQLD" && $2 == "--initialize-insecure" && $4 ~ /Dados Locais/ { found = 1 } END { exit !found }' "$log"
conferir "MySQL sobe com os 7 argumentos inteiros (caminho com espaço)" \
    awk -F'\t' '$1 == "MYSQLD" && $2 ~ /^--basedir=/ && NF == 8 { found = 1 } END { exit !found }' "$log"
conferir "lançador segue depois da espera pelo banco (SQL executado)" \
    awk -F'\t' '$1 == "MYSQL" { found = 1 } END { exit !found }' "$log"
conferir "usuário do sistema é criado" grep -q 'CREATE USER' "$log"
conferir "migrações são executadas" \
    awk -F'\t' '$1 == "PHP" && $0 ~ /\tmigrate\t/ { found = 1 } END { exit !found }' "$log"
conferir "gatilhos são recriados" \
    awk -F'\t' '$1 == "PHP" && $0 ~ /sincronizacao:gatilhos/ { found = 1 } END { exit !found }' "$log"
conferir "servidor do PHP é iniciado com -S" \
    awk -F'\t' '$1 == "PHP" && $0 ~ /\t-S\t/ { found = 1 } END { exit !found }' "$log"
conferir "agendador de sincronização roda ao menos uma vez" grep -q 'schedule:run' "$log"
conferir "execução segue até o laço do agendador (encerrada pelo tempo do teste)" grep -q 'codigo_execucao=124' "$trabalho/saida/roteiro.txt"

echo
if [ "${VER_REGISTRO:-0}" = "1" ]; then
    echo "-- registro das chamadas dos programas falsos (uma linha por chamada, campos separados por tab):"
    sed 's/\t/ | /g' "$log" | cut -c1-220
    echo "--"
fi
if [ "$falhas" -eq 0 ]; then
    echo "TODAS AS CONFERÊNCIAS PASSARAM (PowerShell 7 no Linux; não é o Windows)."
else
    echo "$falhas conferência(s) falharam. Fim do log do lançador:"
    tail -30 "$trabalho/saida/execucao.txt" 2>/dev/null || true
fi
exit "$falhas"
