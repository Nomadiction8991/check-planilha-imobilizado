<?php

declare(strict_types=1);

namespace App\Support\Connection;

use App\Contracts\AccessPointInterface;
use App\Contracts\CommandRunnerInterface;
use RuntimeException;

/**
 * Ponto de acesso no Windows, pela API nativa de compartilhamento de internet móvel (PowerShell).
 *
 * A senha vai só pela variável de ambiente do processo. O script gravado em disco não tem a senha.
 *
 * ATENÇÃO: esta classe ainda não foi testada numa máquina Windows. A API costuma depender de um
 * perfil de internet, e sem internet ela pode recusar a ligação. Testar antes de liberar a versão Windows.
 */
final class WindowsAccessPoint implements AccessPointInterface
{
    private const IP_PADRAO_DO_WINDOWS = '192.168.137.1';

    public function __construct(
        private readonly CommandRunnerInterface $comandos,
        private readonly string $pastaDeTrabalho,
    ) {
    }

    public function ligar(string $nomeDaRede, string $senha): void
    {
        $resultado = $this->rodar($this->scriptLigar(), [
            'CP_REDE_NOME' => $nomeDaRede,
            'CP_REDE_SENHA' => $senha,
        ]);

        if ($resultado['codigo'] !== 0) {
            throw new RuntimeException(
                'Não foi possível ligar o ponto de acesso do Windows. ' . str_replace($senha, '********', $resultado['saida']),
            );
        }
    }

    public function desligar(): void
    {
        $resultado = $this->rodar($this->scriptDesligar());

        if ($resultado['codigo'] !== 0) {
            throw new RuntimeException('Não foi possível desligar o ponto de acesso do Windows. ' . $resultado['saida']);
        }
    }

    public function estaLigado(): bool
    {
        $resultado = $this->rodar($this->scriptEstado());

        return $resultado['codigo'] === 0 && str_contains($resultado['saida'], 'LIGADO');
    }

    public function enderecoDaRede(): ?string
    {
        return $this->estaLigado() ? self::IP_PADRAO_DO_WINDOWS : null;
    }

    /**
     * Script que liga o ponto de acesso. Lê o nome e a senha de CP_REDE_NOME e CP_REDE_SENHA.
     */
    public function scriptLigar(): string
    {
        return $this->cabecalho() . <<<'PS'
$configuracao = $tetheramento.GetCurrentAccessPointConfiguration()
$configuracao.Ssid = $env:CP_REDE_NOME
$configuracao.Passphrase = $env:CP_REDE_SENHA
AguardarAcao ($tetheramento.ConfigureAccessPointAsync($configuracao))
$resultado = AguardarResultado ($tetheramento.StartTetheringAsync()) ([Windows.Networking.NetworkOperators.NetworkOperatorTetheringOperationResult])
if ($resultado.Status -ne 'Success') { Write-Output ('Recusado: ' + $resultado.Status); exit 1 }
Write-Output 'LIGADO'
PS;
    }

    private function scriptDesligar(): string
    {
        return $this->cabecalho() . <<<'PS'
$resultado = AguardarResultado ($tetheramento.StopTetheringAsync()) ([Windows.Networking.NetworkOperators.NetworkOperatorTetheringOperationResult])
if ($resultado.Status -ne 'Success') { Write-Output ('Recusado: ' + $resultado.Status); exit 1 }
Write-Output 'DESLIGADO'
PS;
    }

    private function scriptEstado(): string
    {
        return $this->cabecalho() . <<<'PS'
if ($tetheramento.TetheringOperationalState -eq 'On') { Write-Output 'LIGADO' } else { Write-Output 'DESLIGADO' }
PS;
    }

    /**
     * Carrega as classes do Windows e prepara as funções que esperam as operações assíncronas.
     */
    private function cabecalho(): string
    {
        return <<<'PS'
$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Runtime.WindowsRuntime
[void][Windows.Networking.Connectivity.NetworkInformation, Windows.Networking.Connectivity, ContentType = WindowsRuntime]
[void][Windows.Networking.NetworkOperators.NetworkOperatorTetheringManager, Windows.Networking.NetworkOperators, ContentType = WindowsRuntime]
$metodos = [System.WindowsRuntimeSystemExtensions].GetMethods()
$tarefaSemResultado = $metodos | Where-Object { $_.Name -eq 'AsTask' -and $_.GetParameters().Count -eq 1 -and $_.GetParameters()[0].ParameterType.Name -eq 'IAsyncAction' } | Select-Object -First 1
$tarefaComResultado = $metodos | Where-Object { $_.Name -eq 'AsTask' -and $_.GetParameters().Count -eq 1 -and $_.GetParameters()[0].ParameterType.Name -eq 'IAsyncOperation`1' } | Select-Object -First 1
function AguardarAcao($operacao) {
    $tarefa = $tarefaSemResultado.Invoke($null, @($operacao))
    $tarefa.Wait(-1) | Out-Null
}
function AguardarResultado($operacao, $tipo) {
    $tarefa = $tarefaComResultado.MakeGenericMethod($tipo).Invoke($null, @($operacao))
    $tarefa.Wait(-1) | Out-Null
    return $tarefa.Result
}
$perfil = [Windows.Networking.Connectivity.NetworkInformation]::GetInternetConnectionProfile()
$tetheramento = [Windows.Networking.NetworkOperators.NetworkOperatorTetheringManager]::CreateFromConnectionProfile($perfil)
PS . "\n";
    }

    /**
     * @param  array<string, string>  $ambiente
     * @return array{codigo: int, saida: string}
     */
    private function rodar(string $script, array $ambiente = []): array
    {
        $caminho = $this->pastaDeTrabalho . '/cp-rede-' . bin2hex(random_bytes(6)) . '.ps1';

        // BOM do UTF-8: o Windows PowerShell só lê acentos corretamente com ele.
        file_put_contents($caminho, "\xEF\xBB\xBF" . $script);

        try {
            return $this->comandos->executar(
                ['powershell.exe', '-NoProfile', '-NonInteractive', '-ExecutionPolicy', 'Bypass', '-File', $caminho],
                $ambiente,
            );
        } finally {
            @unlink($caminho);
        }
    }
}
