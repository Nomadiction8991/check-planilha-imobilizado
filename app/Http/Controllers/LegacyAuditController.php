<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\LegacyAuditTrailServiceInterface;
use App\Contracts\LegacyAuthSessionServiceInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class LegacyAuditController extends Controller
{
    public function __construct(
        private readonly LegacyAuditTrailServiceInterface $audits,
        private readonly LegacyAuthSessionServiceInterface $auth,
    ) {
    }

    public function index(Request $request): View
    {
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
