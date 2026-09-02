<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Contracts\LegacyAuthSessionServiceInterface;
use App\Contracts\LegacyPermissionServiceInterface;
use App\DTO\UserFilters;
use App\Models\Legacy\Administracao;
use App\Models\Legacy\Usuario;
use App\Services\LegacyUserBrowserService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

final class LegacyUserBrowserServiceTest extends TestCase
{
    private LegacyAuthSessionServiceInterface $auth;
    private LegacyPermissionServiceInterface $permissions;
    private LegacyUserBrowserService $service;

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

        Schema::create('usuarios', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('administracao_id')->nullable();
            $table->string('nome')->nullable();
            $table->string('email')->nullable();
            $table->tinyInteger('ativo')->default(1);
            $table->string('endereco_estado', 2)->nullable();
        });

        $this->auth = Mockery::mock(LegacyAuthSessionServiceInterface::class);
        $this->permissions = Mockery::mock(LegacyPermissionServiceInterface::class);

        $this->auth->shouldReceive('currentUser')->andReturn([
            'id' => 1,
            'is_admin' => true,
            'administracao_id' => 1,
        ]);

        $this->permissions->shouldReceive('can')->andReturn(true);

        $this->service = new LegacyUserBrowserService($this->auth, $this->permissions);
    }

    public function testPaginateReturnsAllWhenNoFilters(): void
    {
        Usuario::query()->create([
            'administracao_id' => 1,
            'nome' => 'Ana Silva',
            'email' => 'ana@example.com',
            'ativo' => 1,
            'endereco_estado' => 'SP',
        ]);

        Usuario::query()->create([
            'administracao_id' => 1,
            'nome' => 'Carlos Souza',
            'email' => 'carlos@example.com',
            'ativo' => 1,
            'endereco_estado' => 'RJ',
        ]);

        $filters = new UserFilters(
            administrationId: null,
            search: '',
            status: '',
            state: null,
            page: 1,
            perPage: 10,
        );

        $results = $this->service->paginate($filters);

        self::assertSame(2, $results->total());
    }

    public function testPaginateFiltersByState(): void
    {
        Usuario::query()->create([
            'administracao_id' => 1,
            'nome' => 'Ana Silva',
            'email' => 'ana@example.com',
            'ativo' => 1,
            'endereco_estado' => 'SP',
        ]);

        Usuario::query()->create([
            'administracao_id' => 1,
            'nome' => 'Carlos Souza',
            'email' => 'carlos@example.com',
            'ativo' => 1,
            'endereco_estado' => 'RJ',
        ]);

        $filtersSp = new UserFilters(
            administrationId: null,
            search: '',
            status: '',
            state: 'SP',
            page: 1,
            perPage: 10,
        );
        $resultsSp = $this->service->paginate($filtersSp);

        self::assertSame(1, $resultsSp->total());
        self::assertSame('Ana Silva', $resultsSp->items()[0]->nome);

        $filtersRj = new UserFilters(
            administrationId: null,
            search: '',
            status: '',
            state: 'RJ',
            page: 1,
            perPage: 10,
        );
        $resultsRj = $this->service->paginate($filtersRj);

        self::assertSame(1, $resultsRj->total());
        self::assertSame('Carlos Souza', $resultsRj->items()[0]->nome);
    }
}
