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
        $filler = require resource_path('legacy-reports/fillers/14.1.php');

        $html = <<<HTML
<textarea name="administracao"></textarea>
<textarea name="cidade"></textarea>
<textarea name="cnpj-da-administracao"></textarea>
<textarea name="casa_de_oracao"></textarea>
<textarea name="nome_administrador&amp;acessor_1"></textarea>
HTML;

        $filled = $filler($html, [], [
            'descricao' => 'Igreja Central',
            'cidade' => 'Cuiabá',
            'estado' => 'MT',
            'administracao_descricao' => 'Administração Central',
            'administracao_cnpj' => '12345678000190',
            'usuario_nome_relatorio' => 'Maria Silva',
            'cidade_administracao' => '',
            'cnpj' => '',
            'administrador_nome' => 'Nome Antigo',
        ]);

        self::assertSame('Administração Central', $this->extractTextareaValue($filled, 'administracao'));
        self::assertSame('Cuiabá', $this->extractTextareaValue($filled, 'cidade'));
        self::assertSame('12345678000190', $this->extractTextareaValue($filled, 'cnpj-da-administracao'));
        self::assertSame('Maria Silva', $this->extractTextareaValue($filled, 'nome_administrador&amp;acessor_1'));
        self::assertStringNotContainsString('cidade_administracao', $filled);
    }

    public function testReport146FillsAdministrationDescriptionCityAndCnpj(): void
    {
        $filler = require resource_path('legacy-reports/fillers/14.6.php');

        $html = <<<HTML
<textarea name="administracao"></textarea>
<textarea name="cidade"></textarea>
<textarea name="cnpj_da-administracao"></textarea>
<textarea name="casa_de_oracao"></textarea>
<textarea name="nome_do-responsavel"></textarea>
<textarea name="descricao_de_1"></textarea>
<textarea name="descricao_para_2"></textarea>
HTML;

        $filled = $filler($html, [
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
        ]);

        self::assertSame('Administração Central', $this->extractTextareaValue($filled, 'administracao'));
        self::assertSame('Cuiabá', $this->extractTextareaValue($filled, 'cidade'));
        self::assertSame('12345678000190', $this->extractTextareaValue($filled, 'cnpj_da-administracao'));
        self::assertSame('Maria Silva', $this->extractTextareaValue($filled, 'nome_do-responsavel'));
        self::assertSame('CADEIRA METALICA A(1.2m) L(0.8m) C(2m)', $this->extractTextareaValue($filled, 'descricao_de_1'));
        self::assertSame('CADEIRA METALICA TRAMONTINA A(1.3m) L(0.8m) C(2m)', $this->extractTextareaValue($filled, 'descricao_para_2'));
        self::assertStringNotContainsString('cidade_administracao', $filled);
    }

    private function extractTextareaValue(string $html, string $name): string
    {
        $pattern = '/<textarea[^>]*name="' . preg_quote($name, '/') . '"[^>]*>(.*?)<\/textarea>/s';
        if (preg_match($pattern, $html, $matches) !== 1) {
            self::fail('Campo não encontrado: ' . $name);
        }

        return html_entity_decode(trim((string) $matches[1]), ENT_QUOTES, 'UTF-8');
    }
}
