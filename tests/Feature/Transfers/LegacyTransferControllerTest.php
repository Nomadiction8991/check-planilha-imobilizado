<?php

declare(strict_types=1);

namespace Tests\Feature\Transfers;

use App\Models\Legacy\Produto;
use App\Models\Legacy\Transferencia;
use App\Services\LegacyTransferService;
use App\Support\Modo\EstadoModo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

final class LegacyTransferControllerTest extends TransferenciasTestCase
{
    private Transferencia $transferencia;

    protected function setUp(): void
    {
        parent::setUp();

        $this->criarAdministracao(1, 'Administração Cuiabá');
        $this->criarIgreja(7, '12-3456', 'Central Cuiabá');
        $this->criarIgreja(8, '35-0001', 'Central São Paulo');
        $this->criarDependencia(70, 7, 'Salão');
        $this->criarDependencia(80, 8, 'Sala 2');
        $this->criarTipoBem(1, 'Cadeira');
        $this->criarBem(1001, 7, 'A-100', 'Cadeira de madeira', 70, 150.0);

        DB::table('usuarios')->insert(['id' => 9, 'nome' => 'Maria Silva', 'email' => 'maria@exemplo.com']);

        Session::put('comum_id', 7);
        Session::put('usuario_id', 9);
    }

    public function testPaginaDeTransferenciasMostraPendentesEHistorico(): void
    {
        $response = $this->get(route('migration.transfers.index'));

        $response->assertOk();
        $response->assertSee('Pendentes de código');
        $response->assertSee('Nenhum bem pendente de código.');
        $response->assertSee('Histórico');
    }

    public function testPaginaDeTransferirBemMostraAIgrejaAtualDoBem(): void
    {
        $response = $this->get(route('migration.products.transfer', ['product' => 1001]));

        $response->assertOk();
        $response->assertSee('Igreja atual');
        $response->assertSee('12-3456 - Central Cuiabá');
        $response->assertSee('Cadeira de madeira');
        $response->assertSee('Continuar');
    }

    public function testEscolherIgrejaDeDestinoMostraOsLocaisDela(): void
    {
        $response = $this->get(route('migration.products.transfer', ['product' => 1001, 'comum_destino_id' => 8]));

        $response->assertOk();
        $response->assertSee('Sala 2');
        $response->assertSee('Transferir bem');
    }

    public function testTransferirBemPelaListaGravaERedirecionaParaORegistro(): void
    {
        $response = $this->post(route('migration.products.transfer.store', ['product' => 1001]), [
            'comum_destino_id' => 8,
            'dependencia_destino_id' => 80,
        ]);

        $transferencia = Transferencia::query()->firstOrFail();
        $response->assertRedirect(route('migration.transfers.show', $transferencia));
        $this->assertSame(8, (int) Produto::query()->findOrFail(1001)->comum_id);
        $this->assertNull(Produto::query()->findOrFail(1001)->codigo);
    }

    public function testTransferirBemSemLocalNaoGravaNada(): void
    {
        $response = $this->post(route('migration.products.transfer.store', ['product' => 1001]), [
            'comum_destino_id' => 8,
        ]);

        $response->assertSessionHasErrors('dependencia_destino_id');
        $this->assertSame(0, Transferencia::query()->count());
    }

    public function testBemDeIgrejaForaDoEscopoNaoAbreATelaDeTransferencia(): void
    {
        $this->sairDoEscopoDaIgreja7();

        $response = $this->get(route('migration.products.transfer', ['product' => 1001]));

        $response->assertRedirect(route('migration.products.index'));
    }

    public function testTransferirBemDeIgrejaForaDoEscopoNaoGrava(): void
    {
        $this->sairDoEscopoDaIgreja7();

        $response = $this->post(route('migration.products.transfer.store', ['product' => 1001]), [
            'comum_destino_id' => 8,
            'dependencia_destino_id' => 80,
        ]);

        $response->assertSessionHas('status_type', 'error');
        $this->assertSame(0, Transferencia::query()->count());
        $this->assertSame(7, (int) Produto::query()->findOrFail(1001)->comum_id);
    }

    public function testSemOutraIgrejaNoEscopoMostraAvisoEmVezDoFormulario(): void
    {
        Session::put('is_admin', false);
        Session::put('administracoes_permitidas', [1]);
        Session::put('administracao_id', 1);
        DB::table('comums')->where('id', 8)->update(['administracao_id' => 2]);

        $response = $this->get(route('migration.products.transfer', ['product' => 1001]));

        $response->assertOk();
        $response->assertSee('Não há outra igreja disponível para transferir este bem.');
        $response->assertDontSee('name="comum_destino_id"', false);
    }

    public function testIgrejaDeDestinoSemLocaisMostraAvisoEmVezDoFormulario(): void
    {
        $this->criarIgreja(9, '40-0002', 'Central Santos');

        $response = $this->get(route('migration.products.transfer', ['product' => 1001, 'comum_destino_id' => 9]));

        $response->assertOk();
        $response->assertSee('A igreja de destino ainda não tem locais cadastrados.');
        $response->assertDontSee('name="dependencia_destino_id"', false);
    }

