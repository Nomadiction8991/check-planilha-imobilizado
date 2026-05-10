<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\LegacyUserBrowserServiceInterface;
use App\Contracts\LegacyUserManagementServiceInterface;
use App\DTO\UserFilters;
use App\Models\Legacy\Administracao;
use App\Models\Legacy\Usuario;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

final class LegacyUserManagementTest extends TestCase
{
    private Usuario $boundUser;
    private string $localidadesAsset;

    protected function setUp(): void
    {
        parent::setUp();

        $this->boundUser = $this->makeUser();
        $this->localidadesAsset = 'assets/forms/localidades.js';
        $this->app['router']->bind('user', fn (): Usuario => $this->boundUser);
        $this->app->instance(
            LegacyUserBrowserServiceInterface::class,
            new class implements LegacyUserBrowserServiceInterface
            {
                public function paginate(UserFilters $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
                {
                    return new LengthAwarePaginator(
                        items: collect(),
                        total: 0,
                        perPage: 20,
                        currentPage: 1,
                        options: ['path' => '/users']
                    );
                }

                public function administrationOptions(): Collection
                {
                    return collect([
                        (object) ['id' => 7, 'descricao' => 'Administração Central'],
                        (object) ['id' => 8, 'descricao' => 'Administração Vila Nova'],
                    ]);
                }

                public function statusOptions(): array
                {
                    return ['1' => 'Ativos', '0' => 'Inativos'];
                }

                public function countAll(): int
                {
                    return 2;
                }
            }
        );
    }

    public function testCreatePageRendersForm(): void
    {
        $response = $this->get(route('migration.users.create'));

        $response->assertOk();
        $response->assertSee('Novo usuário vinculado a uma administração.');
        $response->assertSee('Administrações permitidas');
        $response->assertDontSee('<select name="administracao_id"', false);
        $response->assertSee('Salvar usuário');
        $response->assertSee('data-mask="cpf"', false);
        $response->assertSee('data-mask="cep"', false);
        $response->assertSee($this->localidadesAsset, false);
    }

    public function testStoreCreatesUser(): void
    {
        $this->mock(
            LegacyUserManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('create')
                    ->once()
                    ->withArgs(fn ($dto): bool =>
                        $dto->administrationId === 7
                        && $dto->administrationIds === [7, 8]
                        && $dto->name === 'Maria Silva'
                        && $dto->email === 'maria@example.com'
                        && $dto->cpf === '123.456.789-09'
                        && $dto->password === 'segredo1'
                    )
                    ->andReturn($this->makeUser());
            }
        );

        $response = $this->post(route('migration.users.store'), [
            'administracao_id' => 7,
            'nome' => 'Maria Silva',
            'email' => 'maria@example.com',
            'cpf' => '123.456.789-09',
            'rg' => '12345678',
            'telefone' => '(65) 99999-0000',
            'senha' => 'segredo1',
            'confirmar_senha' => 'segredo1',
            'ativo' => '1',
            'administracoes_permitidas' => [7, 8],
        ]);

        $response->assertRedirect(route('migration.users.index'));
        $response->assertSessionHas('status', 'Usuário cadastrado com sucesso.');
    }

    public function testLegacyCreateAliasStoresUser(): void
    {
        $this->mock(
            LegacyUserManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('create')
                    ->once()
                    ->withArgs(fn ($dto): bool =>
                        $dto->administrationId === 7
                        && $dto->administrationIds === [7, 8]
                        && $dto->name === 'Maria Silva'
                        && $dto->email === 'maria@example.com'
                    )
                    ->andReturn($this->makeUser());
            }
        );

        $response = $this->post('/users/create', [
            'administracao_id' => 7,
            'nome' => 'Maria Silva',
            'email' => 'maria@example.com',
            'cpf' => '123.456.789-09',
            'rg' => '12345678',
            'telefone' => '(65) 99999-0000',
            'senha' => 'segredo1',
            'confirmar_senha' => 'segredo1',
            'ativo' => '1',
            'administracoes_permitidas' => [7, 8],
        ]);

