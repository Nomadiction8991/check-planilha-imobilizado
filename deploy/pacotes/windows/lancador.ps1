#Requires -Version 5.1
<#
.SYNOPSIS
  Lançador do Check Planilha no notebook da igreja (Windows, modo local).

.DESCRIPTION
  Segue a mesma sequência do lancador.sh do Linux: cria a configuração na primeira vez,
  sobe o banco local (MySQL 8.4) só em 127.0.0.1, prepara o sistema, liga o servidor
  embutido do PHP e a rotina de sincronização. A senha do banco vai só para o ambiente
  deste processo, não para a linha de comando.

.PARAMETER Simular
  Mostra os comandos que rodaria, sem iniciar nada. Usado nos testes.

.NOTES
  SEM TESTE REAL NO WINDOWS. Foi validado em PowerShell 7 no Linux, com programas falsos
  (deploy/pacotes/windows/validacao/validar-powershell.sh). Falta rodar numa máquina Windows.
#>
[CmdletBinding()]
param(
    [switch]$Simular
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$base = $PSScriptRoot
$php = Join-Path $base 'php\php.exe'
$mysqld = Join-Path $base 'mysql\bin\mysqld.exe'
$mysql = Join-Path $base 'mysql\bin\mysql.exe'
$mysqladmin = Join-Path $base 'mysql\bin\mysqladmin.exe'
$app = Join-Path $base 'app'
# Pasta das extensões do PHP em caminho absoluto. O php.ini guarda um caminho relativo, que
# depende da pasta de onde o programa é chamado.
$extensoes = Join-Path $base 'php\ext'

# Certificados do pacote, para o HTTPS da sincronização (o PHP não acha os do Windows sozinho).
$env:SSL_CERT_FILE = Join-Path $base 'php\cacert.pem'

$dados = if ($env:CP_DADOS) {
    $env:CP_DADOS
} elseif ($env:LOCALAPPDATA) {
    Join-Path $env:LOCALAPPDATA 'CheckPlanilhaIgreja'
} else {
    Join-Path $HOME 'CheckPlanilhaIgreja'
}
$ambiente = Join-Path $dados 'modo-igreja.env'
$pastaBanco = Join-Path $dados 'mysql'

# Escreve direto na tela. Write-Output misturaria o texto com o valor devolvido pela função.
function Mostrar-Comando {
    param([string[]]$Comando)
    Write-Host ($Comando -join ' ')
}

# Todo PHP do pacote roda com a pasta de extensões em caminho absoluto.
function Argumentos-Php {
    param([string[]]$Argumentos)
    return @('-d', "extension_dir=$extensoes") + $Argumentos
}

# Start-Process junta a lista de argumentos com espaços e sem aspas. Quem tem espaço
# (ex.: C:\Program Files) precisa ir entre aspas, senão o programa recebe o caminho cortado.
function Juntar-Argumentos {
    param([string[]]$Argumentos)
    return (($Argumentos | ForEach-Object {
        if ($_ -match '\s') { '"' + $_ + '"' } else { $_ }
    }) -join ' ')
}

# Roda o programa e para se ele falhar. Em modo simulação, só mostra o comando.
function Executar-Comando {
    param([string]$Programa, [string[]]$Argumentos)

    if ($Simular) {
        Mostrar-Comando (@($Programa) + $Argumentos)
        return
    }

    & $Programa @Argumentos
    if ($LASTEXITCODE -ne 0) {
        throw "O comando falhou (código $LASTEXITCODE): $Programa"
    }
}

# Roda o programa, descarta a saída e devolve o código de saída. Texto que o programa escreve no
# erro não pode parar o script: no Windows PowerShell isso vira erro quando a preferência é Stop.
# É o caso do mysqladmin ping, que reclama enquanto o banco ainda está subindo.
function Executar-Silencioso {
    param([string]$Programa, [string[]]$Argumentos)

    $preferenciaAnterior = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    try {
        & $Programa @Argumentos *> $null
    }
    finally {
        $ErrorActionPreference = $preferenciaAnterior
    }
    return $LASTEXITCODE
}

# Sobe um programa em segundo plano e devolve o processo, para encerrar depois.
function Iniciar-Em-Segundo-Plano {
    param([string]$Programa, [string[]]$Argumentos, [string]$Pasta)

    if ($Simular) {
        Mostrar-Comando (@($Programa) + $Argumentos + '&')
        return $null
    }

    $parametros = @{
        FilePath = $Programa
        ArgumentList = (Juntar-Argumentos $Argumentos)
        WorkingDirectory = $Pasta
        PassThru = $true
    }
    # A janela oculta só existe no Windows. Em outros sistemas o parâmetro é recusado.
    if ([Environment]::OSVersion.Platform -eq 'Win32NT') {
        $parametros['WindowStyle'] = 'Hidden'
    }
    return Start-Process @parametros
}

# Lê o arquivo de configuração (NOME=valor, com ou sem aspas) para o ambiente deste processo.
function Carregar-Ambiente {
    param([string]$Arquivo)

    foreach ($linha in Get-Content -LiteralPath $Arquivo) {
        if ($linha -match '^\s*([A-Z_][A-Z0-9_]*)=(.*)$') {
            $nome = $Matches[1]
            $valor = $Matches[2].Trim() -replace '^([''"])(.*)\1$', '$2'
            [Environment]::SetEnvironmentVariable($nome, $valor, 'Process')
        }
    }
}

# 1) Configuração: criada só na primeira vez.
if (-not (Test-Path -LiteralPath $ambiente)) {
    Executar-Comando $php (Argumentos-Php @("$app\artisan", 'modo-igreja:configurar', "--arquivo=$ambiente"))
}
if (-not $Simular) {
    Carregar-Ambiente $ambiente
}

$dbPorta = if ($env:DB_PORT) { $env:DB_PORT } else { '3307' }
$dbNome = if ($env:DB_DATABASE) { $env:DB_DATABASE } else { 'check_planilha' }
$dbUsuario = if ($env:DB_USERNAME) { $env:DB_USERNAME } else { 'check_planilha' }
$dbSenha = if ($env:DB_PASSWORD) { $env:DB_PASSWORD } else { 'senha-da-simulacao' }
$porta = if ($env:MODO_IGREJA_PORTA) { $env:MODO_IGREJA_PORTA } else { '8080' }

# 2) Banco local: primeiro sobe, depois cria o banco e o usuário do sistema (repetir é seguro).
if (-not (Test-Path -LiteralPath (Join-Path $pastaBanco 'mysql'))) {
    Executar-Comando $mysqld @('--initialize-insecure', "--basedir=$base\mysql", "--datadir=$pastaBanco")
}

$processoBanco = Iniciar-Em-Segundo-Plano $mysqld @(
    "--basedir=$base\mysql",
    "--datadir=$pastaBanco",
    "--port=$dbPorta",
    '--bind-address=127.0.0.1',
    '--skip-log-bin',
    "--log-error=$dados\mysql.log",
    '--mysqlx=OFF'
) $base

if (-not $Simular) {
    for ($tentativa = 0; $tentativa -lt 60; $tentativa++) {
        if ((Executar-Silencioso $mysqladmin @('-h', '127.0.0.1', '-P', $dbPorta, '-uroot', 'ping')) -eq 0) { break }
        Start-Sleep -Seconds 1
    }
}

$sqlBanco = 'CREATE DATABASE IF NOT EXISTS `{0}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; ' +
    "CREATE USER IF NOT EXISTS '{1}'@'127.0.0.1' IDENTIFIED BY '{2}'; " +
    "ALTER USER '{1}'@'127.0.0.1' IDENTIFIED BY '{2}'; " +
    'GRANT ALL PRIVILEGES ON `{0}`.* TO ''{1}''@''127.0.0.1'';'
$sqlBanco = $sqlBanco -f $dbNome, $dbUsuario, $dbSenha
Executar-Comando $mysql @('-h', '127.0.0.1', '-P', $dbPorta, '-uroot', '-e', $sqlBanco)

# 3) Sistema: tabelas e gatilhos de captura das alterações.
Executar-Comando $php (Argumentos-Php @("$app\artisan", 'migrate', '--force'))
Executar-Comando $php (Argumentos-Php @("$app\artisan", 'sincronizacao:gatilhos'))

# 4) Servidor embutido do PHP. Usa as variáveis deste processo (o artisan serve não usa).
$processoServidor = Iniciar-Em-Segundo-Plano $php (Argumentos-Php @('-S', "0.0.0.0:$porta", '-t', 'public', 'public\index.php')) $app

# 5) Sincronização: o agendador do Laravel roda a cada minuto. A tarefa decide quando sincronizar.
if ($Simular) {
    Mostrar-Comando @($php, "$app\artisan", 'schedule:run', '(a cada 60 segundos, em laço)')
    Mostrar-Comando @('start', "http://localhost:$porta")
    return
}

Start-Process "http://localhost:$porta" | Out-Null
Write-Host ''
Write-Host "O sistema está ligado em http://localhost:$porta."
Write-Host 'Deixe esta janela aberta enquanto usar o sistema.'

try {
    while ($true) {
        Executar-Silencioso $php (Argumentos-Php @("$app\artisan", 'schedule:run')) | Out-Null
        Start-Sleep -Seconds 60
    }
}
finally {
    Executar-Silencioso $mysqladmin @('-h', '127.0.0.1', '-P', $dbPorta, '-uroot', 'shutdown') | Out-Null
    foreach ($processo in @($processoServidor, $processoBanco)) {
        if ($null -ne $processo -and -not $processo.HasExited) {
            Stop-Process -Id $processo.Id -Force
        }
    }
}
