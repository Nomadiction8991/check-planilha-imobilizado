<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\LegacyAssetTypeManagementServiceInterface;
use App\Models\Legacy\TipoBem;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

final class LegacyAssetTypeManagementTest extends TestCase
{
    private TipoBem $boundAssetType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->boundAssetType = $this->makeAssetType(
            id: 4,
            code: 4,
            description: 'CADEIRA ANTIGA',
        );

        $this->app['router']->bind('assetType', fn (): TipoBem => $this->boundAssetType);
    }

    public function testCreatePageRendersForm(): void
    {
        $response = $this->get(route('migration.asset-types.create'));

        $response->assertOk();
        $response->assertSee('Novo tipo de bem.');
        $response->assertSee('Salvar tipo de bem');
    }

    public function testStoreCreatesAssetTypeWithSequentialCode(): void
    {
        $this->mock(
            LegacyAssetTypeManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('create')
                    ->once()
                    ->withArgs(fn ($dto): bool => $dto->description === 'IMOVEIS')
                    ->andReturn($this->makeAssetType(id: 9, code: 41, description: 'IMOVEIS'));
            }
        );

        $response = $this->post(route('migration.asset-types.store'), [
            'descricao' => 'IMOVEIS',
        ]);

        $response->assertRedirect(route('migration.asset-types.index'));
        $response->assertSessionHas('status', 'Tipo de bem criado com sucesso.');
        $response->assertSessionHas('status_detail', 'Código gerado: 41.');
    }

    public function testLegacyCreateAliasStoresAssetType(): void
    {
        $this->mock(
            LegacyAssetTypeManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('create')
                    ->once()
                    ->withArgs(fn ($dto): bool => $dto->description === 'MESA')
                    ->andReturn($this->makeAssetType(id: 10, code: 42, description: 'MESA'));
            }
        );

        $response = $this->post('/asset-types/create', [
            'descricao' => 'MESA',
        ]);

        $response->assertRedirect(route('migration.asset-types.index'));
        $response->assertSessionHas('status', 'Tipo de bem criado com sucesso.');
    }

    public function testUpdateChangesAssetTypeDescription(): void
    {
        $this->mock(
            LegacyAssetTypeManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('update')
                    ->once()
                    ->withArgs(fn (TipoBem $assetType, $dto): bool => $assetType->id === 4 && $dto->description === 'CADEIRA ATUALIZADA')
                    ->andReturn($this->makeAssetType(id: 4, code: 4, description: 'CADEIRA ATUALIZADA'));
            }
        );

        $response = $this->put(route('migration.asset-types.update', ['assetType' => 4]), [
            'descricao' => 'CADEIRA ATUALIZADA',
        ]);

        $response->assertRedirect(route('migration.asset-types.index'));
        $response->assertSessionHas('status', 'Tipo de bem atualizado com sucesso.');
    }

    public function testDestroyDeletesUnusedAssetType(): void
    {
        $this->mock(
            LegacyAssetTypeManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('delete')
                    ->once()
                    ->withArgs(fn (TipoBem $assetType): bool => $assetType->id === 4);
            }
        );

        $response = $this->delete(route('migration.asset-types.destroy', ['assetType' => 4]));

        $response->assertRedirect(route('migration.asset-types.index'));
        $response->assertSessionHas('status', 'Tipo de bem excluído com sucesso.');
    }

    public function testDestroyBlocksDeletionWhenProductsAreLinked(): void
    {
        $this->mock(
            LegacyAssetTypeManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('delete')
                    ->once()
                    ->andThrow(new RuntimeException('Este tipo de bem não pode ser excluído porque já está vinculado a produtos.'));
            }
        );

        $response = $this->delete(route('migration.asset-types.destroy', ['assetType' => 4]));

        $response->assertRedirect(route('migration.asset-types.index'));
        $response->assertSessionHas(
            'status',
            'Este tipo de bem não pode ser excluído porque já está vinculado a produtos.'
        );
        $response->assertSessionHas('status_type', 'error');
    }

    public function testStoreRejectsBlankDescription(): void
    {
        $response = $this->from(route('migration.asset-types.create'))
            ->post(route('migration.asset-types.store'), [
                'descricao' => '   ',
            ]);

        $response->assertRedirect(route('migration.asset-types.create'));
        $response->assertSessionHasErrors(['descricao']);
    }

    private function makeAssetType(int $id, int $code, string $description): TipoBem
    {
        $assetType = new TipoBem();
        $assetType->forceFill([
            'id' => $id,
            'codigo' => $code,
            'descricao' => $description,
        ]);
        $assetType->exists = true;

        return $assetType;
    }
}
