<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Sync\ArquivoCorrompidoException;
use App\Sync\AuditLog;
use App\Sync\AuditLogSync;
use App\Sync\HttpAuditTransport;
use App\Sync\HttpFileTransport;
use App\Sync\HttpSyncTransport;
use App\Sync\NotebookAgent;
use App\Sync\SensitiveFieldException;
use App\Sync\SpreadsheetStore;
use App\Sync\SpreadsheetSync;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

/**
 * Sincroniza este notebook com o servidor: registros, planilhas e histórico de auditoria.
 * Roda sozinho quando há internet (agendado em routes/console.php).
 */
class RunSyncCommand extends Command
{
    protected $signature = 'sincronizacao:executar';

    protected $description = 'Envia e recebe as alterações, as planilhas e a auditoria deste notebook';

    public function handle(NotebookAgent $agente, SpreadsheetSync $planilhas, AuditLogSync $auditoria): int
    {
        $url = config('modo_igreja.servidor_url');
        $token = config('modo_igreja.aparelho_token');

        if (! is_string($url) || $url === '' || ! is_string($token) || $token === '') {
            $this->error('Configure MODO_IGREJA_SERVIDOR_URL e MODO_IGREJA_APARELHO_TOKEN antes de sincronizar.');

            return self::FAILURE;
        }

        try {
            $resumo = $agente->executar(new HttpSyncTransport($url, $token));
            $arquivos = $planilhas->executar(
                new HttpFileTransport($url, $token),
                new SpreadsheetStore((string) config('sincronizacao.pasta_planilhas')),
            );
            $historico = $auditoria->executar(
                new HttpAuditTransport($url, $token),
                new AuditLog((string) config('sincronizacao.arquivo_auditoria')),
            );
        } catch (ConnectionException) {
            // Sem internet ou servidor fora: nada se perde, porque os cursores só avançam com sucesso.
            $this->warn('Sem conexão com o servidor agora. Tudo será enviado na próxima tentativa.');

            return self::SUCCESS;
        } catch (RequestException $erro) {
            $this->error("O servidor respondeu com erro {$erro->response->status()}. Nada se perdeu: a sincronização tenta de novo depois.");

            return self::FAILURE;
        } catch (SensitiveFieldException | ArquivoCorrompidoException $erro) {
            $this->error($erro->getMessage());

            return self::FAILURE;
        }

        $this->info("Registros: enviados {$resumo['enviadas']}, recebidos {$resumo['recebidas']}.");
        $this->info("Planilhas: enviadas {$arquivos['enviadas']}, recebidas {$arquivos['recebidas']}, iguais {$arquivos['iguais']}.");
        $this->info("Auditoria: enviadas {$historico['enviadas']}, recebidas {$historico['recebidas']}.");

        foreach ($arquivos['conflitos'] as $nome) {
            $this->warn("A planilha {$nome} tem conteúdo diferente nos dois lados. Nada foi trocado.");
        }

        foreach ($resumo['erros'] as $recusada) {
            $this->error("Recusada pelo servidor: {$recusada['motivo']} (alteração {$recusada['uuid']}).");
        }

        return $resumo['erros'] === [] ? self::SUCCESS : self::FAILURE;
    }
}
