<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('importacao_acoes', function (Blueprint $table): void {
            $table->id();
            $table->integer('importacao_id');
            $table->string('chave');
            $table->string('tipo');
            $table->string('acao');
            $table->timestamps();
            $table->unique(['importacao_id', 'tipo', 'chave']);
            $table->index(['importacao_id', 'chave']);
            $table->foreign('importacao_id')
                ->references('id')
                ->on('importacoes')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('importacao_acoes');
    }
};
