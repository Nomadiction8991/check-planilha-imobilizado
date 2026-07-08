<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\LegacyInventoryServiceInterface;
use App\Contracts\LegacyAuthSessionServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class LegacyMigrationDashboardController extends Controller
{
    public function __construct(
        private readonly LegacyAuthSessionServiceInterface $auth,
        private readonly LegacyInventoryServiceInterface $inventoryService,
    ) {
    }

    public function __invoke(): View|RedirectResponse
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

        return view('migration-dashboard', [
            'snapshot' => $this->inventoryService->buildSnapshot(),
            'churches' => $this->auth->availableChurches(),
        ]);
    }
}
