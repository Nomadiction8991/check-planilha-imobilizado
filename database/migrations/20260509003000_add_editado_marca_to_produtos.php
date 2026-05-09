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
            $table->string('editado_marca', 255)->nullable()->after('editado_complemento');
        });
    }

    public function down(): void
    {
        Schema::table('produtos', static function (Blueprint $table): void {
            $table->dropColumn('editado_marca');
        });
    }
};
