<?php

declare(strict_types=1);

namespace Tests\Feature\Transfers;

use App\Models\Legacy\Produto;
use App\Models\Legacy\Transferencia;
use App\Models\Legacy\TransferenciaItem;
use App\Services\LegacyTransferService;
use Illuminate\Support\Facades\Session;
use RuntimeException;

final class LegacyTransferServiceTest extends TransferenciasTestCase
{
    private LegacyTransferService $servico;

    protected function setUp(): void
    {
        parent::setUp();

        $this->criarAdministracao(1);
        $this->criarIgreja(7, '12-3456', 'Central Cuiabá');
        $this->criarIgreja(8, '35-0001', 'Central São Paulo');
        $this->criarDependencia(70, 7, 'Salão');
        $this->criarDependencia(80, 8, 'Sala 2');
        $this->criarTipoBem(1, 'Cadeira');
        $this->criarBem(1001, 7, 'A-100', 'Cadeira de madeira', 70, 150.0);

        $this->servico = new LegacyTransferService();
    }

    public function testTransfereBemTrocandoIgrejaERemovendoCodigoComHistorico(): void
    {
        $transferencia = $this->servico->transferir(7, 8, 80, [1001], 9);

        $this->assertInstanceOf(Transferencia::class, $transferencia);
        $this->assertSame(7, $transferencia->comum_origem_id);
        $this->assertSame(8, $transferencia->comum_destino_id);
        $this->assertSame(80, $transferencia->dependencia_destino_id);
        $this->assertSame(1, $transferencia->numero_saida);
        $this->assertSame(1, $transferencia->numero_entrada);
        $this->assertSame(9, $transferencia->usuario_id);

        $item = TransferenciaItem::query()->where('transferencia_id', $transferencia->id)->firstOrFail();
        $this->assertSame(1001, $item->produto_id);
        $this->assertSame('A-100', $item->codigo_anterior);
        $this->assertNull($item->codigo_novo);
        $this->assertSame('Cadeira de madeira', $item->descricao);
        $this->assertSame('Cadeira', $item->tipo_bem);
        $this->assertSame('Salão', $item->dependencia_origem);

        $bem = Produto::query()->findOrFail(1001);
        $this->assertSame(8, (int) $bem->comum_id);
        $this->assertNull($bem->codigo);
        $this->assertSame(80, (int) $bem->dependencia_id);
        $this->assertSame(0, (int) $bem->checado);
        $this->assertSame(0, (int) $bem->imprimir_etiqueta);
    }

    public function testNumeracaoDaSaidaEDaEntradaEPorIgreja(): void
    {
        $this->criarBem(1002, 7, 'A-101', 'Mesa');
        $this->criarBem(1003, 7, 'A-102', 'Banco');
        $this->criarIgreja(9, '40-0002', 'Central Sinop');
        $this->criarDependencia(90, 9, 'Templo');

        $primeira = $this->servico->transferir(7, 8, 80, [1001], 9);
        $segunda = $this->servico->transferir(7, 8, 80, [1002], 9);
        $terceira = $this->servico->transferir(7, 9, 90, [1003], 9);

        $this->assertSame([1, 2, 3], [$primeira->numero_saida, $segunda->numero_saida, $terceira->numero_saida]);
        $this->assertSame([1, 2, 1], [$primeira->numero_entrada, $segunda->numero_entrada, $terceira->numero_entrada]);
    }

    public function testRecusaBemQueNaoEstaNaIgrejaDeOrigem(): void
    {
        $this->criarBem(1002, 8, 'B-100', 'Piano');

        try {
            $this->servico->transferir(7, 8, 80, [1002], 9);
            $this->fail('Era esperado RuntimeException.');
        } catch (RuntimeException $erro) {
            $this->assertStringContainsString('não está mais nesta igreja', $erro->getMessage());
        }

        $this->assertSame(0, Transferencia::query()->count());
        $this->assertSame(8, (int) Produto::query()->findOrFail(1002)->comum_id);
    }

