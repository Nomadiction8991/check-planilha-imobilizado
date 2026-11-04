<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use Tests\Fixtures\IsolatedLerEnv\LerEnv;
use Tests\TestCase;

final class LerEnvTest extends TestCase
{
    private static string $fixtureDirectory;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$fixtureDirectory = sys_get_temp_dir() . '/ler-env-test-' . bin2hex(random_bytes(8));
        mkdir(self::$fixtureDirectory . '/app/Core', 0700, true);
        $source = file_get_contents(__DIR__ . '/../../../app/Core/LerEnv.php');
        file_put_contents(
            self::$fixtureDirectory . '/app/Core/LerEnv.php',
            str_replace('namespace App\\Core;', 'namespace Tests\\Fixtures\\IsolatedLerEnv;', $source),
        );
        file_put_contents(self::$fixtureDirectory . '/.env', <<<'ENV'
APP_NAME=check-planilha
APP_ENV=testing
APP_TIMEZONE=UTC
DB_CONNECTION=sqlite
CACHE_STORE=array
SESSION_DRIVER=array
QUEUE_CONNECTION=sync
LOG_CHANNEL=single
MAIL_MAILER=log
AWS_ACCESS_KEY_ID=
MAIL_FROM_ADDRESS="hello@example.com"
# APP_MAINTENANCE_STORE=database
ENV);
        require self::$fixtureDirectory . '/app/Core/LerEnv.php';
    }

    public static function tearDownAfterClass(): void
    {
        unlink(self::$fixtureDirectory . '/.env');
        unlink(self::$fixtureDirectory . '/app/Core/LerEnv.php');
        rmdir(self::$fixtureDirectory . '/app/Core');
        rmdir(self::$fixtureDirectory . '/app');
        rmdir(self::$fixtureDirectory);
        parent::tearDownAfterClass();
    }

    protected function setUp(): void
    {
        parent::setUp();
        self::resetLerEnvCache();
    }

    // ---------------------------------------------------------------
    //  Helpers
    // ---------------------------------------------------------------

    /**
     * Reset the static cache between tests so each scenario starts fresh.
     */
    private static function resetLerEnvCache(): void
    {
        $prop = new \ReflectionProperty(LerEnv::class, 'variaveis');
        $prop->setAccessible(true);
        $prop->setValue(null);
    }

    /**
     * Directly inject an internal state for edge-case scenarios.
     */
    private static function setLerEnvCache(?array $state): void
    {
        $prop = new \ReflectionProperty(LerEnv::class, 'variaveis');
        $prop->setAccessible(true);
        $prop->setValue($state);
    }

    // ---------------------------------------------------------------
    //  Tests
    // ---------------------------------------------------------------

    /** @test */
    public function testSimpleKeyValueParsing(): void
    {
        $this->assertSame('check-planilha', LerEnv::obter('APP_NAME'));
    }

    /** @test */
    public function testLinesStartingWithHashAreIgnored(): void
    {
        // APP_MAINTENANCE_STORE appears only in a commented line in .env
        $this->assertNull(LerEnv::obter('APP_MAINTENANCE_STORE'));
    }

    /** @test */
    public function testKeyWithEmptyValueReturnsEmptyString(): void
    {
        // AWS_ACCESS_KEY_ID has no value after the '='
        $this->assertSame('', LerEnv::obter('AWS_ACCESS_KEY_ID'));
    }

    /** @test */
    public function testDoubleQuotedValuesHaveQuotesTrimmed(): void
    {
        // MAIL_FROM_ADDRESS="hello@example.com" → quotes must be stripped
        $this->assertSame('hello@example.com', LerEnv::obter('MAIL_FROM_ADDRESS'));
    }

    /** @test */
    public function testNonExistentKeyReturnsDefault(): void
    {
        $this->assertNull(LerEnv::obter('THIS_KEY_DOES_NOT_EXIST'));
        $this->assertSame('fallback', LerEnv::obter('THIS_KEY_DOES_NOT_EXIST', 'fallback'));
        $this->assertSame(42, LerEnv::obter('THIS_KEY_DOES_NOT_EXIST', 42));
    }

    /** @test */
    public function testStaticCacheDoesNotReloadOnSubsequentCalls(): void
    {
        // First call loads from file and populates the cache
        $first = LerEnv::obter('APP_NAME');
        $this->assertSame('check-planilha', $first);

        // Second call reads from the same cached array
        $second = LerEnv::obter('APP_NAME');
        $this->assertSame($first, $second);
    }

    /** @test */
    public function testCacheCanBeResetAndReloads(): void
    {
        // Load once
        $this->assertSame('check-planilha', LerEnv::obter('APP_NAME'));

        // Reset the static cache
        self::resetLerEnvCache();

        // After reset it should reload and still work
        $this->assertSame('check-planilha', LerEnv::obter('APP_NAME'));
    }

    /** @test */
    public function testMissingDotEnvReturnsDefaults(): void
    {
        // Simulate the state after carregar() runs but no .env file exists:
        // $variaveis is set to [] and obter() always falls back to the default.
        self::setLerEnvCache([]);

        $this->assertNull(LerEnv::obter('APP_NAME'));
        $this->assertSame('padrao', LerEnv::obter('APP_NAME', 'padrao'));
        $this->assertNull(LerEnv::obter('DB_HOST'));
        $this->assertSame(0, LerEnv::obter('DB_HOST', 0));
    }

    /** @test */
    public function testAllWellKnownKeysAreAccessible(): void
    {
        $keys = [
            'APP_NAME',
            'APP_ENV',
            'APP_TIMEZONE',
            'DB_CONNECTION',
            'CACHE_STORE',
            'SESSION_DRIVER',
            'QUEUE_CONNECTION',
            'LOG_CHANNEL',
            'MAIL_MAILER',
        ];

        foreach ($keys as $key) {
            $value = LerEnv::obter($key);
            $this->assertNotNull($value, "Key {$key} returned null (not found in .env)");
            $this->assertNotEmpty($value, "Key {$key} returned empty string");
        }
    }
}
