<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Tests that POST endpoints on SpreadsheetImportController reject requests
 * without a valid CSRF token (returning 419), and that routes listed in the
 * middleware's $except array are correctly exempted.
 *
 * Laravel's PreventRequestForgery::runningUnitTests() bypasses CSRF when
 * APP_ENV=testing. We temporarily set the env to 'production' to verify the
 * 419 rejection path, then restore it for exempt-route tests.
 */
final class SpreadsheetImportControllerCsrfTest extends TestCase
{
    private ?string $savedEnv = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->savedEnv = $this->app->environment();
    }

    protected function tearDown(): void
    {
        if ($this->savedEnv !== null) {
            $this->app->instance('env', $this->savedEnv);
        }
        parent::tearDown();
    }

    /**
     * Temporarily set the app environment so that runningUnitTests() returns
     * false and the CSRF middleware runs its full verification logic.
     */
    private function enableCsrfProtection(): void
    {
        $this->app->instance('env', 'production');
    }

    /**
     * Restore the testing environment (CSRF bypass through runningUnitTests).
     */
    private function restoreCsrfBypass(): void
    {
        if ($this->savedEnv !== null) {
            $this->app->instance('env', $this->savedEnv);
        }
    }

    // ─── CSRF-protected POST endpoints (should return 419) ──────────────

    public function test_store_rejects_without_csrf_token(): void
    {
        $this->enableCsrfProtection();

        $response = $this->post(route('migration.spreadsheets.store'), [
            'administracao_id' => 1,
        ]);

        $response->assertStatus(419);
    }

    public function test_confirm_rejects_without_csrf_token(): void
    {
        $this->enableCsrfProtection();

        $response = $this->post(route('migration.spreadsheets.confirm', ['importacao' => 1]));

        $response->assertStatus(419);
    }

    public function test_resolve_error_rejects_without_csrf_token(): void
    {
        $this->enableCsrfProtection();

        $response = $this->post(route('migration.spreadsheets.errors.resolve', ['erro' => 1]));

        $response->assertStatus(419);
    }

    // ─── CSRF-exempt POST endpoints (should NOT return 419) ─────────────

    public function test_save_preview_actions_is_exempt_from_csrf(): void
    {
        $this->enableCsrfProtection();

        // The $except array contains '/spreadsheets/preview/*/actions'.
        // We test a route whose URL matches this pattern.
        $response = $this->postJson(route('migration.spreadsheets.preview.actions', [
            'importacao' => 999,
        ]), ['acoes' => []]);

        $this->assertNotSame(419, $response->getStatusCode(), 'Exempt route should not return 419');
    }

    public function test_start_processing_is_exempt_from_csrf(): void
    {
        $this->enableCsrfProtection();

        // The $except array contains '/spreadsheets/process/*/start'.
        // We test a route whose URL matches this pattern.
        $response = $this->postJson(route('migration.spreadsheets.start', [
            'importacao' => 999,
        ]));

        $this->assertNotSame(419, $response->getStatusCode(), 'Exempt route should not return 419');
    }

    // ─── CSRF-exempt compat routes (also NOT in $except) should be 419 ──

    public function test_legacy_save_preview_actions_not_exempt(): void
    {
        $this->enableCsrfProtection();

        // The $except pattern '/spreadsheets/preview/*/actions' does NOT
        // match the compat route '/spreadsheets/preview/save-actions'.
        $response = $this->postJson(route('migration.compat.spreadsheets.preview.save-actions'), [
            'importacao_id' => 1,
        ]);

        // Legacy compat route has no {importacao} segment, so the
        // $except wildcard pattern does NOT match → CSRF blocks with 419.
        $response->assertStatus(419);
    }

    public function test_legacy_confirm_not_exempt(): void
    {
        $this->enableCsrfProtection();

        // The $except has '/spreadsheets/preview/*/actions' and
        // '/spreadsheets/preview/*/bulk-action' but NOT 'confirm'.
        // The compat route '/spreadsheets/confirm' is NOT in $except.
        $response = $this->postJson(route('migration.compat.spreadsheets.confirm'), [
            'importacao_id' => 1,
        ]);

        $response->assertStatus(419);
    }

    // ─── Sanity check: with default test bypass, CSRF does not block ────

    public function test_store_not_blocked_when_csrf_bypass_active(): void
    {
        // Default APP_ENV=testing keeps runningUnitTests() active, so CSRF
        // is bypassed. The request may fail at controller level but must
        // NOT be blocked by CSRF.
        $this->restoreCsrfBypass();

        $response = $this->post(route('migration.spreadsheets.store'), [
            'administracao_id' => 1,
        ]);

        $this->assertNotSame(419, $response->getStatusCode(), 'Default test bypass should not return 419');
    }
}
