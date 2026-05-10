<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\LegacySpreadsheetImportServiceInterface;
use App\Core\ConnectionManager;
use App\DTO\SpreadsheetImportUploadData;
use App\Models\Legacy\Administracao;
use App\Models\Legacy\Comum;
use App\Models\Legacy\Usuario;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use RuntimeException;
use Throwable;

class LegacySpreadsheetImportService implements LegacySpreadsheetImportServiceInterface
{
    public function __construct()
    {
        ConnectionManager::configure([
            'host' => (string) config('database.connections.mysql.host', '127.0.0.1'),
            'database' => (string) config('database.connections.mysql.database', ''),
            'username' => (string) config('database.connections.mysql.username', ''),
            'password' => (string) config('database.connections.mysql.password', ''),
            'charset' => (string) config('database.connections.mysql.charset', 'utf8mb4'),
            'port' => (string) config('database.connections.mysql.port', '3306'),
        ]);
    }

    public function responsibleUserOptions(): Collection
    {
        $scopeAdministrationIds = $this->currentAdministrationScopeIds();
        $columns = ['id', 'nome', 'email', 'administracao_id', 'tipo'];

        if (Schema::hasColumn('usuarios', 'administracoes_permitidas')) {
            $columns[] = 'administracoes_permitidas';
        }

        return Usuario::query()
            ->where('ativo', 1)
            ->when($scopeAdministrationIds !== null, function ($query) use ($scopeAdministrationIds) {
                // Filtra usuários que pertencem à administração ou que têm permissão explícita
                $query->where(function ($q) use ($scopeAdministrationIds) {
                    $q->whereIn('administracao_id', $scopeAdministrationIds);
                    
                    if (Schema::hasColumn('usuarios', 'administracoes_permitidas')) {
                        foreach ($scopeAdministrationIds as $id) {
                            $q->orWhereJsonContains('administracoes_permitidas', (int) $id);
                        }
                    }
                });
            })
            ->orderBy('nome')
            ->get($columns);
    }

    public function churchOptions(): Collection
    {
        return Comum::query()
            ->orderBy('codigo')
            ->get(['id', 'codigo', 'descricao']);
    }

    public function administrationOptions(): Collection
    {
        $scopeAdministrationIds = $this->currentAdministrationScopeIds();

        return Administracao::query()
            ->when(
                $scopeAdministrationIds !== null,
                static fn ($query) => $query->whereIn('id', $scopeAdministrationIds),
            )
            ->orderBy('descricao')
            ->get(['id', 'descricao']);
    }

    public function uploadAndAnalyze(SpreadsheetImportUploadData $data, UploadedFile $file): int
    {
        try {
            $fallbackChurchId = (int) ($data->churchId ?? 0);

            if (!Administracao::query()->whereKey($data->administrationId)->exists()) {
                throw new RuntimeException('A administração selecionada não está mais disponível.');
            }

            $scopeAdministrationIds = $this->currentAdministrationScopeIds();
            if (
                $scopeAdministrationIds !== null
                && !in_array($data->administrationId, $scopeAdministrationIds, true)
            ) {
                throw new RuntimeException('A administração selecionada não está disponível para este usuário.');
            }

            /** @var Usuario|null $responsibleUser */
            $responsibleUser = Usuario::query()
                ->select(['id', 'administracao_id', 'ativo', 'tipo'])
                ->find($data->responsibleUserId);

            if ($responsibleUser === null || (int) $responsibleUser->ativo !== 1) {
                throw new RuntimeException('Selecione um responsável válido.');
            }

            if (!$this->isUserAllowedForAdministration($responsibleUser, $data->administrationId)) {
                throw new RuntimeException('O responsável selecionado deve pertencer à mesma administração da importação.');
            }

            $importacaoService = new ImportacaoService();
            $csvParserService = new CsvParserService(null, (int) $data->administrationId);
            $destinationPath = $this->moveUploadedFile($file, $fallbackChurchId);

            $importacaoId = $importacaoService->iniciarImportacao(
                $data->responsibleUserId,
                $data->churchId,
                $data->administrationId,
                $file->getClientOriginalName(),
                $destinationPath,
            );

            $analise = $csvParserService->analisar($destinationPath, $fallbackChurchId);
            $csvParserService->salvarAnalise($importacaoId, $analise);

            return $importacaoId;
        } catch (Throwable $throwable) {
            throw new RuntimeException($this->friendlyErrorMessage($throwable->getMessage()), previous: $throwable);
        }
    }

