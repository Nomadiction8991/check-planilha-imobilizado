<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Tests that all protected SpreadsheetImportController routes reject
 * unauthenticated requests.
 *
 * The controller is behind the 'legacy.auth' middleware (RequireLegacySession)
 * which checks 'usuario_id' in the session. In the testing environment the
 * middleware is bypassed by default; setting '_enforce_legacy_auth' to true
 * forces it to run, allowing us to verify the redirect behaviour.
 *
 * Routes under test:
 *   - upload      (store)              POST /spreadsheets/import
 *   - preview                           GET /spreadsheets/preview/{importacao}
 *   - savePreviewActions                POST /spreadsheets/preview/{importacao}/actions
 *   - startProcessing                   POST /spreadsheets/process/{importacao}/start
 *
 * The last two routes are exempt from CSRF protection (see
 * HybridPreventRequestForgery::$except).
 */
final class SpreadsheetImportControllerAuthTest extends TestCase
{
    // ─── Upload (store) ───────────────────────────────────────────────

    public function test_upload_redirects_unauthenticated_user_to_login(): void
    {
        $response = $this->withSession(['_enforce_legacy_auth' => true])
            ->post(route('migration.spreadsheets.store'), [
                'administracao_id' => 1,
            ]);

        $response->assertRedirect(route('migration.login'));
    }

    // ─── Preview ──────────────────────────────────────────────────────

    public function test_preview_redirects_unauthenticated_user_to_login(): void
    {
        $response = $this->withSession(['_enforce_legacy_auth' => true])
            ->get(route('migration.spreadsheets.preview', ['importacao' => 1]));

        $response->assertRedirect(route('migration.login'));
    }

    // ─── Save preview actions ─────────────────────────────────────────

    public function test_save_preview_actions_redirects_unauthenticated_user_to_login(): void
    {
        $response = $this->withSession(['_enforce_legacy_auth' => true])
            ->postJson(route('migration.spreadsheets.preview.actions', ['importacao' => 1]), [
                'acoes' => [],
            ]);

        $response->assertRedirect(route('migration.login'));
    }

    // ─── Start processing ─────────────────────────────────────────────

    public function test_start_processing_redirects_unauthenticated_user_to_login(): void
    {
        $response = $this->withSession(['_enforce_legacy_auth' => true])
            ->post(route('migration.spreadsheets.start', ['importacao' => 1]));

        $response->assertRedirect(route('migration.login'));
    }
}
