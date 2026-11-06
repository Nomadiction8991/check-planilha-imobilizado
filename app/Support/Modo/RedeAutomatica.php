<?php

declare(strict_types=1);

namespace App\Support\Modo;

use App\Contracts\AccessPointInterface;
use App\Contracts\CommandRunnerInterface;
use App\Support\Connection\OfflineModeService;
use Illuminate\Support\Facades\File;
use RuntimeException;

final class RedeAutomatica
{
    private readonly string $caminho;
    private readonly string $sistema;

    public function __construct(
        private readonly CommandRunnerInterface $comandos,
        private readonly OfflineModeService $offline,
        ?string $caminho = null,
        ?string $sistema = null,
        private readonly ?AccessPointInterface $ponto = null,
        private readonly ?EstadoModo $estadoModo = null,
    ) {
        $this->caminho = $caminho ?? storage_path('app/modo-igreja/rede.json');
        $this->sistema = $sistema ?? PHP_OS_FAMILY;
    }

    public function verificar(): string
    {
        if (! config('modo_igreja.ativo') || $this->sistema !== 'Linux') {
            return 'nada';
        }

        File::ensureDirectoryExists(dirname($this->caminho));
        $trava = fopen($this->caminho.'.lock', 'c');
        if ($trava === false || ! flock($trava, LOCK_EX)) {
            throw new RuntimeException('Não foi possível bloquear o estado da rede própria.');
        }

        try {
            $estado = $this->ler();
            $rota = $this->comandos->executar(['ip', '-4', 'route', 'show', 'default']);
            if ($rota['codigo'] !== 0) {
                throw new RuntimeException('Não foi possível verificar a rota de rede.');
            }
            $ligado = ($this->ponto ?? app(AccessPointInterface::class))->estaLigado();
            $decisao = 'nada';
            $desligadoManualmente = $estado['ligada_pelo_sistema'] && ! $ligado;
            if ($desligadoManualmente) {
                $estado['ligada_pelo_sistema'] = false;
                $estado['falhas'] = 0;
            }
            if (trim($rota['saida']) !== '') {
                $estado['falhas'] = 0;
                if ($estado['ligada_pelo_sistema'] && $this->temRotaPorCabo($rota['saida'])
                    && ($this->estadoModo ?? app(EstadoModo::class))->estaOnline()) {
                    $this->offline->desligar();
                    $estado['ligada_pelo_sistema'] = false;
                    $decisao = 'desligar';
                }
            } elseif (! $desligadoManualmente) {
                $estado['falhas'] = min(3, $estado['falhas'] + 1);
                if ($estado['falhas'] >= 3 && ! $ligado) {
                    $this->offline->ligar();
                    $estado['ligada_pelo_sistema'] = true;
                    $decisao = 'ligar';
                }
            }
            File::replace($this->caminho, json_encode($estado, JSON_THROW_ON_ERROR));

            return $decisao;
        } finally {
            flock($trava, LOCK_UN);
            fclose($trava);
        }
    }

    /** @return array{falhas: int, ligada_pelo_sistema: bool} */
    private function ler(): array
    {
        $dados = is_file($this->caminho) ? json_decode((string) file_get_contents($this->caminho), true) : null;
        if (! is_array($dados) || ! is_int($dados['falhas'] ?? null) || $dados['falhas'] < 0
            || ! is_bool($dados['ligada_pelo_sistema'] ?? null)) {
            return ['falhas' => 0, 'ligada_pelo_sistema' => false];
        }

        return ['falhas' => $dados['falhas'], 'ligada_pelo_sistema' => $dados['ligada_pelo_sistema']];
    }

    private function temRotaPorCabo(string $rotas): bool
    {
        $dispositivos = $this->comandos->executar(['nmcli', '-t', '-f', 'DEVICE,TYPE', 'device']);
        if ($dispositivos['codigo'] !== 0) {
            throw new RuntimeException('Não foi possível verificar os dispositivos de rede.');
        }
        preg_match_all('/(?:^|\s)dev\s+(\S+)/m', $rotas, $encontrados);
        foreach (explode("\n", $dispositivos['saida']) as $linha) {
            [$dispositivo, $tipo] = array_pad(explode(':', trim($linha), 2), 2, '');
            if ($tipo === 'ethernet' && in_array($dispositivo, $encontrados[1], true)) {
                return true;
            }
        }

        return false;
    }
}
