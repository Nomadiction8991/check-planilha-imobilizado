<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\LegacyReportServiceInterface;
use App\Services\LegacyReportTemplateService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use RuntimeException;

class LegacyReportController extends Controller
{
    public function __construct(
        private readonly LegacyReportServiceInterface $reports,
        private readonly LegacyReportTemplateService $templates,
    ) {
    }

    public function index(Request $request): View
    {
        $churchId = $request->integer('comum_id') ?: ((int) Session::get('comum_id', 0) ?: null);

        return view('reports.index', [
            'churches' => $this->reports->churchOptions(),
            'selectedChurchId' => $churchId,
            'reports' => $churchId !== null ? $this->reports->listAvailableReports($churchId) : [],
        ]);
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
                ->with('status', 'Selecione uma igreja para abrir o histórico de alterações.')
                ->with('status_type', 'error');
        }

        $filters = [
            'mostrar_pendentes' => $request->boolean('mostrar_pendentes'),
            'mostrar_checados' => $request->boolean('mostrar_checados'),
            'mostrar_observacao' => $request->boolean('mostrar_observacao'),
            'mostrar_checados_observacao' => $request->boolean('mostrar_checados_observacao'),
            'mostrar_etiqueta' => $request->boolean('mostrar_etiqueta'),
            'mostrar_alteracoes' => $request->boolean('mostrar_alteracoes'),
            'mostrar_novos' => $request->boolean('mostrar_novos'),
            'dependencia' => $request->filled('dependencia') ? $request->integer('dependencia') : null,
        ];

        try {
            $history = $this->reports->buildChangeHistory($churchId, $filters);
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('migration.reports.index', ['comum_id' => $churchId])
                ->with('status', $exception->getMessage())
                ->with('status_type', 'error');
        }

        return view('reports.changes', [
            'churches' => $this->reports->churchOptions(),
            'selectedChurchId' => $churchId,
            'history' => $history,
        ]);
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
