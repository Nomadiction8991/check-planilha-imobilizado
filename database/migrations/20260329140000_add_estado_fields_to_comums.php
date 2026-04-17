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
            $table->string('estado', 2)->nullable()->comment('UF da Igreja/Templo');
            $table->string('estado_administracao', 2)->nullable()->comment('UF da Administração');
            $table->string('cidade_administracao')->nullable()->comment('Cidade da Administração');
        });
    }

    public function down(): void
    {
        Schema::table('comums', function (Blueprint $table): void {
            $table->dropColumn(['estado', 'estado_administracao', 'cidade_administracao']);
        });
    }
};
