<?php

declare(strict_types=1);

namespace Tests\Unit\Sync;

use App\Sync\AuditLog;
use App\Sync\AuditLogSync;
use App\Sync\SpreadsheetStore;
use App\Sync\SpreadsheetSync;
use PHPUnit\Framework\TestCase;
use Tests\Support\LocalAuditTransport;
use Tests\Support\LocalFileTransport;

/**
 * Sincronização de planilhas e auditoria nos dois sentidos, com o "servidor" em outra pasta.
 */
class FileSyncTest extends TestCase
{
    private string $raiz;

    protected function setUp(): void
    {
        parent::setUp();

        $this->raiz = sys_get_temp_dir() . '/cp-sync-arquivos-' . uniqid();
        mkdir($this->raiz);
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->raiz));

        parent::tearDown();
    }

    public function test_planilhas_passam_nos_dois_sentidos(): void
    {
        $notebook = new SpreadsheetStore($this->raiz . '/notebook');
        $servidor = new SpreadsheetStore($this->raiz . '/servidor');
        $notebook->gravar('nb.csv', 'do notebook', hash('sha256', 'do notebook'));
        $servidor->gravar('sv.csv', 'do servidor', hash('sha256', 'do servidor'));

        $resumo = (new SpreadsheetSync())->executar(new LocalFileTransport($servidor), $notebook);

        $this->assertSame(['enviadas' => 1, 'recebidas' => 1, 'iguais' => 0, 'conflitos' => []], $resumo);
        $this->assertSame(['nb.csv', 'sv.csv'], array_column($servidor->listar(), 'nome'));
        $this->assertSame(['nb.csv', 'sv.csv'], array_column($notebook->listar(), 'nome'));
    }

    public function test_arquivo_igual_nao_e_transferido_de_novo(): void
    {
        $notebook = new SpreadsheetStore($this->raiz . '/notebook');
        $servidor = new SpreadsheetStore($this->raiz . '/servidor');
        $notebook->gravar('igual.csv', 'mesmo', hash('sha256', 'mesmo'));
        $servidor->gravar('igual.csv', 'mesmo', hash('sha256', 'mesmo'));
        $transporte = new LocalFileTransport($servidor);

        $resumo = (new SpreadsheetSync())->executar($transporte, $notebook);

        $this->assertSame(1, $resumo['iguais']);
        $this->assertSame(0, $transporte->envios, 'Arquivo igual não pode ser enviado de novo.');
        $this->assertSame(0, $transporte->downloads, 'Arquivo igual não pode ser baixado de novo.');
    }

    public function test_mesmo_nome_com_conteudo_diferente_e_reportado_sem_sobrescrever(): void
    {
        $notebook = new SpreadsheetStore($this->raiz . '/notebook');
        $servidor = new SpreadsheetStore($this->raiz . '/servidor');
        $notebook->gravar('c.csv', 'versao do notebook', hash('sha256', 'versao do notebook'));
        $servidor->gravar('c.csv', 'versao do servidor', hash('sha256', 'versao do servidor'));

        $resumo = (new SpreadsheetSync())->executar(new LocalFileTransport($servidor), $notebook);

        $this->assertSame(['c.csv'], $resumo['conflitos']);
        $this->assertSame('versao do notebook', file_get_contents($this->raiz . '/notebook/c.csv'));
        $this->assertSame('versao do servidor', file_get_contents($this->raiz . '/servidor/c.csv'));
    }

    public function test_auditoria_fica_igual_nos_dois_lados_e_a_segunda_rodada_nao_transfere_nada(): void
    {
        $notebook = new AuditLog($this->raiz . '/notebook/audit.jsonl');
        $servidor = new AuditLog($this->raiz . '/servidor/audit.jsonl');
        $notebook->acrescentar([['sha256' => hash('sha256', '{"n":1}'), 'linha' => '{"n":1}']]);
        $servidor->acrescentar([['sha256' => hash('sha256', '{"s":1}'), 'linha' => '{"s":1}']]);
        $transporte = new LocalAuditTransport($servidor);

        $primeira = (new AuditLogSync())->executar($transporte, $notebook);
        $segunda = (new AuditLogSync())->executar($transporte, $notebook);

        $this->assertSame(['enviadas' => 1, 'recebidas' => 1], $primeira);
        $this->assertSame(['enviadas' => 0, 'recebidas' => 0], $segunda);
        $this->assertSame(['{"n":1}', '{"s":1}'], array_map(fn (array $e): string => $e['linha'], $notebook->linhas()));
        $this->assertSame(['{"s":1}', '{"n":1}'], array_map(fn (array $e): string => $e['linha'], $servidor->linhas()));
    }
}
