<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Contracts\LegacyAuthSessionServiceInterface;
use App\Contracts\LegacyPermissionServiceInterface;
use App\Contracts\LegacyUserBrowserServiceInterface;
use App\Contracts\LegacyUserManagementServiceInterface;
use App\DTO\UserFilters;
use App\DTO\UserMutationData;
use App\Http\Controllers\LegacyUserController;
use App\Models\Legacy\Usuario;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

final class LegacyUserControllerTest extends TestCase
{
    private LegacyUserBrowserServiceInterface&MockInterface $users;

    private LegacyUserManagementServiceInterface&MockInterface $userManager;

    private LegacyAuthSessionServiceInterface&MockInterface $auth;

    private LegacyPermissionServiceInterface&MockInterface $permissions;

    private LegacyUserController $controller;

    private Usuario $regularUser;

    private Usuario $protectedAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        // Attach session to the current request so ensureUserWithinScope() doesn't blow up
        $request = Request::create('/dummy', 'GET');
        $request->setLaravelSession($this->app->make('session')->driver());
        $this->app->instance('request', $request);

        $this->users = Mockery::mock(LegacyUserBrowserServiceInterface::class);
        $this->userManager = Mockery::mock(LegacyUserManagementServiceInterface::class);
        $this->auth = Mockery::mock(LegacyAuthSessionServiceInterface::class);
        $this->permissions = Mockery::mock(LegacyPermissionServiceInterface::class);

        $this->controller = new LegacyUserController(
            $this->users,
            $this->userManager,
            $this->auth,
            $this->permissions,
        );

        $this->regularUser = new Usuario();
        $this->regularUser->forceFill([
            'id' => 5,
            'administracao_id' => 7,
            'nome' => 'Maria Silva',
            'email' => 'maria@exemplo.com',
            'tipo' => 'usuario',
        ]);
        $this->regularUser->exists = true;

        $this->protectedAdmin = new Usuario();
        $this->protectedAdmin->forceFill([
            'id' => 1,
            'administracao_id' => 7,
            'nome' => 'Administrador',
            'email' => 'ADMIN@LOCALHOST',
            'tipo' => 'administrador',
        ]);
        $this->protectedAdmin->exists = true;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ========== INDEX ==========

    public function testIndexReturnsViewWithExpectedData(): void
    {
        $request = new Request(['busca' => 'maria', 'status' => '1']);

        $filters = UserFilters::fromRequest($request);

        $paginator = Mockery::mock(LengthAwarePaginator::class);
        $paginator->shouldReceive('appends')
            ->once()
            ->with($filters->toQuery())
            ->andReturnSelf();

        $this->users->shouldReceive('paginate')
            ->once()
            ->with(Mockery::on(fn (UserFilters $f): bool => $f->search === 'maria' && $f->status === '1'))
            ->andReturn($paginator);

        $administrationOptions = collect([
            (object) ['id' => 7, 'descricao' => 'Central'],
        ]);
        $this->users->shouldReceive('administrationOptions')->once()->andReturn($administrationOptions);
        $this->users->shouldReceive('statusOptions')->once()->andReturn(['1' => 'Ativos']);
        $this->users->shouldReceive('countAll')->once()->andReturn(42);

        /** @var View $view */
        $view = $this->controller->index($request);

        $this->assertInstanceOf(View::class, $view);
        $this->assertSame('users.index', $view->name());

        $data = $view->getData();
        $this->assertArrayHasKey('filters', $data);
        $this->assertArrayHasKey('users', $data);
        $this->assertArrayHasKey('administrations', $data);
        $this->assertArrayHasKey('statusOptions', $data);
        $this->assertArrayHasKey('totalAll', $data);
        $this->assertSame($administrationOptions, $data['administrations']);
        $this->assertSame(['1' => 'Ativos'], $data['statusOptions']);
        $this->assertSame(42, $data['totalAll']);
    }

