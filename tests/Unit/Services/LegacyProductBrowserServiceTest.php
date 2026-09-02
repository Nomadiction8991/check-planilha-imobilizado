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

    public function testAdministrationOptionsReturnsAllAdministrationsOrdered(): void
    {
        $adminB = new Administracao();
        $adminB->forceFill(['id' => 2, 'descricao' => 'Brasília']);
        $adminB->save();

        $adminA = new Administracao();
        $adminA->forceFill(['id' => 1, 'descricao' => 'Anápolis']);
        $adminA->save();

        $options = $this->service->administrationOptions();

        self::assertCount(2, $options);
        self::assertSame(1, $options->first()->id);
        self::assertSame('Anápolis', $options->first()->descricao);
    }
}
