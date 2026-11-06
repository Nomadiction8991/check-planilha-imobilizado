<?php

declare(strict_types=1);

namespace Tests\Feature\Sync;

use Tests\TestCase;

/**
 * Base dos testes da sincronização no SQLite: roda só as migrations do módulo.
 *
 * As migrations iniciais do projeto são SQL do MySQL e não rodam no SQLite,
 * por isso o RefreshDatabase não é usado aqui.
 */
abstract class SincronizacaoTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $migrations = [
            'database/migrations/20261105120000_create_sincronizacao_alteracoes_table.php',
            'database/migrations/20261105120200_create_sincronizacao_servidor_tables.php',
            'database/migrations/20261105120300_create_sincronizacao_estado_table.php',
        ];

        foreach ($migrations as $migration) {
            $this->artisan('migrate', ['--path' => $migration, '--realpath' => true])->assertSuccessful();
        }
    }
}