    public function testIndexWithEmptyQueryParamsUsesDefaults(): void
    {
        $request = new Request();

        $paginator = Mockery::mock(LengthAwarePaginator::class);
        $paginator->shouldReceive('appends')->once()->with([])->andReturnSelf();

        $this->users->shouldReceive('paginate')
            ->once()
            ->with(Mockery::on(fn (UserFilters $f): bool =>
                $f->administrationId === null
                && $f->search === ''
                && $f->status === ''
                && $f->page === 1
            ))
            ->andReturn($paginator);

        $this->users->shouldReceive('administrationOptions')->once()->andReturn(collect());
        $this->users->shouldReceive('statusOptions')->once()->andReturn([]);
        $this->users->shouldReceive('countAll')->once()->andReturn(0);

        $view = $this->controller->index($request);

        $this->assertInstanceOf(View::class, $view);
        $this->assertSame('users.index', $view->name());
    }

    // ========== CREATE ==========

    public function testCreateReturnsViewWithOptions(): void
    {
        $administrationOptions = collect([
            (object) ['id' => 7, 'descricao' => 'Central'],
            (object) ['id' => 8, 'descricao' => 'Vila Nova'],
        ]);

        $this->users->shouldReceive('administrationOptions')->once()->andReturn($administrationOptions);

        /** @var View $view */
        $view = $this->controller->create();

        $this->assertInstanceOf(View::class, $view);
        $this->assertSame('users.create', $view->name());

        $data = $view->getData();
        $this->assertArrayHasKey('administrations', $data);
        $this->assertArrayHasKey('states', $data);
        $this->assertSame($administrationOptions, $data['administrations']);
        $this->assertIsArray($data['states']);
    }

    // ========== STORE ==========

