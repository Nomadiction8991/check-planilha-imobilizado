<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('comums', 'administracao')) {
            return;
        }

        Schema::table('comums', function (Blueprint $table): void {
            $table->dropColumn('administracao');
        });
    }

    public function down(): void
    {
        Schema::table('comums', function (Blueprint $table): void {
            $table->string('administracao')->nullable()->after('descricao');
        });
    }
};