    public function recentImports(?int $churchId, int $limit = 5): array
    {
        $connection = DB::connection('mysql');
        $administrationIds = $this->currentAdministrationScopeIds();
        $query = $connection->table('importacoes as i')
            ->leftJoin('usuarios as u', 'u.id', '=', 'i.usuario_id')
            ->leftJoin('comums as c', 'c.id', '=', 'i.comum_id')
            ->leftJoin('administracoes as a', 'a.id', '=', 'i.administracao_id')
            ->select([
                'i.id',
                'i.comum_id',
                'i.administracao_id',
                'i.arquivo_nome',
                'i.total_linhas',
                'i.linhas_processadas',
                'i.linhas_sucesso',
                'i.linhas_erro',
                'i.porcentagem',
                'i.status',
                'i.iniciada_em',
                'i.concluida_em',
                'i.created_at',
                'u.nome as usuario_responsavel_nome',
                'u.email as usuario_responsavel_email',
                'c.codigo as comum_codigo',
                'c.descricao as comum_descricao',
                'a.descricao as administracao_descricao',
            ])
            ->orderByDesc('i.id')
            ->limit(max(1, $limit));

        if ($administrationIds !== null) {
            $query->whereIn('i.administracao_id', $administrationIds);
        } elseif (($churchId ?? 0) > 0) {
            $query->where('i.comum_id', '=', $churchId);
        }

        return $query->get()
            ->map(static function (object $importacao): array {
                $churchCode = trim((string) ($importacao->comum_codigo ?? ''));
                $churchDescription = trim((string) ($importacao->comum_descricao ?? ''));
                $administrationDescription = trim((string) ($importacao->administracao_descricao ?? ''));

                return [
                    'id' => (int) ($importacao->id ?? 0),
                    'comum_id' => $importacao->comum_id !== null ? (int) $importacao->comum_id : null,
                    'administracao_id' => $importacao->administracao_id !== null ? (int) $importacao->administracao_id : null,
                    'arquivo_nome' => (string) ($importacao->arquivo_nome ?? ''),
                    'total_linhas' => (int) ($importacao->total_linhas ?? 0),
                    'linhas_processadas' => (int) ($importacao->linhas_processadas ?? 0),
                    'linhas_sucesso' => (int) ($importacao->linhas_sucesso ?? 0),
                    'linhas_erro' => (int) ($importacao->linhas_erro ?? 0),
                    'porcentagem' => (float) ($importacao->porcentagem ?? 0),
                    'status' => (string) ($importacao->status ?? 'aguardando'),
                    'iniciada_em' => $importacao->iniciada_em !== null ? (string) $importacao->iniciada_em : null,
                    'concluida_em' => $importacao->concluida_em !== null ? (string) $importacao->concluida_em : null,
                    'created_at' => $importacao->created_at !== null ? (string) $importacao->created_at : null,
                    'usuario_responsavel_nome' => (string) ($importacao->usuario_responsavel_nome ?? ''),
                    'usuario_responsavel_email' => (string) ($importacao->usuario_responsavel_email ?? ''),
                    'comum_codigo' => $churchCode,
                    'comum_descricao' => $churchDescription,
                    'administracao_descricao' => $administrationDescription,
                    'administracao_label' => $administrationDescription !== '' ? $administrationDescription : 'Sem administração',
                    'comum_label' => $churchCode !== '' || $churchDescription !== ''
                        ? trim($churchCode . ' - ' . $churchDescription, ' -')
                        : 'Sem igreja informada',
                    'data_referencia' => (string) (
                        $importacao->concluida_em
                        ?? $importacao->iniciada_em
                        ?? $importacao->created_at
                        ?? ''
                    ),
                ];
            })
            ->all();
    }

    public function loadPreview(int $importacaoId): ?array
    {
        try {
            $importacaoService = new ImportacaoService();
            $csvParserService = new CsvParserService();

            $importacao = $importacaoService->buscarProgresso($importacaoId);
            $analise = $csvParserService->carregarAnalise($importacaoId);

            if ($importacao === null || $analise === null) {
                return null;
            }

            $statusByChurch = [];
            $igrejasDetectadas = $this->buildChurchPreviewSummaries(
                (array) ($analise['registros'] ?? []),
                (int) ($importacao['comum_id'] ?? 0),
            );

            foreach (($analise['registros'] ?? []) as $registro) {
                $churchCode = (string) ($registro['dados_csv']['codigo_comum'] ?? '');
                $status = (string) ($registro['status'] ?? '');

                if ($churchCode === '') {
                    continue;
                }

                if (!isset($statusByChurch[$churchCode])) {
                    $statusByChurch[$churchCode] = CsvParserService::STATUS_SEM_ALTERACAO;
                }

                if ($status === CsvParserService::STATUS_NOVO) {
                    $statusByChurch[$churchCode] = CsvParserService::STATUS_NOVO;
                    continue;
                }

                if (
                    $status === CsvParserService::STATUS_ATUALIZAR
                    && $statusByChurch[$churchCode] !== CsvParserService::STATUS_NOVO
                ) {
                    $statusByChurch[$churchCode] = CsvParserService::STATUS_ATUALIZAR;
                }
            }

            return [
                'importacao' => $importacao,
                'analise' => $analise,
                'acoes_salvas' => Session::get($this->previewActionsKey($importacaoId), []),
                'igrejas_salvas' => Session::get($this->previewChurchesKey($importacaoId), []),
                'dependencias_salvas' => Session::get($this->previewDependenciesKey($importacaoId), []),
                'status_por_comum' => $statusByChurch,
                'igrejas_detectadas' => $igrejasDetectadas,
            ];
        } catch (Throwable $throwable) {
            throw new RuntimeException('Não foi possível carregar a prévia da importação.', previous: $throwable);
        }
    }

