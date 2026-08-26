<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\CsvParserService;
use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use ReflectionMethod;
use Tests\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class CsvParserServiceTest extends TestCase
{
    public function testParsearNomePreservesOriginalComplementoWhileIdentifyingAssetType(): void
    {
        $pdo = $this->createMock(PDO::class);
        $statement = $this->createMock(PDOStatement::class);

        $pdo->expects($this->once())
            ->method('prepare')
            ->with('SELECT descricao FROM tipos_bens WHERE codigo = :codigo LIMIT 1')
            ->willReturn($statement);

        $statement->expects($this->once())
            ->method('execute')
            ->with([':codigo' => '4'])
            ->willReturn(true);

        $statement->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(['descricao' => 'CADEIRA']);

        $service = new CsvParserService($pdo);
        $method = new ReflectionMethod($service, 'parsearNome');
        $method->setAccessible(true);

        $result = $method->invoke($service, '4 - CADEIRA CADEIRA TRIBUNA ALMOFADADA PULPITO');

        self::assertSame('4', $result['tipo_bem_codigo']);
        self::assertSame('CADEIRA', $result['bem']);
        self::assertSame('CADEIRA CADEIRA TRIBUNA ALMOFADADA PULPITO', $result['complemento']);
        self::assertSame('CADEIRA CADEIRA TRIBUNA ALMOFADADA PULPITO', $result['descricao_apos_tipo']);
    }

    public function testValidarColunasMapeadasRejeitaPlanilhaSemColunasEssenciais(): void
    {
        $pdo = $this->createMock(PDO::class);
        $service = new CsvParserService($pdo);

        // Amostra simulando planilha com apenas 2 colunas: código e descrição.
        // O mapeamento padrão espera o nome na coluna D (índice 3).
        $amostra = [
            ['09-0565 / 001495', 'CADEIRA GIRATORIA'],
            ['09-0566 / 001496', 'MESA RETANGULAR'],
        ];

        $method = new ReflectionMethod($service, 'validarColunasMapeadas');

        $erro = $method->invoke($service, $amostra, 0, []);

        self::assertIsString($erro);
        self::assertStringContainsString('Nome (coluna D)', $erro);
        self::assertStringContainsString('colunas obrigatórias', $erro);
    }

    public function testValidarColunasMapeadasAceitaPlanilhaComColunasPreenchidas(): void
    {
        $pdo = $this->createMock(PDO::class);
        $service = new CsvParserService($pdo);

        $linhaCompleta = [];
        $linhaCompleta[0] = '09-0565 / 001495';
        $linhaCompleta[3] = '4 - CADEIRA TRIBUNA ALMOFADADA';
        $linhaCompleta[10] = 'CONGREGAÇÃO CENTRAL';
        $linhaCompleta[15] = 'SALAO 1';

        $method = new ReflectionMethod($service, 'validarColunasMapeadas');

        self::assertNull($method->invoke($service, [$linhaCompleta], 0, []));
    }

    public function testAnalisarTodasAsLinhasClassificaLinhaSemNomeComoErroIdentificavel(): void
    {
        $pdo = $this->createMock(PDO::class);
        $service = new CsvParserService($pdo);

        $linhas = [
            [
                'codigo' => '09-0565 / 0001',
                'tipo_bem_codigo' => '4',
                'bem' => 'CADEIRA',
                'complemento' => 'METALICA',
                'dependencia' => 'SALA 01',
                'localidade' => 'BR 09-0565',
                'codigo_comum' => '09-0565',
                'nome_original' => '4 - CADEIRA METALICA',
                '_linha_original' => 30,
            ],
            [
                'codigo' => '09-0565 / 0002',
                'tipo_bem_codigo' => '',
                'bem' => '',
                'complemento' => '',
                'dependencia' => '',
                'localidade' => '',
                'codigo_comum' => '09-0565',
                'nome_original' => '',
                '_linha_original' => 31,
            ],
            [
                'codigo' => '09-0565 / 0003',
                'tipo_bem_codigo' => '7',
                'bem' => 'MESA',
                'complemento' => 'ESCRITORIO',
                'dependencia' => 'SALA 01',
                'localidade' => 'BR 09-0565',
                'codigo_comum' => '09-0565',
                'nome_original' => '7 - MESA ESCRITORIO',
                '_linha_original' => 32,
            ],
        ];

        $method = new ReflectionMethod($service, 'analisarTodasAsLinhas');

        $resultado = $method->invoke($service, $linhas, ['09-0565' => 0], 0, [], [], []);

        self::assertSame(1, $resultado['resumo']['erros'], 'Somente a linha sem nome deve virar erro');
        self::assertSame(3, $resultado['resumo']['total']);

        self::assertSame(CsvParserService::STATUS_NOVO, $resultado['registros'][0]['status']);

        $registroErro = $resultado['registros'][1];
        self::assertSame('erro', $registroErro['status']);
        self::assertSame(31, $registroErro['linha_csv']);
        self::assertStringContainsString('09-0565 / 0002', $registroErro['erro']);
        self::assertStringContainsString('sem nome', mb_strtolower($registroErro['erro'], 'UTF-8'));

        self::assertSame(CsvParserService::STATUS_NOVO, $resultado['registros'][2]['status']);
    }

    public function testAnalisarLinhaRejeitaProdutoComBemVazio(): void
    {
        $pdo = $this->createMock(PDO::class);
        $service = new CsvParserService($pdo);

        $method = new ReflectionMethod($service, 'analisarLinha');

        try {
            $method->invoke($service, [
                'codigo' => '12-9999 / 0042',
                'tipo_bem_codigo' => '',
                'bem' => '   ',
                'complemento' => '',
                'dependencia' => '',
                'codigo_comum' => '12-9999',
                'localidade' => '',
                'nome_original' => '',
                '_linha_original' => 77,
            ], [], [], [], 0);

            self::fail('Era esperada uma exceção para produto sem nome.');
        } catch (\Exception $e) {
            self::assertStringContainsString('12-9999 / 0042', $e->getMessage());
            self::assertStringContainsString('77', $e->getMessage());
        }
    }

    public function testAnalisarRejeitaCsvSemColunasObrigatoriasComMensagemClara(): void
    {
        $base = sys_get_temp_dir() . '/check-planilha-imobilizado/importacao';
        if (!is_dir($base)) {
            mkdir($base, 0777, true);
        }
        $caminho = $base . '/teste-colunas-ausentes-' . uniqid('', true) . '.csv';
        file_put_contents($caminho, "CODIGO;DESCRICAO\n09-0565;CADEIRA\n09-0566;MESA\n");

        try {
            $pdo = $this->createMock(PDO::class);
            $pdo->method('query')->willThrowException(new \RuntimeException('sem banco em teste'));

            $service = new CsvParserService($pdo);

            $this->expectException(\Exception::class);
            $this->expectExceptionMessage('colunas obrigatórias');

            $service->analisar($caminho, 0);
        } finally {
            @unlink($caminho);
        }
    }
}
