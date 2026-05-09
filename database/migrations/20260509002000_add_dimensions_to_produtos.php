<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produtos', static function (Blueprint $table): void {
            $table->decimal('altura_m', 10, 3)->nullable()->after('complemento');
            $table->decimal('largura_m', 10, 3)->nullable()->after('altura_m');
            $table->decimal('comprimento_m', 10, 3)->nullable()->after('largura_m');
            $table->decimal('editado_altura_m', 10, 3)->nullable()->after('editado_complemento');
            $table->decimal('editado_largura_m', 10, 3)->nullable()->after('editado_altura_m');
            $table->decimal('editado_comprimento_m', 10, 3)->nullable()->after('editado_largura_m');
        });
    }

    public function down(): void
    {
        Schema::table('produtos', static function (Blueprint $table): void {
            $table->dropColumn([
                'altura_m',
                'largura_m',
                'comprimento_m',
                'editado_altura_m',
                'editado_largura_m',
                'editado_comprimento_m',
            ]);
        });
    }
};
