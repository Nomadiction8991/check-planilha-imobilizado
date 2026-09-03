<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTO\ProductFilters;
use App\Models\Legacy\Administracao;
use App\Models\Legacy\Comum;
use App\Models\Legacy\Dependencia;
use App\Models\Legacy\Produto;
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
            $table->string('codigo')->nullable();
            $table->string('bem')->nullable();
            $table->string('complemento')->nullable();
            $table->integer('novo')->default(0);
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

    private function filters(?int $administrationId = null): ProductFilters
    {
        return new ProductFilters(
            administrationId: $administrationId,
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
    }
}
