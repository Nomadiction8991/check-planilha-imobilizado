<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('usuarios', 'tipo')) {
            Schema::table('usuarios', function (Blueprint $table): void {
                $table->string('tipo', 50)->nullable()->after('comum_id');
            });
        }

        $defaultPassword = (string) env('LEGACY_ADMIN_PASSWORD', 'admin123');
        $now = now();

        DB::table('usuarios')->updateOrInsert(
            ['id' => 1],
            [
                'comum_id' => DB::table('comums')->orderBy('id')->value('id'),
                'nome' => 'Administrador',
                'email' => 'ADMIN@LOCALHOST',
                'senha' => Hash::make($defaultPassword),
                'ativo' => 1,
                'casado' => 0,
                'rg_conjuge_igual_cpf' => 0,
                'rg_igual_cpf' => 0,
                'tipo' => 'administrador',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    public function down(): void
    {
        DB::table('usuarios')->where('id', 1)->delete();

        if (Schema::hasColumn('usuarios', 'tipo')) {
            Schema::table('usuarios', function (Blueprint $table): void {
                $table->dropColumn('tipo');
            });
        }
    }
};
