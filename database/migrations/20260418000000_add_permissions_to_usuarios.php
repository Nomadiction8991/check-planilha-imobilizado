<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table): void {
            $table->json('permissions')->nullable()->after('tipo');
        });

        $defaultPermissions = array_fill_keys(
            array_keys((array) config('legacy.permissions.defaults', [])),
            true,
        );

        DB::table('usuarios')
            ->where('id', 1)
            ->update([
                'permissions' => json_encode($defaultPermissions, JSON_THROW_ON_ERROR),
            ]);
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table): void {
            $table->dropColumn('permissions');
        });
    }
};
