<?php

declare(strict_types=1);

namespace Tests\Feature\Transfers;

use App\Models\Legacy\Produto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

/**
 * Base dos testes de transferência: cria no SQLite as tabelas legadas
 * mínimas e as tabelas novas, e oferece funções para montar igrejas e bens.
 */
abstract class TransferenciasTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['transferencia_itens', 'transferencias', 'produtos', 'dependencias', 'comums', 'administracoes', 'tipos_bens'] as $tabela) {
            Schema::dropIfExists($tabela);
        }

        Schema::create('administracoes', function ($table): void {
            $table->integer('id')->primary();
            $table->string('descricao')->nullable();
            $table->string('cnpj')->nullable();
            $table->string('estado')->nullable();
            $table->string('cidade')->nullable();
        });

        Schema::create('comums', function ($table): void {
            $table->integer('id')->primary();
            $table->string('codigo')->nullable();
            $table->string('descricao')->nullable();
            $table->integer('administracao_id')->nullable();
            $table->string('estado')->nullable();
            $table->string('cidade')->nullable();
            $table->string('setor')->nullable();
            $table->string('cnpj')->nullable();
            $table->string('estado_administracao')->nullable();
            $table->string('cidade_administracao')->nullable();
        });

        Schema::create('dependencias', function ($table): void {
            $table->integer('id')->primary();
            $table->integer('comum_id');
            $table->string('descricao');
        });

        Schema::create('tipos_bens', function ($table): void {
            $table->integer('id')->primary();
            $table->string('codigo')->nullable();
            $table->string('descricao');
        });

        Schema::create('produtos', function ($table): void {
            $table->integer('id_produto')->primary();
            $table->integer('comum_id');
            $table->string('codigo')->nullable()->unique();
            $table->integer('tipo_bem_id')->nullable();
            $table->string('bem')->nullable();
            $table->string('complemento')->nullable();
            $table->integer('dependencia_id')->nullable();
            $table->integer('editado_tipo_bem_id')->nullable();
            $table->string('editado_bem')->nullable();
            $table->string('editado_complemento')->nullable();
            $table->integer('editado_dependencia_id')->nullable();
            $table->integer('novo')->default(0);
            $table->integer('importado')->default(0);
            $table->integer('checado')->default(0);
            $table->integer('editado')->default(0);
            $table->integer('imprimir_etiqueta')->default(0);
            $table->integer('imprimir_14_1')->default(0);
            $table->integer('condicao_14_1')->default(0);
            $table->text('observacao')->nullable();
            $table->integer('nota_numero')->nullable();
            $table->date('nota_data')->nullable();
            $table->decimal('nota_valor', 12, 2)->nullable();
            $table->string('nota_fornecedor')->nullable();
            $table->integer('administrador_acessor_id')->nullable();
            $table->integer('ativo')->default(1);
            $table->decimal('altura_m', 10, 3)->nullable();
            $table->decimal('largura_m', 10, 3)->nullable();
            $table->decimal('comprimento_m', 10, 3)->nullable();
            $table->decimal('editado_altura_m', 10, 3)->nullable();
            $table->decimal('editado_largura_m', 10, 3)->nullable();
            $table->decimal('editado_comprimento_m', 10, 3)->nullable();
            $table->string('editado_marca')->nullable();
        });

        (require base_path('database/migrations/20261108120000_create_transferencias_tables.php'))->up();

        Session::put('is_admin', true);
        Session::put('administracoes_permitidas', [1]);
        Session::put('administracao_id', 1);
    }

    protected function tearDown(): void
    {
        Produto::flushEventListeners();

        parent::tearDown();
    }

    protected function criarAdministracao(int $id, string $descricao = 'Administração Teste'): void
    {
        DB::table('administracoes')->insert([
            'id' => $id,
            'descricao' => $descricao,
            'cnpj' => '00.000.000/0001-00',
            'estado' => 'MT',
            'cidade' => 'Cuiabá',
        ]);
    }

    protected function criarIgreja(int $id, string $codigo, string $descricao, int $administracaoId = 1): void
    {
        DB::table('comums')->insert([
            'id' => $id,
            'codigo' => $codigo,
            'descricao' => $descricao,
            'administracao_id' => $administracaoId,
            'estado' => 'MT',
            'cidade' => 'Cuiabá',
            'setor' => 'Centro',
            'cnpj' => '00.000.000/0001-00',
        ]);
    }

    protected function criarDependencia(int $id, int $comumId, string $descricao): void
    {
        DB::table('dependencias')->insert(['id' => $id, 'comum_id' => $comumId, 'descricao' => $descricao]);
    }

    protected function criarTipoBem(int $id, string $descricao): void
    {
        DB::table('tipos_bens')->insert(['id' => $id, 'codigo' => (string) $id, 'descricao' => $descricao]);
    }

    protected function criarBem(
        int $idProduto,
        int $comumId,
        ?string $codigo,
        string $bem,
        ?int $dependenciaId = null,
        float $valor = 100.0,
        bool $checado = true,
    ): void {
        DB::table('produtos')->insert([
            'id_produto' => $idProduto,
            'comum_id' => $comumId,
            'codigo' => $codigo,
            'tipo_bem_id' => 1,
            'bem' => $bem,
            'complemento' => '',
            'dependencia_id' => $dependenciaId,
            'editado' => 0,
            'checado' => $checado ? 1 : 0,
            'imprimir_etiqueta' => 0,
            'nota_valor' => $valor,
            'ativo' => 1,
        ]);
    }
}
