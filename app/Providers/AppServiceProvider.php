<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\LegacyAssetTypeBrowserServiceInterface;
use App\Contracts\LegacyAdministrationBrowserServiceInterface;
use App\Contracts\LegacyAdministrationManagementServiceInterface;
use App\Contracts\LegacyAuthSessionServiceInterface;
use App\Contracts\LegacyAssetTypeManagementServiceInterface;
use App\Contracts\LegacyChurchBrowserServiceInterface;
use App\Contracts\LegacyChurchManagementServiceInterface;
use App\Contracts\LegacyAuditTrailServiceInterface;
use App\Contracts\LegacyDepartmentBrowserServiceInterface;
use App\Contracts\LegacyDepartmentManagementServiceInterface;
use App\Contracts\LegacyInventoryServiceInterface;
use App\Contracts\LegacyPermissionServiceInterface;
use App\Contracts\LegacyProductManagementServiceInterface;
use App\Contracts\LegacyProductUtilityServiceInterface;
use App\Contracts\LegacyProductBrowserServiceInterface;
use App\Contracts\LegacyReportServiceInterface;
use App\Contracts\LegacySpreadsheetImportServiceInterface;
use App\Contracts\LegacyUserBrowserServiceInterface;
use App\Contracts\LegacyUserManagementServiceInterface;
use App\Services\LegacyAssetTypeBrowserService;
use App\Services\LegacyAdministrationBrowserService;
use App\Services\LegacyAdministrationManagementService;
use App\Services\LegacyAuthSessionService;
use App\Services\LegacyAssetTypeManagementService;
use App\Services\LegacyChurchBrowserService;
use App\Services\LegacyChurchManagementService;
use App\Services\LegacyAuditTrailService;
use App\Services\LegacyDepartmentBrowserService;
use App\Services\LegacyDepartmentManagementService;
use App\Services\LegacyInventoryService;
use App\Services\LegacyPermissionService;
use App\Services\LegacyProductManagementService;
use App\Services\LegacyProductUtilityService;
use App\Services\LegacyProductBrowserService;
use App\Services\LegacyReportService;
use App\Services\LegacySpreadsheetImportService;
use App\Services\LegacyUserBrowserService;
use App\Services\LegacyUserManagementService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            LegacyInventoryServiceInterface::class,
            LegacyInventoryService::class
        );
        $this->app->singleton(
            LegacyAuthSessionServiceInterface::class,
            LegacyAuthSessionService::class
        );
        $this->app->singleton(
            LegacyAuditTrailServiceInterface::class,
            LegacyAuditTrailService::class
        );
        $this->app->singleton(
            LegacyPermissionServiceInterface::class,
            LegacyPermissionService::class
        );
        $this->app->singleton(
            LegacyChurchBrowserServiceInterface::class,
            LegacyChurchBrowserService::class
        );
        $this->app->singleton(
            LegacyChurchManagementServiceInterface::class,
            LegacyChurchManagementService::class
        );
        $this->app->singleton(
            LegacyProductBrowserServiceInterface::class,
            LegacyProductBrowserService::class
        );
        $this->app->singleton(
            LegacyProductManagementServiceInterface::class,
            LegacyProductManagementService::class
        );
        $this->app->singleton(
            LegacyProductUtilityServiceInterface::class,
            LegacyProductUtilityService::class
        );
        $this->app->singleton(
            LegacyDepartmentBrowserServiceInterface::class,
            LegacyDepartmentBrowserService::class
        );
        $this->app->singleton(
            LegacyDepartmentManagementServiceInterface::class,
            LegacyDepartmentManagementService::class
        );
        $this->app->singleton(
            LegacyAssetTypeBrowserServiceInterface::class,
            LegacyAssetTypeBrowserService::class
        );
        $this->app->singleton(
            LegacyAssetTypeManagementServiceInterface::class,
            LegacyAssetTypeManagementService::class
        );
        $this->app->singleton(
            LegacyAdministrationBrowserServiceInterface::class,
            LegacyAdministrationBrowserService::class
        );
        $this->app->singleton(
            LegacyAdministrationManagementServiceInterface::class,
            LegacyAdministrationManagementService::class
        );
        $this->app->singleton(
            LegacyUserBrowserServiceInterface::class,
            LegacyUserBrowserService::class
        );
        $this->app->singleton(
            LegacyUserManagementServiceInterface::class,
            LegacyUserManagementService::class
        );
        $this->app->singleton(
            LegacySpreadsheetImportServiceInterface::class,
            LegacySpreadsheetImportService::class
        );
        $this->app->singleton(
            LegacyReportServiceInterface::class,
            LegacyReportService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.migration', function ($view): void {
            /** @var LegacyAuthSessionServiceInterface $auth */
            $auth = $this->app->make(LegacyAuthSessionServiceInterface::class);
            /** @var LegacyPermissionServiceInterface $permissions */
            $permissions = $this->app->make(LegacyPermissionServiceInterface::class);
            $currentUser = $auth->currentUser();
            $legacyPermissions = $currentUser !== null
                ? $permissions->currentPermissions()
                : (is_array(Session::get('legacy_permissions', [])) ? Session::get('legacy_permissions', []) : []);

            $view->with('legacySessionUser', $currentUser);
            $view->with('legacySessionChurch', $currentUser !== null ? $auth->currentChurch() : null);
            $view->with('legacySessionChurches', $currentUser !== null ? $auth->availableChurches() : collect());
            $view->with('legacyPermissions', $legacyPermissions);
        });
    }
}
