<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\CsvParserService;
use App\Services\ImportacaoService;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Artisan;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;
use Throwable;

/**
 * Contrato de exit code do comando checkplanilha:processar-importacoes.
 *
 * O comando imprimia "❌ <mensagem>" quando o processamento falhava, mas
 * devolvia 0 ao processo. Qualquer runner (cron, CI, script) que confia no
 * exit code marcava a execução como sucesso e não alertava nem retentava,
 * embora a importação tivesse ficado com status 'erro' no banco.
 *
 * Nenhum destes testes toca a lógica de importação: eles fixam apenas o
 * contrato de sinalização de falha do comando.
 */
final class ProcessarImportacoesExitCodeTest extends TestCase
{
    private const IMPORTACAO_ID = 77;

    private const MENSAGEM_ERRO = 'falha de banco simulada';

    /** @var string[] Mensagens das exceções capturadas pelo espião do handler. */
    private array $relatadas = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->relatadas = [];

        // O handler real tenta escrever no log do Laravel; o espião apenas
        // guarda a mensagem para poder afirmar sobre ela.
        $this->mock(
            ExceptionHandler::class,
            function (MockInterface $handler): void {
                $handler->shouldReceive('report')
                    ->andReturnUsing(function (Throwable $e): void {
                        $this->relatadas[] = $e->getMessage();
                    });
            },
        );
    }

    public function test_falha_no_processamento_retorna_codigo_de_erro(): void
    {
        $exitCode = $this->processar($this->importacaoQueFalha());

        self::assertNotSame(0, $exitCode, 'Falha de processamento precisa gerar exit code diferente de zero.');

        $output = Artisan::output();
        $this->assertStringContainsString(self::MENSAGEM_ERRO, $output);
        $this->assertStringContainsString('Total: 0 registros importados.', $output);
    }

    public function test_falha_no_processamento_registra_a_excecao(): void
    {
        $this->processar($this->importacaoQueFalha());

        self::assertSame(
            [self::MENSAGEM_ERRO],
            $this->relatadas,
            'A exceção precisa ser registrada, não apenas impressa.',
        );
    }

    public function test_analise_ausente_retorna_codigo_de_erro(): void
    {
        $exitCode = $this->processar($this->importacaoProntaParaProcessar(), $this->csvParserQueDevolveNull());

        self::assertNotSame(0, $exitCode, 'Importação sem análise não pode ser sinalizada como sucesso.');
        $this->assertStringContainsString('Análise não encontrada', Artisan::output());
    }

    public function test_processamento_sem_falha_mantem_exit_code_de_sucesso(): void
    {
        $exitCode = $this->processar($this->importacaoProntaParaProcessar());

        self::assertSame(0, $exitCode, 'Processamento bem-sucedido não pode passar a falhar.');

        $output = Artisan::output();
        $this->assertStringContainsString('Total: 3 registros importados.', $output);
        self::assertSame([], $this->relatadas);
    }

    public function test_registro_com_erro_nao_e_falha_do_comando(): void
    {
        $importService = $this->importacaoComResultado(sucesso: 3, erro: 2);

        $exitCode = $this->processar($importService);

        self::assertSame(
            0,
            $exitCode,
            'Erros por registro são situação normal da importação (ficam em import_erros); não são falha do comando.',
        );
        $this->assertStringContainsString('3 sucesso, 2 erros', Artisan::output());
    }

    public function test_importacao_com_outro_status_continua_sendo_pulada(): void
    {
        $exitCode = $this->processar($this->importacaoComStatus('processando'));

        self::assertSame(0, $exitCode, 'Importação fora de "aguardando" é pulada, não é falha.');
        $this->assertStringContainsString('pulando', Artisan::output());
    }

    // ──────────────────────────────────────────────
    //  Helpers
    // ──────────────────────────────────────────────

    private function processar(
        ImportacaoService $importService,
        ?CsvParserService $csvParser = null,
    ): int {
        $this->app->instance(ImportacaoService::class, $importService);
        $this->app->instance(CsvParserService::class, $csvParser ?? $this->csvParser());

        return Artisan::call('checkplanilha:processar-importacoes', ['importacao' => self::IMPORTACAO_ID]);
    }

    private function importacaoQueFalha(): ImportacaoService
    {
        $mock = \Mockery::mock(ImportacaoService::class);
        $mock->shouldReceive('buscarProgresso')
            ->andReturn(['id' => self::IMPORTACAO_ID, 'status' => 'aguardando']);
        $mock->shouldReceive('processarComAcoes')
            ->andThrow(new RuntimeException(self::MENSAGEM_ERRO));

        return $mock;
    }

    private function importacaoProntaParaProcessar(): ImportacaoService
    {
        return $this->importacaoComResultado(sucesso: 3, erro: 0);
    }

    private function importacaoComResultado(int $sucesso, int $erro): ImportacaoService
    {
        $mock = \Mockery::mock(ImportacaoService::class);
        $mock->shouldReceive('buscarProgresso')
            ->andReturn(['id' => self::IMPORTACAO_ID, 'status' => 'aguardando']);
        $mock->shouldReceive('processarComAcoes')
            ->andReturn(['sucesso' => $sucesso, 'erro' => $erro]);

        return $mock;
    }

    private function importacaoComStatus(string $status): ImportacaoService
    {
        $mock = \Mockery::mock(ImportacaoService::class);
        $mock->shouldReceive('buscarProgresso')
            ->andReturn(['id' => self::IMPORTACAO_ID, 'status' => $status]);

        return $mock;
    }

    private function csvParser(): CsvParserService
    {
        $mock = \Mockery::mock(CsvParserService::class);
        $mock->shouldReceive('carregarAnalise')
            ->andReturn([
                'registros' => [
                    ['linha_csv' => 30, 'status' => 'novo'],
                    ['linha_csv' => 31, 'status' => 'novo'],
                ],
            ]);

        return $mock;
    }

    private function csvParserQueDevolveNull(): CsvParserService
    {
        $mock = \Mockery::mock(CsvParserService::class);
        $mock->shouldReceive('carregarAnalise')->andReturn(null);

        return $mock;
    }
}
