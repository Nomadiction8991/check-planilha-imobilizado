<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Contracts\LegacyChurchBrowserServiceInterface;
use App\DTO\ChurchFilters;
use App\Models\Legacy\Administracao;
use App\Models\Legacy\Comum;
use App\Services\LegacyChurchBrowserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

final class LegacyChurchBrowserServiceTest extends TestCase
{
    private LegacyChurchBrowserService $service;

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
            $table->string('cnpj')->nullable();
            $table->unsignedBigInteger('administracao_id')->nullable();
            $table->string('estado')->nullable();
            $table->string('cidade')->nullable();
            $table->string('estado_administracao')->nullable();
            $table->string('cidade_administracao')->nullable();
            $table->string('setor')->nullable();
        });

        Schema::create('produtos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('comum_id')->nullable();
            $table->string('codigo')->nullable();
            $table->string('descricao')->nullable();
            $table->integer('ativo')->default(1);
        });

        $this->service = new LegacyChurchBrowserService();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('produtos');
        Schema::dropIfExists('comums');
        Schema::dropIfExists('administracoes');
        parent::tearDown();
    }

    public function testPaginateFiltersByAdministrationId(): void
    {
        $admin1 = new Administracao();
        $admin1->forceFill([
            'id' => 10,
            'descricao' => 'Administração 10',
            'estado' => 'SP',
            'cidade' => 'Campinas',
        ]);
        $admin1->save();

        $admin2 = new Administracao();
        $admin2->forceFill([
            'id' => 20,
            'descricao' => 'Administração 20',
            'estado' => 'MT',
            'cidade' => 'Cuiabá',
        ]);
        $admin2->save();

        $church1 = new Comum();
        $church1->forceFill([
            'id' => 1,
            'codigo' => 'BR-01',
            'descricao' => 'Igreja Alpha',
            'administracao_id' => 10,
        ]);
        $church1->save();

        $church2 = new Comum();
        $church2->forceFill([
            'id' => 2,
            'codigo' => 'BR-02',
            'descricao' => 'Igreja Beta',
            'administracao_id' => 20,
        ]);
        $church2->save();

        $filters = new ChurchFilters(
            administrationId: 10,
            search: '',
            state: null,
            page: 1,
            perPage: 10,
        );

        $results = $this->service->paginate($filters);

        self::assertCount(1, $results->items());
        self::assertSame('Igreja Alpha', $results->items()[0]->descricao);
    }

    public function testPaginateFiltersByState(): void
    {
        $churchSp = new Comum();
        $churchSp->forceFill([
            'id' => 1,
            'codigo' => 'BR-SP-01',
            'descricao' => 'Igreja São Paulo',
            'estado' => 'SP',
        ]);
        $churchSp->save();

        $churchPr = new Comum();
        $churchPr->forceFill([
            'id' => 2,
            'codigo' => 'BR-PR-01',
            'descricao' => 'Igreja Curitiba',
            'estado' => 'PR',
        ]);
        $churchPr->save();

        $filtersPr = new ChurchFilters(
            administrationId: null,
            search: '',
            state: 'PR',
            page: 1,
            perPage: 10,
        );
        $resultsPr = $this->service->paginate($filtersPr);

        self::assertSame(1, $resultsPr->total());
        self::assertSame('Igreja Curitiba', $resultsPr->items()[0]->descricao);

        $filtersSp = new ChurchFilters(
            administrationId: null,
            search: '',
            state: 'SP',
            page: 1,
            perPage: 10,
        );
        $resultsSp = $this->service->paginate($filtersSp);

        self::assertSame(1, $resultsSp->total());
        self::assertSame('Igreja São Paulo', $resultsSp->items()[0]->descricao);
    }

    public function testPaginateFiltersBySearchAndStateTogether(): void
    {
        $church1 = new Comum();
        $church1->forceFill([
            'id' => 1,
            'codigo' => 'BR-01',
            'descricao' => 'Central Curitiba',
            'estado' => 'PR',
        ]);
        $church1->save();

        $church2 = new Comum();
        $church2->forceFill([
            'id' => 2,
            'codigo' => 'BR-02',
            'descricao' => 'Central Campinas',
            'estado' => 'SP',
        ]);
        $church2->save();

        $filters = new ChurchFilters(
            administrationId: null,
            search: 'Central',
            state: 'PR',
            page: 1,
            perPage: 10,
        );
        $results = $this->service->paginate($filters);

        self::assertSame(1, $results->total());
        self::assertSame('Central Curitiba', $results->items()[0]->descricao);
    }
}
