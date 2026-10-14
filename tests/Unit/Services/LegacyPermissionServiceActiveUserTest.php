<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Legacy\Usuario;
use App\Services\LegacyPermissionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

final class LegacyPermissionServiceActiveUserTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::dropIfExists('usuarios');
        Schema::create('usuarios', function (Blueprint $table): void {
            $table->id();
            $table->string('tipo')->nullable();
            $table->string('email')->nullable();
            $table->unsignedTinyInteger('ativo')->default(1);
            $table->json('permissions')->nullable();
        });
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('usuarios');
        parent::tearDown();
    }

    public function testInactiveAdministratorGetsNoAdministrativePermissions(): void
    {
        DB::table('usuarios')->insert([
            'id' => 31,
            'tipo' => 'administrador',
            'email' => 'admin@example.test',
            'ativo' => 0,
            'permissions' => json_encode(['products.view' => true]),
        ]);
        Session::put('usuario_id', 31);
        Session::put('is_admin', true);
        config(['legacy.permissions.defaults' => ['products.view' => false, 'users.manage' => false]]);

        $service = new LegacyPermissionService();

        self::assertFalse($service->can('products.view'));
        self::assertFalse($service->can('users.manage'));
    }

    public function testDeactivationRevokesPreviouslyCachedAdministratorPermissions(): void
    {
        DB::table('usuarios')->insert([
            'id' => 32,
            'tipo' => 'administrador',
            'email' => 'active-admin@example.test',
            'ativo' => 1,
            'permissions' => json_encode(['products.view' => true]),
        ]);
        Session::put('usuario_id', 32);
        Session::put('is_admin', true);
        config(['legacy.permissions.defaults' => ['products.view' => false]]);

        $service = new LegacyPermissionService();
        self::assertTrue($service->can('products.view'));

        DB::table('usuarios')->where('id', 32)->update(['ativo' => 0]);

        self::assertFalse($service->can('products.view'));
    }
}
