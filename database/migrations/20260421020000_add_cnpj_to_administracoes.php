<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('administracoes', function (Blueprint $table): void {
            $table->string('cnpj', 30)->nullable()->after('descricao');
            $table->unique('cnpj');
        });
    }

    public function down(): void
    {
        Schema::table('administracoes', function (Blueprint $table): void {
            $table->dropUnique(['cnpj']);
            $table->dropColumn('cnpj');
        });
    }
};