    public function testRecusaDestinoIgualAOrigem(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('precisa ser diferente');

        $this->servico->transferir(7, 7, 70, [1001], 9);
    }

    public function testRecusaLocalQueNaoPertenceAIgrejaDeDestino(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('O local selecionado não pertence à igreja de destino.');

        $this->servico->transferir(7, 8, 70, [1001], 9);
    }

    public function testRecusaTransferenciaSemBens(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Selecione ao menos um bem.');

        $this->servico->transferir(7, 8, 80, [], 9);
    }

    public function testRecusaIgrejaForaDoEscopoDoUsuario(): void
    {
        $this->criarAdministracao(2, 'Outra administração');
        $this->criarIgreja(10, '50-0003', 'Igreja de outra administração', 2);
        $this->criarDependencia(100, 10, 'Salão');
        Session::put('is_admin', false);
        Session::put('administracoes_permitidas', [1]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('fora do seu escopo permitido');

        $this->servico->transferir(7, 10, 100, [1001], 9);
    }

    public function testFalhaNoMeioDaTransferenciaNaoDeixaNadaPelaMetade(): void
    {
        $this->criarBem(1002, 7, 'A-101', 'Mesa');
        Produto::updating(static function (Produto $produto): void {
            if ((int) $produto->id_produto === 1002) {
                throw new RuntimeException('falha simulada');
            }
        });

        try {
            $this->servico->transferir(7, 8, 80, [1001, 1002], 9);
            $this->fail('Era esperado RuntimeException.');
        } catch (RuntimeException $erro) {
            $this->assertSame('falha simulada', $erro->getMessage());
        }

        $this->assertSame(0, Transferencia::query()->count());
        $this->assertSame(0, TransferenciaItem::query()->count());
        $this->assertSame(7, (int) Produto::query()->findOrFail(1001)->comum_id);
        $this->assertSame('A-100', Produto::query()->findOrFail(1001)->codigo);
    }

    public function testInformaCodigoNovoDoBemPendente(): void
    {
        $this->servico->transferir(7, 8, 80, [1001], 9);

        $this->servico->informarCodigo(1001, 8, ' B-200 ');

        $bem = Produto::query()->findOrFail(1001);
        $this->assertSame('B-200', $bem->codigo);
        $this->assertSame(1, (int) $bem->checado);
        $this->assertSame(1, (int) $bem->imprimir_etiqueta);
        $this->assertSame('B-200', TransferenciaItem::query()->where('produto_id', 1001)->value('codigo_novo'));
    }

    public function testRecusaCodigoJaUsadoPorOutroBem(): void
    {
        $this->criarBem(1002, 8, 'B-200', 'Piano');
        $this->servico->transferir(7, 8, 80, [1001], 9);

        try {
            $this->servico->informarCodigo(1001, 8, 'B-200');
            $this->fail('Era esperado RuntimeException.');
        } catch (RuntimeException $erro) {
            $this->assertSame('Este código já está em uso por outro bem.', $erro->getMessage());
        }

        $this->assertNull(Produto::query()->findOrFail(1001)->codigo);
    }

    public function testRecusaCodigoDeBemQueNaoEstaPendente(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Este bem não está pendente de código.');

        $this->servico->informarCodigo(1001, 7, 'Z-1');
    }

    public function testPendentesDeCodigoListaSoBensSemCodigoTransferidos(): void
    {
        $this->assertCount(0, $this->servico->pendentesDeCodigo(8));

        $this->servico->transferir(7, 8, 80, [1001], 9);

        $pendentes = $this->servico->pendentesDeCodigo(8);
        $this->assertCount(1, $pendentes);
        $this->assertSame(1001, (int) $pendentes->first()->id_produto);

        $this->servico->informarCodigo(1001, 8, 'B-200');
        $this->assertCount(0, $this->servico->pendentesDeCodigo(8));
    }
}
