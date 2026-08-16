<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\AnalysisPersistenceService;
use Tests\TestCase;

final class AnalysisPersistenceServiceTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . '/checkplanilha_test_' . uniqid('aps_', true);
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->rmdirRecursive($this->tempDir);

        parent::tearDown();
    }

    private function rmdirRecursive(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;

            if (is_dir($path)) {
                $this->rmdirRecursive($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }

    // ── salvarAnalise ──────────────────────────────────────────

    public function testSalvarAnaliseCreatesJsonFile(): void
    {
        $service = new AnalysisPersistenceService($this->tempDir);

        $analise = ['total' => 42, 'valido' => true, 'mensagem' => 'Sucesso'];
        $caminho = $service->salvarAnalise(1, $analise);

        $expectedPath = $this->tempDir . '/analise_1.json';
        $this->assertSame($expectedPath, $caminho);
        $this->assertFileExists($expectedPath);

        $saved = json_decode((string) file_get_contents($expectedPath), true);
        $this->assertSame($analise, $saved);
    }

    public function testSalvarAnalisePersistsUnicodeContent(): void
    {
        $service = new AnalysisPersistenceService($this->tempDir);

        $analise = ['nome' => 'São João', 'cidade' => 'São Paulo', 'acentuação' => 'àéíóú'];
        $service->salvarAnalise(2, $analise);

        $raw = (string) file_get_contents($this->tempDir . '/analise_2.json');
        $decoded = json_decode($raw, true);

        $this->assertSame($analise, $decoded);
        // Verifica que não escapou Unicode (JSON_UNESCAPED_UNICODE)
        $this->assertStringContainsString('ão', $raw);
    }

    public function testSalvarAnaliseOverwritesExistingFile(): void
    {
        $service = new AnalysisPersistenceService($this->tempDir);

        $service->salvarAnalise(3, ['versao' => 1]);
        $service->salvarAnalise(3, ['versao' => 2]);

        $loaded = $service->carregarAnalise(3);
        $this->assertSame(['versao' => 2], $loaded);
    }

    public function testSalvarAnaliseThrowsExceptionWhenDirectoryNotWritable(): void
    {
        $readonlyDir = $this->readonlySubdir();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('permissão de escrita');

        new AnalysisPersistenceService($readonlyDir);
    }

    // ── carregarAnalise ────────────────────────────────────────

    public function testCarregarAnaliseReturnsSavedData(): void
    {
        $service = new AnalysisPersistenceService($this->tempDir);

        $analise = ['importacao_id' => 7, 'itens' => [1, 2, 3], 'status' => 'concluido'];
        $service->salvarAnalise(7, $analise);

        $result = $service->carregarAnalise(7);

        $this->assertSame($analise, $result);
    }

    public function testCarregarAnaliseReturnsNullWhenFileNotFound(): void
    {
        $service = new AnalysisPersistenceService($this->tempDir);

        $this->assertNull($service->carregarAnalise(999));
    }

    public function testCarregarAnaliseReturnsNullForInvalidJson(): void
    {
        $service = new AnalysisPersistenceService($this->tempDir);

        file_put_contents($this->tempDir . '/analise_5.json', '{invalid json}');

        $this->assertNull($service->carregarAnalise(5));
    }

    public function testCarregarAnaliseReturnsNullWhenFileNotReadable(): void
    {
        $service = new AnalysisPersistenceService($this->tempDir);

        file_put_contents($this->tempDir . '/analise_6.json', '{"chave": "valor"}');
        chmod($this->tempDir . '/analise_6.json', 0000);

        $this->assertNull($service->carregarAnalise(6));

        // Restore so tearDown can clean up
        chmod($this->tempDir . '/analise_6.json', 0644);
    }

    public function testCarregarAnaliseReturnsNullOnEmptyFile(): void
    {
        $service = new AnalysisPersistenceService($this->tempDir);

        file_put_contents($this->tempDir . '/analise_8.json', '');

        $this->assertNull($service->carregarAnalise(8));
    }

    // ── limparAnalise ──────────────────────────────────────────

    public function testLimparAnaliseRemovesExistingFile(): void
    {
        $service = new AnalysisPersistenceService($this->tempDir);

        $service->salvarAnalise(10, ['dado' => 'x']);
        $this->assertFileExists($this->tempDir . '/analise_10.json');

        $result = $service->limparAnalise(10);

        $this->assertTrue($result);
        $this->assertFileDoesNotExist($this->tempDir . '/analise_10.json');
    }

    public function testLimparAnaliseReturnsTrueForNonExistentFile(): void
    {
        $service = new AnalysisPersistenceService($this->tempDir);

        $this->assertTrue($service->limparAnalise(999));
    }

    // ── existeAnalise ──────────────────────────────────────────

    public function testExisteAnaliseReturnsTrueWhenFileExists(): void
    {
        $service = new AnalysisPersistenceService($this->tempDir);

        $service->salvarAnalise(20, ['ok' => true]);

        $this->assertTrue($service->existeAnalise(20));
    }

    public function testExisteAnaliseReturnsFalseWhenFileDoesNotExist(): void
    {
        $service = new AnalysisPersistenceService($this->tempDir);

        $this->assertFalse($service->existeAnalise(999));
    }

    public function testExisteAnaliseReturnsFalseAfterLimparAnalise(): void
    {
        $service = new AnalysisPersistenceService($this->tempDir);

        $service->salvarAnalise(30, ['temp' => true]);
        $this->assertTrue($service->existeAnalise(30));

        $service->limparAnalise(30);

        $this->assertFalse($service->existeAnalise(30));
    }

    // ── Constructor / Directory creation ───────────────────────

    public function testConstructorCreatesStorageDirectoryWhenMissing(): void
    {
        $newDir = $this->tempDir . '/nova_pasta';

        $service = new AnalysisPersistenceService($newDir);

        $this->assertDirectoryExists($newDir);
        // Should be able to write
        $service->salvarAnalise(50, ['criado' => true]);
        $this->assertFileExists($newDir . '/analise_50.json');
    }

    public function testConstructorThrowsExceptionWhenParentDirDoesNotExist(): void
    {
        $nonexistentParent = '/tmp/__checkplanilha_nonexistent_parent__/sub';
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('indisponível');

        new AnalysisPersistenceService($nonexistentParent);
    }

    public function testConstructorThrowsExceptionWhenParentNotWritable(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('indisponível');

        new AnalysisPersistenceService('/root/checkplanilha_test');
    }

    public function testConstructorUsesDefaultStorageDirWhenNull(): void
    {
        $service = new AnalysisPersistenceService();

        // Use reflection to check the default path
        $reflection = new \ReflectionProperty($service, 'storageDir');
        $reflection->setAccessible(true);

        $defaultDir = $reflection->getValue($service);

        $this->assertStringEndsWith('/storage/tmp', $defaultDir);
        $this->assertDirectoryExists($defaultDir);
    }

    // ── Helper: readonly subdir ────────────────────────────────

    /**
     * Cria um subdiretório sem permissão de escrita para testar exceções.
     */
    private function readonlySubdir(): string
    {
        $dir = $this->tempDir . '/readonly';
        mkdir($dir, 0555, true);
        return $dir;
    }
}
