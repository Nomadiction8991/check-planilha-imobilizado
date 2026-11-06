#Requires -Version 5.1
<#
.SYNOPSIS
  Lançador do Check Planilha no notebook da igreja (Windows, modo local).

.DESCRIPTION
  Segue a mesma sequência do lancador.sh do Linux: cria a configuração na primeira vez,
  sobe o banco local (MySQL 8.4) só em 127.0.0.1, prepara o sistema, liga o servidor
  embutido do PHP e a rotina de sincronização.

  Senhas: na primeira vez, o administrador do banco (root) recebe uma senha gerada. A senha
  inicial do MySQL é trocada por ela, e o acesso fica num arquivo que só o usuário deste
  computador consegue ler. Nenhuma senha vai para a linha de comando, onde ela apareceria
  na lista de processos.

.PARAMETER Simular
  Mostra os comandos que rodaria, sem iniciar nada. Usado nos testes.

.PARAMETER LimiteInicializacaoSegundos
  Tempo máximo, em segundos, para criar o banco na primeira vez (padrão: 300). Passou disso, o
  lançador encerra o processo e mostra um aviso.

.NOTES
  SEM TESTE REAL NO WINDOWS. Foi validado em PowerShell 7 no Linux, com programas falsos
  (deploy/pacotes/windows/validacao/validar-powershell.sh). A troca de senha do MySQL 8.4 foi
  conferida com o binário do Linux. Falta rodar numa máquina Windows.
