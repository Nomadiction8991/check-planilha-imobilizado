<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\LegacyAuthSessionServiceInterface;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

/**
 * Tests for LegacyMigrationDashboardController.
 *
 * The controller is a single-action invokable that checks legacy_permissions
 * from the session and redirects to the first matching migration section.
 * It does NOT render a view – it always performs a redirect.
 */
final class LegacyMigrationDashboardControllerTest extends TestCase
{
    public function test_unauthenticated_user_with_no_permissions_is_redirected_to_login(): void
    {
        $this->mock(LegacyAuthSessionServiceInterface::class, function ($mock): void {
            $mock->shouldReceive('isAuthenticated')->andReturn(false);
        });

        $response = $this->get(route('migration.dashboard'));

        $response->assertRedirect(route('migration.login'));
    }

    public function test_user_with_products_view_permission_is_redirected_to_products_index(): void
    {
        $this->withSession([
            'legacy_permissions' => [
                'products.view' => true,
            ],
        ]);

        $response = $this->get(route('migration.dashboard'));

        $response->assertRedirect(route('migration.products.index'));
    }

    public function test_user_with_churches_view_permission_is_redirected_to_churches_index(): void
    {
        $this->withSession([
            'legacy_permissions' => [
                'churches.view' => true,
            ],
        ]);

        $response = $this->get(route('migration.dashboard'));

        $response->assertRedirect(route('migration.churches.index'));
    }

    public function test_user_with_users_view_permission_is_redirected_to_users_index(): void
    {
        $this->withSession([
            'legacy_permissions' => [
                'users.view' => true,
            ],
        ]);

        $response = $this->get(route('migration.dashboard'));

        $response->assertRedirect(route('migration.users.index'));
    }

    public function test_user_with_administrations_view_permission_is_redirected_to_administrations_index(): void
    {
        $this->withSession([
            'legacy_permissions' => [
                'administrations.view' => true,
            ],
        ]);

        $response = $this->get(route('migration.dashboard'));

        $response->assertRedirect(route('migration.administrations.index'));
    }

    public function test_user_with_spreadsheets_import_permission_is_redirected_to_spreadsheets_create(): void
    {
        $this->withSession([
            'legacy_permissions' => [
                'spreadsheets.import' => true,
            ],
        ]);

        $response = $this->get(route('migration.dashboard'));

        $response->assertRedirect(route('migration.spreadsheets.create'));
    }

    public function test_user_with_reports_view_permission_is_redirected_to_reports_index(): void
    {
        $this->withSession([
            'legacy_permissions' => [
                'reports.view' => true,
            ],
        ]);

        $response = $this->get(route('migration.dashboard'));

        $response->assertRedirect(route('migration.reports.index'));
    }

    public function test_first_permission_in_priority_order_wins(): void
    {
        // 'products.view' is checked first in the controller, so it wins
        // even when the user also has other permissions.
        $this->withSession([
            'legacy_permissions' => [
                'reports.view' => true,
                'users.view' => true,
                'products.view' => true,
            ],
        ]);

        $response = $this->get(route('migration.dashboard'));

        $response->assertRedirect(route('migration.products.index'));
    }

    public function test_user_without_any_permission_is_redirected_to_login(): void
    {
        $this->withSession([
            'legacy_permissions' => [],
        ]);

        $response = $this->get(route('migration.dashboard'));

        $response->assertRedirect(route('migration.login'));
    }
}
