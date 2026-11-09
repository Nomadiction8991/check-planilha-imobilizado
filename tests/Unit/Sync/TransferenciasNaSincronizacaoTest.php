<?php

declare(strict_types=1);

namespace Tests\Unit\Sync;

use Tests\TestCase;

final class TransferenciasNaSincronizacaoTest extends TestCase
{
    public function testTabelasDeTransferenciaEntramNaSincronizacao(): void
    {
        $tabelas = config('sincronizacao.tabelas');

        $this->assertContains('transferencias', $tabelas);
        $this->assertContains('transferencia_itens', $tabelas);
    }

    public function testTransferenciasSaoCopiadasDepoisDosProdutos(): void
    {
        $ordem = config('sincronizacao.ordem_da_copia');

        $posProdutos = array_search('produtos', $ordem, true);
        $posTransferencias = array_search('transferencias', $ordem, true);
        $posItens = array_search('transferencia_itens', $ordem, true);

        $this->assertNotFalse($posTransferencias);
        $this->assertNotFalse($posItens);
        $this->assertGreaterThan($posProdutos, $posTransferencias);
        $this->assertGreaterThan($posTransferencias, $posItens);
    }
}
