<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Sync\CopyImporter;
use App\Sync\HttpSyncTransport;
use App\Sync\SyncState;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Baixa a cópia completa do servidor para este notebook. Só funciona na primeira vez,
 * porque a cópia substitui os dados locais.
 */
class SyncCopyCommand extends Command
{
    protected $signature = 'sincronizacao:copia';

    protected $description = 'Baixa a cópia completa do servidor para este computador (só na primeira vez)';

    public function handle(CopyImporter $importador, SyncState $estado): int
    {
        $url = config('modo_igreja.servidor_url');
        $token = config('modo_igreja.aparelho_token');

        if (! is_string($url) || $url === '' || ! is_string($token) || $token === '') {
            $this->error('Configure MODO_IGREJA_SERVIDOR_URL e MODO_IGREJA_APARELHO_TOKEN antes de baixar a cópia.');

            return self::FAILURE;
        }

        if ($estado->definido(SyncState::ULTIMO_RECEBIDO)) {
            $this->error('Este computador já tem a cópia. Use sincronizacao:executar para atualizar.');

            return self::FAILURE;
        }

        $temAlteracoesPendentes = DB::table((string) config('sincronizacao.tabela_registro'))
            ->where('origem', 'local')
            ->exists();

        if ($temAlteracoesPendentes) {
            $this->error('Há alterações deste computador que ainda não foram enviadas. Sincronize antes de baixar a cópia.');

            return self::FAILURE;
        }

        try {
            $transporte = new HttpSyncTransport($url, $token);
            // Primeiro a faixa de números deste notebook, depois a cópia: os itens copiados não mudam a faixa.
            $importador->prepararNumeracao($transporte->faixaInicial());
            $importador->importar($transporte->copia());
        } catch (ConnectionException) {
            $this->warn('Sem conexão com o servidor agora. Tente de novo quando a internet voltar.');

            return self::FAILURE;
        } catch (RequestException $erro) {
            $this->error("O servidor respondeu com erro {$erro->response->status()}. Tente de novo mais tarde.");

            return self::FAILURE;
        } catch (QueryException) {
            // A mensagem técnica do banco não sai aqui: ela pode trazer trechos de SQL.
            $this->error('Não foi possível gravar a cópia neste computador. Confira se o banco local está ligado.');

            return self::FAILURE;
        } catch (RuntimeException $erro) {
            $this->error($erro->getMessage());

            return self::FAILURE;
        }

        $this->info('Cópia baixada e gravada neste computador.');

        return self::SUCCESS;
    }
}
