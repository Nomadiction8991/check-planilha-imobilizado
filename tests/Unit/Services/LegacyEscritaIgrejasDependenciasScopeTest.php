<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTO\ChurchMutationData;
use App\DTO\DepartmentMutationData;
use App\Models\Legacy\Administracao;
use App\Models\Legacy\Comum;
use App\Models\Legacy\Dependencia;
use App\Models\Legacy\Produto;
use App\Services\LegacyChurchManagementService;
use App\Services\LegacyDepartmentManagementService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use RuntimeException;
use Tests\TestCase;

final class LegacyEscritaIgrejasDependenciasScopeTest extends TestCase
{
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

        Schema::create('dependencias', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('comum_id')->nullable();
            $table->string('descricao')->nullable();
        });

        Schema::create('produtos', function (Blueprint $table): void {
            $table->id('id_produto');
            $table->unsignedBigInteger('comum_id')->nullable();
            $table->unsignedBigInteger('dependencia_id')->nullable();
            $table->string('codigo')->nullable();
            $table->string('bem')->nullable();
            $table->integer('ativo')->default(1);
        });

        Schema::create('tipos_bens', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo')->nullable();
            $table->string('descricao')->nullable();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('tipos_bens');
        Schema::dropIfExists('produtos');
        Schema::dropIfExists('dependencias');
        Schema::dropIfExists('comums');
        Schema::dropIfExists('administracoes');
        parent::tearDown();
    }

    // ——— Igrejas ———

    public function testChurchUpdateBlockedWhenCurrentChurchOutsideScope(): void
    {
        $this->seedChurches();

        Session::put([
            'is_admin' => false,
            'administracao_id' => 10,
            'administracoes_permitidas' => [],
        ]);

        $churchOutside = Comum::query()->find(200);
        self::assertNotNull($churchOutside);

        $service = new LegacyChurchManagementService();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A igreja selecionada está fora do seu escopo permitido.');

        $service->update($churchOutside, $this->churchDto(administrationId: 10, description: 'Tentativa'));

        self::assertSame('Igreja RJ', Comum::query()->find(200)->descricao);
    }

    public function testChurchUpdateBlockedWhenTargetAdministrationOutsideScope(): void
    {
        $this->seedChurches();

        Session::put([
            'is_admin' => false,
            'administracao_id' => 10,
            'administracoes_permitidas' => [],
        ]);

        $churchInside = Comum::query()->find(100);
        self::assertNotNull($churchInside);

        $service = new LegacyChurchManagementService();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A administração selecionada está fora do seu escopo permitido.');

        $service->update($churchInside, $this->churchDto(administrationId: 20, description: 'Tentativa mover'));

        self::assertSame(10, (int) Comum::query()->find(100)->administracao_id);
    }

    public function testChurchUpdateAllowedInsideScope(): void
    {
        $this->seedChurches();

        Session::put([
            'is_admin' => false,
            'administracao_id' => 10,
            'administracoes_permitidas' => [20],
        ]);

        $churchInside = Comum::query()->find(100);
        self::assertNotNull($churchInside);

        $service = new LegacyChurchManagementService();
        $updated = $service->update($churchInside, $this->churchDto(administrationId: 20, description: 'Central Movida'));

        self::assertSame(20, (int) $updated->administracao_id);
        self::assertSame('CENTRAL MOVIDA', (string) $updated->descricao);
    }

    public function testChurchUpdateGlobalAdminBypassesScope(): void
    {
        $this->seedChurches();

        Session::put([
            'is_admin' => true,
            'administracao_id' => 10,
            'administracoes_permitidas' => [],
        ]);

        $churchOutside = Comum::query()->find(200);
        self::assertNotNull($churchOutside);

        $service = new LegacyChurchManagementService();
        $updated = $service->update($churchOutside, $this->churchDto(administrationId: 10, description: 'Admin moveu'));

        self::assertSame(10, (int) $updated->administracao_id);
    }

    // ——— Dependências ———

    public function testDepartmentCreateBlockedWhenChurchOutsideScope(): void
    {
        $this->seedChurches();

        Session::put([
            'is_admin' => false,
            'administracao_id' => 10,
            'administracoes_permitidas' => [],
        ]);

        $service = new LegacyDepartmentManagementService();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A igreja selecionada está fora do seu escopo permitido.');

        $service->create(new DepartmentMutationData(churchId: 200, description: 'Nova'));

        self::assertSame(0, Dependencia::query()->count());
    }

    public function testDepartmentCreateAllowedInsideScope(): void
    {
        $this->seedChurches();

        Session::put([
            'is_admin' => false,
            'administracao_id' => 10,
            'administracoes_permitidas' => [],
        ]);

        $service = new LegacyDepartmentManagementService();
        $dep = $service->create(new DepartmentMutationData(churchId: 100, description: 'Salao Novo'));

        self::assertSame(100, (int) $dep->comum_id);
        self::assertSame('SALAO NOVO', $dep->descricao);
    }

    public function testDepartmentUpdateBlockedWhenCurrentDepartmentOutsideScope(): void
    {
        $this->seedDepartments();

        Session::put([
            'is_admin' => false,
            'administracao_id' => 10,
            'administracoes_permitidas' => [],
        ]);

        $depOutside = Dependencia::query()->find(2);
        self::assertNotNull($depOutside);

        $service = new LegacyDepartmentManagementService();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A igreja selecionada está fora do seu escopo permitido.');

        $service->update($depOutside, new DepartmentMutationData(churchId: 100, description: 'Tentativa'));

        self::assertSame(200, (int) Dependencia::query()->find(2)->comum_id);
    }

    public function testDepartmentUpdateBlockedWhenNewChurchOutsideScope(): void
    {
        $this->seedDepartments();

        Session::put([
            'is_admin' => false,
            'administracao_id' => 10,
            'administracoes_permitidas' => [],
        ]);

        $depInside = Dependencia::query()->find(1);
        self::assertNotNull($depInside);

        $service = new LegacyDepartmentManagementService();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A igreja selecionada está fora do seu escopo permitido.');

        $service->update($depInside, new DepartmentMutationData(churchId: 200, description: 'Mover para fora'));

        self::assertSame(100, (int) Dependencia::query()->find(1)->comum_id);
    }

    public function testDepartmentDeleteBlockedWhenDepartmentOutsideScope(): void
    {
        $this->seedDepartments();

        Session::put([
            'is_admin' => false,
            'administracao_id' => 10,
            'administracoes_permitidas' => [],
        ]);

        $depOutside = Dependencia::query()->find(2);
        self::assertNotNull($depOutside);

        $service = new LegacyDepartmentManagementService();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A igreja selecionada está fora do seu escopo permitido.');

        $service->delete($depOutside);

        self::assertNotNull(Dependencia::query()->find(2));
    }

    public function testDepartmentUpdateAndDeleteAllowedInsideScopeWithMultipleAdministrations(): void
    {
        $this->seedDepartments();

        Session::put([
            'is_admin' => false,
            'administracao_id' => 10,
            'administracoes_permitidas' => [20],
        ]);

        $service = new LegacyDepartmentManagementService();

        $dep1 = Dependencia::query()->find(1);
        $updated = $service->update($dep1, new DepartmentMutationData(churchId: 200, description: 'Movida'));
        self::assertSame(200, (int) $updated->comum_id);

        $service->delete($updated);
        self::assertNull(Dependencia::query()->find(1));
    }

    // ——— helpers ———

    private function seedChurches(): void
    {
        $admin10 = new Administracao();
        $admin10->forceFill(['id' => 10, 'descricao' => 'Administração 10']);
        $admin10->save();

        $admin20 = new Administracao();
        $admin20->forceFill(['id' => 20, 'descricao' => 'Administração 20']);
        $admin20->save();

        $c1 = new Comum();
        $c1->forceFill(['id' => 100, 'codigo' => '10-0001', 'descricao' => 'Igreja SP', 'administracao_id' => 10, 'estado' => 'SP', 'cidade' => 'Campinas', 'cnpj' => '11.222.333/0001-81', 'estado_administracao' => 'SP', 'cidade_administracao' => 'Campinas']);
        $c1->save();

        $c2 = new Comum();
        $c2->forceFill(['id' => 200, 'codigo' => '20-0001', 'descricao' => 'Igreja RJ', 'administracao_id' => 20, 'estado' => 'RJ', 'cidade' => 'Rio', 'cnpj' => '11.222.333/0001-81', 'estado_administracao' => 'RJ', 'cidade_administracao' => 'Rio']);
        $c2->save();
    }

    private function seedDepartments(): void
    {
        $this->seedChurches();

        $d1 = new Dependencia();
        $d1->forceFill(['id' => 1, 'comum_id' => 100, 'descricao' => 'SALAO SP']);
        $d1->save();

        $d2 = new Dependencia();
        $d2->forceFill(['id' => 2, 'comum_id' => 200, 'descricao' => 'SALAO RJ']);
        $d2->save();
    }

    private function churchDto(int $administrationId, string $description): ChurchMutationData
    {
        return new ChurchMutationData(
            administrationId: $administrationId,
            description: $description,
            cnpj: '11.222.333/0001-81',
            state: 'SP',
            city: 'Campinas',
            administrationState: 'SP',
            administrationCity: 'Campinas',
            sector: 'Centro',
        );
    }
}