        $response->assertRedirect(route('migration.users.index'));
        $response->assertSessionHas('status', 'Usuário cadastrado com sucesso.');
    }

    public function testEditPageRendersForm(): void
    {
        $response = $this->get(route('migration.users.edit', ['user' => 9]));

        $response->assertOk();
        $response->assertSee('Editar usuário vinculado a uma administração.');
        $response->assertSee('Administrações permitidas');
        $response->assertDontSee('<select name="administracao_id"', false);
        $response->assertSee('Salvar alterações');
        $response->assertSee('data-mask="cpf"', false);
        $response->assertSee('data-mask="cep"', false);
        $response->assertSee($this->localidadesAsset, false);
    }

    public function testUsersIndexShowsEditActionForProtectedAdministrator(): void
    {
        $administrator = $this->makeAdministratorUser();

        $this->app->instance(
            LegacyUserBrowserServiceInterface::class,
            new class ($administrator) implements LegacyUserBrowserServiceInterface
            {
                public function __construct(private readonly Usuario $administrator)
                {
                }

                public function paginate(UserFilters $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
                {
                    return new LengthAwarePaginator(
                        items: collect([$this->administrator]),
                        total: 1,
                        perPage: 20,
                        currentPage: 1,
                        options: ['path' => '/users']
                    );
                }

                public function administrationOptions(): Collection
                {
                    return collect([
                        (object) ['id' => 7, 'descricao' => 'Administração Central'],
                    ]);
                }

                public function statusOptions(): array
                {
                    return ['1' => 'Ativos', '0' => 'Inativos'];
                }

                public function countAll(): int
                {
                    return 1;
                }
            }
        );

        $response = $this->withSession(['is_admin' => true])->get(route('migration.users.index'));

        $response->assertOk();
        $response->assertSee(route('migration.users.edit', ['user' => 1]), false);
        $response->assertDontSee('Protegido');
    }

    public function testAdministratorCanOpenEditPage(): void
    {
        $administrator = $this->makeAdministratorUser();
        $this->app['router']->bind('user', fn (): Usuario => $administrator);

        $response = $this->get(route('migration.users.edit', ['user' => 1]));

        $response->assertOk();
        $response->assertSee('Editar usuário vinculado a uma administração.');
        $response->assertSee('Administrador mantém acesso total.');
        $response->assertSee('Permissões e administrações ficam travadas por regra.');
        $response->assertDontSee('<select name="administracao_id"', false);
    }

    public function testUpdateChangesUser(): void
    {
        $this->mock(
            LegacyUserManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('update')
                    ->once()
                    ->withArgs(fn (Usuario $user, $dto): bool =>
                        $user->id === 9
                        && $dto->administrationId === 8
                        && $dto->administrationIds === [8, 7]
                        && $dto->active === false
                        && $dto->email === 'maria.nova@example.com'
                    )
                    ->andReturn($this->makeUser());
            }
        );

        $response = $this->put(route('migration.users.update', ['user' => 9]), [
            'administracao_id' => 8,
            'nome' => 'Maria Silva',
            'email' => 'maria.nova@example.com',
            'cpf' => '123.456.789-09',
            'rg' => '12345678',
            'telefone' => '(65) 99999-0000',
            'ativo' => '0',
            'administracoes_permitidas' => [8, 7],
        ]);

        $response->assertRedirect(route('migration.users.index'));
        $response->assertSessionHas('status', 'Usuário atualizado com sucesso.');
    }

    public function testAdministratorUpdateKeepsFullAccess(): void
    {
        $administrator = $this->makeAdministratorUser();
        $this->app['router']->bind('user', fn (): Usuario => $administrator);

        $this->mock(
            LegacyUserManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('update')
                    ->once()
                    ->withArgs(function (Usuario $user, $dto): bool {
                        return $user->id === 1
                            && $dto->administrationId === 8
                            && $dto->administrationIds === [8]
                            && $dto->email === 'admin.novo@example.com';
                    })
                    ->andReturn($this->makeAdministratorUser());
            }
        );

        $response = $this->put(route('migration.users.update', ['user' => 1]), [
            'administracao_id' => 8,
            'nome' => 'Administrador',
            'email' => 'admin.novo@example.com',
            'cpf' => '123.456.789-09',
            'rg' => '12345678',
            'telefone' => '(65) 99999-0000',
            'ativo' => '1',
            'administracoes_permitidas' => [8],
        ]);

        $response->assertRedirect(route('migration.users.index'));
        $response->assertSessionHas('status', 'Usuário atualizado com sucesso.');
    }

    public function testDestroyDeletesUser(): void
    {
        $this->mock(
            LegacyUserManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('delete')
                    ->once()
                    ->withArgs(fn (Usuario $user): bool => $user->id === 9);
            }
        );

        $response = $this->delete(route('migration.users.destroy', ['user' => 9]));

        $response->assertRedirect(route('migration.users.index'));
        $response->assertSessionHas('status', 'Usuário excluído com sucesso.');
    }

    public function testDestroyShowsBusinessError(): void
    {
        $this->mock(
            LegacyUserManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('delete')
                    ->once()
                    ->andThrow(new RuntimeException('Você não pode deletar sua própria conta.'));
            }
        );

        $response = $this->delete(route('migration.users.destroy', ['user' => 9]));

        $response->assertRedirect(route('migration.users.index'));
        $response->assertSessionHas('status', 'Você não pode deletar sua própria conta.');
        $response->assertSessionHas('status_type', 'error');
    }

    public function testStoreRejectsInvalidFields(): void
    {
        $response = $this->from(route('migration.users.create'))
            ->post(route('migration.users.store'), [
                'nome' => '   ',
                'email' => 'invalido',
                'cpf' => '123',
                'rg' => '1',
                'telefone' => '99',
                'senha' => '123',
                'confirmar_senha' => '321',
                'administracao_id' => '',
                'casado' => '1',
                'nome_conjuge' => '',
                'cpf_conjuge' => '',
                'telefone_conjuge' => '',
            ]);

        $response->assertRedirect(route('migration.users.create'));
        $response->assertSessionHasErrors([
            'nome',
            'email',
            'cpf',
            'rg',
            'telefone',
            'senha',
            'administracao_id',
            'nome_conjuge',
            'cpf_conjuge',
            'telefone_conjuge',
        ]);
    }

    private function makeUser(): Usuario
    {
        $user = new Usuario();
        $user->forceFill([
            'id' => 9,
            'administracao_id' => 7,
            'comum_id' => null,
            'nome' => 'Maria Silva',
            'email' => 'MARIA@EXEMPLO.COM',
            'ativo' => 1,
            'cpf' => '12345678909',
            'rg' => '1234567-8',
            'telefone' => '(65) 99999-0000',
            'administracoes_permitidas' => [7],
        ]);
        $user->exists = true;
        $user->setRelation('administracao', new Administracao([
            'id' => 7,
            'descricao' => 'Administração Central',
        ]));

        return $user;
    }

    private function makeAdministratorUser(): Usuario
    {
        $user = new Usuario();
        $user->forceFill([
            'id' => 1,
            'administracao_id' => 7,
            'comum_id' => null,
            'nome' => 'Administrador',
            'email' => 'ADMIN@LOCALHOST',
            'ativo' => 1,
            'cpf' => '12345678909',
            'rg' => '1234567-8',
            'telefone' => '(65) 99999-0000',
            'tipo' => 'administrador',
            'administracoes_permitidas' => [7, 8],
            'permissions' => [
                'users.view' => true,
                'users.edit' => true,
            ],
        ]);
        $user->exists = true;
        $user->setRelation('administracao', new Administracao([
            'id' => 7,
            'descricao' => 'Administração Central',
        ]));

        return $user;
    }
}
