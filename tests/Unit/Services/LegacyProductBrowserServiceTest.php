<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTO\ProductFilters;
use App\Models\Legacy\Administracao;
use App\Models\Legacy\Comum;
use App\Models\Legacy\Dependencia;
use App\Models\Legacy\Produto;
use App\Models\Legacy\TipoBem;
use App\Services\LegacyProductBrowserService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

final class LegacyProductBrowserServiceTest extends TestCase
{
    private LegacyProductBrowserService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('administracoes', function (Blueprint $table): void {
            $table->id();
            $table->string('descricao')->nullable();
            $table->string('estado', 2)->nullable();
            $table->string('cidade')->nullable();
            $table->string('cnpj')->nullable();
        });

        Schema::create('comums', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo')->nullable();
            $table->string('descricao')->nullable();
            $table->unsignedBigInteger('administracao_id')->nullable();
            $table->string('estado', 2)->nullable();
        });

        Schema::create('dependencias', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('comum_id')->nullable();
            $table->string('descricao')->nullable();
        });

        Schema::create('tipos_bens', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo')->nullable();
            $table->string('descricao')->nullable();
            $table->unsignedBigInteger('administracao_id')->nullable();
        });

        Schema::create('produtos', function (Blueprint $table): void {
            $table->id('id_produto');
            $table->unsignedBigInteger('comum_id')->nullable();
            $table->unsignedBigInteger('dependencia_id')->nullable();
            $table->unsignedBigInteger('tipo_bem_id')->nullable();
            $table->unsignedBigInteger('editado_tipo_bem_id')->nullable();
            $table->unsignedBigInteger('editado_dependencia_id')->nullable();
            $table->string('codigo')->nullable();
            $table->string('bem')->nullable();
            $table->string('complemento')->nullable();
            $table->integer('novo')->default(0);
            $table->integer('editado')->default(0);
            $table->integer('ativo')->default(1);
            $table->integer('imprimir_14_1')->default(0);
            $table->string('nota_numero')->nullable();
        });

        Session::put('is_admin', true);
        $this->service = new LegacyProductBrowserService();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('produtos');
        Schema::dropIfExists('tipos_bens');
        Schema::dropIfExists('dependencias');
        Schema::dropIfExists('comums');
        Schema::dropIfExists('administracoes');
        parent::tearDown();
    }

    public function testPaginateFiltersByAdministrationId(): void
    {
        $admin1 = new Administracao();
        $admin1->forceFill(['id' => 10, 'descricao' => 'Administração Curitiba']);
        $admin1->save();

        $admin2 = new Administracao();
        $admin2->forceFill(['id' => 20, 'descricao' => 'Administração Maringá']);
        $admin2->save();

        $church1 = new Comum();
        $church1->forceFill(['id' => 100, 'codigo' => 'CTA-01', 'descricao' => 'Central Curitiba', 'administracao_id' => 10, 'estado' => 'PR']);
        $church1->save();

        $church2 = new Comum();
        $church2->forceFill(['id' => 200, 'codigo' => 'MGA-01', 'descricao' => 'Central Maringá', 'administracao_id' => 20, 'estado' => 'PR']);
        $church2->save();

        $prod1 = new Produto();
        $prod1->forceFill(['id_produto' => 1, 'comum_id' => 100, 'codigo' => 'P-001', 'bem' => 'MESA', 'ativo' => 1]);
        $prod1->save();

        $prod2 = new Produto();
        $prod2->forceFill(['id_produto' => 2, 'comum_id' => 200, 'codigo' => 'P-002', 'bem' => 'CADEIRA', 'ativo' => 1]);
        $prod2->save();

        $filters = new ProductFilters(
            administrationId: 10,
            comumId: null,
            search: '',
            dependencyId: null,
            assetTypeId: null,
            state: null,
            status: '',
            onlyNew: false,
            page: 1,
            perPage: 10,
        );

        $result = $this->service->paginate($filters);

        self::assertSame(1, $result->total());
        self::assertSame(1, $result->items()[0]->id_produto);
        self::assertSame('P-001', $result->items()[0]->codigo);
    }

    public function testPaginateFiltersByState(): void
    {
        $church1 = new Comum();
        $church1->forceFill(['id' => 100, 'codigo' => 'SP-01', 'descricao' => 'Igreja SP', 'estado' => 'SP']);
        $church1->save();

        $church2 = new Comum();
        $church2->forceFill(['id' => 200, 'codigo' => 'RJ-01', 'descricao' => 'Igreja RJ', 'estado' => 'RJ']);
        $church2->save();

        $prod1 = new Produto();
        $prod1->forceFill(['id_produto' => 1, 'comum_id' => 100, 'codigo' => 'P-SP', 'bem' => 'MESA', 'ativo' => 1]);
        $prod1->save();

        $prod2 = new Produto();
        $prod2->forceFill(['id_produto' => 2, 'comum_id' => 200, 'codigo' => 'P-RJ', 'bem' => 'CADEIRA', 'ativo' => 1]);
        $prod2->save();

        $filters = new ProductFilters(
            administrationId: null,
            comumId: null,
            search: '',
            dependencyId: null,
            assetTypeId: null,
            state: 'SP',
            status: '',
            onlyNew: false,
            page: 1,
            perPage: 10,
        );

        $result = $this->service->paginate($filters);

        self::assertSame(1, $result->total());
        self::assertSame(1, $result->items()[0]->id_produto);
        self::assertSame('P-SP', $result->items()[0]->codigo);
    }

    public function testRestrictedUserSeesOnlyProductsFromPermittedAdministrations(): void
    {
        Session::put([
            'is_admin' => false,
            'administracao_id' => 10,
            'administracoes_permitidas' => [20],
            'usuario_id' => 7,
        ]);

        foreach ([10, 20, 30] as $administrationId) {
            $church = new Comum();
            $church->forceFill([
                'id' => $administrationId * 10,
                'codigo' => 'IG-' . $administrationId,
                'descricao' => 'Igreja ' . $administrationId,
                'administracao_id' => $administrationId,
                'estado' => 'SP',
            ]);
            $church->save();

            $product = new Produto();
            $product->forceFill([
                'id_produto' => $administrationId,
                'comum_id' => $administrationId * 10,
                'codigo' => 'P-' . $administrationId,
                'bem' => 'MESA',
                'ativo' => 1,
            ]);
            $product->save();
        }

        $result = $this->service->paginate($this->filters());

        self::assertSame(2, $result->total());
        self::assertEqualsCanonicalizing(
            ['P-10', 'P-20'],
            array_map(static fn (Produto $product): string => (string) $product->codigo, $result->items()),
        );
    }

    public function testRestrictedUserCannotUseAnAdministrationOutsideTheirScope(): void
    {
        Session::put([
            'is_admin' => false,
            'administracao_id' => 10,
            'administracoes_permitidas' => [],
            'usuario_id' => 7,
        ]);

        $church = new Comum();
        $church->forceFill([
            'id' => 300,
            'codigo' => 'IG-30',
            'descricao' => 'Igreja 30',
            'administracao_id' => 30,
            'estado' => 'SP',
        ]);
        $church->save();

        $product = new Produto();
        $product->forceFill([
            'id_produto' => 30,
            'comum_id' => 300,
            'codigo' => 'P-30',
            'bem' => 'MESA',
            'ativo' => 1,
        ]);
        $product->save();

        $result = $this->service->paginate($this->filters(administrationId: 30));

        self::assertSame(0, $result->total());
    }

    public function testAdministratorKeepsGlobalProductAccess(): void
    {
        Session::put([
            'is_admin' => true,
            'administracao_id' => null,
            'administracoes_permitidas' => [],
        ]);

        foreach ([10, 20] as $administrationId) {
            $church = new Comum();
            $church->forceFill([
                'id' => $administrationId * 10,
                'codigo' => 'IG-' . $administrationId,
                'descricao' => 'Igreja ' . $administrationId,
                'administracao_id' => $administrationId,
                'estado' => 'SP',
            ]);
            $church->save();

            $product = new Produto();
            $product->forceFill([
                'id_produto' => $administrationId,
                'comum_id' => $administrationId * 10,
                'codigo' => 'P-' . $administrationId,
                'bem' => 'MESA',
                'ativo' => 1,
            ]);
            $product->save();
        }

        $result = $this->service->paginate($this->filters());

        self::assertSame(2, $result->total());
    }

    public function testPaginateEagerLoadsOriginalAndEditedClassificationRelations(): void
    {
        $product = new Produto();
        $product->forceFill([
            'id_produto' => 1,
            'comum_id' => 100,
            'codigo' => 'P-001',
            'bem' => 'MESA',
            'ativo' => 1,
            'editado' => 1,
            'tipo_bem_id' => 4,
            'editado_tipo_bem_id' => 7,
            'dependencia_id' => 2,
            'editado_dependencia_id' => 3,
        ]);
        $product->save();

        $church = new Comum();
        $church->forceFill(['id' => 100, 'codigo' => 'IG-100', 'descricao' => 'Igreja 100']);
        $church->save();

        foreach ([
            [4, 'CADEIRA'],
            [7, 'MESA'],
        ] as [$id, $description]) {
            $type = new TipoBem();
            $type->forceFill(['id' => $id, 'codigo' => (string) $id, 'descricao' => $description]);
            $type->save();
        }

        foreach ([
            [2, 'SALAO'],
            [3, 'SECRETARIA'],
        ] as [$id, $description]) {
            $dependency = new Dependencia();
            $dependency->forceFill(['id' => $id, 'comum_id' => 100, 'descricao' => $description]);
            $dependency->save();
        }

        $result = $this->service->paginate($this->filters());
        $loadedProduct = $result->items()[0];

        self::assertTrue($loadedProduct->relationLoaded('tipoBem'));
        self::assertTrue($loadedProduct->relationLoaded('dependencia'));
        self::assertTrue($loadedProduct->relationLoaded('editadoTipoBem'));
        self::assertTrue($loadedProduct->relationLoaded('editadoDependencia'));
        self::assertSame('MESA', $loadedProduct->editadoTipoBem->descricao);
        self::assertSame('SECRETARIA', $loadedProduct->editadoDependencia->descricao);
    }

    public function testSearchUsesCurrentEditedClassification(): void
    {
        $this->seedEditedProduct();

        $byType = $this->service->paginate($this->filters(search: 'MESA'));
        $byDependency = $this->service->paginate($this->filters(search: 'SECRETARIA'));
        $byReplacedType = $this->service->paginate($this->filters(search: 'CADEIRA'));
        $byReplacedDependency = $this->service->paginate($this->filters(search: 'SALAO'));

        self::assertSame(1, $byType->total());
        self::assertSame(1, $byDependency->total());
        self::assertSame(0, $byReplacedType->total());
        self::assertSame(0, $byReplacedDependency->total());
    }

    public function testClassificationFiltersUseCurrentEditedRelations(): void
    {
        $this->seedEditedProduct();

        $byType = $this->service->paginate($this->filters(assetTypeId: 7));
        $byDependency = $this->service->paginate($this->filters(dependencyId: 3));
        $byOriginalType = $this->service->paginate($this->filters(assetTypeId: 4));
        $byOriginalDependency = $this->service->paginate($this->filters(dependencyId: 2));

        self::assertSame(1, $byType->total());
        self::assertSame(1, $byDependency->total());
        self::assertSame(0, $byOriginalType->total());
        self::assertSame(0, $byOriginalDependency->total());
    }

    public function testClassificationSearchAndFiltersFallbackToOriginalRelations(): void
    {
        $product = $this->seedEditedProduct(
            editedTypeId: null,
            editedDependencyId: null,
        );

        $byType = $this->service->paginate($this->filters(search: 'CADEIRA'));
        $byDependency = $this->service->paginate($this->filters(search: 'SALAO'));
        $byTypeFilter = $this->service->paginate($this->filters(assetTypeId: 4));
        $byDependencyFilter = $this->service->paginate($this->filters(dependencyId: 2));

        self::assertSame(1, $byType->total());
        self::assertSame(1, $byDependency->total());
        self::assertSame(1, $byTypeFilter->total());
        self::assertSame(1, $byDependencyFilter->total());
        self::assertSame(1, $product->id_produto);
    }

    public function testClassificationSearchAndFiltersFallbackWhenEditedRelationsHaveNoDisplayValue(): void
    {
        $this->seedEditedProduct(
            editedTypeId: 7,
            editedDependencyId: 3,
            editedTypeCode: null,
            editedTypeDescription: '',
            editedDependencyDescription: '',
        );

        $byType = $this->service->paginate($this->filters(search: 'CADEIRA'));
        $byDependency = $this->service->paginate($this->filters(search: 'SALAO'));
        $byTypeFilter = $this->service->paginate($this->filters(assetTypeId: 4));
        $byDependencyFilter = $this->service->paginate($this->filters(dependencyId: 2));

        self::assertSame(1, $byType->total());
        self::assertSame(1, $byDependency->total());
        self::assertSame(1, $byTypeFilter->total());
        self::assertSame(1, $byDependencyFilter->total());
    }

    public function testRestrictedUserCannotFindOutOfScopeProductByEditedClassification(): void
    {
        Session::put([
            'is_admin' => false,
            'administracao_id' => 10,
            'administracoes_permitidas' => [],
            'usuario_id' => 7,
        ]);
        $this->seedEditedProduct();

        $result = $this->service->paginate($this->filters(search: 'MESA'));

        self::assertSame(0, $result->total());
    }

    public function testRestrictedUserCanFindPermittedProductByEditedClassification(): void
    {
        Session::put([
            'is_admin' => false,
            'administracao_id' => 10,
            'administracoes_permitidas' => [],
            'usuario_id' => 7,
        ]);
        $this->seedEditedProduct(administrationId: 10);

        $result = $this->service->paginate($this->filters(search: 'MESA'));

        self::assertSame(1, $result->total());
    }

    public function testRestrictedUserCanFindPermittedProductByEditedClassificationWithAdditionalScope(): void
    {
        Session::put([
            'is_admin' => false,
            'administracao_id' => 10,
            'administracoes_permitidas' => [20],
            'usuario_id' => 7,
        ]);
        $this->seedEditedProduct(administrationId: 20);

        $result = $this->service->paginate($this->filters(search: 'MESA'));

        self::assertSame(1, $result->total());
    }

    public function testSearchStillUsesProductCodeNameAndComplement(): void
    {
        $product = $this->seedEditedProduct();

        $byCode = $this->service->paginate($this->filters(search: 'P-001'));
        $byName = $this->service->paginate($this->filters(search: 'ARMARIO'));
        $byComplement = $this->service->paginate($this->filters(search: 'GRANDE'));

        self::assertSame(1, $byCode->total());
        self::assertSame(1, $byName->total());
        self::assertSame(1, $byComplement->total());
        self::assertSame(1, $product->id_produto);
    }

    public function testRestrictedUserSearchByEditedClassificationRespectsCurrentAdministrationFilter(): void
    {
        Session::put([
            'is_admin' => false,
            'administracao_id' => 10,
            'administracoes_permitidas' => [20],
            'usuario_id' => 7,
        ]);
        $this->seedEditedProduct(administrationId: 20);

        $result = $this->service->paginate($this->filters(search: 'MESA', administrationId: 10));

        self::assertSame(0, $result->total());
    }

    public function testProductWithEditedClassificationAndOriginalFallbackCanBePaginatedTogether(): void
    {
        $this->seedEditedProduct(administrationId: 30);
        $this->seedEditedProduct(
            id: 2,
            code: 'P-002',
            administrationId: 31,
            editedTypeId: null,
            editedDependencyId: null,
        );

        $result = $this->service->paginate($this->filters(search: 'CADEIRA'));

        self::assertSame(1, $result->total());
        self::assertSame(2, $result->items()[0]->id_produto);
    }

    public function testNoClassificationFilterReturnsBothCurrentClassifications(): void
    {
        $this->seedEditedProduct(administrationId: 30);
        $this->seedEditedProduct(
            id: 2,
            code: 'P-002',
            administrationId: 31,
            editedTypeId: null,
            editedDependencyId: null,
        );

        $result = $this->service->paginate($this->filters());

        self::assertSame(2, $result->total());
    }

    public function testRestrictedUserSeesOnlyPermittedChurchesAndDependencies(): void
    {
        Session::put([
            'is_admin' => false,
            'administracao_id' => 10,
            'administracoes_permitidas' => [],
            'usuario_id' => 7,
        ]);

        $permittedChurch = new Comum();
        $permittedChurch->forceFill([
            'id' => 100,
            'codigo' => 'IG-10',
            'descricao' => 'Igreja permitida',
            'administracao_id' => 10,
        ]);
        $permittedChurch->save();

        $outsideChurch = new Comum();
        $outsideChurch->forceFill([
            'id' => 200,
            'codigo' => 'IG-20',
            'descricao' => 'Igreja fora do escopo',
            'administracao_id' => 20,
        ]);
        $outsideChurch->save();

        $permittedDependency = new Dependencia();
        $permittedDependency->forceFill(['id' => 1, 'comum_id' => 100, 'descricao' => 'SALAO permitido']);
        $permittedDependency->save();

        $outsideDependency = new Dependencia();
        $outsideDependency->forceFill(['id' => 2, 'comum_id' => 200, 'descricao' => 'SALAO fora']);
        $outsideDependency->save();

        self::assertEqualsCanonicalizing(
            [100],
            $this->service->churchOptions()->pluck('id')->all(),
        );
        self::assertSame([1], $this->service->dependencyOptions(null)->pluck('id')->all());
        self::assertSame([], $this->service->dependencyOptions(200)->pluck('id')->all());
    }

    public function testAdministratorSeesAllChurchesAndDependencies(): void
    {
        Session::put('is_admin', true);

        foreach ([100, 200] as $churchId) {
            $church = new Comum();
            $church->forceFill([
                'id' => $churchId,
                'codigo' => 'IG-' . $churchId,
                'descricao' => 'Igreja ' . $churchId,
                'administracao_id' => intdiv($churchId, 10),
            ]);
            $church->save();

            $dependency = new Dependencia();
            $dependency->forceFill([
                'id' => $churchId,
                'comum_id' => $churchId,
                'descricao' => 'SALAO ' . $churchId,
            ]);
            $dependency->save();
        }

        self::assertCount(2, $this->service->churchOptions());
        self::assertCount(2, $this->service->dependencyOptions(null));
    }

    public function testAdministrationOptionsFollowTheCurrentScope(): void
    {
        Session::put([
            'is_admin' => false,
            'administracao_id' => 10,
            'administracoes_permitidas' => [20],
            'usuario_id' => 7,
        ]);

        foreach ([10, 20, 30] as $administrationId) {
            $administration = new Administracao();
            $administration->forceFill([
                'id' => $administrationId,
                'descricao' => 'Administração ' . $administrationId,
            ]);
            $administration->save();
        }

        self::assertEqualsCanonicalizing(
            [10, 20],
            $this->service->administrationOptions()->pluck('id')->all(),
        );
    }

    public function testAssetTypeOptionsIncludeAllPermittedAdministrationsAndSharedTypes(): void
    {
        Session::put([
            'is_admin' => false,
            'administracao_id' => 10,
            'administracoes_permitidas' => [20],
            'usuario_id' => 7,
        ]);

        foreach ([
            [10, 10, 'Tipo permitido ativo'],
            [20, 20, 'Tipo permitido adicional'],
            [30, 30, 'Tipo fora do escopo'],
            [40, null, 'Tipo compartilhado'],
        ] as [$id, $administrationId, $description]) {
            $assetType = new TipoBem();
            $assetType->forceFill([
                'id' => $id,
                'codigo' => $id,
                'descricao' => $description,
                'administracao_id' => $administrationId,
            ]);
            $assetType->save();
        }

        self::assertEqualsCanonicalizing(
            [10, 20, 40],
            $this->service->assetTypeOptions()->pluck('id')->all(),
        );
    }

    private function seedEditedProduct(
        int $id = 1,
        string $code = 'P-001',
        int $administrationId = 30,
        ?int $editedTypeId = 7,
        ?int $editedDependencyId = 3,
        ?string $editedTypeCode = '7',
        string $editedTypeDescription = 'MESA',
        string $editedDependencyDescription = 'SECRETARIA',
    ): Produto {
        $churchId = $administrationId * 10;
        $church = Comum::query()->whereKey($churchId)->first() ?? new Comum();
        $church->forceFill([
            'id' => $churchId,
            'codigo' => 'IG-' . $administrationId,
            'descricao' => 'Igreja ' . $administrationId,
            'administracao_id' => $administrationId,
            'estado' => 'SP',
        ]);
        $church->save();

        $originalType = TipoBem::query()->whereKey(4)->first() ?? new TipoBem();
        $originalType->forceFill(['id' => 4, 'codigo' => '4', 'descricao' => 'CADEIRA']);
        $originalType->save();

        $editedType = TipoBem::query()->whereKey(7)->first() ?? new TipoBem();
        $editedType->forceFill(['id' => 7, 'codigo' => $editedTypeCode, 'descricao' => $editedTypeDescription]);
        $editedType->save();

        $dependencyRecordId = $administrationId === 30 ? 2 : $administrationId * 10 + 2;
        $editedDependencyRecordId = $dependencyRecordId + 1;

        $originalDependency = Dependencia::query()->whereKey($dependencyRecordId)->first() ?? new Dependencia();
        $originalDependency->forceFill(['id' => $dependencyRecordId, 'comum_id' => $church->id, 'descricao' => 'SALAO']);
        $originalDependency->save();

        $editedDependency = Dependencia::query()->whereKey($editedDependencyRecordId)->first() ?? new Dependencia();
        $editedDependency->forceFill([
            'id' => $editedDependencyRecordId,
            'comum_id' => $church->id,
            'descricao' => $editedDependencyDescription,
        ]);
        $editedDependency->save();

        $product = new Produto();
        $product->forceFill([
            'id_produto' => $id,
            'comum_id' => $church->id,
            'codigo' => $code,
            'tipo_bem_id' => $originalType->id,
            'editado_tipo_bem_id' => $editedTypeId !== null ? $editedType->id : null,
            'bem' => 'ARMARIO',
            'complemento' => 'GRANDE',
            'dependencia_id' => $originalDependency->id,
            'editado_dependencia_id' => $editedDependencyId !== null ? $editedDependencyRecordId : null,
            'editado' => 1,
            'ativo' => 1,
        ]);
        $product->save();

        return $product;
    }

    private function filters(
        ?int $administrationId = null,
        string $search = '',
        ?int $dependencyId = null,
        ?int $assetTypeId = null,
    ): ProductFilters {
        return new ProductFilters(
            administrationId: $administrationId,
            comumId: null,
            search: $search,
            dependencyId: $dependencyId,
            assetTypeId: $assetTypeId,
            state: null,
            status: '',
            onlyNew: false,
            page: 1,
            perPage: 10,
        );
    }
}
