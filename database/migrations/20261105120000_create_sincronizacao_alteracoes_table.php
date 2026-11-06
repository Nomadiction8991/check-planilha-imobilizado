<?php

declare(strict_types=1);

use App\Sync\TriggerGenerator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sincronizacao_alteracoes', function (Blueprint $table): void {
            $table->id();
            $table->string('tabela', 64);
            $table->string('chave', 255);
            $table->string('operacao', 10);
            $table->json('antes')->nullable();
            $table->json('depois')->nullable();
            $table->string('origem', 64)->default('local');
            $table->timestamp('criado_em', 6)->useCurrent();
            $table->index(['tabela', 'chave']);
        });

        // Os gatilhos só existem no MySQL. Em outros bancos, só a tabela é criada.
        if (DB::getDriverName() === 'mysql') {
            /** @var list<string> $tabelas */
            $tabelas = config('sincronizacao.tabelas');
            app(TriggerGenerator::class)->generateFor($tabelas);
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            /** @var list<string> $tabelas */
            $tabelas = config('sincronizacao.tabelas');
            app(TriggerGenerator::class)->dropFor($tabelas);
        }

        Schema::dropIfExists('sincronizacao_alteracoes');
    }
};
