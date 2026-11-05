<?php

declare(strict_types=1);

namespace Tests\Feature\Spreadsheets;

use App\Models\ImportacaoAcao;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class ImportacaoAcoesPersistenceTest extends TestCase
{
    public function test_tabela_permite_persistir_acoes_e_exclui_linhas_em_cascata(): void
    {
        Schema::create('importacoes', function (Blueprint $table): void {
            $table->id();
        });

        $caminho = database_path('migrations/20261104180000_create_importacao_acoes_table.php');
        if (is_file($caminho)) {
            (require $caminho)->up();
        }

        self::assertTrue(Schema::hasTable('importacao_acoes'));
        self::assertTrue(Schema::hasColumns('importacao_acoes', [
            'id', 'importacao_id', 'chave', 'tipo', 'acao', 'created_at', 'updated_at',
        ]));
        self::assertTrue(Schema::hasIndex('importacao_acoes', ['importacao_id', 'chave']));

        $importacaoId = DB::table('importacoes')->insertGetId([]);
        $acao = ImportacaoAcao::create([
            'importacao_id' => $importacaoId,
            'chave' => 'bem-1',
            'tipo' => 'bem',
            'acao' => 'manter',
        ]);

        $this->assertDatabaseHas('importacao_acoes', [
            'id' => $acao->id,
            'importacao_id' => $importacaoId,
            'chave' => 'bem-1',
            'tipo' => 'bem',
            'acao' => 'manter',
        ]);

        DB::table('importacoes')->where('id', $importacaoId)->delete();
        $this->assertDatabaseMissing('importacao_acoes', ['id' => $acao->id]);
    }
}
