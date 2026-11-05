<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CartaoListaMarcacaoTest extends TestCase
{
    #[DataProvider('telas')]
    public function test_celulas_identificam_titulo_e_acoes(string $arquivo, array $titulos, ?string $acoes): void
    {
        $conteudo = file_get_contents(__DIR__.'/../../resources/views/'.$arquivo.'.blade.php');
        self::assertIsString($conteudo);

        foreach ($titulos as $titulo) {
            self::assertMatchesRegularExpression('/<td\b(?=[^>]*data-label="'.preg_quote($titulo, '/').'")(?=[^>]*\bdata-card-title\b)[^>]*>/', $conteudo, "Título ausente: {$arquivo} / {$titulo}");
        }

        if ($acoes !== null) {
            self::assertMatchesRegularExpression('/<td\b(?=[^>]*data-label="'.preg_quote($acoes, '/').'")(?=[^>]*\bdata-card-actions\b)[^>]*>/', $conteudo, "Ações ausentes: {$arquivo} / {$acoes}");
        } else {
            self::assertStringNotContainsString('data-card-actions', $conteudo);
        }
    }

    public static function telas(): array
    {
        return [
            'produtos' => ['products/index', ['Produto'], 'Ações'],
            'igrejas' => ['churches/index', ['Código', 'Descrição'], 'Ações'],
            'administrações' => ['administrations/index', ['Descrição'], 'Ações'],
            'dependências' => ['departments/index', ['Dependência'], 'Ações'],
            'auditoria' => ['audits/index', ['Data', 'Usuário'], null],
            'relatórios' => ['reports/index', ['Código', 'Descrição'], 'Ação'],
            'posição' => ['reports/position', ['Código', 'Descrição'], null],
            'importações' => ['spreadsheets/import', ['Igreja'], 'Ação'],
            'erros' => ['spreadsheets/errors', ['Código', 'Descrição CSV'], 'Situação'],
        ];
    }
}
