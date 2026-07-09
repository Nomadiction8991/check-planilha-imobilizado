<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\ConnectionManager;
use PDO;
use Tests\TestCase;

final class ConnectionManagerTest extends TestCase
{
    protected function tearDown(): void
    {
        ConnectionManager::reset();
        parent::tearDown();
    }

    public function testCreateNewConnectionUsesInjectedSqliteConfigWithoutTouchingExternalDatabase(): void
    {
        $originalEnv = [
            'DB_CONNECTION' => getenv('DB_CONNECTION'),
            'DB_HOST' => getenv('DB_HOST'),
            'DB_DATABASE' => getenv('DB_DATABASE'),
            'DB_USERNAME' => getenv('DB_USERNAME'),
            'DB_PASSWORD' => getenv('DB_PASSWORD'),
        ];

        try {
            putenv('DB_CONNECTION=mysql');
            putenv('DB_HOST=db');
            putenv('DB_DATABASE=prod_db');
            putenv('DB_USERNAME=prod_user');
            putenv('DB_PASSWORD=prod_pass');

            ConnectionManager::configure([
                'driver' => 'sqlite',
                'database' => ':memory:',
            ]);

            $connection = ConnectionManager::createNewConnection();

            self::assertInstanceOf(PDO::class, $connection);
            self::assertSame('sqlite', $connection->getAttribute(PDO::ATTR_DRIVER_NAME));
            self::assertSame('1', (string) $connection->query('SELECT 1')->fetchColumn());

            $databaseList = $connection->query('PRAGMA database_list')->fetchAll(PDO::FETCH_ASSOC);
            self::assertNotEmpty($databaseList);
            self::assertSame('main', $databaseList[0]['name'] ?? null);
        } finally {
            ConnectionManager::reset();

            foreach ($originalEnv as $key => $value) {
                if ($value === false || $value === null) {
                    putenv($key);
                    continue;
                }

                putenv($key . '=' . $value);
            }
        }
    }
}
