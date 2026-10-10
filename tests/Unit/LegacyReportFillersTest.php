<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

final class LegacyReportFillersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        require_once resource_path('legacy-reports/fillers/bootstrap.php');
    }

    public function testReport141FillsAdministrationDescriptionCityAndCnpj(): void
    {
        $html = <<<HTML
<textarea name="administracao"></textarea>
<textarea name="cidade"></textarea>
<textarea name="cnpj-da-administracao"></textarea>
<textarea name="n_relatorio"></textarea>
<textarea name="casa_de_oracao"></textarea>
<textarea name="nome_administrador&amp;acessor_1"></textarea>
HTML;

        $filled = appReportAplicarPreenchimentoRelatorio('14.1', $html, [], [
            'descricao' => 'Igreja Central',
            'cidade' => 'Cuiabá',
            'estado' => 'MT',
            'administracao_descricao' => 'Administração Central',
            'administracao_cnpj' => '12345678000190',
            'usuario_nome_relatorio' => 'Maria Silva',
            'cidade_administracao' => '',
            'cnpj' => '',
            'administrador_nome' => 'Nome Antigo',
            'codigo' => '12-3456',
        ]);

        self::assertSame('Administração Central', $this->extractTextareaValue($filled, 'administracao'));
        self::assertSame('Cuiabá', $this->extractTextareaValue($filled, 'cidade'));
        self::assertSame('12345678000190', $this->extractTextareaValue($filled, 'cnpj-da-administracao'));
        self::assertSame('12-3456', $this->extractTextareaValue($filled, 'n_relatorio'));
        self::assertSame('Maria Silva', $this->extractTextareaValue($filled, 'nome_administrador&amp;acessor_1'));
        self::assertStringNotContainsString('cidade_administracao', $filled);
    }

    public function testReport146FillsAdministrationDescriptionCityAndCnpj(): void
    {
        $html = <<<HTML
<textarea name="administracao"></textarea>
<textarea name="cidade"></textarea>
<textarea name="cnpj_da-administracao"></textarea>
<textarea name="n_relatorio"></textarea>
<textarea name="casa_de_oracao"></textarea>
<textarea name="nome_do-responsavel"></textarea>
<textarea name="descricao_de_1"></textarea>
<textarea name="descricao_para_2"></textarea>
HTML;

        $filled = appReportAplicarPreenchimentoRelatorio('14.6', $html, [
            'itens' => [[
                'nome_original' => 'CADEIRA METALICA A(1.2m) L(0.8m) C(2m)',
                'nome_atual' => 'CADEIRA METALICA TRAMONTINA A(1.3m) L(0.8m) C(2m)',
            ]],
        ], [
            'descricao' => 'Igreja Central',
            'cidade' => 'Cuiabá',
            'estado' => 'MT',
            'administracao_descricao' => 'Administração Central',
            'administracao_cnpj' => '12345678000190',
            'usuario_nome_relatorio' => 'Maria Silva',
            'cidade_administracao' => '',
            'cnpj' => '',
            'codigo' => '12-3456',
        ]);

        self::assertSame('Administração Central', $this->extractTextareaValue($filled, 'administracao'));
        self::assertSame('Cuiabá', $this->extractTextareaValue($filled, 'cidade'));
        self::assertSame('12345678000190', $this->extractTextareaValue($filled, 'cnpj_da-administracao'));
        self::assertSame('12-3456', $this->extractTextareaValue($filled, 'n_relatorio'));
        self::assertSame('Maria Silva', $this->extractTextareaValue($filled, 'nome_do-responsavel'));
        self::assertSame('CADEIRA METALICA A(1.2m) L(0.8m) C(2m)', $this->extractTextareaValue($filled, 'descricao_de_1'));
        self::assertSame('CADEIRA METALICA TRAMONTINA A(1.3m) L(0.8m) C(2m)', $this->extractTextareaValue($filled, 'descricao_para_2'));
        self::assertStringNotContainsString('cidade_administracao', $filled);
    }

    public function testReport141PreservaValoresComCifraoETodosOsTiposDeCampo(): void
    {
        $html = <<<HTML
<textarea name="descricao_bem"></textarea>
<textarea name="casa_de_oracao"></textarea>
<input type="text" name="valor" value="">
<input type="text" name="fornecedor" value="">
<input type="text" name="nota_fiscal">
HTML;

        $html = appReportPreencherCampoPorName($html, 'descricao_bem', 'Caixa $100');
        $html = appReportPreencherCampoPorName($html, 'casa_de_oracao', 'Igreja $1 de Maio');
        $html = appReportPreencherCampoPorName($html, 'valor', 'R$ 1.500,00 ($ total)');
        $html = appReportPreencherCampoPorName($html, 'fornecedor', 'Loja $var & Cia');
        $html = appReportPreencherCampoPorName($html, 'nota_fiscal', 'NF $99');

        self::assertSame('Caixa $100', $this->extractTextareaValue($html, 'descricao_bem'));
        self::assertSame('Igreja $1 de Maio', $this->extractTextareaValue($html, 'casa_de_oracao'));

        self::assertSame('R$ 1.500,00 ($ total)', $this->extractInputValue($html, 'valor'));
        self::assertSame('Loja $var & Cia', html_entity_decode($this->extractInputValue($html, 'fornecedor')));
        self::assertSame('NF $99', html_entity_decode($this->extractInputValue($html, 'nota_fiscal')));
    }

    public function testReport141FillsProductDescriptionWithDollarInsideFiller(): void
    {
        $html = <<<HTML
<textarea name="descricao_bem"></textarea>
<textarea name="n_relatorio"></textarea>
HTML;

        $filled = appReportAplicarPreenchimentoRelatorio('14.1', $html, [
            'descricao_completa' => 'Caixa organizadora $100',
        ], [
            'descricao' => 'Igreja Central',
            'cidade' => 'Cuiabá',
            'estado' => 'MT',
            'administracao_descricao' => 'Administração Central',
            'administracao_cnpj' => '12345678000190',
            'usuario_nome_relatorio' => 'Maria Silva',
            'codigo' => '12-3456',
        ]);

        self::assertSame('Caixa organizadora $100', html_entity_decode($this->extractTextareaValue($filled, 'descricao_bem')));
        self::assertSame('12-3456', $this->extractTextareaValue($filled, 'n_relatorio'));
    }

    private function extractTextareaValue(string $html, string $name): string
    {
        $pattern = '/<textarea[^>]*name="' . preg_quote($name, '/') . '"[^>]*>(.*?)<\/textarea>/s';
        if (preg_match($pattern, $html, $matches) !== 1) {
            self::fail('Campo não encontrado: ' . $name);
        }

        return html_entity_decode(trim((string) $matches[1]), ENT_QUOTES, 'UTF-8');
    }

    private function extractInputValue(string $html, string $name): string
    {
        $pattern = '/<input[^>]*name="' . preg_quote($name, '/') . '"[^>]*value="([^"]*)"/';
        if (preg_match($pattern, $html, $matches) !== 1) {
            self::fail('Campo input não encontrado: ' . $name);
        }

        return (string) $matches[1];
    }
}
