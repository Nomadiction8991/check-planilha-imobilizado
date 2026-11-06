#!/usr/bin/env bash
# Lançador do Check Planilha no notebook da igreja (modo local).
#
# Prepara a configuração na primeira vez, sobe o banco local (MySQL 8.4), prepara o
# sistema, liga a rotina de sincronização e o servidor embutido do PHP.
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

if (( ! simular )); then
    [[ -x "$CP_PHP" ]] || erro "o PHP do pacote não foi encontrado em $CP_PHP"
    [[ -x "$CP_MYSQLD" ]] || erro "o MySQL do pacote não foi encontrado em $CP_MYSQLD"
fi

rodar mkdir -p "$CP_DADOS"
# A pasta de dados (banco, socket e ambiente) só pode ser lida pelo dono.
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
if [[ ! -d "$CP_DADOS/mysql/mysql" ]]; then
    rodar "$CP_MYSQLD" --initialize-insecure --basedir="$CP_HOME/mysql" --datadir="$CP_DADOS/mysql" --user="$(id -un)"
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

if (( ! simular )); then
    for _ in $(seq 1 60); do
        "$CP_MYSQLADMIN" --socket="$CP_SOCKET" -uroot ping >/dev/null 2>&1 && break
        sleep 1
    done
fi

# O socket deve aceitar só o dono: o MySQL o cria com permissão aberta.
if (( ! simular )) && [[ -S "$CP_SOCKET" ]]; then
    chmod 600 "$CP_SOCKET"
fi

# Usuário e banco do sistema. Repetir é seguro: o comando é idempotente.
# A conexão pelo socket chega ao MySQL como "localhost" (não 127.0.0.1), então o usuário é criado para esse host.
rodar "$CP_MYSQL" --socket="$CP_SOCKET" -uroot -e "
CREATE DATABASE IF NOT EXISTS \`$DB_DATABASE\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USERNAME'@'localhost' IDENTIFIED BY '$DB_PASSWORD';
ALTER USER '$DB_USERNAME'@'localhost' IDENTIFIED BY '$DB_PASSWORD';
GRANT ALL PRIVILEGES ON \`$DB_DATABASE\`.* TO '$DB_USERNAME'@'localhost';"

# 3) Sistema: tabelas e gatilhos de captura das alterações.
if (( ! simular )); then
    cd "$CP_APP"
fi
rodar "$CP_PHP" "$CP_APP/artisan" migrate --force
rodar "$CP_PHP" "$CP_APP/artisan" sincronizacao:gatilhos

# 4) Servidor embutido do PHP. "php -S" usa as variáveis deste processo (artisan serve não usa).
rodar_em_segundo_plano "$CP_PHP" -S "0.0.0.0:$MODO_IGREJA_PORTA" -t public public/index.php

# 5) Sincronização: o agendador do Laravel roda a cada minuto; a tarefa decide quando sincronizar.
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
