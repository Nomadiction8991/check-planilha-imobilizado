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
            $table->string('estado', 2)->nullable()->after('descricao');
            $table->string('cidade')->nullable()->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('administracoes', function (Blueprint $table): void {
            $table->dropColumn(['estado', 'cidade']);
        });
    }
};
