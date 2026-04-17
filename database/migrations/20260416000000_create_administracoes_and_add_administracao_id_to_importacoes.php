<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('administracoes', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('descricao');
        });

        Schema::table('importacoes', function (Blueprint $table): void {
            $table->unsignedInteger('administracao_id')->nullable()->after('usuario_id');
            $table->foreign('administracao_id')
                ->references('id')
                ->on('administracoes')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('importacoes', function (Blueprint $table): void {
            $table->dropForeign(['administracao_id']);
            $table->dropColumn('administracao_id');
        });

        Schema::dropIfExists('administracoes');
    }
};
