<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\LegacyDepartmentBrowserServiceInterface;
use App\Contracts\LegacyDepartmentManagementServiceInterface;
use App\DTO\DepartmentFilters;
use App\Models\Legacy\Dependencia;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

final class LegacyDepartmentManagementTest extends TestCase
{
    private Dependencia $boundDepartment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->boundDepartment = $this->makeDepartment(
            id: 2,
            churchId: 7,
            description: 'SALAO',
        );

        $this->app['router']->bind('department', fn (): Dependencia => $this->boundDepartment);
        $this->app->instance(
            LegacyDepartmentBrowserServiceInterface::class,
            new class implements LegacyDepartmentBrowserServiceInterface
            {
                public function paginate(DepartmentFilters $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
                {
                    return new LengthAwarePaginator(
                        items: collect(),
                        total: 0,
                        perPage: 20,
                        currentPage: 1,
                        options: ['path' => '/departments']
                    );
                }

                public function churchOptions(): Collection
                {
                    return collect([
                        (object) ['id' => 7, 'codigo' => '12-3456', 'descricao' => 'Central Cuiabá'],
                        (object) ['id' => 8, 'codigo' => '12-9999', 'descricao' => 'Vila Nova'],
                    ]);
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
        $response = $this->get(route('migration.departments.create'));

        $response->assertOk();
        $response->assertSee('Nova dependência.');
        $response->assertSee('Salvar dependência');
    }

    public function testStoreCreatesDepartment(): void
    {
        $this->mock(
            LegacyDepartmentManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('create')
                    ->once()
                    ->withArgs(fn ($dto): bool => $dto->churchId === 7 && $dto->description === 'Salao Central')
                    ->andReturn($this->makeDepartment(id: 15, churchId: 7, description: 'SALAO CENTRAL'));
            }
        );

        $response = $this->post(route('migration.departments.store'), [
            'comum_id' => 7,
            'descricao' => 'Salao Central',
        ]);

        $response->assertRedirect(route('migration.departments.index'));
        $response->assertSessionHas('status', 'Dependência criada com sucesso.');
        $response->assertSessionHas('status_detail', 'ID gerado: 15.');
    }

    public function testLegacyCreateAliasStoresDepartment(): void
    {
        $this->mock(
            LegacyDepartmentManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('create')
                    ->once()
                    ->withArgs(fn ($dto): bool => $dto->churchId === 7 && $dto->description === 'Salao Central')
                    ->andReturn($this->makeDepartment(id: 16, churchId: 7, description: 'SALAO CENTRAL'));
            }
        );

        $response = $this->post('/departments/create', [
            'comum_id' => 7,
            'descricao' => 'Salao Central',
        ]);

        $response->assertRedirect(route('migration.departments.index'));
        $response->assertSessionHas('status', 'Dependência criada com sucesso.');
    }

    public function testStoreShowsRuntimeValidationError(): void
    {
        $this->mock(
            LegacyDepartmentManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('create')
                    ->once()
                    ->andThrow(new RuntimeException('Já existe uma dependência com essa descrição para a igreja selecionada.'));
            }
        );

        $response = $this->from(route('migration.departments.create'))
            ->post(route('migration.departments.store'), [
                'comum_id' => 7,
                'descricao' => 'Salao',
            ]);

        $response->assertRedirect(route('migration.departments.create'));
        $response->assertSessionHas('status', 'Já existe uma dependência com essa descrição para a igreja selecionada.');
        $response->assertSessionHas('status_type', 'error');
    }

    public function testUpdateChangesDepartment(): void
    {
        $this->mock(
            LegacyDepartmentManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('update')
                    ->once()
                    ->withArgs(
                        fn (Dependencia $department, $dto): bool =>
                            $department->id === 2
                            && $dto->churchId === 8
                            && $dto->description === 'Novo Salao'
                    )
                    ->andReturn($this->makeDepartment(id: 2, churchId: 8, description: 'NOVO SALAO'));
            }
        );

        $response = $this->put(route('migration.departments.update', ['department' => 2]), [
            'comum_id' => 8,
            'descricao' => 'Novo Salao',
        ]);

        $response->assertRedirect(route('migration.departments.index'));
        $response->assertSessionHas('status', 'Dependência atualizada com sucesso.');
    }

    public function testDestroyDeletesUnusedDepartment(): void
    {
        $this->mock(
            LegacyDepartmentManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('delete')
                    ->once()
                    ->withArgs(fn (Dependencia $department): bool => $department->id === 2);
            }
        );

        $response = $this->delete(route('migration.departments.destroy', ['department' => 2]));

        $response->assertRedirect(route('migration.departments.index'));
        $response->assertSessionHas('status', 'Dependência excluída com sucesso.');
    }

    public function testDestroyBlocksDeletionWhenProductsAreLinked(): void
    {
        $this->mock(
            LegacyDepartmentManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('delete')
                    ->once()
                    ->andThrow(new RuntimeException('Esta dependência não pode ser excluída porque já está vinculada a produtos.'));
            }
        );

        $response = $this->delete(route('migration.departments.destroy', ['department' => 2]));

        $response->assertRedirect(route('migration.departments.index'));
        $response->assertSessionHas('status', 'Esta dependência não pode ser excluída porque já está vinculada a produtos.');
        $response->assertSessionHas('status_type', 'error');
    }

    public function testStoreRejectsBlankFields(): void
    {
        $response = $this->from(route('migration.departments.create'))
            ->post(route('migration.departments.store'), [
                'comum_id' => 0,
                'descricao' => '   ',
            ]);

        $response->assertRedirect(route('migration.departments.create'));
        $response->assertSessionHasErrors(['comum_id', 'descricao']);
    }

    private function makeDepartment(int $id, int $churchId, string $description): Dependencia
    {
        $department = new Dependencia();
        $department->forceFill([
            'id' => $id,
            'comum_id' => $churchId,
            'descricao' => $description,
        ]);
        $department->exists = true;

        return $department;
    }
}
