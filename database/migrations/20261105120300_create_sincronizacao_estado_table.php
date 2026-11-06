<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cursores do notebook (até onde enviou e até onde recebeu). Não sincroniza.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sincronizacao_estado', function (Blueprint $table): void {
            $table->string('chave', 64)->primary();
            $table->text('valor')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sincronizacao_estado');
    }
};
