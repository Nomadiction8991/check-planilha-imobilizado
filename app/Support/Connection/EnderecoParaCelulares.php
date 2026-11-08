<?php

declare(strict_types=1);

namespace App\Support\Connection;

use App\Contracts\CommandRunnerInterface;
use Throwable;

final class EnderecoParaCelulares
{
    public function __construct(
        private readonly CommandRunnerInterface $comandos,
        private readonly OfflineModeService $modoOffline,
        private readonly string $sistema = PHP_OS_FAMILY,
    ) {
    }

    public function endereco(): ?string
    {
        try {
            $status = $this->modoOffline->status();
            if ($status['ligado']) {
                return $status['endereco_sistema'];
            }

            if ($this->sistema !== 'Linux') {
                return null;
            }

            $resultado = $this->comandos->executar(['ip', '-4', 'route', 'get', '1.1.1.1']);
            if ($resultado['codigo'] !== 0 || ! preg_match('/\bsrc\s+(\S+)/', $resultado['saida'], $encontrado)) {
                return null;
            }

            $ip = $encontrado[1];
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false || str_starts_with($ip, '127.')) {
                return null;
            }

            return 'http://' . $ip . ':' . (int) config('modo_igreja.porta');
        } catch (Throwable) {
            return null;
        }
    }
}