    public function savePreviewActions(int $importacaoId, array $acoes, array $igrejas, array $dependencias = []): array
    {
        $this->ensureImportExists($importacaoId);

        $savedActions = Session::get($this->previewActionsKey($importacaoId), []);
        foreach ($acoes as $line => $action) {
            if (in_array($action, [
                CsvParserService::ACAO_IMPORTAR,
                CsvParserService::ACAO_PULAR,
                CsvParserService::ACAO_EXCLUIR,
            ], true)) {
                $savedActions[(string) $line] = $action;
            }
        }

        $savedChurches = Session::get($this->previewChurchesKey($importacaoId), []);
        foreach ($igrejas as $churchCode => $action) {
            if (in_array($action, ['', 'importar', 'pular', 'personalizado'], true)) {
                $savedChurches[(string) $churchCode] = $action;
            }
        }

        $savedDependencies = Session::get($this->previewDependenciesKey($importacaoId), []);
        foreach ($dependencias as $churchDepKey => $action) {
            // chave formatada como "CHURCH_CODE:DEPENDENCY_NAME"
            if (in_array($action, ['', 'importar', 'pular'], true)) {
                $savedDependencies[(string) $churchDepKey] = $action;
            }
        }

        Session::put($this->previewActionsKey($importacaoId), $savedActions);
        Session::put($this->previewChurchesKey($importacaoId), $savedChurches);
        Session::put($this->previewDependenciesKey($importacaoId), $savedDependencies);

        return [
            'total_salvas' => count($savedActions) + count($savedChurches) + count($savedDependencies),
            'igrejas_salvas' => count($savedChurches),
            'dependencias_salvas' => count($savedDependencies),
        ];
    }

    public function applyBulkPreviewAction(int $importacaoId, string $acao): array
    {
        if (!in_array($acao, [CsvParserService::ACAO_IMPORTAR, CsvParserService::ACAO_PULAR], true)) {
            throw new RuntimeException('Ação em massa inválida.');
        }

        $this->ensureImportExists($importacaoId);

        $csvParserService = new CsvParserService();
        $analise = $csvParserService->carregarAnalise($importacaoId);

        if ($analise === null) {
            throw new RuntimeException('Análise da importação não encontrada.');
        }

        $actions = [];

        foreach (($analise['registros'] ?? []) as $registro) {
            $line = (string) ($registro['linha_csv'] ?? '');
            $status = (string) ($registro['status'] ?? 'erro');

            if ($line === '' || $status === 'erro') {
                continue;
            }

            $actions[$line] = $acao;
        }

        Session::put($this->previewActionsKey($importacaoId), $actions);

        return [
            'acao' => $acao,
            'total_aplicadas' => count($actions),
        ];
    }

    public function confirmImport(int $importacaoId, bool $importAll = true, array $acoes = [], array $igrejas = [], array $dependencias = []): array
    {
        $importacaoService = new ImportacaoService();
        $csvParserService = new CsvParserService();

        try {
            $importacao = $importacaoService->buscarProgresso($importacaoId);
            $analise = $csvParserService->carregarAnalise($importacaoId);

            if ($importacao !== null && (string) ($importacao['status'] ?? '') === 'concluida') {
                Session::forget($this->previewActionsKey($importacaoId));
                Session::forget($this->previewChurchesKey($importacaoId));
                Session::forget($this->confirmOptionsKey($importacaoId));

                return [
                    'sucesso' => (int) ($importacao['linhas_sucesso'] ?? 0),
                    'erro' => (int) ($importacao['linhas_erro'] ?? 0),
                    'status' => 'concluida',
                    'reutilizada' => true,
                ];
            }

            if ($analise === null || $importacao === null) {
                throw new RuntimeException('Análise da importação não encontrada.');
            }

            $savedChurches = Session::get($this->previewChurchesKey($importacaoId), []);
            $savedDependencies = Session::get($this->previewDependenciesKey($importacaoId), []);
            
            $churchActions = array_merge($savedChurches, $igrejas);
            $dependencyActions = array_merge($savedDependencies, $dependencias);
            
            $fallbackChurchId = (int) ($importacao['comum_id'] ?? 0);
            $actions = [];

            if ($importAll) {
                $actions = $this->buildConfirmActionsByChurch(
                    (array) ($analise['registros'] ?? []),
                    [],
                    [],
                    $fallbackChurchId,
                    true,
                );
            } else {
                $actions = $this->buildConfirmActionsByChurch(
                    (array) ($analise['registros'] ?? []),
                    $churchActions,
                    $dependencyActions,
                    $fallbackChurchId,
                    false,
                );
            }

            $resultado = $importacaoService->processarComAcoes($importacaoId, $actions, $analise);
            Session::forget($this->previewActionsKey($importacaoId));
            Session::forget($this->previewChurchesKey($importacaoId));
            Session::forget($this->previewDependenciesKey($importacaoId));

            try {
                $csvParserService->limparAnalise($importacaoId);
                $this->pruneStoredImportFiles($importacaoService, $csvParserService, 5);
            } catch (Throwable $cleanupThrowable) {
                report($cleanupThrowable);
            }

            return $resultado;
        } catch (Throwable $throwable) {
            if ($throwable instanceof RuntimeException) {
                try {
                    $importacao = $importacaoService->buscarProgresso($importacaoId);
                    if ($importacao !== null && (string) ($importacao['status'] ?? '') === 'concluida') {
                        Session::forget($this->previewActionsKey($importacaoId));
                        Session::forget($this->previewChurchesKey($importacaoId));
                        Session::forget($this->previewDependenciesKey($importacaoId));
                        Session::forget($this->confirmOptionsKey($importacaoId));

                        return [
                            'sucesso' => (int) ($importacao['linhas_sucesso'] ?? 0),
                            'erro' => (int) ($importacao['linhas_erro'] ?? 0),
                            'status' => 'concluida',
                            'reutilizada' => true,
                        ];
                    }
                } catch (Throwable) {
                    // Mantém o erro original abaixo.
                }

                throw $throwable;
            }

            try {
                $importacao = $importacaoService->buscarProgresso($importacaoId);
                if ($importacao !== null && (string) ($importacao['status'] ?? '') === 'concluida') {
                    Session::forget($this->previewActionsKey($importacaoId));
                    Session::forget($this->previewChurchesKey($importacaoId));
                    Session::forget($this->confirmOptionsKey($importacaoId));

                    return [
                        'sucesso' => (int) ($importacao['linhas_sucesso'] ?? 0),
                        'erro' => (int) ($importacao['linhas_erro'] ?? 0),
                        'status' => 'concluida',
                        'reutilizada' => true,
                    ];
                }
            } catch (Throwable) {
                // Mantém a mensagem genérica abaixo.
            }

            throw new RuntimeException('Falha ao concluir a importação.', previous: $throwable);
        }
    }

