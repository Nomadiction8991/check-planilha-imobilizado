<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTO\AssetTypeFilters;
use App\Models\Legacy\Administracao;
use App\Models\Legacy\TipoBem;
use App\Services\LegacyAssetTypeBrowserService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

final class LegacyAssetTypeBrowserServiceTest extends TestCase
{
    private LegacyAssetTypeBrowserService $service;

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

        Schema::create('tipos_bens', function (Blueprint $table): void {
            $table->id();
            $table->integer('codigo')->nullable();
            $table->string('descricao')->nullable();
            $table->unsignedBigInteger('administracao_id')->nullable();
        });

        Schema::create('produtos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('comum_id')->nullable();
            $table->unsignedBigInteger('tipo_bem_id')->nullable();
            $table->integer('codigo')->nullable();
            $table->string('descricao')->nullable();
            $table->integer('ativo')->default(1);
        });

        $this->service = new LegacyAssetTypeBrowserService();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('produtos');
        Schema::dropIfExists('tipos_bens');
        Schema::dropIfExists('administracoes');
        parent::tearDown();
    }

    public function testPaginateFiltersByAdministrationId(): void
    {
        Session::put('is_admin', true);

        $admin1 = new Administracao();
        $admin1->forceFill([
            'id' => 10,
            'descricao' => 'Administração SP',
            'estado' => 'SP',
        ]);
        $admin1->save();

        $admin2 = new Administracao();
        $admin2->forceFill([
            'id' => 20,
            'descricao' => 'Administração RJ',
            'estado' => 'RJ',
        ]);
        $admin2->save();

        $type1 = new TipoBem();
        $type1->forceFill([
            'id' => 1,
            'codigo' => 101,
            'descricao' => 'CADEIRA SP',
            'administracao_id' => 10,
        ]);
        $type1->save();

        $type2 = new TipoBem();
        $type2->forceFill([
            'id' => 2,
            'codigo' => 102,
            'descricao' => 'MESA RJ',
            'administracao_id' => 20,
        ]);
        $type2->save();

        $filters = new AssetTypeFilters(
            administrationId: 10,
            search: '',
            state: null,
            page: 1,
            perPage: 10,
        );

        $result = $this->service->paginate($filters);

        self::assertCount(1, $result->items());
        self::assertSame('CADEIRA SP', $result->items()[0]->descricao);
    }

    public function testPaginateFiltersByState(): void
    {
        Session::put('is_admin', true);

        $admin1 = new Administracao();
        $admin1->forceFill([
            'id' => 10,
            'descricao' => 'Administração SP',
            'estado' => 'SP',
        ]);
        $admin1->save();

        $admin2 = new Administracao();
        $admin2->forceFill([
            'id' => 20,
            'descricao' => 'Administração RJ',
            'estado' => 'RJ',
        ]);
        $admin2->save();

        $type1 = new TipoBem();
        $type1->forceFill([
            'id' => 1,
            'codigo' => 101,
            'descricao' => 'CADEIRA SP',
            'administracao_id' => 10,
        ]);
        $type1->save();

        $type2 = new TipoBem();
        $type2->forceFill([
            'id' => 2,
            'codigo' => 102,
            'descricao' => 'MESA RJ',
            'administracao_id' => 20,
        ]);
        $type2->save();

        $filters = new AssetTypeFilters(
            administrationId: null,
            search: '',
            state: 'RJ',
            page: 1,
            perPage: 10,
        );

        $result = $this->service->paginate($filters);

        self::assertCount(1, $result->items());
        self::assertSame('MESA RJ', $result->items()[0]->descricao);
    }

    public function testAdministrationOptionsReturnsOrderedAdministrations(): void
    {
        $admin1 = new Administracao();
        $admin1->forceFill([
            'id' => 2,
            'descricao' => 'Administração B',
        ]);
        $admin1->save();

        $admin2 = new Administracao();
        $admin2->forceFill([
            'id' => 1,
            'descricao' => 'Administração A',
        ]);
        $admin2->save();

        $options = $this->service->administrationOptions();

        self::assertCount(2, $options);
        self::assertSame(1, $options->first()->id);
        self::assertSame('Administração A', $options->first()->descricao);
    }
}
