<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\LegacyAuditTrailServiceInterface;
use App\Contracts\LegacyAuthSessionServiceInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class LegacyAuditController extends Controller
{
    public function __construct(
        private readonly LegacyAuditTrailServiceInterface $audits,
        private readonly LegacyAuthSessionServiceInterface $auth,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        $invalidPeriod = $this->validatePeriod($request);
        if ($invalidPeriod !== null) {
            return $invalidPeriod;
        }

        $currentUser = $this->auth->currentUser();
        $filters = [
            'search' => trim((string) $request->query('busca', '')),
            'module' => trim((string) $request->query('modulo', '')),
            'date_from' => trim((string) $request->query('data_inicio', '')),
            'date_to' => trim((string) $request->query('data_fim', '')),
        ];

        $audits = $this->audits->paginate(
            $filters,
            isset($currentUser['id']) ? (int) $currentUser['id'] : null,
            isset($currentUser['administracao_id']) ? (int) $currentUser['administracao_id'] : null,
            isset($currentUser['comum_id']) ? (int) $currentUser['comum_id'] : null,
            (bool) ($currentUser['is_admin'] ?? false),
            $request->url(),
            $request->query(),
            max(1, (int) $request->query('page', '1')),
            20,
        );

        return view('audits.index', [
            'audits' => $audits,
            'filters' => $filters,
            'modules' => $this->audits->availableModules(),
            'scopeLabel' => $this->resolveScopeLabel($currentUser),
        ]);
    }

    public function export(Request $request): StreamedResponse|RedirectResponse
    {
        $invalidPeriod = $this->validatePeriod($request);
        if ($invalidPeriod !== null) {
            return $invalidPeriod;
        }

        $currentUser = $this->auth->currentUser();
        $filters = [
            'search' => trim((string) $request->query('busca', '')),
            'module' => trim((string) $request->query('modulo', '')),
            'date_from' => trim((string) $request->query('data_inicio', '')),
            'date_to' => trim((string) $request->query('data_fim', '')),
        ];

        $file = $this->audits->exportCsv(
            $filters,
            isset($currentUser['id']) ? (int) $currentUser['id'] : null,
            isset($currentUser['administracao_id']) ? (int) $currentUser['administracao_id'] : null,
            isset($currentUser['comum_id']) ? (int) $currentUser['comum_id'] : null,
            (bool) ($currentUser['is_admin'] ?? false),
        );

        if ($file['content'] === '') {
            $query = array_filter([
                'busca' => $filters['search'],
                'modulo' => $filters['module'],
                'data_inicio' => $filters['date_from'],
                'data_fim' => $filters['date_to'],
            ], static fn (string $value): bool => $value !== '');

            return redirect()
                ->route('migration.audits.index', $query)
                ->with('status', 'Não há eventos auditados para os filtros atuais.')
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

    private function validatePeriod(Request $request): ?RedirectResponse
    {
        $dateFrom = trim((string) $request->query('data_inicio', ''));
        $dateTo = trim((string) $request->query('data_fim', ''));
        $errors = [];

        foreach (['data_inicio' => $dateFrom, 'data_fim' => $dateTo] as $field => $value) {
            if ($value !== '' && (! preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $value) || ! \Carbon\Carbon::createFromFormat('!Y-m-d', $value))) {
                $label = $field === 'data_inicio' ? 'inicial' : 'final';
                $errors[$field] = "Data {$label} precisa usar o formato AAAA-MM-DD.";
            }
        }

        if ($errors !== []) {
            return redirect()
                ->route('migration.audits.index', $request->query())
                ->withErrors($errors);
        }
        if ($dateFrom !== '' && $dateTo !== '' && $dateTo < $dateFrom) {
            return redirect()
                ->route('migration.audits.index', $request->query())
                ->withErrors(['data_fim' => 'Data final não pode ser anterior à data inicial.']);
        }

        return null;
    }

    /**
     * @param array<string, mixed>|null $currentUser
     */
    private function resolveScopeLabel(?array $currentUser): string
    {
        if ($currentUser === null) {
            return 'Escopo atual';
        }

        if ((bool) ($currentUser['is_admin'] ?? false)) {
            return 'Todas as administrações';
        }

        if (isset($currentUser['administracao_id']) && (int) $currentUser['administracao_id'] > 0) {
            return 'Administração #' . (int) $currentUser['administracao_id'];
        }

        if (isset($currentUser['comum_id']) && (int) $currentUser['comum_id'] > 0) {
            return 'Igreja #' . (int) $currentUser['comum_id'];
        }

        return 'Escopo atual';
    }
}
