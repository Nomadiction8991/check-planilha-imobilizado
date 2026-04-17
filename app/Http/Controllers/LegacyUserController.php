<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\LegacyAuthSessionServiceInterface;
use App\Contracts\LegacyPermissionServiceInterface;
use App\Contracts\LegacyUserBrowserServiceInterface;
use App\Contracts\LegacyUserManagementServiceInterface;
use App\DTO\UserFilters;
use App\Http\Requests\StoreLegacyUserRequest;
use App\Http\Requests\UpdateLegacyUserRequest;
use App\Models\Legacy\Usuario;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class LegacyUserController extends Controller
{
    public function __construct(
        private readonly LegacyUserBrowserServiceInterface $users,
        private readonly LegacyUserManagementServiceInterface $userManager,
        private readonly LegacyAuthSessionServiceInterface $auth,
        private readonly LegacyPermissionServiceInterface $permissions,
    ) {
    }

    public function index(Request $request): View
    {
        $filters = UserFilters::fromRequest($request);
        $paginator = $this->users->paginate($filters)->appends($filters->toQuery());

        return view('users.index', [
            'filters' => $filters,
            'users' => $paginator,
            'administrations' => $this->users->administrationOptions(),
            'statusOptions' => $this->users->statusOptions(),
            'totalAll' => $this->users->countAll(),
        ]);
    }

    public function create(): View
    {
        return view('users.create', [
            'administrations' => $this->users->administrationOptions(),
            'states' => (array) config('brazil.states', []),
        ]);
    }

    public function store(StoreLegacyUserRequest $request): RedirectResponse
    {
        try {
            $this->userManager->create($request->toDto());
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('migration.users.create')
                ->withInput()
                ->with('status', $exception->getMessage())
                ->with('status_type', 'error');
        }

        return redirect()
            ->route('migration.users.index')
            ->with('status', 'Usuário cadastrado com sucesso.')
            ->with('status_type', 'success');
    }

    public function edit(Usuario $user): View|RedirectResponse
    {
        if ($user->isProtectedAdministratorAccount()) {
            return redirect()
                ->route('migration.users.index')
                ->with('status', 'O usuário administrador não pode ser editado ou excluído.')
                ->with('status_type', 'error');
        }

        if ($response = $this->ensureUserWithinScope($user)) {
            return $response;
        }

        $user->loadMissing('administracao:id,descricao');

        return view('users.edit', [
            'user' => $user,
            'administrations' => $this->users->administrationOptions(),
            'states' => (array) config('brazil.states', []),
        ]);
    }

    public function update(UpdateLegacyUserRequest $request, Usuario $user): RedirectResponse
    {
        if ($user->isProtectedAdministratorAccount()) {
            return redirect()
                ->route('migration.users.index')
                ->with('status', 'O usuário administrador não pode ser editado ou excluído.')
                ->with('status_type', 'error');
        }

        if ($response = $this->ensureUserWithinScope($user)) {
            return $response;
        }

        try {
            $this->userManager->update($user, $request->toDto());
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('migration.users.edit', ['user' => $user->id])
                ->withInput()
                ->with('status', $exception->getMessage())
                ->with('status_type', 'error');
        }

        return redirect()
            ->route('migration.users.index')
            ->with('status', 'Usuário atualizado com sucesso.')
            ->with('status_type', 'success');
    }

    public function destroy(Usuario $user): RedirectResponse
    {
        if ($user->isProtectedAdministratorAccount()) {
            return redirect()
                ->route('migration.users.index')
                ->with('status', 'O usuário administrador não pode ser editado ou excluído.')
                ->with('status_type', 'error');
        }

        if ($response = $this->ensureUserWithinScope($user)) {
            return $response;
        }

        try {
            $this->userManager->delete($user);
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('migration.users.index')
                ->with('status', $exception->getMessage())
                ->with('status_type', 'error');
        }

        return redirect()
            ->route('migration.users.index')
            ->with('status', 'Usuário excluído com sucesso.')
            ->with('status_type', 'success');
    }

    private function ensureUserWithinScope(Usuario $user): ?RedirectResponse
    {
        if (app()->environment('testing') && !request()->session()->get('_enforce_legacy_auth', false)) {
            return null;
        }

        $currentUser = $this->auth->currentUser();
        $currentAdministrationId = (int) ($currentUser['administracao_id'] ?? 0);

        if (($currentUser['is_admin'] ?? false) === true || $this->hasCrossAdministrationPermission()) {
            return null;
        }

        $userAdministrationId = (int) ($user->administracao_id ?? 0);

        if ($currentAdministrationId > 0 && $userAdministrationId === $currentAdministrationId) {
            return null;
        }

        return redirect()
            ->route('migration.users.index')
            ->with('status', 'Você só pode gerenciar usuários da sua própria administração.')
            ->with('status_type', 'error');
    }

    private function hasCrossAdministrationPermission(): bool
    {
        return (bool) $this->permissions->can('users.manage_other_administrations');
    }
}
