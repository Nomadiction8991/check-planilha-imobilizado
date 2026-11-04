<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SystemMaintenanceCommandTest extends TestCase
{
    private ?string $healthDirectory = null;
    private string $originalBasePath;
    private string $originalStoragePath;

    private function isolateHealthDirectories(): void
    {
        $this->originalBasePath = base_path();
        $this->originalStoragePath = storage_path();
        $this->healthDirectory = sys_get_temp_dir() . '/health-test-' . bin2hex(random_bytes(8));
        foreach (['storage/app', 'storage/framework', 'storage/logs', 'bootstrap/cache'] as $directory) {
            mkdir($this->healthDirectory . '/' . $directory, 0700, true);
        }
        $this->app->setBasePath($this->healthDirectory);
        $this->app->useStoragePath($this->healthDirectory . '/storage');
    }

    protected function tearDown(): void
    {
        if ($this->healthDirectory !== null) {
            $this->app->setBasePath($this->originalBasePath);
            $this->app->useStoragePath($this->originalStoragePath);
            foreach (['storage/app', 'storage/framework', 'storage/logs', 'storage', 'bootstrap/cache', 'bootstrap'] as $directory) {
                rmdir($this->healthDirectory . '/' . $directory);
            }
            rmdir($this->healthDirectory);
        }
        parent::tearDown();
    }

    public function test_system_health_check_command_runs_successfully(): void
    {
        $this->isolateHealthDirectories();
        $exitCode = Artisan::call('system:health-check');

        $this->assertSame(0, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Diagnóstico de Integridade do Sistema', $output);
    }

    public function test_system_health_check_json_output(): void
    {
        $this->isolateHealthDirectories();
        $exitCode = Artisan::call('system:health-check', ['--json' => true]);

        $this->assertSame(0, $exitCode);
        $output = Artisan::output();
        $data = json_decode($output, true);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('checks', $data);
    }

    public function test_system_storage_cleanup_dry_run(): void
    {
        $exitCode = Artisan::call('system:storage-cleanup', ['--dry-run' => true]);

        $this->assertSame(0, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Dry-run', $output);
    }

    public function test_system_storage_cleanup_execution(): void
    {
        $tmpDir = storage_path('tmp');
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $oldFile = $tmpDir . '/test_old_temp_file.tmp';
        file_put_contents($oldFile, 'sample content');
        touch($oldFile, time() - (10 * 86400));

        $exitCode = Artisan::call('system:storage-cleanup', ['--days' => 5]);

        $this->assertSame(0, $exitCode);
        $this->assertFileDoesNotExist($oldFile);
    }
}
