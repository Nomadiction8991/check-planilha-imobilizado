<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\LerEnv;
use Tests\TestCase;

final class LerEnvTest extends TestCase
{
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