#>
[CmdletBinding()]
param(
    [switch]$Simular,
    # Tempo máximo, em segundos, para a inicialização do banco. Passou disso, o lançador encerra o processo.
    [int]$LimiteInicializacaoSegundos = 300
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$base = $PSScriptRoot
$php = Join-Path $base 'php\php.exe'
$mysqlBase = Join-Path $base 'mysql'
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
$logServidor = Join-Path $dados 'mysql.log'
# Acesso do administrador do banco (root). Criado na primeira vez e protegido para este usuário.
$arquivoAdmin = Join-Path $dados 'mysql-administrador.cnf'
# Arquivos da primeira configuração. Somem ao fim dela.
$arquivoInicial = Join-Path $dados 'mysql-inicial.cnf'
$logInicializacao = Join-Path $dados 'mysql-inicializacao.log'

# Letras e números, sem caracteres que exijam aspas em nenhum lugar.
$alfabeto = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789'

# Escreve na tela. Write-Output misturaria o texto com o valor devolvido pela função.
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

# Manda os comandos SQL pela entrada padrão do cliente do MySQL. Assim a senha não aparece na linha
# de comando. Em modo simulação, mostra só o comando, sem o SQL.
function Executar-Sql {
    param([string]$Programa, [string[]]$Argumentos, [string]$Sql)

    if ($Simular) {
        Mostrar-Comando (@($Programa) + $Argumentos + '< comandos SQL (senhas ocultas)')
        return
    }

    $Sql | & $Programa @Argumentos
    if ($LASTEXITCODE -ne 0) {
        throw "O comando SQL falhou (código $LASTEXITCODE)."
    }
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

# Cria o banco na primeira vez, com limite de tempo. Se passar do limite ou falhar, encerra o
# processo e apaga a pasta do banco pela metade. Essa pasta estava vazia antes desta execução
# (a checagem da primeira vez garante isso), então só tem o que esta execução criou.
function Inicializar-Banco {
    param([string[]]$Argumentos, [int]$Limite)

    if ($Simular) {
        Mostrar-Comando (@($mysqld) + $Argumentos)
        return
    }
    # Apaga o registro de uma tentativa anterior: a senha inicial tem que vir desta criação.
    Remove-Item -LiteralPath $logInicializacao -Force -ErrorAction SilentlyContinue
    $processo = Iniciar-Em-Segundo-Plano $mysqld $Argumentos $base
    if (-not $processo.WaitForExit($Limite * 1000)) {
        Stop-Process -Id $processo.Id -Force -ErrorAction SilentlyContinue
        Remove-Item -LiteralPath $pastaBanco -Recurse -Force -ErrorAction SilentlyContinue
        throw "A criação do banco não terminou em $Limite segundos, e o lançador encerrou o processo. Abra o programa de novo. Se o aviso voltar, fale com o suporte."
    }
    if ($processo.ExitCode -ne 0) {
        Remove-Item -LiteralPath $pastaBanco -Recurse -Force -ErrorAction SilentlyContinue
        throw "A criação do banco falhou (código $($processo.ExitCode)). O registro está em: $logInicializacao"
    }
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

# Gera uma senha aleatória com letras e números. Usa um gerador seguro e descarta os valores que
# deixariam algumas letras mais prováveis que outras.
function Gerar-Senha {
    param([int]$Tamanho = 32)

    $limite = [math]::Floor(256 / $alfabeto.Length) * $alfabeto.Length
    $gerador = [System.Security.Cryptography.RandomNumberGenerator]::Create()
    $senha = New-Object System.Text.StringBuilder
    try {
        while ($senha.Length -lt $Tamanho) {
            $sorteio = New-Object byte[] 1
            $gerador.GetBytes($sorteio)
            if ($sorteio[0] -lt $limite) {
                [void]$senha.Append($alfabeto[$sorteio[0] % $alfabeto.Length])
            }
        }
    }
    finally {
        $gerador.Dispose()
    }
    return $senha.ToString()
}

# Deixa o arquivo legível só pelo usuário que roda o lançador. Só age no Windows.
function Restringir-Arquivo {
    param([string]$Caminho)

    if ([Environment]::OSVersion.Platform -ne 'Win32NT') { return }
    & icacls.exe $Caminho /inheritance:r /grant:r "$($env:USERNAME):F" | Out-Null
    if ($LASTEXITCODE -ne 0) {
        throw "Não foi possível proteger o arquivo: $Caminho"
    }
}

# Grava um arquivo de acesso ao banco. A proteção vem antes da senha, para ela nunca ficar em um
# arquivo aberto. A senha vai entre aspas, com \ e " escapados, do jeito que o MySQL lê.
function Gravar-Acesso {
    param([string]$Caminho, [string]$Senha)

    $escapada = $Senha.Replace('\', '\\').Replace('"', '\"')
    $conteudo = "[client]`r`nuser=root`r`npassword=`"$escapada`"`r`n"
    New-Item -ItemType File -Path $Caminho -Force | Out-Null
    Restringir-Arquivo $Caminho
    [System.IO.File]::WriteAllText($Caminho, $conteudo, [System.Text.Encoding]::ASCII)
}

# Lê a senha inicial do MySQL, que o programa escreve no registro da primeira inicialização.
function Ler-Senha-Inicial {
    param([string]$Registro)

    $texto = Get-Content -LiteralPath $Registro -Raw
    $casamento = [regex]::Match($texto, 'temporary password is generated for root@localhost: (\S+)')
    if (-not $casamento.Success) {
        throw "Não encontrei a senha inicial do banco no registro: $Registro"
    }
    return $casamento.Groups[1].Value
}

# Argumentos para falar com o banco local. O arquivo de acesso vem primeiro, como o MySQL exige.
function Argumentos-Banco {
    param([string]$Acesso, [string]$Porta, [string[]]$Complemento = @())

    return @("--defaults-extra-file=$Acesso", '-h', '127.0.0.1', '-P', $Porta) + $Complemento
}

# Espera o banco responder, por até 60 segundos. O ping devolve 0 quando o servidor está no ar,
# mesmo se a senha for recusada, então aqui só importa que o servidor respondeu.
function Esperar-Banco {
    param([string]$Acesso, [string]$Porta)

    if ($Simular) {
        Mostrar-Comando (@($mysqladmin) + (Argumentos-Banco $Acesso $Porta @('ping')))
        return
    }
    for ($tentativa = 0; $tentativa -lt 60; $tentativa++) {
        if ((Executar-Silencioso $mysqladmin (Argumentos-Banco $Acesso $Porta @('ping'))) -eq 0) { return }
        Start-Sleep -Seconds 1
    }
}

# Primeira vez: entra com a senha inicial (que expira), troca pela senha gerada e guarda o acesso.
# A troca só é dada como feita quando o banco aceita o comando.
function Preparar-Administrador {
    param([string]$SenhaInicial, [string]$SenhaAdmin, [string]$Porta)

    Gravar-Acesso $arquivoInicial $SenhaInicial
    try {
        Esperar-Banco $arquivoInicial $Porta
        Executar-Sql $mysql (Argumentos-Banco $arquivoInicial $Porta @('--connect-expired-password')) "ALTER USER 'root'@'localhost' IDENTIFIED BY '$SenhaAdmin';"
    }
    finally {
        Remove-Item -LiteralPath $arquivoInicial -Force -ErrorAction SilentlyContinue
    }
    Gravar-Acesso $arquivoAdmin $SenhaAdmin
    Remove-Item -LiteralPath $logInicializacao -Force -ErrorAction SilentlyContinue
}

# Encerra o banco (com o acesso do administrador) e os processos que este lançador abriu.
function Encerrar-Servicos {
    if (-not $Simular -and (Test-Path -LiteralPath $arquivoAdmin)) {
        Executar-Silencioso $mysqladmin (Argumentos-Banco $arquivoAdmin $dbPorta @('shutdown')) | Out-Null
    }
    foreach ($processo in @($processoServidor, $processoBanco)) {
        if ($null -ne $processo -and -not $processo.HasExited) {
            Stop-Process -Id $processo.Id -Force
        }
    }
}

# 1) Configuração: criada só na primeira vez. Depois, o arquivo é protegido e carregado.
if (-not (Test-Path -LiteralPath $ambiente)) {
    if (-not $Simular) {
        # O gerador da configuração exige a pasta pronta. Na primeira vez ela ainda não existe.
        New-Item -ItemType Directory -Path $dados -Force | Out-Null
    }
    Executar-Comando $php (Argumentos-Php @("$app\artisan", 'modo-igreja:configurar', "--arquivo=$ambiente"))
}
if (-not $Simular) {
    Restringir-Arquivo $ambiente
    Carregar-Ambiente $ambiente
}

$dbPorta = if ($env:DB_PORT) { $env:DB_PORT } else { '3307' }
$dbNome = if ($env:DB_DATABASE) { $env:DB_DATABASE } else { 'check_planilha' }
$dbUsuario = if ($env:DB_USERNAME) { $env:DB_USERNAME } else { 'check_planilha' }
$dbSenha = if ($env:DB_PASSWORD) { $env:DB_PASSWORD } else { 'senha-da-simulacao' }
$porta = if ($env:MODO_IGREJA_PORTA) { $env:MODO_IGREJA_PORTA } else { '8080' }

$processoBanco = $null
$processoServidor = $null
try {
    # 2) Banco local. Na primeira vez, cria o banco com a senha inicial e troca pela senha do administrador.
    $primeiraVez = -not (Test-Path -LiteralPath $arquivoAdmin)
    if ($primeiraVez -and (Test-Path -LiteralPath $pastaBanco) -and (Get-ChildItem -LiteralPath $pastaBanco -Force | Select-Object -First 1)) {
        throw "A pasta do banco já tem dados, mas o acesso do administrador não está guardado ($arquivoAdmin). Não apague a pasta do banco: fale com o suporte."
    }
    $senhaInicial = $null
    $senhaAdmin = $null
    if ($primeiraVez) {
        $senhaAdmin = Gerar-Senha
        Inicializar-Banco @('--initialize', "--basedir=$mysqlBase", "--datadir=$pastaBanco", "--log-error=$logInicializacao") $LimiteInicializacaoSegundos
        if (-not $Simular) {
            Restringir-Arquivo $logInicializacao
            $senhaInicial = Ler-Senha-Inicial $logInicializacao
        }
    }

    $processoBanco = Iniciar-Em-Segundo-Plano $mysqld @(
        "--basedir=$mysqlBase",
        "--datadir=$pastaBanco",
        "--port=$dbPorta",
        '--bind-address=127.0.0.1',
        '--skip-log-bin',
        "--log-error=$logServidor",
        '--mysqlx=OFF'
    ) $base

    if ($primeiraVez) {
        if ($Simular) {
            Mostrar-Comando @('(cria o acesso inicial, espera o banco, troca a senha do administrador e guarda o acesso)')
        }
        else {
            Preparar-Administrador $senhaInicial $senhaAdmin $dbPorta
        }
    }
    elseif (-not $Simular) {
        Esperar-Banco $arquivoAdmin $dbPorta
    }

    # 3) Banco e usuário do sistema, com o acesso do administrador. Repetir é seguro.
    $sqlBanco = 'CREATE DATABASE IF NOT EXISTS `{0}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; ' +
        "CREATE USER IF NOT EXISTS '{1}'@'127.0.0.1' IDENTIFIED BY '{2}'; " +
        "ALTER USER '{1}'@'127.0.0.1' IDENTIFIED BY '{2}'; " +
        'GRANT ALL PRIVILEGES ON `{0}`.* TO ''{1}''@''127.0.0.1'';'
    $sqlBanco = $sqlBanco -f $dbNome, $dbUsuario, $dbSenha
    Executar-Sql $mysql (Argumentos-Banco $arquivoAdmin $dbPorta) $sqlBanco

    # 4) Sistema: tabelas e gatilhos de captura das alterações.
    Executar-Comando $php (Argumentos-Php @("$app\artisan", 'migrate', '--force'))
    Executar-Comando $php (Argumentos-Php @("$app\artisan", 'sincronizacao:gatilhos'))

    # 5) Servidor embutido do PHP. Usa as variáveis deste processo (o artisan serve não usa).
    $processoServidor = Iniciar-Em-Segundo-Plano $php (Argumentos-Php @('-S', "0.0.0.0:$porta", '-t', 'public', 'public\index.php')) $app

    # 6) Sincronização: o agendador do Laravel roda a cada minuto. A tarefa decide quando sincronizar.
    if ($Simular) {
        Mostrar-Comando @($php, "$app\artisan", 'schedule:run', '(a cada 60 segundos, em laço)')
        Mostrar-Comando @('start', "http://localhost:$porta")
        return
    }

    Start-Process "http://localhost:$porta" | Out-Null
    Write-Host ''
    Write-Host "O sistema está ligado em http://localhost:$porta."
    Write-Host 'Deixe esta janela aberta enquanto usar o sistema.'

    while ($true) {
        Executar-Silencioso $php (Argumentos-Php @("$app\artisan", 'schedule:run')) | Out-Null
        Start-Sleep -Seconds 60
    }
}
finally {
    Encerrar-Servicos
}
