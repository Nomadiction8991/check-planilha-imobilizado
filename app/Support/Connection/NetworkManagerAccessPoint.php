<?php

declare(strict_types=1);

namespace App\Support\Connection;

use App\Contracts\AccessPointInterface;
use App\Contracts\CommandRunnerInterface;
use RuntimeException;

/**
 * Ponto de acesso no Linux, pelo NetworkManager (comando nmcli).
 *
 * A senha vai como argumento do nmcli e nunca aparece nas mensagens de erro.
 */
final class NetworkManagerAccessPoint implements AccessPointInterface
{
    public function __construct(
        private readonly CommandRunnerInterface $comandos,
        private readonly string $nomeDaConexao = 'CheckPlanilha-Igreja',
    ) {
    }

    public function ligar(string $nomeDaRede, string $senha): void
    {
        $adaptador = $this->adaptadorWifi();

        // Apaga a versão anterior do ponto de acesso. Se ela não existir, não há problema.
        $this->comandos->executar(['nmcli', 'connection', 'delete', 'id', $this->nomeDaConexao]);

        $resultado = $this->comandos->executar([
            'nmcli', 'device', 'wifi', 'hotspot',
            'ifname', $adaptador,
            'con-name', $this->nomeDaConexao,
            'ssid', $nomeDaRede,
            'band', 'bg',
            'password', $senha,
        ]);

        if ($resultado['codigo'] !== 0) {
            throw new RuntimeException(
                'Não foi possível ligar o ponto de acesso. ' . str_replace($senha, '********', $resultado['saida']),
            );
        }
    }

    public function desligar(): void
    {
        if (! $this->estaLigado()) {
            return;
        }

        $resultado = $this->comandos->executar(['nmcli', 'connection', 'down', 'id', $this->nomeDaConexao]);

        if ($resultado['codigo'] !== 0) {
            throw new RuntimeException('Não foi possível desligar o ponto de acesso. ' . $resultado['saida']);
        }
    }

    public function estaLigado(): bool
    {
        $resultado = $this->comandos->executar(['nmcli', '-t', '-f', 'NAME', 'connection', 'show', '--active']);

        return $resultado['codigo'] === 0
            && in_array($this->nomeDaConexao, $this->linhas($resultado['saida']), true);
    }

    public function enderecoDaRede(): ?string
    {
        if (! $this->estaLigado()) {
            return null;
        }

        $adaptador = $this->adaptadorWifiOuNull();

        if ($adaptador === null) {
            return null;
        }

        $resultado = $this->comandos->executar(['nmcli', '-g', 'IP4.ADDRESS', 'device', 'show', $adaptador]);
        $primeiroEndereco = $this->linhas($resultado['saida'])[0] ?? '';

        return $primeiroEndereco === '' ? null : explode('/', $primeiroEndereco)[0];
    }

    private function adaptadorWifi(): string
    {
        $adaptador = $this->adaptadorWifiOuNull();

        if ($adaptador === null) {
            throw new RuntimeException(
                'Nenhum adaptador Wi-Fi foi encontrado neste computador. O modo offline precisa de Wi-Fi.',
            );
        }

        return $adaptador;
    }

    private function adaptadorWifiOuNull(): ?string
    {
        $resultado = $this->comandos->executar(['nmcli', '-t', '-f', 'DEVICE,TYPE', 'device']);

        if ($resultado['codigo'] !== 0) {
            throw new RuntimeException(
                'O NetworkManager (comando nmcli) não respondeu. Confira se ele está instalado e ligado.',
            );
        }

        foreach ($this->linhas($resultado['saida']) as $linha) {
            [$dispositivo, $tipo] = array_pad(explode(':', $linha, 2), 2, '');

            if ($tipo === 'wifi' && $dispositivo !== '') {
                return $dispositivo;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function linhas(string $saida): array
    {
        $linhas = array_map('trim', explode("\n", $saida));

        return array_values(array_filter($linhas, fn (string $linha): bool => $linha !== ''));
    }
}
