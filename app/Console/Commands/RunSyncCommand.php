<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Sync\HttpSyncTransport;
use App\Sync\NotebookAgent;
use App\Sync\SensitiveFieldException;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

/**
 * Sincroniza este notebook com o servidor. Roda sozinho quando há internet (agendado em routes/console.php).
 */
class RunSyncCommand extends Command
{
    protected $signature = 'sincronizacao:executar';

    protected $description = 'Envia o que mudou neste notebook e recebe o que mudou no servidor';

    public function handle(NotebookAgent $agente): int
    {
        $url = config('modo_igreja.servidor_url');
        $token = config('modo_igreja.aparelho_token');

        if (! is_string($url) || $url === '' || ! is_string($token) || $token === '') {
            $this->error('Configure MODO_IGREJA_SERVIDOR_URL e MODO_IGREJA_APARELHO_TOKEN antes de sincronizar.');

            return self::FAILURE;
        }

        try {
            $resumo = $agente->executar(new HttpSyncTransport($url, $token));
        } catch (ConnectionException) {
            // Sem internet ou servidor fora: nada se perde, porque os cursores só avançam com sucesso.
            $this->warn('Sem conexão com o servidor agora. Tudo será enviado na próxima tentativa.');

            return self::SUCCESS;
        } catch (RequestException $erro) {
            $this->error("O servidor respondeu com erro {$erro->response->status()}. Nada se perdeu: a sincronização tenta de novo depois.");

            return self::FAILURE;
        } catch (SensitiveFieldException $erro) {
            $this->error($erro->getMessage());

            return self::FAILURE;
        }

        $this->info("Enviadas: {$resumo['enviadas']}. Recebidas: {$resumo['recebidas']}.");

        foreach ($resumo['erros'] as $recusada) {
            $this->error("Recusada pelo servidor: {$recusada['motivo']} (alteração {$recusada['uuid']}).");
        }

        return $resumo['erros'] === [] ? self::SUCCESS : self::FAILURE;
    }
}
