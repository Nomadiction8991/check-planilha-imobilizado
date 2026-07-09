<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

#[TestDox('Proteção contra backups rastreados de .env e segredos em arquivos de ambiente')]
final class TrackedEnvBackupFilesTest extends TestCase
{
    public function test_tracked_env_backup_files_are_not_committed(): void
    {
        $trackedFiles = $this->trackedFiles();
        $backupFiles = array_values(array_filter(
            $trackedFiles,
            fn (string $path): bool => $this->isBackupLikeEnvFile($path),
        ));

        $this->assertSame(
            [],
            $backupFiles,
            'Não versione backups de .env no git: '.implode(', ', $backupFiles),
        );
    }

    public function test_tracked_env_backup_files_do_not_contain_secrets(): void
    {
        $backupFiles = array_values(array_filter(
            $this->trackedFiles(),
            fn (string $path): bool => $this->isBackupLikeEnvFile($path),
        ));

        $violations = [];

        foreach ($backupFiles as $file) {
            $content = file_get_contents(base_path($file));

            if ($content === false) {
                continue;
            }

            foreach ($this->secretPatterns() as $label => $pattern) {
                if (preg_match($pattern, $content) === 1) {
                    $violations[] = sprintf('%s [%s]', $file, $label);
                }
            }
        }

        $this->assertSame(
            [],
            $violations,
            'Backups de .env não podem versionar segredos como APP_KEY ou DB_PASSWORD: '.implode(', ', $violations),
        );
    }

    public function test_tracked_env_templates_use_safe_placeholders(): void
    {
        $expectedValues = [
            '.env.example' => [
                'APP_KEY' => '',
                'DB_PASSWORD' => 'change-me',
                'DB_ROOT_PASSWORD' => 'change-me',
            ],
            '.env.testing' => [
                'APP_KEY' => '',
                'DB_PASSWORD' => '',
                'DB_ROOT_PASSWORD' => '',
            ],
        ];

        foreach ($expectedValues as $file => $values) {
            $content = file_get_contents(base_path($file));

            $this->assertNotFalse($content, sprintf('Não foi possível ler %s', $file));

            $env = $this->parseEnvContent($content);

            foreach ($values as $key => $expectedValue) {
                $this->assertArrayHasKey(
                    $key,
                    $env,
                    sprintf('%s deve declarar %s', $file, $key),
                );

                $this->assertSame(
                    $expectedValue,
                    $env[$key],
                    sprintf('%s deve usar um placeholder seguro em %s', $file, $key),
                );
            }
        }
    }

    private function trackedFiles(): array
    {
        $output = trim((string) shell_exec(sprintf('git -C %s ls-files', escapeshellarg(base_path()))));

        if ($output === '') {
            $this->markTestSkipped('Não foi possível listar arquivos rastreados pelo git.');
        }

        return preg_split('/\R/', $output) ?: [];
    }

    private function isBackupLikeEnvFile(string $path): bool
    {
        return preg_match(
            '/(?:^|\/)(?:\.env|[^\/]+\.env)(?:\.[^\/]*)*(?:bak|backup|old|save|copy|orig|snapshot)[^\/]*$/i',
            $path,
        ) === 1 || preg_match('/(?:^|\/)\.env~$/i', $path) === 1 || $path === '.env';
    }

    /**
     * @return array<string, string>
     */
    private function secretPatterns(): array
    {
        return [
            'APP_KEY' => '/^APP_KEY\s*=\s*(?!$|""|\'\'|null\b)/mi',
            'DB_PASSWORD' => '/^DB_PASSWORD\s*=\s*(?!$|""|\'\'|null\b)/mi',
            'DB_ROOT_PASSWORD' => '/^DB_ROOT_PASSWORD\s*=\s*(?!$|""|\'\'|null\b)/mi',
            'DB_PASS' => '/^DB_PASS\s*=\s*(?!$|""|\'\'|null\b)/mi',
            'MAIL_PASSWORD' => '/^MAIL_PASSWORD\s*=\s*(?!$|""|\'\'|null\b)/mi',
            'AWS_SECRET_ACCESS_KEY' => '/^AWS_SECRET_ACCESS_KEY\s*=\s*(?!$|""|\'\'|null\b)/mi',
            'LOG_SLACK_WEBHOOK_URL' => '/^LOG_SLACK_WEBHOOK_URL\s*=\s*(?!$|""|\'\'|null\b)/mi',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function parseEnvContent(string $content): array
    {
        $values = [];

        foreach (preg_split('/\R/', $content) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
            $values[trim($key)] = trim($value, "\"'");
        }

        return $values;
    }
}
