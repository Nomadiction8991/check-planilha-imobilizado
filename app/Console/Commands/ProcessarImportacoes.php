<?php
/**
 * Script para processar importações pendentes do checkplanilha.
 * 
 * Uso:
 *   php artisan checkplanilha:processar-importacoes
 *   php artisan checkplanilha:processar-importacoes 23
 *   php artisan checkplanilha:processar-importacoes --todas
 * 
 * Este comando processa importações que estão "aguardando" sem precisar
 * passar pelo fluxo do navegador (preview/save/confirm).
 */

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\CsvParserService;
use App\Services\ImportacaoService;
use Illuminate\Console\Command;
use Throwable;

class ProcessarImportacoes extends Command
{
    protected $signature = 'checkplanilha:processar-importacoes
                           {importacao? : ID da importação específica}
                           {--todas : Processa todas as importações pendentes}';

    protected $description = 'Processa importações pendentes (aguardando) diretamente sem o fluxo web';

    public function handle(ImportacaoService $importService, CsvParserService $csvParser): int
    {
        $importId = $this->argument('importacao');
        $todas = $this->option('todas');

        if ($importId) {
            $importacoes = [(int) $importId];
        } elseif ($todas) {
            try {
                $importacoes = $this->getPendingImports();
            } catch (Throwable $e) {
                // A lista de pendentes é o que define o batch inteiro. Se a
                // consulta quebra, nada foi processado — tratar o erro como
                // lista vazia faria o comando anunciar "nenhuma importação
                // pendente" e sair com 0, sinalizando sucesso ao cron/CI.
                $this->error('Erro ao buscar importações: ' . $e->getMessage());

                // report() manda a causa para o log/handler da aplicação.
                // Sem isso o rastro existia só no stdout do cron.
                report($e);

                return Command::FAILURE;
            }
        } else {
            $importacoes = [$this->askId()];
        }

        if (empty($importacoes)) {
            $this->warn('Nenhuma importação pendente encontrada.');
            return 0;
        }

        $total = 0;
        $falhas = 0;

        foreach ($importacoes as $id) {
            $resultado = $this->processSingle($id, $importService, $csvParser);
            $total += $resultado['total'];
            $falhas += $resultado['falhou'];
        }

        $this->info("Total: {$total} registros importados.");

        // Um importador só confia no exit code. Se qualquer importação falhou,
        // o processo precisa sinalizar falha — antes ele devolvia 0 mesmo
        // imprimindo "❌", e o runner marcava a execução como sucesso.
        return $falhas > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Processa uma importação e devolve o resultado sinalizando se falhou.
     *
     * @return array{total: int, falhou: int}
     */
    private function processSingle(int $importId, ImportacaoService $importService, CsvParserService $csvParser): array
    {
        $this->line("Processando importação #{$importId}...");

        $import = $importService->buscarProgresso($importId);
        if (!$import || ($import['status'] ?? '') !== 'aguardando') {
            $this->warn("  Status: " . ($import['status'] ?? 'nulo') . " — pulando.");
            return ['total' => 0, 'falhou' => 0];
        }

        $analise = $csvParser->carregarAnalise($importId);
        if (!$analise) {
            $this->error("  Análise não encontrada.");
            return ['total' => 0, 'falhou' => 1];
        }

        $actions = [];
        foreach ($analise['registros'] ?? [] as $reg) {
            $line = (string) ($reg['linha_csv'] ?? '');
            if ($line !== '' && ($reg['status'] ?? '') !== 'erro') {
                $actions[$line] = 'importar';
            }
        }

        $this->line("  Registros: " . count($analise['registros'] ?? []) . ", Ações: " . count($actions));

        try {
            $result = $importService->processarComAcoes($importId, $actions, $analise);
            $sucesso = $result['sucesso'] ?? 0;
            $erro = $result['erro'] ?? 0;
            $this->info("  ✅ {$sucesso} sucesso, {$erro} erros.");

            // Erros por registro são situação normal da importação (dados
            // inconsistentes na planilha) e ficam visíveis para o usuário em
            // import_erros. Não são falha do comando — só uma exceção é.
            return ['total' => $sucesso, 'falhou' => 0];
        } catch (Throwable $e) {
            $mensagem = $e->getMessage();

            $this->error("  ❌ " . $mensagem);

            // report() manda a causa para o log/handler da aplicação. Sem isso
            // o rastro existia só no stdout do cron, que ninguém lê depois.
            report($e);

            return ['total' => 0, 'falhou' => 1];
        }
    }

    /**
     * Devolve os IDs das importações aguardando, na conexão padrão.
     *
     * Não há catch: a consulta define o batch inteiro. Uma falha de banco
     * precisa subir para handle(), que imprime o erro, registra a exceção e
     * devolve exit code de falha. Quando este método engolia o erro e
     * devolvia [], o comando anunciava "nenhuma importação pendente" e saía
     * com 0 — sucesso falso enquanto nada tinha sido processado.
     *
     * @return int[]
     */
    private function getPendingImports(): array
    {
        // A conexao e sempre a padrao da aplicacao (config('database.default'),
        // via DB facade sem argumento). O cod anterior fixava 'pgsql': no
        // ambiente padrao (docker-compose + .env.example) so existe MySQL,
        // e a consulta falhava antes mesmo de achar os registros.
        $rows = \DB::connection(config('database.default'))
            ->table('importacoes')
            ->where('status', 'aguardando')
            ->orderBy('id')
            ->pluck('id')
            ->toArray();

        return array_map('intval', $rows);
    }

    private function askId(): int
    {
        $id = $this->ask('ID da importação');
        return max(0, (int) $id);
    }
}
