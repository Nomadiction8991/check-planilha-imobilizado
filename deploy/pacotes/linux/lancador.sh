#!/usr/bin/env bash
# Lançador do Check Planilha no notebook da igreja (modo local).
#
# Prepara a configuração na primeira vez, sobe o banco local (MySQL 8.4), prepara o
# sistema, liga a rotina de sincronização e o servidor embutido do PHP.
#
# Senhas: na primeira vez, o administrador do banco (root) recebe uma senha gerada. A senha
# inicial do MySQL é trocada por ela, e o acesso fica num arquivo que só o dono deste computador
# lê. Os comandos SQL entram pela entrada padrão do cliente, nunca pela linha de comando.
#
# Uso:
#   check-planilha-igreja             sobe tudo
#   check-planilha-igreja --simular   mostra os comandos, sem iniciar nada (usado nos testes)
set -euo pipefail

# Arquivos criados pelo lançador (socket, logs, dados) só podem ser lidos pelo dono.
umask 077

CP_HOME="${CP_HOME:-/usr/lib/check-planilha}"
CP_APP="${CP_APP:-$CP_HOME/app}"
CP_DADOS="${CP_DADOS:-$HOME/.local/share/check-planilha}"
CP_PHP="$CP_HOME/php/bin/php"
CP_MYSQLD="$CP_HOME/mysql/bin/mysqld"
CP_MYSQL="$CP_HOME/mysql/bin/mysql"
CP_MYSQLADMIN="$CP_HOME/mysql/bin/mysqladmin"
CP_AMBIENTE="$CP_DADOS/modo-igreja.env"
CP_SOCKET="$CP_DADOS/mysql.sock"
# Acesso do administrador do banco (root), criado na primeira vez.
CP_ADMIN="$CP_DADOS/mysql-administrador.cnf"
# Arquivos da primeira configuração. Somem ao fim dela.
CP_INICIAL="$CP_DADOS/mysql-inicial.cnf"
CP_LOG_INICIALIZACAO="$CP_DADOS/mysql-inicializacao.log"

# Certificados do pacote, para o HTTPS da sincronização (o PHP estático não acha os do sistema).
export SSL_CERT_FILE="$CP_HOME/php/cacert.pem"

simular=0
if [[ "${1:-}" == "--simular" ]]; then
    simular=1
fi

# Valores padrão, usados quando o arquivo de configuração ainda não existe (modo simulação).
DB_DATABASE="${DB_DATABASE:-check_planilha}"
DB_USERNAME="${DB_USERNAME:-check_planilha}"
DB_PASSWORD="${DB_PASSWORD:-senha-da-simulacao}"
MODO_IGREJA_PORTA="${MODO_IGREJA_PORTA:-8080}"

erro() {
    echo "Erro: $*" >&2
    exit 1
}

# Mostra o comando (modo simulação) ou o executa.
rodar() {
    if (( simular )); then
        printf '%s ' "$@"
        printf '\n'
    else
        "$@"
    fi
}

# Igual a rodar, mas deixa o comando em segundo plano.
rodar_em_segundo_plano() {
    if (( simular )); then
        printf '%s ' "$@" '&'
        printf '\n'
    else
        "$@" &
    fi
}

# Manda os comandos SQL pela entrada padrão do cliente do MySQL. Assim a senha não aparece na
# linha de comando, onde ficaria visível na lista de processos. Em simulação, mostra só o comando.
rodar_sql() {
    local sql="$1"
    shift
    if (( simular )); then
        printf '%s ' "$@"
        printf '%s\n' '< comandos SQL (senhas ocultas)'
        return 0
    fi
    printf '%s\n' "$sql" | "$@"
}

