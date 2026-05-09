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
            $table->text('menu_order')->nullable()->after('mail_from_name');
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', static function (Blueprint $table): void {
            $table->dropColumn('menu_order');
        });
    }
};
