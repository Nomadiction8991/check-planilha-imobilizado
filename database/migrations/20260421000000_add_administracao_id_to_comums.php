<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comums', function (Blueprint $table): void {
            $table->unsignedInteger('administracao_id')->nullable()->after('descricao');
            $table->index('administracao_id', 'idx_comums_administracao_id');
            $table->foreign('administracao_id')
                ->references('id')
                ->on('administracoes')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('comums', function (Blueprint $table): void {
            $table->dropForeign(['administracao_id']);
            $table->dropIndex('idx_comums_administracao_id');
            $table->dropColumn('administracao_id');
        });
    }
};
