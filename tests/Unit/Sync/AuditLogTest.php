<?php

declare(strict_types=1);

namespace Tests\Unit\Sync;

use App\Sync\AuditLog;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Histórico de auditoria (uma linha JSON por evento): a mescla só acrescenta o que falta.
 */
class AuditLogTest extends TestCase
{
    private string $arquivo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->arquivo = sys_get_temp_dir() . '/cp-auditoria-' . uniqid() . '/audit-log.jsonl';
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg(dirname($this->arquivo)));

        parent::tearDown();
    }

    public function test_acrescenta_so_as_linhas_que_ainda_nao_existem(): void
    {
        $log = new AuditLog($this->arquivo);
        $a = '{"acao":"login","usuario":1}';
        $b = '{"acao":"importou","usuario":2}';
        $log->acrescentar([$this->entrada($a)]);

        $recebidas = $log->acrescentar([$this->entrada($a), $this->entrada($b)]);

        $this->assertSame(1, $recebidas);
        $this->assertSame([$a, $b], array_map(fn (array $e): string => $e['linha'], $log->linhas()));
    }

    public function test_recusa_o_lote_inteiro_se_uma_linha_nao_confere_com_o_sha256(): void
    {
        $log = new AuditLog($this->arquivo);
        $log->acrescentar([$this->entrada('{"acao":"login"}')]);

        try {
            $log->acrescentar([
                $this->entrada('{"acao":"novo"}'),
                ['sha256' => str_repeat('0', 64), 'linha' => '{"acao":"adulterado"}'],
            ]);
            $this->fail('Linha corrompida precisava derrubar o lote.');
        } catch (InvalidArgumentException) {
            $this->assertCount(1, $log->linhas(), 'Nenhuma linha do lote pode entrar.');
        }
    }

    public function test_recusa_linha_que_nao_e_json(): void
    {
        $log = new AuditLog($this->arquivo);

        $this->expectException(InvalidArgumentException::class);

        $log->acrescentar([$this->entrada('isto nao e json')]);
    }

    /**
     * @return array{sha256: string, linha: string}
     */
    private function entrada(string $linha): array
    {
        return ['sha256' => hash('sha256', $linha), 'linha' => $linha];
    }
}
