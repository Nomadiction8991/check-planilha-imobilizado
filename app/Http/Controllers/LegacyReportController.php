<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\LegacyReportServiceInterface;
use App\Services\LegacyReportTemplateService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LegacyReportController extends Controller
{
    public function __construct(
        private readonly LegacyReportServiceInterface $reports,
        private readonly LegacyReportTemplateService $templates,
    ) {
    }

    public function index(Request $request): View
    {
        $administrationId = $request->integer('administracao_id') ?: null;
        $state = strtoupper(trim((string) $request->query('estado', '')));
        $state = $state !== '' ? $state : null;
        $churchId = $request->integer('comum_id') ?: ((int) Session::get('comum_id', 0) ?: null);

        $churches = $this->reports->churchOptions($administrationId, $state);
        $selectedChurchId = $this->normalizeSelectedChurchId($churchId, $churches);

        return view('reports.index', [
            'administrations' => $this->reports->administrationOptions(),
            'selectedAdministrationId' => $administrationId,
            'selectedState' => $state,
            'states' => (array) config('brazil.states', []),
            'churches' => $churches,
            'selectedChurchId' => $selectedChurchId,
            'reports' => $selectedChurchId !== null ? $this->reports->listAvailableReports($selectedChurchId) : [],
        ]);
    }

    /**
     * @param Collection<int, object> $churches
     */
    private function normalizeSelectedChurchId(?int $churchId, Collection $churches): ?int
    {
        if ($churchId === null || $churchId <= 0) {
            return null;
        }

        return $churches->contains(
            static fn (mixed $church): bool => (int) data_get($church, 'id') === $churchId,
        ) ? $churchId : null;
    }

    public function show(Request $request, string $formulario): View|RedirectResponse
    {
        $churchId = $request->integer('comum_id', (int) Session::get('comum_id', 0));

        if ($churchId <= 0) {
            return redirect()
                ->route('migration.reports.index')
                ->with('status', 'Selecione uma igreja para visualizar o relatório.')
                ->with('status_type', 'error');
        }

        try {
            $preview = $this->reports->buildReportPreview($churchId, $formulario);
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('migration.reports.index', ['comum_id' => $churchId])
                ->with('status', $exception->getMessage())
                ->with('status_type', 'error');
        }

        return view('reports.show', [
            'churches' => $this->reports->churchOptions(),
            'selectedChurchId' => $churchId,
            'preview' => $preview,
        ]);
    }

    public function changes(Request $request): View|RedirectResponse
    {
        $churchId = $request->integer('comum_id', (int) Session::get('comum_id', 0));

        if ($churchId <= 0) {
            return redirect()
                ->route('migration.reports.index')
                ->with('status', 'Selecione uma igreja para abrir a posição de estoque.')
                ->with('status_type', 'error');
        }

        try {
            $report = $this->reports->buildVerificationPositionReport($churchId);
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('migration.reports.index', ['comum_id' => $churchId])
                ->with('status', $exception->getMessage())
                ->with('status_type', 'error');
        }

        return view('reports.position', [
            'churches' => $this->reports->churchOptions(),
            'selectedChurchId' => $churchId,
            'report' => $report,
        ]);
    }

    public function changesExport(Request $request): StreamedResponse|RedirectResponse
    {
        $churchId = $request->integer('comum_id', (int) Session::get('comum_id', 0));

        if ($churchId <= 0) {
            return redirect()
                ->route('migration.reports.index')
                ->with('status', 'Selecione uma igreja para exportar o backup da posição.')
                ->with('status_type', 'error');
        }

        try {
            $file = $this->reports->downloadVerificationPositionCsv($churchId);
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('migration.reports.index', ['comum_id' => $churchId])
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

    public function formularioExport(Request $request, string $formulario): StreamedResponse|RedirectResponse
    {
        $churchId = $request->integer('comum_id', (int) Session::get('comum_id', 0));

        if ($churchId <= 0) {
            return redirect()
                ->route('migration.reports.index')
                ->with('status', 'Selecione uma igreja para exportar o relatório.')
                ->with('status_type', 'error');
        }

        try {
            $file = $this->reports->downloadFormularioCsv($churchId, $formulario);
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('migration.reports.index', ['comum_id' => $churchId])
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

    public function editor(Request $request): View|RedirectResponse
    {
        $formulario = str_replace('-', '.', trim((string) $request->query('formulario', '14.1')));

        if ($formulario === '') {
            $formulario = '14.1';
        }

        $templatePath = $this->templates->templatePath($formulario);

        if (!is_file($templatePath)) {
            return redirect()
                ->route('migration.reports.index', array_filter([
                    'comum_id' => $request->integer('comum_id') ?: null,
                ]))
                ->with('status', 'Formulário inválido para o editor de células.')
                ->with('status_type', 'error');
        }

        $comumId = $request->integer('comum_id', (int) Session::get('comum_id', 0));
        $cellEditorBaseUrl = route('migration.reports.editor', absolute: false);
        $cellEditorQuerySuffix = $comumId > 0 ? '&comum_id=' . $comumId : '';
        $editorBackUrl = $comumId > 0
            ? route('migration.reports.show', ['formulario' => $formulario, 'comum_id' => $comumId])
            : route('migration.reports.index');

        return view('reports.editor', [
            'formulario' => $formulario,
            'cellEditorBaseUrl' => $cellEditorBaseUrl,
            'cellEditorQuerySuffix' => $cellEditorQuerySuffix,
            'editorBackUrl' => $editorBackUrl,
            'bgUrl' => $this->templates->extractBackgroundImageUrl($formulario),
        ]);
    }
}
