<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\LegacyInventoryServiceInterface;
use Illuminate\Contracts\View\View;

class LegacyMigrationDashboardController extends Controller
{
    public function __construct(
        private readonly LegacyInventoryServiceInterface $inventoryService,
    ) {
    }

    public function __invoke(): View
    {
        return view('migration-dashboard', [
            'snapshot' => $this->inventoryService->buildSnapshot(),
        ]);
    }
}
