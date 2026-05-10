<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\LegacyChurchBrowserServiceInterface;
use App\Contracts\LegacyChurchManagementServiceInterface;
use App\DTO\ChurchFilters;
use App\Models\Legacy\Comum;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

final class LegacyChurchManagementTest extends TestCase
{
    private Comum $boundChurch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->boundChurch = $this->makeChurch();
        $this->app['router']->bind('church', fn (): Comum => $this->boundChurch);
        $this->app->instance(
            LegacyChurchBrowserServiceInterface::class,
            new class implements LegacyChurchBrowserServiceInterface
            {
                public function paginate(ChurchFilters $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
                {
                    return new LengthAwarePaginator(
                        items: collect(),
                        total: 0,
                        perPage: 20,
                        currentPage: 1,
                        options: ['path' => '/churches']
                    );
                }

                public function countAll(): int
                {
                    return 1;
                }

                public function administrationOptions(): Collection
                {
                    return collect([
                        (object) ['id' => 4, 'descricao' => 'Administração Central'],
                    ]);
                }
            }
        );
    }

    public function testEditPageRendersForm(): void
    {
        $response = $this->get(route('migration.churches.edit', ['church' => 7]));

        $response->assertOk();
        $response->assertSee('Editar igreja.');
        $response->assertSee('Salvar alterações');
        $response->assertSee('12-3456');
        $response->assertSee('assets/forms/input-mask.js', false);
        $response->assertSee('assets/forms/localidades.js', false);
        $response->assertSee('data-mask="cnpj"', false);
    }

    public function testUpdateChangesChurchData(): void
    {
        $this->mock(
            LegacyChurchManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('update')
                    ->once()
                    ->withArgs(fn (Comum $church, $dto): bool =>
                        $church->id === 7
                        && $dto->description === 'Central Atualizada'
                        && $dto->cnpj === '12.345.678/0001-90'
                        && $dto->state === 'MT'
                        && $dto->city === 'Cuiaba'
                        && $dto->sector === 'Norte'
                    )
                    ->andReturn($this->makeChurch(description: 'CENTRAL ATUALIZADA'));
            }
        );

        $response = $this->put(route('migration.churches.update', ['church' => 7]), [
            'administracao_id' => 4,
            'descricao' => 'Central Atualizada',
            'cnpj' => '12.345.678/0001-90',
            'estado' => 'MT',
            'cidade' => 'Cuiaba',
            'setor' => 'Norte',
        ]);

        $response->assertRedirect(route('migration.churches.index'));
        $response->assertSessionHas('status', 'Igreja atualizada com sucesso.');
    }

    public function testUpdateShowsBusinessError(): void
    {
        $this->mock(
            LegacyChurchManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('update')
                    ->once()
                    ->andThrow(new RuntimeException('CNPJ inválido: CNPJ deve conter exatamente 14 caracteres.'));
            }
        );

        $response = $this->from(route('migration.churches.edit', ['church' => 7]))
            ->put(route('migration.churches.update', ['church' => 7]), [
                'administracao_id' => 4,
                'descricao' => 'Central Atualizada',
                'cnpj' => '123',
                'estado' => 'MT',
                'cidade' => 'Cuiaba',
                'setor' => 'Norte',
            ]);

        $response->assertRedirect(route('migration.churches.edit', ['church' => 7]));
        $response->assertSessionHas('status', 'CNPJ inválido: CNPJ deve conter exatamente 14 caracteres.');
        $response->assertSessionHas('status_type', 'error');
    }

    public function testUpdateRejectsMissingFields(): void
    {
        $response = $this->from(route('migration.churches.edit', ['church' => 7]))
            ->put(route('migration.churches.update', ['church' => 7]), [
                'administracao_id' => '',
                'descricao' => '   ',
                'cnpj' => '',
                'estado' => '',
                'cidade' => '',
                'setor' => '',
            ]);

        $response->assertRedirect(route('migration.churches.edit', ['church' => 7]));
        $response->assertSessionHasErrors([
            'administracao_id',
            'descricao',
            'cnpj',
            'estado',
            'cidade',
        ]);
    }

    public function testProductsCountReturnsLegacyJsonPayload(): void
    {
        $this->mock(
            LegacyChurchManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('countProducts')
                    ->once()
                    ->with(7)
                    ->andReturn(14);
            }
        );

        $response = $this->get(route('migration.churches.products-count', ['comum_id' => 7]));

        $response->assertOk();
        $response->assertJson([
            'count' => 14,
        ]);
    }

    public function testDeleteProductsRedirectsWithSuccessMessage(): void
    {
        $this->mock(
            LegacyChurchManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('findChurch')
                    ->once()
                    ->with(7)
                    ->andReturn($this->makeChurch());
                $mock->shouldReceive('deleteProducts')
                    ->once()
                    ->withArgs(fn (Comum $church): bool => $church->id === 7)
                    ->andReturn(5);
            }
        );

        $response = $this->post(route('migration.churches.delete-products'), [
            'comum_id' => 7,
        ]);

        $response->assertRedirect(route('migration.churches.index'));
        $response->assertSessionHas('status', 'Todos os 5 produto(s) da igreja CENTRAL CUIABÁ foram excluídos.');
        $response->assertSessionHas('status_type', 'success');
    }

    private function makeChurch(string $description = 'Central Cuiabá'): Comum
    {
        $church = new Comum();
        $church->forceFill([
            'id' => 7,
            'codigo' => '12-3456',
            'descricao' => $description,
            'cnpj' => '12.345.678/0001-90',
            'estado' => 'MT',
            'cidade' => 'Cuiaba',
            'estado_administracao' => 'SP',
            'cidade_administracao' => 'Campinas',
            'setor' => 'Centro',
        ]);
        $church->exists = true;

        return $church;
    }
}
