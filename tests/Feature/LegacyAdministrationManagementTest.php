<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\LegacyAuthSessionServiceInterface;
use App\Contracts\LegacyAdministrationBrowserServiceInterface;
use App\Contracts\LegacyAdministrationManagementServiceInterface;
use App\Contracts\LegacyNavigationServiceInterface;
use App\DTO\AdministrationFilters;
use App\Models\Legacy\Administracao;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

final class LegacyAdministrationManagementTest extends TestCase
{
    private Administracao $boundAdministration;
    private string $localidadesAsset;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(
            LegacyAuthSessionServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('currentUser')->andReturn([
                    'id' => 1,
                    'nome' => 'Administrador',
                    'email' => 'ADMIN@LOCALHOST',
                    'comum_id' => null,
                    'administracao_id' => null,
                    'administracoes_permitidas' => [4],
                    'is_admin' => true,
                ]);
                $mock->shouldReceive('currentChurch')->andReturn(null);
                $mock->shouldReceive('availableChurches')->andReturn(collect());
                $mock->shouldReceive('filterPinStates')->andReturn([]);
            }
        );

        $this->mock(
            LegacyNavigationServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('navigation')->andReturn([]);
                $mock->shouldReceive('editorItems')->andReturn([]);
            }
        );

        $this->boundAdministration = $this->makeAdministration(
            id: 4,
            description: 'Administração Central',
            cnpj: '12345678000190',
        );
        $this->localidadesAsset = 'assets/forms/localidades.js';

        $this->app['router']->bind('administration', fn (): Administracao => $this->boundAdministration);
        $this->app->instance(
            LegacyAdministrationBrowserServiceInterface::class,
            new class implements LegacyAdministrationBrowserServiceInterface
            {
                public function paginate(AdministrationFilters $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
                {
                    return new LengthAwarePaginator(
                        items: collect([
                            (object) [
                                'id' => 4,
                                'descricao' => 'Administração Central',
                                'cnpj' => '12345678000190',
                            ],
                        ]),
                        total: 1,
                        perPage: 20,
                        currentPage: 1,
                        options: ['path' => '/administrations']
                    );
                }

                public function countAll(): int
                {
                    return 1;
                }
            }
        );
    }

    public function testIndexPageRendersList(): void
    {
        $response = $this->withSession([
            'usuario_id' => 1,
            'usuario_nome' => 'Administrador',
            'usuario_email' => 'ADMIN@LOCALHOST',
            'is_admin' => true,
            'legacy_permissions' => [
                'administrations.view' => true,
                'administrations.create' => true,
                'administrations.edit' => true,
                'administrations.delete' => true,
            ],
        ])->get(route('migration.administrations.index'));

        $response->assertOk();
        $response->assertSee('Administrações do sistema.');
        $response->assertSee('Administração Central');
        $response->assertSee('12345678000190');
        $response->assertSee('Nova administração');
    }

    public function testCreatePageRendersForm(): void
    {
        $response = $this->get(route('migration.administrations.create'));

        $response->assertOk();
        $response->assertSee('Nova administração.');
        $response->assertSee('Salvar administração');
        $response->assertSee('assets/forms/input-mask.js', false);
        $response->assertSee($this->localidadesAsset, false);
        $response->assertSee('data-mask="cnpj"', false);
    }

    public function testStoreCreatesAdministration(): void
    {
        $this->mock(
            LegacyAdministrationManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('create')
                    ->once()
                    ->withArgs(fn ($dto): bool =>
                        $dto->description === 'Administração Regional'
                        && $dto->cnpj === '12345678000191'
                    )
                    ->andReturn($this->makeAdministration(
                        id: 11,
                        description: 'Administração Regional',
                        cnpj: '12345678000191'
                    ));
            }
        );

        $response = $this->post(route('migration.administrations.store'), [
            'descricao' => 'Administração Regional',
            'cnpj' => '12345678000191',
            'estado' => 'MT',
            'cidade' => 'Cuiabá',
        ]);

        $response->assertRedirect(route('migration.administrations.index'));
        $response->assertSessionHas('status', 'Administração criada com sucesso.');
        $response->assertSessionHas('status_detail', 'ID gerado: 11.');
    }

    public function testUpdateChangesAdministrationDescription(): void
    {
        $this->mock(
            LegacyAdministrationManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('update')
                    ->once()
                    ->withArgs(
                        fn (Administracao $administration, $dto): bool =>
                            $administration->id === 4
                            && $dto->description === 'Administração Atualizada'
                            && $dto->cnpj === '12345678000192'
                    )
                    ->andReturn($this->makeAdministration(
                        id: 4,
                        description: 'Administração Atualizada',
                        cnpj: '12345678000192'
                    ));
            }
        );

        $response = $this->put(route('migration.administrations.update', ['administration' => 4]), [
            'descricao' => 'Administração Atualizada',
            'cnpj' => '12345678000192',
            'estado' => 'MT',
            'cidade' => 'Cuiabá',
        ]);

        $response->assertRedirect(route('migration.administrations.index'));
        $response->assertSessionHas('status', 'Administração atualizada com sucesso.');
    }

    public function testEditPageRendersForm(): void
    {
        $response = $this->get(route('migration.administrations.edit', ['administration' => 4]));

        $response->assertOk();
        $response->assertSee('Editar administração.');
        $response->assertSee('Salvar alterações');
        $response->assertSee('data-mask="cnpj"', false);
        $response->assertSee($this->localidadesAsset, false);
    }

    public function testDestroyDeletesUnusedAdministration(): void
    {
        $this->mock(
            LegacyAdministrationManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('delete')
                    ->once()
                    ->withArgs(fn (Administracao $administration): bool => $administration->id === 4);
            }
        );

        $response = $this->delete(route('migration.administrations.destroy', ['administration' => 4]));

        $response->assertRedirect(route('migration.administrations.index'));
        $response->assertSessionHas('status', 'Administração excluída com sucesso.');
    }

    public function testDestroyShowsBusinessError(): void
    {
        $this->mock(
            LegacyAdministrationManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('delete')
                    ->once()
                    ->andThrow(new RuntimeException('Esta administração não pode ser excluída porque já está vinculada a importações.'));
            }
        );

        $response = $this->delete(route('migration.administrations.destroy', ['administration' => 4]));

        $response->assertRedirect(route('migration.administrations.index'));
        $response->assertSessionHas('status', 'Esta administração não pode ser excluída porque já está vinculada a importações.');
        $response->assertSessionHas('status_type', 'error');
    }

    public function testDestroyShowsBusinessErrorWhenLinkedToUsers(): void
    {
        $this->mock(
            LegacyAdministrationManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('delete')
                    ->once()
                    ->andThrow(new RuntimeException('Esta administração não pode ser excluída porque já está vinculada a usuários.'));
            }
        );

        $response = $this->delete(route('migration.administrations.destroy', ['administration' => 4]));

        $response->assertRedirect(route('migration.administrations.index'));
        $response->assertSessionHas('status', 'Esta administração não pode ser excluída porque já está vinculada a usuários.');
        $response->assertSessionHas('status_type', 'error');
    }

    public function testStoreRejectsBlankDescription(): void
    {
        $response = $this->from(route('migration.administrations.create'))
            ->post(route('migration.administrations.store'), [
                'descricao' => '   ',
                'cnpj' => '12345678000193',
                'estado' => 'MT',
                'cidade' => 'Cuiabá',
            ]);

        $response->assertRedirect(route('migration.administrations.create'));
        $response->assertSessionHasErrors(['descricao']);
    }

    public function testStoreRejectsBlankCnpj(): void
    {
        $response = $this->from(route('migration.administrations.create'))
            ->post(route('migration.administrations.store'), [
                'descricao' => 'Administração Regional',
                'cnpj' => '   ',
                'estado' => 'MT',
                'cidade' => 'Cuiabá',
            ]);

        $response->assertRedirect(route('migration.administrations.create'));
        $response->assertSessionHasErrors(['cnpj']);
    }

    private function makeAdministration(int $id, string $description, string $cnpj): Administracao
    {
        $administration = new Administracao();
        $administration->forceFill([
            'id' => $id,
            'descricao' => $description,
            'cnpj' => $cnpj,
        ]);
        $administration->exists = true;

        return $administration;
    }
}
