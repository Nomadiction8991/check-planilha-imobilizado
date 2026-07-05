<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Contracts\LegacyAuthSessionServiceInterface;
use App\Contracts\LegacyPermissionServiceInterface;
use App\Contracts\LegacyUserBrowserServiceInterface;
use App\Contracts\LegacyUserManagementServiceInterface;
use App\Http\Controllers\LegacyUserController;
use App\Models\Legacy\Usuario;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use RuntimeException;
use Tests\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class LegacyUserPermissionTest extends TestCase
{
    private LegacyUserBrowserServiceInterface $users;
    private LegacyUserManagementServiceInterface $userManager;
    private LegacyAuthSessionServiceInterface $auth;
    private LegacyPermissionServiceInterface $permissions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->users = $this->createMock(LegacyUserBrowserServiceInterface::class);
        $this->userManager = $this->createMock(LegacyUserManagementServiceInterface::class);
        $this->auth = $this->createMock(LegacyAuthSessionServiceInterface::class);
        $this->permissions = $this->createMock(LegacyPermissionServiceInterface::class);
    }

    // ─── permissions() — display the permissions page ─────────────────────

    public function testPermissionsRedirectsForProtectedAdministrator(): void
    {
        $this->bootstrapSession();

        $controller = $this->makeController();
        $user = $this->makeAdministratorUser();

        $response = $controller->permissions($user);

        self::assertTrue($response->isRedirect());
        self::assertSame(route('migration.users.index'), $response->getTargetUrl());
        self::assertSame(
            'O usuário administrador já possui todas as permissões.',
            session('status'),
        );
        self::assertSame('error', session('status_type'));
    }

    public function testPermissionsReturnsViewForRegularUser(): void
    {
        $this->bootstrapSession();

        $controller = $this->makeController();
        $user = $this->makeRegularUser();

        $response = $controller->permissions($user);

        self::assertInstanceOf(View::class, $response);
        self::assertSame('users.permissions', $response->getName());

        /** @var array{user: Usuario, groups: array, currentPermissions: array} $data */
        $data = $response->getData();
        self::assertSame($user, $data['user']);
        self::assertIsArray($data['groups']);
        self::assertNotEmpty($data['groups'], 'Expected at least one permission group from config');
        self::assertIsArray($data['currentPermissions']);
        self::assertSame(['dashboard.view' => true], $data['currentPermissions']);
    }

    public function testPermissionsProvidesCurrentUserPermissionsToView(): void
    {
        $this->bootstrapSession();

        $controller = $this->makeController();
        $user = $this->makeRegularUser();

        $response = $controller->permissions($user);

        /** @var array{currentPermissions: array} $data */
        $data = $response->getData();

        self::assertTrue($data['currentPermissions']['dashboard.view']);
    }

    public function testPermissionsRedirectsWhenOutOfScope(): void
    {
        $this->bootstrapSession(['_enforce_legacy_auth' => true]);

        $this->auth->method('currentUser')->willReturn([
            'id' => 5,
            'nome' => 'Restrito',
            'email' => 'restrito@exemplo.com',
            'administracao_id' => 10,
            'comum_id' => null,
            'administracoes_permitidas' => [10],
            'is_admin' => false,
        ]);
        $this->permissions->method('can')->willReturn(false);

        $controller = $this->makeController();
        $user = $this->makeRegularUser(); // administracao_id = 7

        $response = $controller->permissions($user);

        self::assertTrue($response->isRedirect());
        self::assertSame(route('migration.users.index'), $response->getTargetUrl());
        self::assertSame(
            'Você só pode gerenciar usuários da sua própria administração.',
            session('status'),
        );
        self::assertSame('error', session('status_type'));
    }

    public function testPermissionsAllowsAdminCurrentUserEvenOutOfScope(): void
    {
        $this->bootstrapSession(['_enforce_legacy_auth' => true]);

        $this->auth->method('currentUser')->willReturn([
            'id' => 1,
            'nome' => 'Admin',
            'email' => 'ADMIN@LOCALHOST',
            'administracao_id' => 10,
            'comum_id' => null,
            'administracoes_permitidas' => [10],
            'is_admin' => true,
        ]);

        $controller = $this->makeController();
        $user = $this->makeRegularUser(); // administracao_id = 7

        $response = $controller->permissions($user);

        self::assertInstanceOf(View::class, $response);
        self::assertSame('users.permissions', $response->getName());
    }

    public function testPermissionsAllowsCrossAdministrationPermission(): void
    {
        $this->bootstrapSession(['_enforce_legacy_auth' => true]);

        $this->auth->method('currentUser')->willReturn([
            'id' => 5,
            'nome' => 'MultiAdmin',
            'email' => 'multi@exemplo.com',
            'administracao_id' => 10,
            'comum_id' => null,
            'administracoes_permitidas' => [10],
            'is_admin' => false,
        ]);
        $this->permissions->method('can')->willReturnCallback(
            fn (string $ability): bool => $ability === 'users.manage_other_administrations',
        );

        $controller = $this->makeController();
        $user = $this->makeRegularUser(); // administracao_id = 7

        $response = $controller->permissions($user);

        self::assertInstanceOf(View::class, $response);
        self::assertSame('users.permissions', $response->getName());
    }

    // ─── updatePermissions() — save permissions ──────────────────────────

    public function testUpdatePermissionsRedirectsForProtectedAdministrator(): void
    {
        $this->bootstrapSession();

        $controller = $this->makeController();
        $user = $this->makeAdministratorUser();
        $request = Request::create('/users/1/permissions', 'PUT', [
            'abilities' => ['users.view' => '1'],
        ]);

        $response = $controller->updatePermissions($request, $user);

        self::assertTrue($response->isRedirect());
        self::assertSame(route('migration.users.index'), $response->getTargetUrl());
        self::assertSame(
            'O usuário administrador já possui todas as permissões.',
            session('status'),
        );
        self::assertSame('error', session('status_type'));
    }

    public function testUpdatePermissionsCallsManagerAndRedirectsWithSuccess(): void
    {
        $this->bootstrapSession();

        $user = $this->makeRegularUser();
        $expectedPermissions = ['users.view' => true, 'users.edit' => true];

        $this->userManager->expects($this->once())
            ->method('updatePermissions')
            ->with($user, $expectedPermissions)
            ->willReturn($user);

        $controller = $this->makeController();
        $request = Request::create('/users/9/permissions', 'PUT', [
            'abilities' => ['users.view' => '1', 'users.edit' => '1'],
        ]);

        $response = $controller->updatePermissions($request, $user);

        self::assertTrue($response->isRedirect());
        self::assertSame(route('migration.users.index'), $response->getTargetUrl());
        self::assertSame(
            'Permissões do usuário atualizadas com sucesso.',
            session('status'),
        );
        self::assertSame('success', session('status_type'));
    }

    public function testUpdatePermissionsShowsBusinessError(): void
    {
        $this->bootstrapSession();

        $user = $this->makeRegularUser();

        $this->userManager->expects($this->once())
            ->method('updatePermissions')
            ->willThrowException(new RuntimeException('Erro ao atualizar permissões.'));

        $controller = $this->makeController();
        $request = Request::create('/users/9/permissions', 'PUT', [
            'abilities' => ['users.view' => '1'],
        ]);

        $response = $controller->updatePermissions($request, $user);

        self::assertTrue($response->isRedirect());
        self::assertSame(
            route('migration.users.permissions', ['user' => $user->id]),
            $response->getTargetUrl(),
        );
        self::assertSame('Erro ao atualizar permissões.', session('status'));
        self::assertSame('error', session('status_type'));
    }

    public function testUpdatePermissionsWithEmptyAbilitiesPassesEmptyArray(): void
    {
        $this->bootstrapSession();

        $user = $this->makeRegularUser();

        $this->userManager->expects($this->once())
            ->method('updatePermissions')
            ->with($user, [])
            ->willReturn($user);

        $controller = $this->makeController();
        $request = Request::create('/users/9/permissions', 'PUT', []);

        $response = $controller->updatePermissions($request, $user);

        self::assertTrue($response->isRedirect());
        self::assertSame(route('migration.users.index'), $response->getTargetUrl());
    }

    public function testUpdatePermissionsRedirectsWhenOutOfScope(): void
    {
        $this->bootstrapSession(['_enforce_legacy_auth' => true]);

        $this->auth->method('currentUser')->willReturn([
            'id' => 5,
            'nome' => 'Restrito',
            'email' => 'restrito@exemplo.com',
            'administracao_id' => 10,
            'comum_id' => null,
            'administracoes_permitidas' => [10],
            'is_admin' => false,
        ]);
        $this->permissions->method('can')->willReturn(false);

        $controller = $this->makeController();
        $user = $this->makeRegularUser();
        $request = Request::create('/users/9/permissions', 'PUT', [
            'abilities' => ['users.view' => '1'],
        ]);

        $response = $controller->updatePermissions($request, $user);

        self::assertTrue($response->isRedirect());
        self::assertSame(route('migration.users.index'), $response->getTargetUrl());
        self::assertSame(
            'Você só pode gerenciar usuários da sua própria administração.',
            session('status'),
        );
        self::assertSame('error', session('status_type'));
    }

    public function testUpdatePermissionsForSameAdministrationPassesScope(): void
    {
        $this->bootstrapSession(['_enforce_legacy_auth' => true]);

        // Current user is from same administracao_id (7) as the target user
        $this->auth->method('currentUser')->willReturn([
            'id' => 5,
            'nome' => 'MesmaAdmin',
            'email' => 'mesma@exemplo.com',
            'administracao_id' => 7,
            'comum_id' => null,
            'administracoes_permitidas' => [7],
            'is_admin' => false,
        ]);
        $this->permissions->method('can')->willReturn(false);

        $user = $this->makeRegularUser(); // administracao_id = 7

        $this->userManager->expects($this->once())
            ->method('updatePermissions')
            ->with($user, ['users.view' => true])
            ->willReturn($user);

        $controller = $this->makeController();
        $request = Request::create('/users/9/permissions', 'PUT', [
            'abilities' => ['users.view' => '1'],
        ]);

        $response = $controller->updatePermissions($request, $user);

        self::assertTrue($response->isRedirect());
        self::assertSame(route('migration.users.index'), $response->getTargetUrl());
        self::assertSame(
            'Permissões do usuário atualizadas com sucesso.',
            session('status'),
        );
    }

    // ─── Helpers ─────────────────────────────────────────────────────────

    private function makeController(): LegacyUserController
    {
        return new LegacyUserController(
            $this->users,
            $this->userManager,
            $this->auth,
            $this->permissions,
        );
    }

    private function makeRegularUser(): Usuario
    {
        $user = new Usuario();
        $user->forceFill([
            'id' => 9,
            'administracao_id' => 7,
            'nome' => 'Maria Silva',
            'email' => 'maria@exemplo.com',
            'ativo' => 1,
            'tipo' => 'usuario',
            'permissions' => ['dashboard.view' => true],
        ]);
        $user->exists = true;

        return $user;
    }

    private function makeAdministratorUser(): Usuario
    {
        $user = new Usuario();
        $user->forceFill([
            'id' => 1,
            'administracao_id' => 7,
            'nome' => 'Administrador',
            'email' => 'ADMIN@LOCALHOST',
            'ativo' => 1,
            'tipo' => 'administrador',
            'permissions' => ['dashboard.view' => true, 'users.view' => true],
        ]);
        $user->exists = true;

        return $user;
    }

    /**
     * @param array<string, mixed> $sessionData
     */
    private function bootstrapSession(array $sessionData = []): void
    {
        $request = Request::create('/test-session-bootstrap', 'GET');
        $session = $this->app->make('session')->driver();
        $session->setId('test-session-' . bin2hex(random_bytes(4)));

        foreach ($sessionData as $key => $value) {
            $session->put($key, $value);
        }

        $request->setLaravelSession($session);
        $this->app->instance('request', $request);

        // Ensure the redirector and URL generator use this session for
        // flash data and route URL generation.
        $this->app['redirect']->setSession($session);
    }
}
