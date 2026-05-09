<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\LegacyInventoryServiceInterface;
use App\Contracts\LegacyAuthSessionServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;

class LegacyMigrationDashboardController extends Controller
{
    public function __construct(
        private readonly LegacyAuthSessionServiceInterface $auth,
    ) {
    }

    public function __invoke(): RedirectResponse
    {
        $permissions = (array) Session::get('legacy_permissions', []);

        if (!empty($permissions['products.view'])) {
            return redirect()->route('migration.products.index');
        }

        if (!empty($permissions['churches.view'])) {
            return redirect()->route('migration.churches.index');
        }

        if (!empty($permissions['users.view'])) {
            return redirect()->route('migration.users.index');
        }

        if (!empty($permissions['administrations.view'])) {
            return redirect()->route('migration.administrations.index');
        }

        if (!empty($permissions['spreadsheets.import'])) {
            return redirect()->route('migration.spreadsheets.create');
        }

        if (!empty($permissions['reports.view'])) {
            return redirect()->route('migration.reports.index');
        }

        return redirect()->route('migration.login');
    }
}
