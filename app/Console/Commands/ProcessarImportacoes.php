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
            $importacoes = $this->getPendingImports();
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

    private function getPendingImports(): array
    {
        try {
            $rows = \DB::connection('pgsql')
                ->table('importacoes')
                ->where('status', 'aguardando')
                ->orderBy('id')
                ->pluck('id')
                ->toArray();

            return array_map('intval', $rows);
        } catch (\Throwable $e) {
            $this->error('Erro ao buscar importações: ' . $e->getMessage());
            return [];
        }
    }

    private function askId(): int
    {
        $id = $this->ask('ID da importação');
        return max(0, (int) $id);
    }
}
