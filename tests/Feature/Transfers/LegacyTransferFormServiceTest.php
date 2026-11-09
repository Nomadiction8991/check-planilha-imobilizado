<?php

declare(strict_types=1);

namespace Tests\Feature\Transfers;

use App\Models\Legacy\Transferencia;
use App\Services\LegacyReportTemplateService;
use App\Services\LegacyTransferFormService;
use App\Services\LegacyTransferService;

final class LegacyTransferFormServiceTest extends TransferenciasTestCase
{
    private Transferencia $transferencia;

    private LegacyTransferFormService $formularios;

    protected function setUp(): void
    {
        parent::setUp();

        $this->criarAdministracao(1, 'Administração Cuiabá');
        $this->criarIgreja(7, '12-3456', 'Central Cuiabá');
        $this->criarIgreja(8, '35-0001', 'Central São Paulo');
        $this->criarIgreja(9, '40-0002', 'Central Sinop');
        $this->criarDependencia(70, 7, 'Salão');
        $this->criarDependencia(80, 8, 'Sala 2');
        $this->criarDependencia(90, 9, 'Templo');
        $this->criarTipoBem(1, 'Cadeira');
        $this->criarBem(1001, 7, 'A-100', 'Cadeira de madeira', 70, 150.0);
        $this->criarBem(1002, 7, 'A-101', 'Mesa de reunião', 70, 80.0);

        $servico = new LegacyTransferService();
        // Primeira saída da igreja 7: deixa os números de saída e de entrada diferentes na transferência seguinte.
        $servico->transferir(7, 9, 90, [1002], 9);
        $this->transferencia = $servico->transferir(7, 8, 80, [1001], 9);

        $this->formularios = new LegacyTransferFormService(new LegacyReportTemplateService());
    }

    public function testFormularioDeSaidaMarcaTransferenciaEnviadaEListaOCodigoAntigo(): void
    {
        $previa = $this->formularios->previa($this->transferencia, 'saida');
        $html = $previa['paginas'][0]['html'];

        $this->assertSame('14.3', $previa['formulario']);
        $this->assertSame(2, $previa['numero']);
        $this->assertSame('X', $this->campo($html, 'transferencia_enviada'));
        $this->assertSame('A-100', $this->campo($html, 'codigo_do_bem_1'));
        $this->assertSame('Cadeira de madeira', $this->campo($html, 'descricao_1'));
        $this->assertSame('150,00', $this->campo($html, 'valor_original_1'));
        $this->assertStringContainsString('Transferência para 35-0001 - Central São Paulo', $this->campo($html, 'descricao_do_motivo_1'));
        $this->assertSame('2', $this->campo($html, 'n_do_relatorio_1'));
        $this->assertSame('Administração Cuiabá', $this->campo($html, 'administrador_1'));
        $this->assertSame('Central Cuiabá', $this->campo($html, 'casa_de_oracao_1'));
        $this->assertSame('', $this->campo($html, 'nome_colaborador_administrativo'));
    }

    public function testFormularioDeEntradaTrazOrigemENumerosDeSaidaEDeEntrada(): void
    {
        $previa = $this->formularios->previa($this->transferencia, 'entrada');
        $html = $previa['paginas'][0]['html'];

        $this->assertSame('14.2', $previa['formulario']);
        $this->assertSame('X', $this->campo($html, 'transferencia_recebida'));
        $this->assertSame('Cadeira', $this->campo($html, 'tipo_de_bem_1'));
        $this->assertSame('Cadeira de madeira', $this->campo($html, 'descricao_1'));
        $this->assertSame('Sala 2', $this->campo($html, 'local_do_bem_1'));
        $this->assertSame('150,00', $this->campo($html, 'valor_de_aquisicao_1'));
        $this->assertSame('1', $this->campo($html, 'n_do_relatorio_1'));
        $this->assertSame('2', $this->campo($html, 'n_do_relatorio'));
        $this->assertSame('Administração Cuiabá', $this->campo($html, 'administracao_2'));
        $this->assertSame('Central Cuiabá', $this->campo($html, 'casa_de_oracao'));
        $this->assertSame('Central São Paulo', $this->campo($html, 'casa_de_horacao'));
        $this->assertStringContainsString('Transferência de 12-3456 - Central Cuiabá', $this->campo($html, 'descricao_do_motivo_1'));
    }

    public function testBensQueNaoCabemNumaFolhaGeramMaisFolhasComOMesmoNumero(): void
    {
        for ($i = 0; $i < 12; $i++) {
            $this->criarBem(2000 + $i, 7, 'C-' . $i, 'Bem ' . $i);
        }

        $transferencia = (new LegacyTransferService())->transferir(7, 8, 80, range(2000, 2011), 9);
        $previa = $this->formularios->previa($transferencia, 'saida');

        $this->assertSame(2, $previa['total_paginas']);
        $this->assertSame('C-0', $this->campo($previa['paginas'][0]['html'], 'codigo_do_bem_1'));
        $this->assertSame('C-10', $this->campo($previa['paginas'][0]['html'], 'codigo_do_bem_11'));
        $this->assertSame('C-11', $this->campo($previa['paginas'][1]['html'], 'codigo_do_bem_1'));
        $this->assertSame(
            $transferencia->numero_saida,
            (int) $this->campo($previa['paginas'][1]['html'], 'n_do_relatorio_1'),
        );
    }

    private function campo(string $html, string $nome): string
    {
        $padrao = '/name="' . preg_quote($nome, '/') . '"[^>]*>(.*?)<\/textarea>/s';

        if (preg_match($padrao, $html, $encontrado) !== 1) {
            $this->fail('Campo não encontrado no formulário: ' . $nome);
        }

        return html_entity_decode(trim($encontrado[1]), ENT_QUOTES, 'UTF-8');
    }
}
