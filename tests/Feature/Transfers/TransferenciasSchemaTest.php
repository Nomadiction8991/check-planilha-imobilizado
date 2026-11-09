<?php

declare(strict_types=1);

namespace Tests\Feature\Transfers;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class TransferenciasSchemaTest extends TestCase
{
    public function testMigracaoCriaTabelasDeTransferencia(): void
    {
        $this->artisan('migrate', [
            '--path' => 'database/migrations/20261108120000_create_transferencias_tables.php',
            '--realpath' => true,
        ])->assertSuccessful();

        $this->assertTrue(Schema::hasTable('transferencias'));
        $this->assertTrue(Schema::hasColumns('transferencias', [
            'comum_origem_id',
            'comum_destino_id',
            'dependencia_destino_id',
            'numero_saida',
            'numero_entrada',
            'usuario_id',
        ]));

        $this->assertTrue(Schema::hasTable('transferencia_itens'));
        $this->assertTrue(Schema::hasColumns('transferencia_itens', [
            'transferencia_id',
            'produto_id',
            'codigo_anterior',
            'codigo_novo',
            'tipo_bem',
            'descricao',
            'quantidade',
            'valor',
            'dependencia_origem',
        ]));
    }
}
