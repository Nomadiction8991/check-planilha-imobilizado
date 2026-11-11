<?php

declare(strict_types=1);

namespace Tests\Feature\Transfers;

use App\Contracts\LegacyNavigationServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class MenuTransferenciasTest extends TransferenciasTestCase
{
    public function testOrdemSalvadaComTransferenciasNaoQuebraOMenu(): void
    {
        // Bases antigas podem ter gravado "transfers" na ordem do menu; essa chave deve ser ignorada.
        Schema::table('configuracoes', function ($tabela): void {
            $tabela->text('menu_order')->nullable();
        });
        DB::table('configuracoes')->insert(['id' => 1, 'menu_order' => json_encode(['transfers', 'products'])]);

        $itens = app(LegacyNavigationServiceInterface::class)->navigation(['products.edit' => true], true);

        $this->assertSame('products', $itens[0]['key']);
        $this->assertNotContains('transfers', array_column($itens, 'key'));
    }

    public function testMenuNaoTemItemDeTransferencias(): void
    {
        $itens = app(LegacyNavigationServiceInterface::class)->navigation(['products.edit' => true], true);

        $this->assertNotContains('transfers', array_column($itens, 'key'));
    }

    public function testListaDeOrdemDoMenuNaoTrazTransferencias(): void
    {
        $itens = app(LegacyNavigationServiceInterface::class)->editorItems();

        $this->assertNotContains('transfers', array_column($itens, 'key'));
    }

    public function testProdutosFicaDestacadoNasTelasDeTransferencia(): void
    {
        $itens = app(LegacyNavigationServiceInterface::class)->navigation(['products.edit' => true], true);
        $produtos = collect($itens)->firstWhere('key', 'products');

        $this->assertNotNull($produtos);
        $this->assertContains('migration.transfers.*', $produtos['active_patterns']);
        $this->assertContains('migration.products.transfer*', $produtos['active_patterns']);
    }
}
