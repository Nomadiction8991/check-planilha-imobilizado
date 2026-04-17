<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\LegacySpreadsheetImportServiceInterface;
use App\Http\Requests\StoreSpreadsheetImportRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SpreadsheetImportController extends Controller
{
    public function __construct(
        private readonly LegacySpreadsheetImportServiceInterface $imports,
    ) {
    }

    public function create(): View
    {
        $selectedAdministrationId = (int) Session::get('administracao_id', 0) ?: null;

        return view('spreadsheets.import', [
            'users' => $this->imports->responsibleUserOptions(),
            'administrations' => $this->imports->administrationOptions(),
            'selectedAdministrationId' => $selectedAdministrationId,
            'recentImports' => $this->imports->recentImports(null, 5),
        ]);
    }

    public function store(StoreSpreadsheetImportRequest $request): RedirectResponse
    {
        try {
            $importacaoId = $this->imports->uploadAndAnalyze(
                $request->toDto(),
                $request->file('arquivo_csv'),
            );
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('migration.spreadsheets.create')
                ->withInput()
                ->with('status', $exception->getMessage())
                ->with('status_type', 'error');
        }

        return redirect()
            ->route('migration.spreadsheets.preview', ['importacao' => $importacaoId])
            ->with('status', 'Arquivo analisado com sucesso. Revise o resumo antes de confirmar.')
            ->with('status_type', 'success');
    }

    public function preview(int $importacao): View|RedirectResponse
    {
        $preview = $this->imports->loadPreview($importacao);

        if ($preview === null) {
            return redirect()
                ->route('migration.spreadsheets.create')
                ->with('status', 'Importação não encontrada ou análise indisponível.')
                ->with('status_type', 'error');
        }

        return view('spreadsheets.preview', [
            'importacaoId' => $importacao,
            'importacao' => $preview['importacao'],
            'analise' => $preview['analise'],
            'igrejasSalvas' => $preview['igrejas_salvas'],
            'igrejasDetectadas' => $preview['igrejas_detectadas'] ?? [],
        ]);
    }

    public function savePreviewActions(Request $request, int $importacao): JsonResponse
    {
        try {
            $result = $this->imports->savePreviewActions(
                $importacao,
                (array) $request->input('acoes', []),
                (array) $request->input('igrejas', []),
            );
        } catch (RuntimeException $exception) {
            return response()->json(['erro' => $exception->getMessage()], 422);
        }

        return response()->json([
            'sucesso' => true,
            'total_salvas' => $result['total_salvas'],
            'igrejas_salvas' => $result['igrejas_salvas'],
        ]);
    }

    public function legacySavePreviewActions(Request $request): JsonResponse
    {
        return $this->savePreviewActions($request, $this->legacyImportId($request));
    }

    public function bulkPreviewAction(Request $request, int $importacao): JsonResponse
    {
        try {
            $result = $this->imports->applyBulkPreviewAction(
                $importacao,
                (string) $request->input('acao', ''),
            );
        } catch (RuntimeException $exception) {
            return response()->json(['erro' => $exception->getMessage()], 422);
        }

        return response()->json([
            'sucesso' => true,
            'acao' => $result['acao'],
            'total_aplicadas' => $result['total_aplicadas'],
        ]);
    }

    public function legacyBulkPreviewAction(Request $request): JsonResponse
    {
        return $this->bulkPreviewAction($request, $this->legacyImportId($request));
    }

    public function confirm(Request $request, int $importacao): RedirectResponse
    {
        Session::put($this->confirmOptionsKey($importacao), [
            'importar_tudo' => $request->boolean('importar_tudo'),
            'acoes' => (array) $request->input('acao', []),
            'igrejas' => (array) $request->input('igrejas', []),
        ]);

        return redirect()
            ->route('migration.spreadsheets.processing', ['importacao' => $importacao])
            ->with('status', 'Processamento iniciado. Acompanhe o progresso abaixo.')
            ->with('status_type', 'success');
    }

    public function legacyConfirm(Request $request): RedirectResponse
    {
        return $this->confirm($request, $this->legacyImportId($request));
    }

    public function processing(int $importacao): View|RedirectResponse
    {
        $progress = $this->imports->loadProgress($importacao);

        if ($progress === null) {
            return redirect()
                ->route('migration.spreadsheets.create')
                ->with('status', 'Importação não encontrada.')
                ->with('status_type', 'error');
        }

        return view('spreadsheets.processing', [
            'importacaoId' => $importacao,
            'importacao' => $progress,
        ]);
    }

    public function startProcessing(int $importacao): JsonResponse
    {
        $ownership = $this->assertOwnership($importacao);
        if ($ownership !== null) {
            return $ownership;
        }

        $options = Session::get($this->confirmOptionsKey($importacao), [
            'importar_tudo' => false,
            'acoes' => [],
            'igrejas' => [],
        ]);

        try {
            $resultado = $this->imports->confirmImport(
                $importacao,
                importAll: (bool) ($options['importar_tudo'] ?? false),
                acoes: (array) ($options['acoes'] ?? []),
                igrejas: (array) ($options['igrejas'] ?? []),
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        Session::forget($this->confirmOptionsKey($importacao));

        return response()->json([
            'success' => true,
            'message' => 'Importação processada com sucesso.',
            'resultado' => $resultado,
        ]);
    }

    public function progress(int $importacao): JsonResponse
    {
        $ownership = $this->assertOwnership($importacao);
        if ($ownership !== null) {
            return $ownership;
        }

        try {
            $progress = $this->imports->loadProgress($importacao);
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        if ($progress === null) {
            return response()->json([
                'success' => false,
                'message' => 'Importação não encontrada.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'id' => (int) ($progress['id'] ?? 0),
            'status' => (string) ($progress['status'] ?? 'aguardando'),
            'total_linhas' => (int) ($progress['total_linhas'] ?? 0),
            'linhas_processadas' => (int) ($progress['linhas_processadas'] ?? 0),
            'linhas_sucesso' => (int) ($progress['linhas_sucesso'] ?? 0),
            'linhas_erro' => (int) ($progress['linhas_erro'] ?? 0),
            'porcentagem' => (float) ($progress['porcentagem'] ?? 0),
            'arquivo_nome' => (string) ($progress['arquivo_nome'] ?? ''),
            'mensagem_erro' => (string) ($progress['mensagem_erro'] ?? ''),
        ]);
    }

    public function processFile(Request $request): JsonResponse
    {
        $importacao = max(0, (int) $request->input('id', 0));

        if ($importacao <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Importação inválida',
            ], 400);
        }

        $ownership = $this->assertOwnership($importacao);
        if ($ownership !== null) {
            return $ownership;
        }

        try {
            $progress = $this->imports->loadProgress($importacao);
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        if ($progress === null) {
            return response()->json([
                'success' => false,
                'message' => 'Importação não encontrada',
            ], 404);
        }

        $status = (string) ($progress['status'] ?? '');
        if (in_array($status, ['processando', 'concluida'], true)) {
            return response()->json([
                'success' => true,
                'message' => 'Importação já iniciada.',
                'status' => $status,
            ]);
        }

        return $this->startProcessing($importacao);
    }

    public function legacyProgress(Request $request): JsonResponse
    {
        $importacao = max(0, (int) ($request->query('id') ?? 0));

        if ($importacao <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Importação inválida',
            ], 400);
        }

        return $this->progress($importacao);
    }

    public function errors(Request $request): View
    {
        $churchId = $request->integer('comum_id') ?: null;
        $importacaoId = $request->integer('importacao_id') ?: null;
        $page = max(1, $request->integer('pagina', 1));
        $data = $this->imports->loadImportErrors($churchId, $importacaoId, $page);

        return view('spreadsheets.errors', [
            'modo' => $data['modo'],
            'comumId' => $churchId,
            'importacaoId' => $importacaoId,
            'comum' => $data['comum'],
            'administracao' => $data['administracao'],
            'importacao' => $data['importacao'],
            'resumo' => $data['resumo'],
            'erros' => $data['erros'],
        ]);
    }

    public function downloadErrorsCsv(Request $request): StreamedResponse|RedirectResponse
    {
        $churchId = $request->integer('comum_id') ?: null;
        $importacaoId = $request->integer('importacao_id') ?: null;

        try {
            $file = $this->imports->downloadImportErrorsCsv($churchId, $importacaoId);
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('migration.spreadsheets.errors', array_filter([
                    'comum_id' => $churchId,
                    'importacao_id' => $importacaoId,
                ]))
                ->with('status', $exception->getMessage())
                ->with('status_type', 'error');
        }

        return response()->streamDownload(
            static function () use ($file): void {
                echo $file['content'];
            },
            $file['filename'],
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ],
        );
    }

    public function resolveError(Request $request, int $erro): JsonResponse
    {
        try {
            $result = $this->imports->markImportErrorResolved(
                $erro,
                $request->boolean('resolvido', true),
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'sucesso' => false,
                'erro' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'sucesso' => true,
            'pendentes' => $result['pendentes'],
            'resolvido' => $result['resolvido'],
        ]);
    }

    public function legacyResolveError(Request $request): JsonResponse
    {
        $erro = max(0, (int) $request->input('erro_id', $request->input('erro', 0)));

        if ($erro <= 0) {
            return response()->json([
                'sucesso' => false,
                'erro' => 'Erro inválido',
            ], 400);
        }

        return $this->resolveError($request, $erro);
    }

    private function confirmOptionsKey(int $importacao): string
    {
        return 'importacao_confirm_options_' . $importacao;
    }

    private function legacyImportId(Request $request): int
    {
        return max(0, (int) $request->input('importacao_id', $request->input('id', $request->input('importacao', 0))));
    }

    private function assertOwnership(int $importacao): ?JsonResponse
    {
        $userId = (int) Session::get('usuario_id', 0);

        try {
            $progress = $this->imports->loadProgress($importacao);
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        if ($progress === null) {
            return response()->json([
                'success' => false,
                'message' => 'Importação não encontrada.',
            ], 404);
        }

        $ownerId = (int) ($progress['usuario_id'] ?? 0);
        if ($userId <= 0 || $ownerId <= 0 || $ownerId !== $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado à importação.',
                'responsavel' => trim((string) ($progress['usuario_responsavel_nome'] ?? 'outro usuário')) ?: 'outro usuário',
            ], 403);
        }

        return null;
    }
}
