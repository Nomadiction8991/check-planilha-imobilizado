<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', static function (Blueprint $table): void {
            $table->string('app_name', 40)->nullable();
            $table->longText('app_logo')->nullable();
            $table->string('app_logo_mime', 50)->nullable();
            $table->char('app_logo_hash', 32)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', static function (Blueprint $table): void {
            $table->dropColumn(['app_name', 'app_logo', 'app_logo_mime', 'app_logo_hash']);
        });
    }
};
