<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTO\ProductVerificationItemData;
use App\Models\Legacy\Comum;
use App\Services\LegacyProductUtilityService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

final class LegacyProductUtilityServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('comums', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('administracao_id')->nullable();
            $table->string('descricao')->nullable();
        });

        Schema::create('dependencias', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('comum_id')->nullable();
            $table->string('descricao')->nullable();
        });

        Schema::create('produtos', function (Blueprint $table): void {
            $table->unsignedBigInteger('id_produto')->primary();
            $table->unsignedBigInteger('comum_id')->nullable();
            $table->unsignedBigInteger('dependencia_id')->nullable();
            $table->unsignedBigInteger('editado_dependencia_id')->nullable();
            $table->string('codigo')->nullable();
            $table->integer('editado')->nullable();
            $table->integer('imprimir_etiqueta')->nullable();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('produtos');
        Schema::dropIfExists('dependencias');
        Schema::dropIfExists('comums');

        parent::tearDown();
    }

    public function testLabelCopyDataUsesEditedDependencyOnlyForEditedProductWithDescription(): void
    {
        session(['is_admin' => true]);
        $this->seedLabelCopyFixture();

        $data = (new LegacyProductUtilityService())->labelCopyData(7, 2);

        self::assertSame([
            ['id' => 2, 'descricao' => 'SALAO'],
            ['id' => 3, 'descricao' => 'SECRETARIA'],
        ], $data['dependencies']);
        self::assertSame([
            ['codigo' => 'A-102', 'dependencia' => 'SALAO'],
            ['codigo' => 'A-103', 'dependencia' => 'SALAO'],
            ['codigo' => 'A-104', 'dependencia' => 'SALAO'],
        ], $data['products']);
        self::assertSame('A-102,A-103,A-104', $data['codes']);
    }

    public function testLabelCopyDataKeepsDependencyOptionsAndProductsOnSameCurrentClassification(): void
    {
        session(['is_admin' => true]);
        $this->seedLabelCopyFixture();

        $data = (new LegacyProductUtilityService())->labelCopyData(7, null);

        self::assertSame([
            ['codigo' => 'A-101', 'dependencia' => 'SECRETARIA'],
            ['codigo' => 'A-102', 'dependencia' => 'SALAO'],
            ['codigo' => 'A-103', 'dependencia' => 'SALAO'],
            ['codigo' => 'A-104', 'dependencia' => 'SALAO'],
        ], $data['products']);
        self::assertSame(4, $data['total_products']);
    }

    private function seedLabelCopyFixture(): void
    {
        DB::table('comums')->insert([
            'id' => 7,
            'administracao_id' => 4,
            'descricao' => 'Central Cuiabá',
        ]);
        DB::table('dependencias')->insert([
            ['id' => 2, 'comum_id' => 7, 'descricao' => 'SALAO'],
            ['id' => 3, 'comum_id' => 7, 'descricao' => 'SECRETARIA'],
            ['id' => 4, 'comum_id' => 7, 'descricao' => ''],
        ]);
        DB::table('produtos')->insert([
            [
                'id_produto' => 101,
                'comum_id' => 7,
                'dependencia_id' => 2,
                'editado_dependencia_id' => 3,
                'codigo' => 'A-101',
                'editado' => 1,
                'imprimir_etiqueta' => 1,
            ],
            [
                'id_produto' => 102,
                'comum_id' => 7,
                'dependencia_id' => 2,
                'editado_dependencia_id' => 3,
                'codigo' => 'A-102',
                'editado' => 0,
                'imprimir_etiqueta' => 1,
            ],
            [
                'id_produto' => 103,
                'comum_id' => 7,
                'dependencia_id' => 2,
                'editado_dependencia_id' => null,
                'codigo' => 'A-103',
                'editado' => 1,
                'imprimir_etiqueta' => 1,
            ],
            [
                'id_produto' => 104,
                'comum_id' => 7,
                'dependencia_id' => 2,
                'editado_dependencia_id' => 4,
                'codigo' => 'A-104',
                'editado' => 1,
                'imprimir_etiqueta' => 1,
            ],
            [
                'id_produto' => 105,
                'comum_id' => 7,
                'dependencia_id' => 2,
                'editado_dependencia_id' => 3,
                'codigo' => 'A-105',
                'editado' => 1,
                'imprimir_etiqueta' => 0,
            ],
        ]);
    }

    public function testPrintLabelMarksProductAsCheckedWhenSavingVerificationChecklist(): void
    {
        session(['is_admin' => true]);
        $connection = DB::connection();
        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(static function (callable $callback): mixed {
                return $callback();
            });
        DB::shouldReceive('connection')
            ->zeroOrMoreTimes()
            ->andReturn($connection);

        $service = Mockery::mock(LegacyProductUtilityService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('assertChurchWithinProductScope')->once()->with(7)->andReturn(new Comum(['id' => 7, 'administracao_id' => 4]));
        $service->shouldReceive('updateLabel')
            ->once()
            ->with(19, 7, true)
            ->andReturnTrue();
        $service->shouldReceive('updateCheck')
            ->once()
            ->with(19, 7, true)
            ->andReturnTrue();
        $service->shouldReceive('updateObservation')
            ->once()
            ->with(19, 7, '')
            ->andReturnTrue();

        $processed = $service->saveVerificationChecklist(7, [
            new ProductVerificationItemData(
                productId: 19,
                printLabel: true,
                verified: false,
                observation: '',
            ),
        ]);

        $this->assertSame(1, $processed);
    }
}