    public function testStoreCreatesUserAndRedirects(): void
    {
        $dto = $this->makeMutationDto();
        $request = Mockery::mock('App\Http\Requests\StoreLegacyUserRequest');
        $request->shouldReceive('toDto')->once()->andReturn($dto);

        $this->userManager->shouldReceive('create')->once()->with($dto)->andReturn($this->regularUser);

        /** @var RedirectResponse $response */
        $response = $this->controller->store($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(route('migration.users.index'), $response->getTargetUrl());
        $this->assertSame('Usuário cadastrado com sucesso.', session('status'));
        $this->assertSame('success', session('status_type'));
    }

    public function testStoreReturnsErrorRedirectOnRuntimeException(): void
    {
        $dto = $this->makeMutationDto();
        $request = Mockery::mock('App\Http\Requests\StoreLegacyUserRequest');
        $request->shouldReceive('toDto')->once()->andReturn($dto);

        $this->userManager->shouldReceive('create')
            ->once()
            ->with($dto)
            ->andThrow(new RuntimeException('E-mail já cadastrado.'));

        /** @var RedirectResponse $response */
        $response = $this->controller->store($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(route('migration.users.create'), $response->getTargetUrl());
        $this->assertSame('E-mail já cadastrado.', session('status'));
        $this->assertSame('error', session('status_type'));
    }

    // ========== EDIT ==========

    public function testEditReturnsViewWithUser(): void
    {
        $this->users->shouldReceive('administrationOptions')->once()->andReturn(collect());

        /** @var View $view */
        $view = $this->controller->edit($this->regularUser);

        $this->assertInstanceOf(View::class, $view);
        $this->assertSame('users.edit', $view->name());

        $data = $view->getData();
        $this->assertArrayHasKey('user', $data);
        $this->assertArrayHasKey('administrations', $data);
        $this->assertArrayHasKey('states', $data);
        $this->assertSame($this->regularUser, $data['user']);
    }

    public function testEditRedirectsWhenUserOutOfScope(): void
    {
        $this->enableScopeEnforcement();
        $this->auth->shouldReceive('currentUser')
            ->once()
            ->andReturn([
                'id' => 2,
                'nome' => 'João',
                'email' => 'joao@example.com',
                'administracao_id' => 8,
                'comum_id' => null,
                'administracoes_permitidas' => [8],
                'is_admin' => false,
            ]);
        $this->permissions->shouldReceive('can')
            ->once()
            ->with('users.manage_other_administrations')
            ->andReturn(false);

        $differentUser = new Usuario();
        $differentUser->forceFill(['id' => 5, 'administracao_id' => 7]);
        $differentUser->exists = true;

        /** @var RedirectResponse $response */
        $response = $this->controller->edit($differentUser);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(route('migration.users.index'), $response->getTargetUrl());
        $this->assertSame('error', session('status_type'));
    }

    // ========== UPDATE ==========

    public function testUpdateChangesUserAndRedirects(): void
    {
        $dto = $this->makeMutationDto();
        $request = Mockery::mock('App\Http\Requests\UpdateLegacyUserRequest');
        $request->shouldReceive('toDto')->once()->andReturn($dto);

        $this->userManager->shouldReceive('update')
            ->once()
            ->with($this->regularUser, $dto)
            ->andReturn($this->regularUser);

        /** @var RedirectResponse $response */
        $response = $this->controller->update($request, $this->regularUser);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(route('migration.users.index'), $response->getTargetUrl());
        $this->assertSame('Usuário atualizado com sucesso.', session('status'));
        $this->assertSame('success', session('status_type'));
    }

    public function testUpdateReturnsErrorRedirectOnRuntimeException(): void
    {
        $dto = $this->makeMutationDto();
        $request = Mockery::mock('App\Http\Requests\UpdateLegacyUserRequest');
        $request->shouldReceive('toDto')->once()->andReturn($dto);

        $this->userManager->shouldReceive('update')
            ->once()
            ->with($this->regularUser, $dto)
            ->andThrow(new RuntimeException('E-mail já cadastrado.'));

        /** @var RedirectResponse $response */
        $response = $this->controller->update($request, $this->regularUser);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(
            route('migration.users.edit', ['user' => $this->regularUser->id]),
            $response->getTargetUrl(),
        );
        $this->assertSame('E-mail já cadastrado.', session('status'));
        $this->assertSame('error', session('status_type'));
    }

    // ========== PERMISSIONS ==========

    public function testPermissionsReturnsViewForRegularUser(): void
    {
        /** @var View $view */
        $view = $this->controller->permissions($this->regularUser);

        $this->assertInstanceOf(View::class, $view);
        $this->assertSame('users.permissions', $view->name());

        $data = $view->getData();
        $this->assertArrayHasKey('user', $data);
        $this->assertArrayHasKey('groups', $data);
        $this->assertArrayHasKey('currentPermissions', $data);
        $this->assertSame($this->regularUser, $data['user']);
    }

    public function testPermissionsRedirectsForProtectedAdmin(): void
    {
        /** @var RedirectResponse $response */
        $response = $this->controller->permissions($this->protectedAdmin);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(route('migration.users.index'), $response->getTargetUrl());
        $this->assertSame('O usuário administrador já possui todas as permissões.', session('status'));
        $this->assertSame('error', session('status_type'));
    }

    public function testPermissionsRedirectsWhenUserOutOfScope(): void
    {
        $this->enableScopeEnforcement();
        $this->auth->shouldReceive('currentUser')
            ->once()
            ->andReturn([
                'id' => 2,
                'nome' => 'João',
                'email' => 'joao@example.com',
                'administracao_id' => 8,
                'comum_id' => null,
                'administracoes_permitidas' => [8],
                'is_admin' => false,
            ]);
        $this->permissions->shouldReceive('can')
            ->once()
            ->with('users.manage_other_administrations')
            ->andReturn(false);

        $differentUser = new Usuario();
        $differentUser->forceFill(['id' => 5, 'administracao_id' => 7]);
        $differentUser->exists = true;

        /** @var RedirectResponse $response */
        $response = $this->controller->permissions($differentUser);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('error', session('status_type'));
    }

    // ========== UPDATE PERMISSIONS ==========

    public function testUpdatePermissionsSucceeds(): void
    {
        $request = new Request(['abilities' => ['users.view', 'users.edit']]);

        $this->userManager->shouldReceive('updatePermissions')
            ->once()
            ->with($this->regularUser, ['users.view', 'users.edit'])
            ->andReturn($this->regularUser);

        /** @var RedirectResponse $response */
        $response = $this->controller->updatePermissions($request, $this->regularUser);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(route('migration.users.index'), $response->getTargetUrl());
        $this->assertSame('Permissões do usuário atualizadas com sucesso.', session('status'));
        $this->assertSame('success', session('status_type'));
    }

    public function testUpdatePermissionsWithoutAbilitiesPassesEmptyArray(): void
    {
        $request = new Request();

        $this->userManager->shouldReceive('updatePermissions')
            ->once()
            ->with($this->regularUser, [])
            ->andReturn($this->regularUser);

        /** @var RedirectResponse $response */
        $response = $this->controller->updatePermissions($request, $this->regularUser);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('Permissões do usuário atualizadas com sucesso.', session('status'));
    }

    public function testUpdatePermissionsHandlesRuntimeException(): void
    {
        $request = new Request(['abilities' => ['users.view']]);

        $this->userManager->shouldReceive('updatePermissions')
            ->once()
            ->andThrow(new RuntimeException('Erro ao atualizar permissões.'));

        /** @var RedirectResponse $response */
        $response = $this->controller->updatePermissions($request, $this->regularUser);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(
            route('migration.users.permissions', ['user' => $this->regularUser->id]),
            $response->getTargetUrl(),
        );
        $this->assertSame('Erro ao atualizar permissões.', session('status'));
        $this->assertSame('error', session('status_type'));
    }

    public function testUpdatePermissionsRedirectsForProtectedAdmin(): void
    {
        $request = new Request();

        /** @var RedirectResponse $response */
        $response = $this->controller->updatePermissions($request, $this->protectedAdmin);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('O usuário administrador já possui todas as permissões.', session('status'));
        $this->assertSame('error', session('status_type'));
    }

    // ========== DESTROY ==========

    public function testDestroyDeletesUserAndRedirects(): void
    {
        $this->userManager->shouldReceive('delete')->once()->with($this->regularUser);

        /** @var RedirectResponse $response */
        $response = $this->controller->destroy($this->regularUser);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(route('migration.users.index'), $response->getTargetUrl());
        $this->assertSame('Usuário excluído com sucesso.', session('status'));
        $this->assertSame('success', session('status_type'));
    }

    public function testDestroyHandlesRuntimeException(): void
    {
        $this->userManager->shouldReceive('delete')
            ->once()
            ->andThrow(new RuntimeException('Você não pode deletar sua própria conta.'));

        /** @var RedirectResponse $response */
        $response = $this->controller->destroy($this->regularUser);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(route('migration.users.index'), $response->getTargetUrl());
        $this->assertSame('Você não pode deletar sua própria conta.', session('status'));
        $this->assertSame('error', session('status_type'));
    }

    public function testDestroyRedirectsForProtectedAdmin(): void
    {
        $request = new Request();

        /** @var RedirectResponse $response */
        $response = $this->controller->destroy($this->protectedAdmin);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('O usuário administrador não pode ser editado ou excluído.', session('status'));
        $this->assertSame('error', session('status_type'));
        $this->userManager->shouldNotHaveReceived('delete');
    }

    public function testDestroyRedirectsWhenUserOutOfScope(): void
    {
        $this->enableScopeEnforcement();
        $this->auth->shouldReceive('currentUser')
            ->once()
            ->andReturn([
                'id' => 2,
                'nome' => 'João',
                'email' => 'joao@example.com',
                'administracao_id' => 8,
                'comum_id' => null,
                'administracoes_permitidas' => [8],
                'is_admin' => false,
            ]);
        $this->permissions->shouldReceive('can')
            ->once()
            ->with('users.manage_other_administrations')
            ->andReturn(false);

        $differentUser = new Usuario();
        $differentUser->forceFill(['id' => 5, 'administracao_id' => 7]);
        $differentUser->exists = true;

        /** @var RedirectResponse $response */
        $response = $this->controller->destroy($differentUser);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('error', session('status_type'));
        $this->userManager->shouldNotHaveReceived('delete');
    }

    // ========== ENSURE USER WITHIN SCOPE ==========

    public function testEnsureUserWithinScopeBypassesInTestingWithoutEnforcement(): void
    {
        // Without _enforce_legacy_auth, the scope check returns null
        $this->users->shouldReceive('administrationOptions')->once()->andReturn(collect());

        $anyUser = new Usuario();
        $anyUser->forceFill(['id' => 5, 'administracao_id' => 99]);
        $anyUser->exists = true;

        /** @var View $view */
        $view = $this->controller->edit($anyUser);

        $this->assertInstanceOf(View::class, $view);
    }

    public function testEnsureUserWithinScopePassesForAdminUser(): void
    {
        $this->enableScopeEnforcement();
        $this->auth->shouldReceive('currentUser')
            ->once()
            ->andReturn([
                'id' => 1,
                'nome' => 'Admin',
                'email' => 'admin@localhost',
                'administracao_id' => 7,
                'comum_id' => null,
                'administracoes_permitidas' => [7, 8],
                'is_admin' => true,
            ]);

        $this->users->shouldReceive('administrationOptions')->once()->andReturn(collect());

        $differentUser = new Usuario();
        $differentUser->forceFill(['id' => 5, 'administracao_id' => 99]);
        $differentUser->exists = true;

        /** @var View $view */
        $view = $this->controller->edit($differentUser);

        $this->assertInstanceOf(View::class, $view);
    }

    public function testEnsureUserWithinScopePassesForCrossAdministrationPermission(): void
    {
        $this->enableScopeEnforcement();
        $this->auth->shouldReceive('currentUser')
            ->once()
            ->andReturn([
                'id' => 2,
                'nome' => 'João',
                'email' => 'joao@example.com',
                'administracao_id' => 7,
                'comum_id' => null,
                'administracoes_permitidas' => [7],
                'is_admin' => false,
            ]);
        $this->permissions->shouldReceive('can')
            ->once()
            ->with('users.manage_other_administrations')
            ->andReturn(true);

        $this->users->shouldReceive('administrationOptions')->once()->andReturn(collect());

        $differentUser = new Usuario();
        $differentUser->forceFill(['id' => 5, 'administracao_id' => 99]);
        $differentUser->exists = true;

        /** @var View $view */
        $view = $this->controller->edit($differentUser);

        $this->assertInstanceOf(View::class, $view);
    }

    public function testEnsureUserWithinScopePassesForSameAdministration(): void
    {
        $this->enableScopeEnforcement();
        $this->auth->shouldReceive('currentUser')
            ->once()
            ->andReturn([
                'id' => 2,
                'nome' => 'João',
                'email' => 'joao@example.com',
                'administracao_id' => 7,
                'comum_id' => null,
                'administracoes_permitidas' => [7],
                'is_admin' => false,
            ]);
        $this->permissions->shouldReceive('can')
            ->once()
            ->with('users.manage_other_administrations')
            ->andReturn(false);

        $this->users->shouldReceive('administrationOptions')->once()->andReturn(collect());

        $sameAdminUser = new Usuario();
        $sameAdminUser->forceFill(['id' => 5, 'administracao_id' => 7]);
        $sameAdminUser->exists = true;

        /** @var View $view */
        $view = $this->controller->edit($sameAdminUser);

        $this->assertInstanceOf(View::class, $view);
    }

    // ========== HELPERS ==========

    private function enableScopeEnforcement(): void
    {
        session()->put('_enforce_legacy_auth', true);
    }

    private function makeMutationDto(): UserMutationData
    {
        return new UserMutationData(
            administrationId: 7,
            administrationIds: [7, 8],
            name: 'Maria Silva',
            email: 'maria@example.com',
            active: true,
            cpf: '123.456.789-09',
            rg: '12345678',
            rgEqualsCpf: false,
            phone: '(65) 99999-0000',
            married: false,
            spouseName: '',
            spouseCpf: '',
            spouseRg: '',
            spouseRgEqualsCpf: false,
            spousePhone: '',
            addressZip: '',
            addressStreet: '',
            addressNumber: '',
            addressComplement: '',
            addressDistrict: '',
            addressCity: '',
            addressState: '',
            permissions: [],
            permissionsProvided: false,
            password: 'segredo1',
        );
    }
}
