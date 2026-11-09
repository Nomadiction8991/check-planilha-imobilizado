<?php

declare(strict_types=1);

namespace Tests\Feature\Transfers;

use App\Contracts\LegacyAuthSessionServiceInterface;
use App\Services\LegacyReportService;
use App\Services\LegacyReportTemplateService;
use App\Services\LegacyTransferService;
use Mockery;

final class RelatorioAlteracoesTransferenciasTest extends TransferenciasTestCase
{
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

        (new LegacyTransferService())->transferir(7, 8, 80, [1001], 9);
    }

    public function testRelatorioDeAlteracoesTrazAsTransferenciasDaIgreja(): void
    {
        $relatorio = $this->relatorioDaIgreja(8);

        $this->assertCount(1, $relatorio['transferencias']);
        $this->assertSame('entrada', $relatorio['transferencias'][0]['tipo']);
        $this->assertSame(1, $relatorio['resumo']['total_transferidos']);
    }

    public function testTelaDeAlteracoesExibeASecaoDeTransferencias(): void
    {
        $html = view('reports.position', [
            'churches' => collect(),
            'selectedChurchId' => 8,
            'report' => $this->relatorioDaIgreja(8),
        ])->render();

        $this->assertStringContainsString('Transferências', $html);
        $this->assertStringContainsString('12-3456 - Central Cuiabá', $html);
        $this->assertStringContainsString('A-100', $html);
        $this->assertStringContainsString('Pendente', $html);
    }

    /**
     * @return array<string, mixed>
     */
    private function relatorioDaIgreja(int $comumId): array
    {
        $auth = Mockery::mock(LegacyAuthSessionServiceInterface::class)->shouldIgnoreMissing();
        $servico = new LegacyReportService(new LegacyReportTemplateService(), $auth);

        return $servico->buildVerificationPositionReport($comumId);
    }
}
