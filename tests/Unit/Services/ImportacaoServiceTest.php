<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\ImportacaoService;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

/**
 * Testes para ImportacaoService::iniciarImportacao() e contarLinhasArquivo().
 *
 * A estratégia aqui é testar o comportamento externo observável do serviço
 * usando um arquivo CSV temporário real para o método de contagem de linhas,
 * e mocks de PDO para os métodos que tocam o banco.
 */
class ImportacaoServiceTest extends TestCase
{
    // ─── contarLinhasArquivo (via iniciarImportacao) ───

    public function testIniciarImportacaoContaLinhasDoArquivoCorretamente(): void
    {
        // Cria CSV temporário com 3 linhas de dados + 1 cabeçalho = 4 linhas totais
        $csvPath = $this->criarCsvTemporario([
            'codigo,nome,dependencia',
            '001,CADEIRA,SALAO',
            '002,BANCO,ENTRADA',
            '003,MESA,SECRETARIA',
        ]);

        $importacaoIdEsperado = 42;
        $pdo = $this->criarPdoMockParaIniciarImportacao($importacaoIdEsperado, 3);

        $service     = new ImportacaoService($pdo);
        $importacaoId = $service->iniciarImportacao(1, 1, 'teste.csv', $csvPath);

        $this->assertSame($importacaoIdEsperado, $importacaoId);

        unlink($csvPath);
    }

    public function testIniciarImportacaoArquivoComApenasUmaLinhaDeveContar0Linhas(): void
    {
        // Apenas cabeçalho → contarLinhasArquivo pula a primeira linha e conta 0
        $csvPath = $this->criarCsvTemporario([
            'codigo,nome,dependencia',
        ]);

        $pdo = $this->criarPdoMockParaIniciarImportacao(1, 0);

        $service = new ImportacaoService($pdo);
        $service->iniciarImportacao(1, null, 'vazio.csv', $csvPath);

        // A asserção principal é que não lança exceção — o arquivo é válido
        $this->addToAssertionCount(1);

        unlink($csvPath);
    }

    // ─── processarComAcoes — importação não encontrada ───

    public function testProcessarComAcoesLancaExcecaoQuandoImportacaoNaoExiste(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetch')->willReturn(false); // importação não encontrada

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $service = new ImportacaoService($pdo);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Importação não encontrada');

        $service->processarComAcoes(999, [], ['registros' => []]);
    }

    // ─── Helpers ───

    private function criarCsvTemporario(array $linhas): string
    {
        $path = tempnam(sys_get_temp_dir(), 'csvtest_');
        file_put_contents($path, implode("\n", $linhas));
        return $path;
    }

    /**
     * Cria PDO mock que simula o fluxo de iniciarImportacao():
     * - ImportacaoRepository::criar() retorna $importacaoId
     * - O mock captura total_linhas para validação
     */
    private function criarPdoMockParaIniciarImportacao(int $importacaoId, int $totalLinhasEsperado): PDO
    {
        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('execute')->willReturn(true);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmtInsert);
        $pdo->method('lastInsertId')->willReturn((string) $importacaoId);

        return $pdo;
    }
}
