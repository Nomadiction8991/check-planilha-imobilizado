<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_erros', function (Blueprint $table): void {
            $table->index(['importacao_id', 'resolvido'], 'idx_import_erros_importacao_resolvido');
        });

        Schema::table('produtos', function (Blueprint $table): void {
            $table->index(['comum_id', 'codigo'], 'idx_produtos_comum_codigo');
        });
    }

    public function down(): void
    {
        Schema::table('import_erros', function (Blueprint $table): void {
            $table->dropIndex('idx_import_erros_importacao_resolvido');
        });

        Schema::table('produtos', function (Blueprint $table): void {
            $table->dropIndex('idx_produtos_comum_codigo');
        });
    }
};