    /**
     * Remove arquivos de importações mais antigas, preservando apenas as $keep
     * últimas concluídas/erros ainda relevantes para consulta.
     */
    private function pruneStoredImportFiles(
        ImportacaoService $importacaoService,
        CsvParserService $csvParserService,
        int $keep = 5,
    ): int
    {
        $keep = max(0, $keep);

        $connection = DB::connection('mysql');
        $importIds = $connection->table('importacoes')
            ->select(['id'])
            ->whereIn('status', ['concluida', 'erro'])
            ->orderByRaw('COALESCE(concluida_em, created_at) DESC')
            ->orderByDesc('id')
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        $oldImportIds = array_slice($importIds, $keep);

        $removed = 0;

        foreach ($oldImportIds as $importacaoId) {
            $importacaoService->limparArquivo($importacaoId);
            $csvParserService->limparAnalise($importacaoId);
            $removed++;
        }

        return $removed;
    }

    /**
     * @param array<int, array<string, mixed>> $registros
     * @return array<int, array{
     *   chave: string,
     *   codigo: string,
     *   descricao: string,
     *   total: int,
     *   novos: int,
     *   atualizar: int,
     *   sem_alteracao: int,
     *   exclusoes: int,
     *   erros: int,
     *   status: string
     * }>
     */
    private function buildChurchPreviewSummaries(array $registros, int $fallbackChurchId): array
    {
        $fallbackChurch = $fallbackChurchId > 0
            ? Comum::query()->find($fallbackChurchId, ['id', 'codigo', 'descricao'])
            : null;

        $fallbackChurchCode = $fallbackChurch instanceof Comum
            ? strtoupper(trim((string) $fallbackChurch->codigo))
            : '';
        $fallbackChurchDescription = $fallbackChurch instanceof Comum
            ? trim((string) $fallbackChurch->descricao)
            : '';

        $groups = [];

        foreach ($registros as $registro) {
            $churchCode = $this->resolveChurchCodeForRecord($registro, $fallbackChurchCode);
            $dependencyName = strtoupper(trim((string) ($registro['dados_csv']['dependencia_descricao'] ?? $registro['dependencia'] ?? 'SEM DEPENDÊNCIA')));
            if ($dependencyName === '') {
                $dependencyName = 'SEM DEPENDÊNCIA';
            }

            $status = (string) ($registro['status'] ?? '');

            if (!isset($groups[$churchCode])) {
                $groups[$churchCode] = [
                    'chave' => $churchCode,
                    'codigo' => $churchCode === '__sem_localidade__' ? '' : $churchCode,
                    'descricao' => $churchCode === '__sem_localidade__'
                        ? ($fallbackChurchDescription !== ''
                            ? sprintf('Sem localidade detectada - usa %s', $fallbackChurchDescription)
                            : 'Sem localidade detectada')
                        : $churchCode,
                    'total' => 0,
                    'novos' => 0,
                    'atualizar' => 0,
                    'sem_alteracao' => 0,
                    'exclusoes' => 0,
                    'erros' => 0,
                    'status' => 'sem_alteracao',
                    'dependencias' => [],
                ];
            }

            if (!isset($groups[$churchCode]['dependencias'][$dependencyName])) {
                $groups[$churchCode]['dependencias'][$dependencyName] = [
                    'nome' => $dependencyName,
                    'total' => 0,
                    'novos' => 0,
                    'atualizar' => 0,
                    'sem_alteracao' => 0,
                    'exclusoes' => 0,
                    'erros' => 0,
                ];
            }

            $groups[$churchCode]['total']++;
            $groups[$churchCode]['dependencias'][$dependencyName]['total']++;

            switch ($status) {
                case CsvParserService::STATUS_NOVO:
                    $groups[$churchCode]['novos']++;
                    $groups[$churchCode]['dependencias'][$dependencyName]['novos']++;
                    break;
                case CsvParserService::STATUS_ATUALIZAR:
                    $groups[$churchCode]['atualizar']++;
                    $groups[$churchCode]['dependencias'][$dependencyName]['atualizar']++;
                    break;
                case CsvParserService::STATUS_SEM_ALTERACAO:
                    $groups[$churchCode]['sem_alteracao']++;
                    $groups[$churchCode]['dependencias'][$dependencyName]['sem_alteracao']++;
                    break;
                case CsvParserService::STATUS_EXCLUIR:
                    $groups[$churchCode]['exclusoes']++;
                    $groups[$churchCode]['dependencias'][$dependencyName]['exclusoes']++;
                    break;
                default:
                    $groups[$churchCode]['erros']++;
                    $groups[$churchCode]['dependencias'][$dependencyName]['erros']++;
                    break;
            }
        }

        if ($groups === []) {
            return [];
        }

        $codes = array_values(array_filter(
            array_keys($groups),
            static fn (string $code): bool => $code !== '__sem_localidade__',
        ));

        $descriptions = [];
        if ($codes !== [] && !app()->runningUnitTests()) {
            $descriptions = Comum::query()
                ->whereIn('codigo', $codes)
                ->get(['codigo', 'descricao'])
                ->mapWithKeys(static fn (Comum $comum): array => [
                    strtoupper(trim((string) $comum->codigo)) => trim((string) $comum->descricao),
                ])
                ->all();
        }

        foreach ($groups as $code => &$group) {
            if ($code !== '__sem_localidade__') {
                $group['descricao'] = $descriptions[$code] ?? $group['descricao'];

                if ($group['descricao'] === '') {
                    $group['descricao'] = $code;
                }
            }

            // Ordenar dependências por nome
            ksort($group['dependencias']);

            $group['status'] = $group['erros'] > 0
                ? 'com_erro'
                : (($group['novos'] + $group['atualizar'] + $group['exclusoes']) > 0
                    ? 'com_alteracoes'
                    : 'sem_alteracao');
        }
        unset($group);

        $summaries = array_values($groups);

        usort(
            $summaries,
            static function (array $left, array $right): int {
                $leftDescription = mb_strtolower((string) ($left['descricao'] ?? ''));
                $rightDescription = mb_strtolower((string) ($right['descricao'] ?? ''));

                return $leftDescription <=> $rightDescription;
            },
        );

        return $summaries;
    }

