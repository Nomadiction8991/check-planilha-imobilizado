<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\ConnectionManager;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConnectionManagerLaravelAdapterTest extends TestCase
{
    public function test_get_connection_retorna_o_mesmo_pdo_gerenciado_pelo_laravel(): void
    {
        ConnectionManager::reset();
        $pdoLaravel = DB::connection()->getPdo();
        $pdoManager = ConnectionManager::getConnection();

        $this->assertSame($pdoLaravel, $pdoManager);
    }
}
