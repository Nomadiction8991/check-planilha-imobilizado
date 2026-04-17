<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Contracts\LegacyAuditTrailServiceInterface;
use App\Contracts\LegacyAuthSessionServiceInterface;
use App\DTO\LegacyAuditEntryData;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class RecordLegacyAudit
{
    public function __construct(
        private readonly LegacyAuthSessionServiceInterface $auth,
        private readonly LegacyAuditTrailServiceInterface $audits,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $beforeUser = $this->resolveCurrentUser();
        $response = $next($request);
        $afterUser = $this->resolveCurrentUser();
        $routeName = $request->route()?->getName();

        try {
            if (!$this->shouldRecord($request, $response, $routeName, $beforeUser, $afterUser)) {
                return $response;
            }

            $actor = $afterUser ?? $beforeUser;

            if ($actor === null) {
                return $response;
            }

            $this->audits->record(new LegacyAuditEntryData(
                occurredAt: now()->format('Y-m-d H:i:s'),
                userId: isset($actor['id']) ? (int) $actor['id'] : null,
                userName: (string) ($actor['nome'] ?? 'Sistema'),
                userEmail: isset($actor['email']) ? (string) $actor['email'] : null,
                administrationId: isset($actor['administracao_id']) ? (int) $actor['administracao_id'] : null,
                churchId: isset($actor['comum_id']) ? (int) $actor['comum_id'] : null,
                isAdmin: (bool) ($actor['is_admin'] ?? false),
                module: $this->resolveModuleLabel($routeName),
                action: $this->resolveActionLabel($routeName, $request->method()),
                description: $this->resolveDescription($request, $response, $routeName),
                routeName: $routeName,
                path: $request->path(),
                method: $request->method(),
                statusCode: $response->getStatusCode(),
                ipAddress: $request->ip(),
                userAgent: $this->normalizeUserAgent($request->userAgent()),
            ));
        } catch (Throwable) {
            return $response;
        }

        return $response;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveCurrentUser(): ?array
    {
        try {
            return $this->auth->currentUser();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param array<string, mixed>|null $beforeUser
     * @param array<string, mixed>|null $afterUser
     */
    private function shouldRecord(
        Request $request,
        Response $response,
        ?string $routeName,
        ?array $beforeUser,
        ?array $afterUser,
    ): bool {
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return false;
        }

        if ($response instanceof JsonResponse) {
            return $this->isSuccessfulJson($response);
        }

        if (!($response instanceof RedirectResponse)) {
            return false;
        }

        if ($routeName === 'migration.login.store') {
            return $afterUser !== null;
        }

        if ($routeName === 'migration.logout') {
            return $beforeUser !== null;
        }

        return $this->sessionString($request, 'status_type') === 'success';
    }

    private function isSuccessfulJson(JsonResponse $response): bool
    {
        $decoded = json_decode((string) $response->getContent(), true);

        if (!is_array($decoded)) {
            return false;
        }

        return (bool) ($decoded['success'] ?? $decoded['sucesso'] ?? false);
    }

    private function resolveModuleLabel(?string $routeName): string
    {
        if ($routeName === null || $routeName === '') {
            return 'Sistema';
        }

        if (
            str_contains($routeName, 'login')
            || str_contains($routeName, 'logout')
            || str_contains($routeName, 'session.church')
        ) {
            return 'Sessão';
        }

        if (str_contains($routeName, 'products')) {
            return 'Produtos';
        }

        if (str_contains($routeName, 'churches')) {
            return 'Igrejas';
        }

        if (str_contains($routeName, 'departments')) {
            return 'Dependências';
        }

        if (str_contains($routeName, 'asset-types')) {
            return 'Tipos de bem';
        }

        if (str_contains($routeName, 'administrations')) {
            return 'Administrações';
        }

        if (str_contains($routeName, 'users')) {
            return 'Usuários';
        }

        if (str_contains($routeName, 'reports')) {
            return 'Relatórios';
        }

        if (str_contains($routeName, 'spreadsheets')) {
            return 'Importação';
        }

        if (str_contains($routeName, 'audits')) {
            return 'Auditoria';
        }

        return 'Sistema';
    }

    private function resolveActionLabel(?string $routeName, string $method): string
    {
        if ($routeName === 'migration.login.store') {
            return 'Login';
        }

        if ($routeName === 'migration.logout') {
            return 'Logout';
        }

        if ($routeName === 'migration.session.church') {
            return 'Troca de igreja';
        }

        if ($routeName !== null) {
            if (str_contains($routeName, 'lookup')) {
                return 'Consulta';
            }

            if (str_contains($routeName, 'verification')) {
                return 'Checklist';
            }

            if (str_contains($routeName, 'observation')) {
                return 'Observação';
            }

            if (str_contains($routeName, 'label')) {
                return 'Etiquetas';
            }

            if (str_contains($routeName, 'sign')) {
                return 'Assinatura';
            }

            if (str_contains($routeName, 'clear-edits')) {
                return 'Limpeza';
            }

            if (str_contains($routeName, 'confirm')) {
                return 'Confirmação';
            }

            if (str_contains($routeName, 'process')) {
                return 'Processamento';
            }
        }

        return match ($method) {
            'POST' => 'Criação',
            'PUT', 'PATCH' => 'Atualização',
            'DELETE' => 'Exclusão',
            default => 'Ação',
        };
    }

    private function resolveDescription(Request $request, Response $response, ?string $routeName): string
    {
        if ($response instanceof JsonResponse) {
            $decoded = json_decode((string) $response->getContent(), true);
            if (is_array($decoded)) {
                $message = trim((string) ($decoded['message'] ?? $decoded['mensagem'] ?? ''));
                if ($message !== '') {
                    return $message;
                }
            }
        }

        $flash = trim((string) ($this->sessionString($request, 'status') ?? ''));
        if ($flash !== '') {
            return $flash;
        }

        return match ($routeName) {
            'migration.login.store' => 'Autenticação realizada com sucesso.',
            'migration.logout' => 'Sessão encerrada com sucesso.',
            'migration.session.church' => 'Igreja ativa atualizada com sucesso.',
            default => 'Ação concluída com sucesso.',
        };
    }

    private function normalizeUserAgent(?string $userAgent): ?string
    {
        $normalized = trim((string) $userAgent);

        if ($normalized === '') {
            return null;
        }

        return mb_substr($normalized, 0, 255, 'UTF-8');
    }

    private function sessionString(Request $request, string $key): ?string
    {
        if (!$request->hasSession()) {
            return null;
        }

        try {
            $value = $request->session()->get($key, '');
        } catch (Throwable) {
            return null;
        }

        $stringValue = trim((string) $value);

        return $stringValue === '' ? null : $stringValue;
    }
}