    /**
     * @param array<string, mixed> $registro
     */
    private function resolveChurchCodeForRecord(array $registro, string $fallbackChurchCode): string
    {
        $churchCode = strtoupper(trim((string) ($registro['dados_csv']['codigo_comum'] ?? '')));

        if ($churchCode !== '') {
            return $churchCode;
        }

        if ($fallbackChurchCode !== '') {
            return $fallbackChurchCode;
        }

        return '__sem_localidade__';
    }

    /**
     * @param array<int, array<string, mixed>> $registros
     * @param array<string, string> $churchActions
     * @param array<string, string> $dependencyActions
     * @return array<string, string>
     */
    private function buildConfirmActionsByChurch(array $registros, array $churchActions, array $dependencyActions, int $fallbackChurchId, bool $importAll): array
    {
        $actions = [];
        $fallbackChurchCode = $this->resolveFallbackChurchCode($fallbackChurchId);

        foreach ($registros as $registro) {
            $line = (string) ($registro['linha_csv'] ?? '');

            if ($line === '') {
                continue;
            }

            $status = (string) ($registro['status'] ?? '');
            $churchCode = $this->resolveChurchCodeForRecord($registro, $fallbackChurchCode);
            $dependencyName = strtoupper(trim((string) ($registro['dados_csv']['dependencia_descricao'] ?? $registro['dependencia'] ?? 'SEM DEPENDÊNCIA')));
            if ($dependencyName === '') {
                $dependencyName = 'SEM DEPENDÊNCIA';
            }
            $depKey = $churchCode . ':' . $dependencyName;

            if ($importAll) {
                if ($status === 'erro') {
                    $actions[$line] = CsvParserService::ACAO_PULAR;
                    continue;
                }

                $actions[$line] = $status === CsvParserService::STATUS_EXCLUIR
                    ? CsvParserService::ACAO_EXCLUIR
                    : CsvParserService::ACAO_IMPORTAR;
                continue;
            }

            $churchAction = $churchActions[$churchCode] ?? 'personalizado';
            
            // Se a igreja estiver marcada para pular, pula tudo dela
            if ($churchAction === CsvParserService::ACAO_PULAR) {
                $actions[$line] = CsvParserService::ACAO_PULAR;
                continue;
            }

            // Se estiver em modo personalizado ou importar, checa a dependência
            $depAction = $dependencyActions[$depKey] ?? ($churchAction === 'importar' ? 'importar' : 'pular');

            if ($depAction !== CsvParserService::ACAO_IMPORTAR || $status === 'erro') {
                $actions[$line] = CsvParserService::ACAO_PULAR;
                continue;
            }

            $actions[$line] = $status === CsvParserService::STATUS_EXCLUIR
                ? CsvParserService::ACAO_EXCLUIR
                : CsvParserService::ACAO_IMPORTAR;
        }

        return $actions;
    }

    private function resolveFallbackChurchCode(int $fallbackChurchId): string
    {
        if ($fallbackChurchId <= 0) {
            return '';
        }

        $fallbackChurch = Comum::query()->find($fallbackChurchId, ['id', 'codigo']);
        if (!$fallbackChurch instanceof Comum) {
            return '';
        }

        return strtoupper(trim((string) $fallbackChurch->codigo));
    }

    public function loadProgress(int $importacaoId): ?array
    {
        try {
            $importacaoService = new ImportacaoService();

            return $importacaoService->buscarProgresso($importacaoId);
        } catch (Throwable $throwable) {
            throw new RuntimeException('Não foi possível consultar o progresso da importação.', previous: $throwable);
        }
    }