    public function testDestinoForaDoEscopoNaoMostraOsLocaisDela(): void
    {
        Session::put('is_admin', false);
        Session::put('administracoes_permitidas', [1]);
        Session::put('administracao_id', 1);
        DB::table('comums')->where('id', 8)->update(['administracao_id' => 2]);

        $response = $this->get(route('migration.products.transfer', ['product' => 1001, 'comum_destino_id' => 8]));

        $response->assertOk();
        $response->assertDontSee('Sala 2');
        $response->assertDontSee('name="dependencia_destino_id"', false);
    }

    private function sairDoEscopoDaIgreja7(): void
    {
        Session::put('is_admin', false);
        Session::put('administracoes_permitidas', [2]);
        Session::put('administracao_id', 2);
    }

    public function testTransferenciaComServidorOfflineNaoGravaNada(): void
    {
        config(['modo_igreja.ativo' => true]);

        // O estado do modo igreja fica num arquivo JSON: o teste cria um arquivo dizendo "offline".
        $caminho = sys_get_temp_dir() . '/cp-estado-offline-' . uniqid() . '.json';
        file_put_contents($caminho, json_encode(['modo' => 'offline', 'acertos' => 0, 'falhas' => 2, 'desde' => null]));
        $this->app->instance(EstadoModo::class, new EstadoModo($caminho));

        try {
            $response = $this->post(route('migration.products.transfer.store', ['product' => 1001]), [
                'comum_destino_id' => 8,
                'dependencia_destino_id' => 80,
            ]);

            $response->assertSessionHas('status_type', 'error');
            $this->assertSame(0, Transferencia::query()->count());
            $this->assertSame(7, (int) Produto::query()->findOrFail(1001)->comum_id);
        } finally {
            @unlink($caminho);
        }
    }

    public function testInformarCodigoPeloFormularioSalvaOCodigoNovo(): void
    {
        $this->transferencia = (new LegacyTransferService())->transferir(7, 8, 80, [1001], 9);
        Session::put('comum_id', 8);

        $response = $this->post(route('migration.transfers.code'), [
            'produto_id' => 1001,
            'codigo' => 'B-200',
        ]);

        $response->assertRedirect(route('migration.transfers.index'));
        $this->assertSame('B-200', Produto::query()->findOrFail(1001)->codigo);
    }

    public function testPaginaDeTransferenciasListaOPendenteEOHistorico(): void
    {
        $this->transferencia = (new LegacyTransferService())->transferir(7, 8, 80, [1001], 9);
        Session::put('comum_id', 8);

        $response = $this->get(route('migration.transfers.index'));

        $response->assertOk();
        $response->assertSee('Salvar código');
        $response->assertSee(route('migration.transfers.show', $this->transferencia));
    }

    public function testResumoDaTransferenciaTrazOsDoisFormularios(): void
    {
        $this->transferencia = (new LegacyTransferService())->transferir(7, 8, 80, [1001], 9);

        $response = $this->get(route('migration.transfers.show', $this->transferencia));

        $response->assertOk();
        $response->assertSee('Formulário de saída');
        $response->assertSee('Cadeira de madeira');
    }

    public function testFormularioDeSaidaAbreParaAIgrejaDeOrigem(): void
    {
        $this->transferencia = (new LegacyTransferService())->transferir(7, 8, 80, [1001], 9);

        $response = $this->get(route('migration.transfers.form', [$this->transferencia, 'saida']));

        $response->assertOk();
        $response->assertSee('Transferência para 35-0001 - Central São Paulo', escape: false);
    }

    public function testFormularioDeSaidaNaoAbreParaOutraIgreja(): void
    {
        $this->transferencia = (new LegacyTransferService())->transferir(7, 8, 80, [1001], 9);
        Session::put('comum_id', 8);

        $response = $this->get(route('migration.transfers.form', [$this->transferencia, 'saida']));

        $response->assertRedirect(route('migration.transfers.show', $this->transferencia));
    }

    public function testTipoDeFormularioDesconhecidoDaNaoEncontrado(): void
    {
        $this->transferencia = (new LegacyTransferService())->transferir(7, 8, 80, [1001], 9);

        $this->get(route('migration.transfers.form', [$this->transferencia, 'qualquer']))
            ->assertNotFound();
    }

    public function testResumoMostraOFormularioDeEntradaParaAIgrejaDeDestino(): void
    {
        $this->transferencia = (new LegacyTransferService())->transferir(7, 8, 80, [1001], 9);
        Session::put('comum_id', 8);

        $response = $this->get(route('migration.transfers.show', $this->transferencia));

        $response->assertOk();
        $response->assertSee('Formulário de entrada (14.2)');
    }

    public function testFormularioDeEntradaAbreParaAIgrejaDeDestino(): void
    {
        $this->transferencia = (new LegacyTransferService())->transferir(7, 8, 80, [1001], 9);
        Session::put('comum_id', 8);

        $response = $this->get(route('migration.transfers.form', [$this->transferencia, 'entrada']));

        $response->assertOk();
        $response->assertSee('Transferência de 12-3456 - Central Cuiabá', escape: false);
    }
}
