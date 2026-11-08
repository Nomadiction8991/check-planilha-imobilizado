<?php

declare(strict_types=1);

namespace App\Support\Connection;

use App\Contracts\CommandRunnerInterface;
use Illuminate\Support\Facades\Cache;
use Throwable;

final class EnderecoParaCelulares
{
    /** Por quanto tempo (em segundos) o IP da rede principal fica guardado. */
    private const SEGUNDOS_NO_CACHE = 15;

    private const CHAVE_DO_CACHE = 'modo-igreja.ip-da-rota';

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

            $ip = $this->ipDaRota();
            if ($ip === null) {
                return null;
            }

            return 'http://' . $ip . ':' . (int) config('modo_igreja.porta');
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * IP que o notebook usa na rede principal, lido da tabela de rotas. Fica guardado por pouco tempo.
     */
    private function ipDaRota(): ?string
    {
        return Cache::remember(self::CHAVE_DO_CACHE, self::SEGUNDOS_NO_CACHE, function (): ?string {
            $resultado = $this->comandos->executar(['ip', '-4', 'route', 'get', '1.1.1.1']);
            if ($resultado['codigo'] !== 0 || ! preg_match('/\bsrc\s+(\S+)/', $resultado['saida'], $encontrado)) {
                return null;
            }

            $ip = $encontrado[1];
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false || str_starts_with($ip, '127.')) {
                return null;
            }

            return $ip;
        });
    }
}