    public function loadImportErrors(?int $churchId, ?int $importacaoId, int $page = 1, int $perPage = 30): array
    {
        $page = max(1, $page);
        $connection = DB::connection('mysql');
        $administrationIds = $this->currentAdministrationScopeIds();
        $effectiveChurchId = $churchId ?? $this->currentChurchId();

        $baseQuery = $connection->table('import_erros as ie');
        $mode = 'geral';

        if (($importacaoId ?? 0) > 0) {
            $mode = 'importacao';
            $baseQuery
                ->join('importacoes as imp', 'imp.id', '=', 'ie.importacao_id')
                ->where('ie.importacao_id', '=', $importacaoId);

            if ($administrationIds !== null) {
                $baseQuery->whereIn('imp.administracao_id', $administrationIds);
            } elseif (($effectiveChurchId ?? 0) > 0) {
                $baseQuery->where('imp.comum_id', '=', $effectiveChurchId);
            }
        } elseif ($administrationIds !== null) {
            $mode = 'administracao';
            $baseQuery
                ->join('importacoes as imp', 'imp.id', '=', 'ie.importacao_id')
                ->whereIn('imp.administracao_id', $administrationIds)
                ->where('ie.resolvido', '=', 0);
        } elseif (($effectiveChurchId ?? 0) > 0) {
            $mode = 'comum';
            $baseQuery
                ->join('importacoes as imp', 'imp.id', '=', 'ie.importacao_id')
                ->where('imp.comum_id', '=', $effectiveChurchId)
                ->where('ie.resolvido', '=', 0);
        } else {
            $baseQuery->where('ie.resolvido', '=', 0);
        }

        $total = (clone $baseQuery)->count();
        $items = (clone $baseQuery)
            ->select([
                'ie.id',
                'ie.importacao_id',
                'ie.linha_csv',
                'ie.codigo',
                'ie.localidade',
                'ie.codigo_comum',
                'ie.descricao_csv',
                'ie.bem',
                'ie.complemento',
                'ie.dependencia',
                'ie.mensagem_erro',
                'ie.resolvido',
                'ie.created_at',
            ])
            ->orderBy('ie.id')
            ->forPage($page, $perPage)
            ->get();

        $paginator = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => route('migration.spreadsheets.errors'),
                'pageName' => 'pagina',
            ],
        );

        $church = null;
        $administration = null;
        $import = null;

        if (($importacaoId ?? 0) > 0) {
            $import = $connection->table('importacoes as i')
                ->leftJoin('usuarios as u', 'u.id', '=', 'i.usuario_id')
                ->leftJoin('administracoes as a', 'a.id', '=', 'i.administracao_id')
                ->select([
                    'i.id',
                    'i.arquivo_nome',
                    'i.iniciada_em',
                    'i.status',
                    'i.comum_id',
                    'a.descricao as administracao_descricao',
                    'u.nome as usuario_responsavel_nome',
                    'u.email as usuario_responsavel_email',
                ])
                ->where('i.id', '=', $importacaoId);

            if ($administrationIds !== null) {
                $import->whereIn('i.administracao_id', $administrationIds);
            } elseif (($effectiveChurchId ?? 0) > 0) {
                $import->where('i.comum_id', '=', $effectiveChurchId);
            }

            $import = $import->first();
            $import = $import === null ? null : $this->applyAdministrationLabel((array) $import);
        } elseif ($administrationIds !== null) {
            $administrations = $connection->table('administracoes')
                ->select(['id', 'descricao'])
                ->whereIn('id', $administrationIds)
                ->orderBy('descricao')
                ->get();

            $administrationDescriptions = $administrations
                ->pluck('descricao')
                ->filter(static fn (mixed $value): bool => trim((string) $value) !== '')
                ->values()
                ->all();

            $administration = [
                'id' => $administrations->count() === 1 ? (int) $administrations->first()->id : null,
                'descricao' => $administrationDescriptions !== []
                    ? implode(', ', $administrationDescriptions)
                    : 'Administração atual',
                'ids' => $administrationIds,
            ];
        } elseif (($effectiveChurchId ?? 0) > 0) {
            $church = $connection->table('comums')
                ->select(['id', 'descricao', 'codigo'])
                ->where('id', '=', $effectiveChurchId)
                ->first();
            $church = $church === null ? null : (array) $church;
        }

        $summary = $this->loadImportErrorsSummary($effectiveChurchId, $importacaoId);

        return [
            'modo' => $mode,
            'comum' => $church,
            'administracao' => $administration,
            'importacao' => $import,
            'resumo' => $summary,
            'erros' => $paginator,
        ];
    }

    public function downloadImportErrorsCsv(?int $churchId, ?int $importacaoId): array
    {
        $connection = DB::connection('mysql');
        $query = $connection->table('import_erros as ie');
        $suffix = '';
        $administrationIds = $this->currentAdministrationScopeIds();
        $effectiveChurchId = $churchId ?? $this->currentChurchId();

        if (($importacaoId ?? 0) > 0) {
            $query->where('ie.importacao_id', '=', $importacaoId)->where('ie.resolvido', '=', 0);
            $suffix = 'imp_' . $importacaoId;
        } elseif ($administrationIds !== null) {
            $query
                ->join('importacoes as imp', 'imp.id', '=', 'ie.importacao_id')
                ->whereIn('imp.administracao_id', $administrationIds)
                ->where('ie.resolvido', '=', 0);
            $suffix = 'adm_' . implode('_', $administrationIds);
        } elseif (($effectiveChurchId ?? 0) > 0) {
            $query
                ->join('importacoes as imp', 'imp.id', '=', 'ie.importacao_id')
                ->where('imp.comum_id', '=', $effectiveChurchId)
                ->where('ie.resolvido', '=', 0);
            $suffix = 'comum_' . $effectiveChurchId;
        } else {
            throw new RuntimeException('Informe uma importação ou igreja para baixar o CSV.');
        }

        $errors = $query
            ->select([
                'ie.codigo',
                'ie.descricao_csv',
                'ie.localidade',
                'ie.dependencia',
                'ie.bem',
                'ie.complemento',
            ])
            ->orderBy('ie.id')
            ->cursor();

        $rows = (function () use ($errors) {
            $header = array_fill(0, 16, '');
            $header[0] = 'Codigo';
            $header[3] = 'Nome';
            $header[10] = 'Localidade';
            $header[15] = 'Dependencia';
            yield $header;

            foreach ($errors as $error) {
                $row = array_fill(0, 16, '');
                $row[0] = (string) ($error->codigo ?? '');

                $originalName = trim((string) ($error->descricao_csv ?? ''));
                if ($originalName === '') {
                    $originalName = trim((string) (($error->bem ?? '') . ' ' . ($error->complemento ?? '')));
                }

                $row[3] = $originalName;
                $row[10] = (string) ($error->localidade ?? '');
                $row[15] = (string) ($error->dependencia ?? '');

                yield $row;
            }
        })();

        return [
            'filename' => 'correcao_erros_' . $suffix . '_' . date('Ymd_His') . '.csv',
            'rows' => $rows,
        ];
    }

    public function markImportErrorResolved(int $errorId, bool $resolved): array
    {
        $connection = DB::connection('mysql');
        $error = $connection->table('import_erros as ie')
            ->join('importacoes as imp', 'imp.id', '=', 'ie.importacao_id')
            ->select([
                'ie.id',
                'ie.importacao_id',
                'imp.comum_id',
                'imp.administracao_id',
            ])
            ->where('ie.id', '=', $errorId)
            ->first();

        if ($error === null) {
            throw new RuntimeException('Erro de importação não encontrado.');
        }

        $this->assertImportErrorScope($error);

        $connection->table('import_erros')
            ->where('id', '=', $errorId)
            ->update(['resolvido' => $resolved ? 1 : 0]);

        $pending = (int) $connection->table('import_erros')
            ->where('importacao_id', '=', (int) $error->importacao_id)
            ->where('resolvido', '=', 0)
            ->count();

        return [
            'pendentes' => $pending,
            'resolvido' => $resolved,
        ];
    }

    private function ensureImportExists(int $importacaoId): void
    {
        $importacaoService = new ImportacaoService();
        $importacao = $importacaoService->buscarProgresso($importacaoId);

        if ($importacao === null) {
            throw new RuntimeException('Importação não encontrada.');
        }
    }

    public function previewActionsKey(int $importacaoId): string
    {
        return 'spreadsheet_preview_actions_' . $importacaoId;
    }

    public function previewChurchesKey(int $importacaoId): string
    {
        return 'spreadsheet_preview_churches_' . $importacaoId;
    }

    public function previewDependenciesKey(int $importacaoId): string
    {
        return 'spreadsheet_preview_dependencies_' . $importacaoId;
    }

    private function moveUploadedFile(UploadedFile $file, int $churchId): string
    {
        $directory = $this->resolveImportDirectory();
        $extension = strtolower($file->getClientOriginalExtension() ?: 'csv');
        $filename = 'importacao_' . $churchId . '_' . time() . '.' . $extension;
        $destination = $directory . DIRECTORY_SEPARATOR . $filename;

        $file->move($directory, $filename);

        return $destination;
    }

    private function resolveImportDirectory(): string
    {
        $candidates = [
            storage_path('importacao'),
            sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'check-planilha-imobilizado' . DIRECTORY_SEPARATOR . 'importacao',
        ];

        foreach ($candidates as $candidate) {
            if ($this->ensureWritableDirectory($candidate)) {
                return $candidate;
            }
        }

        throw new RuntimeException('Não foi possível preparar um diretório gravável para a importação.');
    }

    private function ensureWritableDirectory(string $directory): bool
    {
        if (is_dir($directory)) {
            return is_writable($directory);
        }

        $parent = dirname($directory);

        if (!is_dir($parent) || !is_writable($parent)) {
            return false;
        }

        if (!mkdir($directory, 0775, true) && !is_dir($directory)) {
            return false;
        }

        return is_writable($directory);
    }

    private function friendlyErrorMessage(string $technicalMessage): string
    {
        $message = mb_strtolower($technicalMessage, 'UTF-8');

        if (str_contains($message, 'responsável selecionado')) {
            return 'O responsável selecionado deve pertencer à mesma administração da importação.';
        }

        if (str_contains($message, 'responsável válido')) {
            return 'Selecione um responsável válido.';
        }

        if (str_contains($message, 'administração selecionada')) {
            return 'A administração selecionada não está mais disponível.';
        }

        if (str_contains($message, 'não está disponível para este usuário')) {
            return 'A administração selecionada não está disponível para este usuário.';
        }

        if (str_contains($message, 'csv') || str_contains($message, 'arquivo')) {
            return 'Erro ao ler o arquivo enviado. Verifique se a planilha está em CSV válido.';
        }

        if (str_contains($message, 'diretório') || str_contains($message, 'writ')) {
            return 'Não foi possível gravar o arquivo de importação. Verifique permissões e tente novamente.';
        }

        return 'Não foi possível processar a importação.';
    }

    /**
     * @return array{pendentes: int, resolvidos: int}
     */
    private function loadImportErrorsSummary(?int $churchId, ?int $importacaoId): array
    {
        $connection = DB::connection('mysql');
        $query = $connection->table('import_erros as ie');
        $administrationIds = $this->currentAdministrationScopeIds();
        $effectiveChurchId = $churchId ?? $this->currentChurchId();

        if (($importacaoId ?? 0) > 0) {
            $query
                ->join('importacoes as imp', 'ie.importacao_id', '=', 'imp.id')
                ->where('ie.importacao_id', '=', $importacaoId);

            if ($administrationIds !== null) {
                $query->whereIn('imp.administracao_id', $administrationIds);
            } elseif (($effectiveChurchId ?? 0) > 0) {
                $query->where('imp.comum_id', '=', $effectiveChurchId);
            }
        } elseif ($administrationIds !== null) {
            $query
                ->join('importacoes as imp', 'ie.importacao_id', '=', 'imp.id')
                ->whereIn('imp.administracao_id', $administrationIds);
        } elseif (($effectiveChurchId ?? 0) > 0) {
            $query
                ->join('importacoes as imp', 'ie.importacao_id', '=', 'imp.id')
                ->where('imp.comum_id', '=', $effectiveChurchId);
        }

        $pending = (clone $query)->where('ie.resolvido', '=', 0)->count();
        $resolved = (clone $query)->where('ie.resolvido', '=', 1)->count();

        return [
            'pendentes' => (int) $pending,
            'resolvidos' => (int) $resolved,
        ];
    }

    /**
     * @param array<string, mixed> $importacao
     * @return array<string, mixed>
     */
    private function applyAdministrationLabel(array $importacao): array
    {
        $administrationDescription = trim((string) ($importacao['administracao_descricao'] ?? ''));
        $importacao['administracao_label'] = $administrationDescription !== ''
            ? $administrationDescription
            : 'Sem administração';

        return $importacao;
    }

    private function currentAdministrationId(): ?int
    {
        $administrationId = (int) Session::get('administracao_id', 0);

        return $administrationId > 0 ? $administrationId : null;
    }

    /**
     * @return array<int, int>|null
     */
    private function currentAdministrationScopeIds(): ?array
    {
        if ((bool) Session::get('is_admin', false)) {
            return null;
        }

        $administrationIds = array_values(array_filter(array_map(
            static fn (mixed $value): int => (int) $value,
            (array) Session::get('administracoes_permitidas', []),
        ), static fn (int $value): bool => $value > 0));

        if ($administrationIds !== []) {
            return array_values(array_unique($administrationIds));
        }

        $administrationId = $this->currentAdministrationId();
        if ($administrationId !== null) {
            return [$administrationId];
        }

        return null;
    }

    private function currentChurchId(): ?int
    {
        $churchId = (int) Session::get('comum_id', 0);

        return $churchId > 0 ? $churchId : null;
    }

    /**
     * @param object{administracao_id?: int|null, comum_id?: int|null} $error
     */
    private function assertImportErrorScope(object $error): void
    {
        $administrationIds = $this->currentAdministrationScopeIds();
        if ($administrationIds !== null) {
            $errorAdministrationId = (int) ($error->administracao_id ?? 0);
            if (!in_array($errorAdministrationId, $administrationIds, true)) {
                throw new RuntimeException('Erro de importação não encontrado.');
            }

            return;
        }

        $churchId = $this->currentChurchId();
        if ($churchId !== null && (int) ($error->comum_id ?? 0) !== $churchId) {
            throw new RuntimeException('Erro de importação não encontrado.');
        }
    }

    private function isUserAllowedForAdministration(Usuario $user, int $administrationId): bool
    {
        if ($user->isAdministrator()) {
            return true;
        }

        if ((int) ($user->administracao_id ?? 0) === $administrationId) {
            return true;
        }

        $permittedIds = array_values(array_filter(array_map(
            static fn (mixed $value): int => (int) $value,
            (array) ($user->administracoes_permitidas ?? []),
        ), static fn (int $value): bool => $value > 0));

        return in_array($administrationId, $permittedIds, true);
    }

    /**
     * @param array<int, int> $administrationIds
     */
    private function isUserWithinAdministrationScope(Usuario $user, array $administrationIds): bool
    {
        if ($user->isAdministrator()) {
            return true;
        }

        if ((int) ($user->administracao_id ?? 0) > 0
            && in_array((int) $user->administracao_id, $administrationIds, true)
        ) {
            return true;
        }

        $permittedIds = array_values(array_filter(array_map(
            static fn (mixed $value): int => (int) $value,
            (array) ($user->administracoes_permitidas ?? []),
        ), static fn (int $value): bool => $value > 0));

        return array_intersect($permittedIds, $administrationIds) !== [];
    }
    private function confirmOptionsKey(int $importacao): string
    {
        return 'importacao_confirm_options_' . $importacao;
    }
}
