<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tipos_bens', function (Blueprint $table): void {
            $table->unsignedInteger('administracao_id')->nullable()->after('codigo');
            $table->index('administracao_id', 'idx_tipos_bens_administracao_id');
            $table->foreign('administracao_id')
                ->references('id')
                ->on('administracoes')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tipos_bens', function (Blueprint $table): void {
            $table->dropForeign(['administracao_id']);
            $table->dropIndex('idx_tipos_bens_administracao_id');
            $table->dropColumn('administracao_id');
        });
    }
};
