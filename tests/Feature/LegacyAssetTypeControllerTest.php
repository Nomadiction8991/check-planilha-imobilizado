<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\LegacyAssetTypeBrowserServiceInterface;
use App\Contracts\LegacyAssetTypeManagementServiceInterface;
use App\Contracts\LegacyAuthSessionServiceInterface;
use App\Contracts\LegacyNavigationServiceInterface;
use App\DTO\AssetTypeFilters;
use App\Models\Legacy\Administracao;
use App\Models\Legacy\TipoBem;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

final class LegacyAssetTypeControllerTest extends TestCase
{
    private const int ADMINISTRATION_ID = 99;
    private Administracao $administration;
    private TipoBem $boundAssetType;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock auth session to prevent view composer from querying DB.
        $this->mock(
            LegacyAuthSessionServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('currentUser')
                    ->andReturn([
                        'id' => 1,
                        'nome' => 'Administrador',
                        'email' => 'ADMIN@LOCALHOST',
                        'comum_id' => null,
                        'administracao_id' => null,
                        'administracoes_permitidas' => [self::ADMINISTRATION_ID],
                        'is_admin' => true,
                    ]);
                $mock->shouldReceive('currentChurch')->andReturn(null);
                $mock->shouldReceive('availableChurches')->andReturn(collect());
                $mock->shouldReceive('filterPinStates')->andReturn([]);
            }
        );

        // Mock navigation service so the layout doesn't hit the DB.
        $this->mock(
            LegacyNavigationServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('navigation')->andReturn([]);
                $mock->shouldReceive('editorItems')->andReturn([]);
            }
        );

        $this->createAdministracoesTable();

        $this->administration = Administracao::query()->create([
            'id' => self::ADMINISTRATION_ID,
            'descricao' => 'Administração Central',
        ]);

        $this->boundAssetType = $this->makeAssetType(
            id: 4,
            code: 4,
            description: 'CADEIRA ANTIGA',
            administrationId: self::ADMINISTRATION_ID,
        );

        $this->app['router']->bind('assetType', fn (): TipoBem => $this->boundAssetType);
    }

    // ─── Index ───────────────────────────────────────────────────────

    public function testIndexPageRendersPaginatedList(): void
    {
        $assetType = $this->makeAssetType(
            id: 5,
            code: 10,
            description: 'MESA ESCRITÓRIO',
            administrationId: self::ADMINISTRATION_ID,
        );

        $this->mockBrowserService(paginatorItems: [$assetType], totalCount: 1);

        $response = $this->withSession($this->authSession())
            ->get(route('migration.asset-types.index'));

        $response->assertOk();
        $response->assertSee('MESA ESCRITÓRIO');
        $response->assertSee('10');
    }

    public function testIndexPageRespectsSearchFilter(): void
    {
        $this->mockBrowserService(paginatorItems: [], totalCount: 0);

        $response = $this->withSession($this->authSession())
            ->get(route('migration.asset-types.index', ['busca' => 'NONEXISTENT']));

        $response->assertOk();
    }

    public function testIndexPageRedirectsGuestsToLogin(): void
    {
        $response = $this->withSession(['_enforce_legacy_auth' => true])
            ->get(route('migration.asset-types.index'));

        $response->assertRedirect(route('migration.login'));
    }

    // ─── Create ──────────────────────────────────────────────────────

    public function testCreatePageRendersForm(): void
    {
        $response = $this->withSession($this->authSession())
            ->get(route('migration.asset-types.create'));

        $response->assertOk();
        $response->assertSee('Novo tipo de bem.');
        $response->assertSee('Administração');
        $response->assertSee('Salvar tipo de bem');
    }

    public function testCreatePageRedirectsGuestsToLogin(): void
    {
        $response = $this->withSession(['_enforce_legacy_auth' => true])
            ->get(route('migration.asset-types.create'));

        $response->assertRedirect(route('migration.login'));
    }

    // ─── Store ───────────────────────────────────────────────────────

    public function testStoreCreatesAssetType(): void
    {
        $this->mock(
            LegacyAssetTypeManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('create')
                    ->once()
                    ->withArgs(fn ($dto): bool =>
                        $dto->description === 'IMOVEIS'
                        && $dto->administrationId === self::ADMINISTRATION_ID
                    )
                    ->andReturn($this->makeAssetType(
                        id: 9, code: 41, description: 'IMOVEIS',
                        administrationId: self::ADMINISTRATION_ID,
                    ));
            }
        );

        $response = $this->withSession($this->authSession())
            ->post(route('migration.asset-types.store'), [
                'administracao_id' => (string) self::ADMINISTRATION_ID,
                'descricao' => 'IMOVEIS',
            ]);

        $response->assertRedirect(route('migration.asset-types.index'));
        $response->assertSessionHas('status', 'Tipo de bem criado com sucesso.');
        $response->assertSessionHas('status_detail', 'Código gerado: 41.');
    }

    public function testStoreViaLegacyCreateAlias(): void
    {
        $this->mock(
            LegacyAssetTypeManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('create')
                    ->once()
                    ->andReturn($this->makeAssetType(
                        id: 10, code: 42, description: 'MESA',
                        administrationId: self::ADMINISTRATION_ID,
                    ));
            }
        );

        $response = $this->withSession($this->authSession())
            ->post('/asset-types/create', [
                'administracao_id' => (string) self::ADMINISTRATION_ID,
                'descricao' => 'MESA',
            ]);

        $response->assertRedirect(route('migration.asset-types.index'));
        $response->assertSessionHas('status', 'Tipo de bem criado com sucesso.');
    }

    public function testStoreValidatesRequiredFields(): void
    {
        $response = $this->withSession($this->authSession())
            ->from(route('migration.asset-types.create'))
            ->post(route('migration.asset-types.store'), [
                'administracao_id' => '',
                'descricao' => '',
            ]);

        $response->assertRedirect(route('migration.asset-types.create'));
        $response->assertSessionHasErrors(['administracao_id', 'descricao']);
    }

    public function testStoreValidatesBlankDescription(): void
    {
        $response = $this->withSession($this->authSession())
            ->from(route('migration.asset-types.create'))
            ->post(route('migration.asset-types.store'), [
                'administracao_id' => (string) self::ADMINISTRATION_ID,
                'descricao' => '   ',
            ]);

        $response->assertRedirect(route('migration.asset-types.create'));
        $response->assertSessionHasErrors(['descricao']);
    }

    // ─── Edit ────────────────────────────────────────────────────────

    public function testEditPageRendersForm(): void
    {
        $response = $this->withSession($this->authSession())
            ->get(route('migration.asset-types.edit', ['assetType' => $this->boundAssetType->id]));

        $response->assertOk();
        $response->assertSee('Editar tipo de bem');
        $response->assertSee('CADEIRA ANTIGA');
        $response->assertSee('Salvar alterações');
    }

    // ─── Update ──────────────────────────────────────────────────────

    public function testUpdateChangesAssetType(): void
    {
        $this->mock(
            LegacyAssetTypeManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('update')
                    ->once()
                    ->withArgs(fn (TipoBem $assetType, $dto): bool =>
                        $assetType->id === 4
                        && $dto->description === 'CADEIRA ATUALIZADA'
                    )
                    ->andReturn($this->makeAssetType(
                        id: 4, code: 4, description: 'CADEIRA ATUALIZADA',
                        administrationId: self::ADMINISTRATION_ID,
                    ));
            }
        );

        $response = $this->withSession($this->authSession())
            ->put(route('migration.asset-types.update', ['assetType' => 4]), [
                'administracao_id' => (string) self::ADMINISTRATION_ID,
                'descricao' => 'CADEIRA ATUALIZADA',
            ]);

        $response->assertRedirect(route('migration.asset-types.index'));
        $response->assertSessionHas('status', 'Tipo de bem atualizado com sucesso.');
    }

    public function testUpdateViaLegacyPostAlias(): void
    {
        $this->mock(
            LegacyAssetTypeManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('update')
                    ->once()
                    ->andReturn($this->makeAssetType(
                        id: 4, code: 4, description: 'CADEIRA ATUALIZADA',
                        administrationId: self::ADMINISTRATION_ID,
                    ));
            }
        );

        $response = $this->withSession($this->authSession())
            ->post(route('migration.asset-types.update.post', ['assetType' => 4]), [
                'administracao_id' => (string) self::ADMINISTRATION_ID,
                'descricao' => 'CADEIRA ATUALIZADA',
            ]);

        $response->assertRedirect(route('migration.asset-types.index'));
        $response->assertSessionHas('status', 'Tipo de bem atualizado com sucesso.');
    }

    public function testUpdateValidatesRequiredFields(): void
    {
        $response = $this->withSession($this->authSession())
            ->from(route('migration.asset-types.edit', ['assetType' => 4]))
            ->put(route('migration.asset-types.update', ['assetType' => 4]), [
                'administracao_id' => '',
                'descricao' => '',
            ]);

        $response->assertRedirect(route('migration.asset-types.edit', ['assetType' => 4]));
        $response->assertSessionHasErrors(['administracao_id', 'descricao']);
    }

    // ─── Destroy ─────────────────────────────────────────────────────

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

        $response = $this->withSession($this->authSession())
            ->delete(route('migration.asset-types.destroy', ['assetType' => 4]));

        $response->assertRedirect(route('migration.asset-types.index'));
        $response->assertSessionHas('status', 'Tipo de bem excluído com sucesso.');
    }

    public function testDestroyViaLegacyPostAlias(): void
    {
        $this->mock(
            LegacyAssetTypeManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('delete')
                    ->once()
                    ->withArgs(fn (TipoBem $assetType): bool => $assetType->id === 4);
            }
        );

        $response = $this->withSession($this->authSession())
            ->post(route('migration.asset-types.destroy.post', ['assetType' => 4]));

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
                    ->andThrow(new RuntimeException(
                        'Este tipo de bem não pode ser excluído porque já está vinculado a produtos.'
                    ));
            }
        );

        $response = $this->withSession($this->authSession())
            ->delete(route('migration.asset-types.destroy', ['assetType' => 4]));

        $response->assertRedirect(route('migration.asset-types.index'));
        $response->assertSessionHas(
            'status',
            'Este tipo de bem não pode ser excluído porque já está vinculado a produtos.'
        );
        $response->assertSessionHas('status_type', 'error');
    }

    // ─── Helpers ─────────────────────────────────────────────────────

    private function createAdministracoesTable(): void
    {
        DB::statement('
            CREATE TABLE IF NOT EXISTS "administracoes" (
                "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                "descricao" VARCHAR(255) NOT NULL,
                "cnpj" VARCHAR(255) DEFAULT NULL,
                "estado" VARCHAR(255) DEFAULT NULL,
                "cidade" VARCHAR(255) DEFAULT NULL
            )
        ');
    }

    /**
     * @return array<string, bool|int|string|null>
     */
    private function authSession(): array
    {
        return [
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'comum_id' => 7,
            'is_admin' => true,
        ];
    }

    private function makeAssetType(
        int $id,
        int $code,
        string $description,
        int $administrationId,
    ): TipoBem {
        $assetType = new TipoBem();
        $assetType->forceFill([
            'id' => $id,
            'administracao_id' => $administrationId,
            'codigo' => $code,
            'descricao' => $description,
        ]);
        $assetType->exists = true;
        $assetType->setRelation('administracao', new Administracao([
            'id' => $administrationId,
            'descricao' => 'Administração Central',
        ]));

        return $assetType;
    }

    /**
     * @param list<TipoBem> $paginatorItems
     */
    private function mockBrowserService(array $paginatorItems, int $totalCount): void
    {
        $this->app->instance(
            LegacyAssetTypeBrowserServiceInterface::class,
            new class($paginatorItems, $totalCount) implements LegacyAssetTypeBrowserServiceInterface
            {
                /**
                 * @param list<TipoBem> $paginatorItems
                 */
                public function __construct(
                    private readonly array $paginatorItems,
                    private readonly int $totalCount,
                ) {
                }

                public function paginate(AssetTypeFilters $filters): LengthAwarePaginator
                {
                    return new LengthAwarePaginator(
                        items: collect($this->paginatorItems),
                        total: $this->totalCount,
                        perPage: 20,
                        currentPage: 1,
                        options: ['path' => '/asset-types'],
                    );
                }

                public function countAll(): int
                {
                    return $this->totalCount;
                }
            }
        );
    }
}
