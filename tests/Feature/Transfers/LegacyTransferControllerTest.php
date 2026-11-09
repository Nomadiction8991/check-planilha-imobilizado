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

    public function testPaginaDeTransferenciasMostraOBotaoNovaTransferencia(): void
    {
        $response = $this->get(route('migration.transfers.index'));

        $response->assertOk();
        $response->assertSee('Nova transferência');
    }

    public function testPaginaDeNovaTransferenciaListaOsBensDaIgrejaDeOrigem(): void
    {
        $response = $this->get(route('migration.transfers.create', ['comum_destino_id' => 8]));

        $response->assertOk();
        $response->assertSee('A-100');
        $response->assertSee('Cadeira de madeira');
        $response->assertSee('Sala 2');
    }

    public function testTransferenciaPeloFormularioGravaERedirecionaParaORegistro(): void
    {
        $response = $this->post(route('migration.transfers.store'), [
            'comum_destino_id' => 8,
            'dependencia_destino_id' => 80,
            'produto_ids' => [1001],
        ]);

        $transferencia = Transferencia::query()->firstOrFail();
        $response->assertRedirect(route('migration.transfers.show', $transferencia));
        $this->assertSame(8, (int) Produto::query()->findOrFail(1001)->comum_id);
    }

    public function testTransferenciaSemBensNaoGravaNada(): void
    {
        $response = $this->post(route('migration.transfers.store'), [
            'comum_destino_id' => 8,
            'dependencia_destino_id' => 80,
        ]);

        $response->assertSessionHasErrors('produto_ids');
        $this->assertSame(0, Transferencia::query()->count());
    }

    public function testTransferenciaComServidorOfflineNaoGravaNada(): void
    {
        config(['modo_igreja.ativo' => true]);

        // O estado do modo igreja fica num arquivo JSON: o teste cria um arquivo dizendo "offline".
        $caminho = sys_get_temp_dir() . '/cp-estado-offline-' . uniqid() . '.json';
        file_put_contents($caminho, json_encode(['modo' => 'offline', 'acertos' => 0, 'falhas' => 2, 'desde' => null]));
        $this->app->instance(EstadoModo::class, new EstadoModo($caminho));

        try {
            $response = $this->post(route('migration.transfers.store'), [
                'comum_destino_id' => 8,
                'dependencia_destino_id' => 80,
                'produto_ids' => [1001],
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