# Gera uma senha aleatória com 32 letras e números, a partir do gerador do sistema.
gerar_senha() {
    local senha
    senha="$(head -c 512 /dev/urandom | LC_ALL=C tr -dc 'A-Za-z0-9' | cut -c1-32)"
    [[ ${#senha} -eq 32 ]] || erro "não foi possível gerar a senha do banco"
    printf '%s' "$senha"
}

# Grava um arquivo de acesso do MySQL (usuário root). A senha vai entre aspas, com \ e " escapados.
gravar_acesso() {
    local arquivo="$1" senha="$2" escapada
    escapada="$(printf '%s' "$senha" | sed -e 's/\\/\\\\/g' -e 's/"/\\"/g')"
    printf '[client]\nuser=root\npassword="%s"\n' "$escapada" >"$arquivo"
    chmod 600 "$arquivo"
}

# Lê a senha inicial que o MySQL escreve no registro da primeira inicialização.
ler_senha_inicial() {
    local senha
    senha="$(grep -a 'temporary password is generated for root@localhost: ' "$1" | sed -E 's/.*root@localhost: //' | tr -d '\r\n ' | head -n 1)" || true
    [[ -n "$senha" ]] || erro "não encontrei a senha inicial do banco no registro $1"
    printf '%s' "$senha"
}

# Espera o banco responder, por até 60 segundos. O ping devolve 0 quando o servidor está no ar,
# mesmo que a senha seja recusada.
esperar_banco() {
    local acesso="$1" tentativa
    if (( simular )); then
        rodar "$CP_MYSQLADMIN" "--defaults-extra-file=$acesso" "--socket=$CP_SOCKET" ping
        return 0
    fi
    for tentativa in $(seq 1 60); do
        if "$CP_MYSQLADMIN" "--defaults-extra-file=$acesso" "--socket=$CP_SOCKET" ping >/dev/null 2>&1; then
            return 0
        fi
        sleep 1
    done
    erro "o banco não respondeu em 60 segundos"
}

# O socket deve aceitar só o dono: o MySQL o cria com permissão aberta.
proteger_socket() {
    if (( ! simular )) && [[ -S "$CP_SOCKET" ]]; then
        chmod 600 "$CP_SOCKET"
    fi
}

if (( ! simular )); then
    [[ -x "$CP_PHP" ]] || erro "o PHP do pacote não foi encontrado em $CP_PHP"
    [[ -x "$CP_MYSQLD" ]] || erro "o MySQL do pacote não foi encontrado em $CP_MYSQLD"
fi

rodar mkdir -p "$CP_DADOS"
# A pasta de dados (banco, socket e acessos) só pode ser lida pelo dono.
rodar chmod 700 "$CP_DADOS"

# 1) Configuração: criada só na primeira vez. As variáveis entram no ambiente deste processo.
if [[ ! -f "$CP_AMBIENTE" ]]; then
    rodar "$CP_PHP" "$CP_APP/artisan" modo-igreja:configurar --arquivo="$CP_AMBIENTE" --socket="$CP_SOCKET"
fi
if (( ! simular )); then
    set -a
    # shellcheck disable=SC1090
    . "$CP_AMBIENTE"
    set +a
    # O socket é do lançador: o sistema conecta por ele, mesmo que o arquivo de ambiente seja antigo.
    export DB_SOCKET="$CP_SOCKET"
fi

# 2) Banco local: sem porta de rede. Só fala pelo socket, dentro da pasta de dados (0700).
primeira_vez=0
if [[ ! -f "$CP_ADMIN" ]]; then
    primeira_vez=1
    if [[ -n "$(ls -A "$CP_DADOS/mysql" 2>/dev/null)" ]]; then
        erro "a pasta do banco já tem dados, mas o acesso do administrador não está guardado ($CP_ADMIN). Não apague a pasta do banco: fale com o suporte."
    fi
fi

senha_admin=""
senha_inicial=""
if (( primeira_vez )); then
    senha_admin="$(gerar_senha)"
    rodar "$CP_MYSQLD" --initialize --basedir="$CP_HOME/mysql" --datadir="$CP_DADOS/mysql" \
        --log-error="$CP_LOG_INICIALIZACAO" --user="$(id -un)"
    if (( ! simular )); then
        senha_inicial="$(ler_senha_inicial "$CP_LOG_INICIALIZACAO")"
    fi
fi

rodar_em_segundo_plano "$CP_MYSQLD" \
    --datadir="$CP_DADOS/mysql" \
    --basedir="$CP_HOME/mysql" \
    --skip-log-bin \
    --skip-networking \
    --socket="$CP_SOCKET" \
    --pid-file="$CP_DADOS/mysql.pid" \
    --log-error="$CP_DADOS/mysql.log" \
    --mysqlx=OFF

if (( primeira_vez )); then
    # A senha inicial expira: o cliente só entra com --connect-expired-password, e só para trocá-la.
    if (( ! simular )); then
        gravar_acesso "$CP_INICIAL" "$senha_inicial"
    fi
    esperar_banco "$CP_INICIAL"
    proteger_socket
    troca_ok=1
    rodar_sql "ALTER USER 'root'@'localhost' IDENTIFIED BY '$senha_admin';" \
        "$CP_MYSQL" "--defaults-extra-file=$CP_INICIAL" --connect-expired-password "--socket=$CP_SOCKET" || troca_ok=0
    if (( ! simular )); then
        rm -f "$CP_INICIAL"
    fi
    (( troca_ok )) || erro "não foi possível trocar a senha inicial do banco"
    if (( ! simular )); then
        gravar_acesso "$CP_ADMIN" "$senha_admin"
        rm -f "$CP_LOG_INICIALIZACAO"
    fi
else
    esperar_banco "$CP_ADMIN"
    proteger_socket
fi

# 3) Banco e usuário do sistema, com o acesso do administrador. Repetir é seguro.
# A conexão pelo socket chega ao MySQL como "localhost" (não 127.0.0.1), então o usuário é criado para esse host.
rodar_sql "CREATE DATABASE IF NOT EXISTS \`$DB_DATABASE\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USERNAME'@'localhost' IDENTIFIED BY '$DB_PASSWORD';
ALTER USER '$DB_USERNAME'@'localhost' IDENTIFIED BY '$DB_PASSWORD';
GRANT ALL PRIVILEGES ON \`$DB_DATABASE\`.* TO '$DB_USERNAME'@'localhost';" \
    "$CP_MYSQL" "--defaults-extra-file=$CP_ADMIN" "--socket=$CP_SOCKET"

# 4) Sistema: tabelas e gatilhos de captura das alterações.
if (( ! simular )); then
    cd "$CP_APP"
fi
rodar "$CP_PHP" "$CP_APP/artisan" migrate --force
rodar "$CP_PHP" "$CP_APP/artisan" sincronizacao:gatilhos

# 5) Servidor embutido do PHP. "php -S" usa as variáveis deste processo (artisan serve não usa).
rodar_em_segundo_plano "$CP_PHP" -S "0.0.0.0:$MODO_IGREJA_PORTA" -t public public/index.php

# 6) Sincronização: o agendador do Laravel roda a cada minuto; a tarefa decide quando sincronizar.
if (( simular )); then
    echo 'while true; do php artisan schedule:run; sleep 60; done &'
else
    (
        while true; do
            "$CP_PHP" "$CP_APP/artisan" schedule:run >/dev/null 2>&1 || true
            sleep 60
        done
    ) &
fi

if command -v xdg-open >/dev/null 2>&1; then
    rodar xdg-open "http://localhost:$MODO_IGREJA_PORTA"
fi

if (( ! simular )); then
    trap 'trap - EXIT INT TERM; kill 0 2>/dev/null' EXIT INT TERM
    wait
fi
