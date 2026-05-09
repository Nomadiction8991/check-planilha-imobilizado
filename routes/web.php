<?php

declare(strict_types=1);

use App\Http\Controllers\LegacyAssetTypeController;
use App\Http\Controllers\LegacyAuditController;
use App\Http\Controllers\LegacyAdministrationController;
use App\Http\Controllers\LegacyAuthController;
use App\Http\Controllers\LegacyChurchController;
use App\Http\Controllers\LegacyDepartmentController;
use App\Http\Controllers\LegacyMigrationDashboardController;
use App\Http\Controllers\LegacyProductController;
use App\Http\Controllers\LegacyReportController;
use App\Http\Controllers\LegacyRouteCompatibilityController;
use App\Http\Controllers\LegacyUserController;
use App\Http\Controllers\BrazilLocalityController;
use App\Http\Controllers\CnpjLookupController;
use App\Http\Controllers\PublicAccessController;
use App\Http\Controllers\SpreadsheetImportController;
use Illuminate\Support\Facades\Route;

Route::get('/assinatura-publica', [PublicAccessController::class, 'create'])->name('public.access.create');
Route::post('/assinatura-publica', [PublicAccessController::class, 'store'])->name('public.access.store');
Route::post('/logout-publico', [PublicAccessController::class, 'logout'])->name('public.access.logout');

Route::middleware(['legacy.bridge', 'legacy.audit'])->group(function (): void {
    Route::get('/login', [LegacyAuthController::class, 'showLogin'])->name('migration.login');
    Route::post('/login', [LegacyAuthController::class, 'login'])->name('migration.login.store');

    Route::middleware('legacy.auth')->group(function (): void {
    Route::post('/logout', [LegacyAuthController::class, 'logout'])->name('migration.logout');
    Route::post('/session/church', [LegacyAuthController::class, 'switchChurch'])->name('migration.session.church');
    Route::post('/users/select-church', [LegacyRouteCompatibilityController::class, 'usersSelectChurch'])
        ->name('migration.compat.users.select-church');
    Route::get('/menu', [LegacyRouteCompatibilityController::class, 'menu'])->name('migration.compat.menu');
    Route::get('/churches', [LegacyChurchController::class, 'index'])
        ->middleware('legacy.permission:churches.view')
        ->name('migration.churches.index');
    Route::get('/churches/public', [LegacyChurchController::class, 'index'])
        ->name('migration.churches.public');

    Route::get('/', LegacyMigrationDashboardController::class)->name('migration.dashboard');
    Route::get('/products/novo', [LegacyRouteCompatibilityController::class, 'productsNew'])
        ->middleware('legacy.permission:products.view')
        ->name('migration.compat.products.new');
    Route::get('/products/view', [LegacyRouteCompatibilityController::class, 'productsView'])
        ->middleware('legacy.permission:products.view')
        ->name('migration.compat.products.view');
    Route::get('/products/edit', [LegacyRouteCompatibilityController::class, 'productsEdit'])
        ->middleware('legacy.permission:products.edit')
        ->name('migration.compat.products.edit');
    Route::get('/products/label', [LegacyRouteCompatibilityController::class, 'productsCopyLabels'])
        ->middleware('legacy.permission:products.view')
        ->name('migration.compat.products.copy-labels');
    Route::get('/products/verificacao', [LegacyProductController::class, 'verification'])
        ->middleware('legacy.permission:products.edit')
        ->name('migration.products.verification');
    Route::post('/products/verificacao/sync', [LegacyProductController::class, 'syncVerification'])
        ->middleware('legacy.permission:products.edit')
        ->name('migration.products.verification.sync');
    Route::post('/products/verificacao', [LegacyProductController::class, 'storeVerification'])
        ->middleware('legacy.permission:products.edit')
        ->name('migration.products.verification.store');
    Route::get('/products/observation', [LegacyRouteCompatibilityController::class, 'productsObservation'])
        ->middleware('legacy.permission:products.edit')
        ->name('migration.compat.products.observation');
    Route::post('/products/observation', [LegacyRouteCompatibilityController::class, 'storeProductsObservation'])
        ->middleware('legacy.permission:products.edit')
        ->name('migration.compat.products.observation.store');
    Route::post('/products/check', [LegacyRouteCompatibilityController::class, 'productsCheck'])
        ->middleware('legacy.permission:products.edit')
        ->name('migration.compat.products.check');
    Route::post('/products/label', [LegacyRouteCompatibilityController::class, 'productsLabel'])
        ->middleware('legacy.permission:products.edit')
        ->name('migration.compat.products.label');
    Route::post('/products/sign', [LegacyRouteCompatibilityController::class, 'productsSign'])
        ->middleware('legacy.permission:products.edit')
        ->name('migration.compat.products.sign');
    Route::post('/products/clear-edits', [LegacyRouteCompatibilityController::class, 'productsClearEdits'])
        ->middleware('legacy.permission:products.edit')
        ->name('migration.compat.products.clear-edits');
    Route::post('/products/edit', [LegacyRouteCompatibilityController::class, 'productsUpdate'])
        ->middleware('legacy.permission:products.edit')
        ->name('migration.compat.products.update');
    Route::get('/products', [LegacyProductController::class, 'index'])
        ->middleware('legacy.permission:products.view')
        ->name('migration.products.index');
    Route::get('/products/create', [LegacyProductController::class, 'create'])
        ->middleware('legacy.permission:products.create')
        ->name('migration.products.create');
    Route::post('/products/create', [LegacyProductController::class, 'store'])
        ->middleware('legacy.permission:products.create')
        ->name('migration.compat.products.store');
    Route::post('/products', [LegacyProductController::class, 'store'])
        ->middleware('legacy.permission:products.create')
        ->name('migration.products.store');
    Route::get('/products/{product}/edit', [LegacyProductController::class, 'edit'])
        ->middleware('legacy.permission:products.edit')
        ->whereNumber('product')
        ->name('migration.products.edit');
    Route::post('/products/{product}', [LegacyProductController::class, 'update'])
        ->middleware('legacy.permission:products.edit')
        ->whereNumber('product')
        ->name('migration.products.update.post');
    Route::put('/products/{product}', [LegacyProductController::class, 'update'])
        ->middleware('legacy.permission:products.edit')
        ->whereNumber('product')
        ->name('migration.products.update');
    Route::get('/reports', [LegacyReportController::class, 'index'])
        ->middleware('legacy.permission:reports.view')
        ->name('migration.reports.index');
    Route::get('/reports/changes/export', [LegacyReportController::class, 'changesExport'])
        ->middleware('legacy.permission:reports.changes.view')
        ->name('migration.reports.changes.export');
    Route::get('/reports/view', [LegacyRouteCompatibilityController::class, 'reportsView'])
        ->middleware('legacy.permission:reports.view')
        ->name('migration.compat.reports.view');
    Route::get('/audits', [LegacyAuditController::class, 'index'])
        ->middleware('legacy.permission:audits.view')
        ->name('migration.audits.index');
    Route::post('/api/cnpj-lookup', [CnpjLookupController::class, 'lookup'])
        ->middleware('legacy.admin')
        ->name('migration.api.cnpj.lookup');
    Route::get('/api/localidades/estados', [BrazilLocalityController::class, 'states'])
        ->middleware('legacy.auth')
        ->name('migration.api.localidades.states');
    Route::get('/api/localidades/estados/{state}/municipios', [BrazilLocalityController::class, 'cities'])
        ->middleware('legacy.auth')
        ->name('migration.api.localidades.cities');
    Route::get('/spreadsheets/import', [SpreadsheetImportController::class, 'create'])
        ->middleware('legacy.permission:spreadsheets.import')
        ->name('migration.spreadsheets.create');
    Route::get('/spreadsheets/view', [LegacyRouteCompatibilityController::class, 'spreadsheetsView'])
        ->middleware('legacy.permission:spreadsheets.import')
        ->name('migration.compat.spreadsheets.view');
    Route::get('/spreadsheets/preview', [LegacyRouteCompatibilityController::class, 'spreadsheetsPreview'])
        ->middleware('legacy.permission:spreadsheets.import')
        ->name('migration.compat.spreadsheets.preview');
    Route::post('/spreadsheets/preview/save-actions', [SpreadsheetImportController::class, 'legacySavePreviewActions'])
        ->middleware('legacy.permission:spreadsheets.import')
        ->name('migration.compat.spreadsheets.preview.save-actions');
    Route::post('/spreadsheets/preview/bulk-action', [SpreadsheetImportController::class, 'legacyBulkPreviewAction'])
        ->middleware('legacy.permission:spreadsheets.import')
        ->name('migration.compat.spreadsheets.preview.bulk-action');
    Route::post('/spreadsheets/confirm', [SpreadsheetImportController::class, 'legacyConfirm'])
        ->middleware('legacy.permission:spreadsheets.import')
        ->name('migration.compat.spreadsheets.confirm');
    Route::post('/spreadsheets/process-file', [SpreadsheetImportController::class, 'processFile'])
        ->middleware('legacy.permission:spreadsheets.import')
        ->name('migration.compat.spreadsheets.process-file');
    Route::get('/spreadsheets/api/progress', [SpreadsheetImportController::class, 'legacyProgress'])
        ->middleware('legacy.permission:spreadsheets.import')
        ->name('migration.compat.spreadsheets.api-progress');
    Route::post('/spreadsheets/import', [SpreadsheetImportController::class, 'store'])
        ->middleware('legacy.permission:spreadsheets.import')
        ->name('migration.spreadsheets.store');
    Route::get('/spreadsheets/errors', [SpreadsheetImportController::class, 'errors'])
        ->middleware('legacy.permission:spreadsheets.errors.view')
        ->name('migration.spreadsheets.errors');
    Route::get('/spreadsheets/import-errors', [SpreadsheetImportController::class, 'errors'])
        ->middleware('legacy.permission:spreadsheets.errors.view')
        ->name('migration.compat.spreadsheets.import-errors');
    Route::get('/spreadsheets/errors/download', [SpreadsheetImportController::class, 'downloadErrorsCsv'])
        ->middleware('legacy.permission:spreadsheets.errors.export')
        ->name('migration.spreadsheets.errors.download');
    Route::get('/spreadsheets/import-errors/download', [SpreadsheetImportController::class, 'downloadErrorsCsv'])
        ->middleware('legacy.permission:spreadsheets.errors.export')
        ->name('migration.compat.spreadsheets.import-errors.download');
    Route::post('/spreadsheets/errors/{erro}/resolve', [SpreadsheetImportController::class, 'resolveError'])
        ->middleware('legacy.permission:spreadsheets.errors.resolve')
        ->name('migration.spreadsheets.errors.resolve');
    Route::post('/spreadsheets/import-errors/resolver', [SpreadsheetImportController::class, 'legacyResolveError'])
        ->middleware('legacy.permission:spreadsheets.errors.resolve')
        ->name('migration.compat.spreadsheets.import-errors.resolve');
    Route::get('/spreadsheets/preview/{importacao}', [SpreadsheetImportController::class, 'preview'])
        ->middleware('legacy.permission:spreadsheets.import')
        ->name('migration.spreadsheets.preview');
    Route::post('/spreadsheets/preview/{importacao}/actions', [SpreadsheetImportController::class, 'savePreviewActions'])
        ->middleware('legacy.permission:spreadsheets.import')
        ->name('migration.spreadsheets.preview.actions');
    Route::post('/spreadsheets/preview/{importacao}/bulk-action', [SpreadsheetImportController::class, 'bulkPreviewAction'])
        ->middleware('legacy.permission:spreadsheets.import')
        ->name('migration.spreadsheets.preview.bulk');
    Route::post('/spreadsheets/preview/{importacao}/confirm', [SpreadsheetImportController::class, 'confirm'])
        ->middleware('legacy.permission:spreadsheets.import')
        ->name('migration.spreadsheets.confirm');
    Route::get('/spreadsheets/process/{importacao}', [SpreadsheetImportController::class, 'processing'])
        ->middleware('legacy.permission:spreadsheets.import')
        ->name('migration.spreadsheets.processing');
    Route::post('/spreadsheets/process/{importacao}/start', [SpreadsheetImportController::class, 'startProcessing'])
        ->middleware('legacy.permission:spreadsheets.import')
        ->name('migration.spreadsheets.start');
    Route::get('/spreadsheets/process/{importacao}/progress', [SpreadsheetImportController::class, 'progress'])
        ->middleware('legacy.permission:spreadsheets.import')
        ->name('migration.spreadsheets.progress');

    Route::middleware('legacy.admin')->group(function (): void {
        Route::get('/churches/products-count', [LegacyChurchController::class, 'productsCount'])
            ->middleware('legacy.permission:churches.delete')
            ->name('migration.churches.products-count');
        Route::post('/churches/delete-products', [LegacyChurchController::class, 'deleteProducts'])
            ->middleware('legacy.permission:churches.delete')
            ->name('migration.churches.delete-products');
        Route::get('/churches/edit', [LegacyRouteCompatibilityController::class, 'churchesEdit'])
            ->middleware('legacy.permission:churches.edit')
            ->name('migration.compat.churches.edit');
        Route::post('/churches/edit', [LegacyRouteCompatibilityController::class, 'churchesUpdate'])
            ->middleware('legacy.permission:churches.edit')
            ->name('migration.compat.churches.update');
        Route::get('/churches/{church}/edit', [LegacyChurchController::class, 'edit'])
            ->middleware('legacy.permission:churches.edit')
            ->whereNumber('church')
            ->name('migration.churches.edit');
        Route::post('/churches/{church}', [LegacyChurchController::class, 'update'])
            ->middleware('legacy.permission:churches.edit')
            ->whereNumber('church')
            ->name('migration.churches.update.post');
        Route::put('/churches/{church}', [LegacyChurchController::class, 'update'])
            ->middleware('legacy.permission:churches.edit')
            ->whereNumber('church')
            ->name('migration.churches.update');
        Route::get('/departments', [LegacyDepartmentController::class, 'index'])
            ->middleware('legacy.permission:departments.view')
            ->name('migration.departments.index');
        Route::get('/departments/edit', [LegacyRouteCompatibilityController::class, 'departmentsEdit'])
            ->middleware('legacy.permission:departments.edit')
            ->name('migration.compat.departments.edit');
        Route::post('/departments/edit', [LegacyRouteCompatibilityController::class, 'departmentsUpdate'])
            ->middleware('legacy.permission:departments.edit')
            ->name('migration.compat.departments.update');
        Route::get('/departments/create', [LegacyDepartmentController::class, 'create'])
            ->middleware('legacy.permission:departments.create')
            ->name('migration.departments.create');
        Route::post('/departments/create', [LegacyDepartmentController::class, 'store'])
            ->middleware('legacy.permission:departments.create')
            ->name('migration.compat.departments.store');
        Route::post('/departments', [LegacyDepartmentController::class, 'store'])
            ->middleware('legacy.permission:departments.create')
            ->name('migration.departments.store');
        Route::get('/departments/{department}/edit', [LegacyDepartmentController::class, 'edit'])
            ->middleware('legacy.permission:departments.edit')
            ->whereNumber('department')
            ->name('migration.departments.edit');
        Route::post('/departments/{department}', [LegacyDepartmentController::class, 'update'])
            ->middleware('legacy.permission:departments.edit')
            ->whereNumber('department')
            ->name('migration.departments.update.post');
        Route::put('/departments/{department}', [LegacyDepartmentController::class, 'update'])
            ->middleware('legacy.permission:departments.edit')
            ->whereNumber('department')
            ->name('migration.departments.update');
        Route::post('/departments/delete', [LegacyRouteCompatibilityController::class, 'departmentsDelete'])
            ->middleware('legacy.permission:departments.delete')
            ->name('migration.compat.departments.delete');
        Route::post('/departments/{department}/delete', [LegacyDepartmentController::class, 'destroy'])
            ->middleware('legacy.permission:departments.delete')
            ->whereNumber('department')
            ->name('migration.departments.destroy.post');
        Route::delete('/departments/{department}', [LegacyDepartmentController::class, 'destroy'])
            ->middleware('legacy.permission:departments.delete')
            ->whereNumber('department')
            ->name('migration.departments.destroy');
        Route::get('/asset-types', [LegacyAssetTypeController::class, 'index'])
            ->middleware('legacy.permission:asset-types.view')
            ->name('migration.asset-types.index');
        Route::get('/asset-types/create', [LegacyAssetTypeController::class, 'create'])
            ->middleware('legacy.permission:asset-types.create')
            ->name('migration.asset-types.create');
        Route::post('/asset-types/create', [LegacyAssetTypeController::class, 'store'])
            ->middleware('legacy.permission:asset-types.create')
            ->name('migration.compat.asset-types.store');
        Route::post('/asset-types', [LegacyAssetTypeController::class, 'store'])
            ->middleware('legacy.permission:asset-types.create')
            ->name('migration.asset-types.store');
        Route::get('/asset-types/{assetType}/edit', [LegacyAssetTypeController::class, 'edit'])
            ->middleware('legacy.permission:asset-types.edit')
            ->whereNumber('assetType')
            ->name('migration.asset-types.edit');
        Route::post('/asset-types/{assetType}/edit', [LegacyAssetTypeController::class, 'update'])
            ->middleware('legacy.permission:asset-types.edit')
            ->whereNumber('assetType')
            ->name('migration.asset-types.update.post');
        Route::put('/asset-types/{assetType}', [LegacyAssetTypeController::class, 'update'])
            ->middleware('legacy.permission:asset-types.edit')
            ->whereNumber('assetType')
            ->name('migration.asset-types.update');
        Route::post('/asset-types/delete', [LegacyRouteCompatibilityController::class, 'assetTypesDelete'])
            ->middleware('legacy.permission:asset-types.delete')
            ->name('migration.compat.asset-types.delete');
        Route::post('/asset-types/{assetType}/delete', [LegacyAssetTypeController::class, 'destroy'])
            ->middleware('legacy.permission:asset-types.delete')
            ->whereNumber('assetType')
            ->name('migration.asset-types.destroy.post');
        Route::delete('/asset-types/{assetType}', [LegacyAssetTypeController::class, 'destroy'])
            ->middleware('legacy.permission:asset-types.delete')
            ->whereNumber('assetType')
            ->name('migration.asset-types.destroy');
        Route::get('/administrations', [LegacyAdministrationController::class, 'index'])
            ->middleware('legacy.permission:administrations.view')
            ->name('migration.administrations.index');
        Route::get('/administrations/create', [LegacyAdministrationController::class, 'create'])
            ->middleware('legacy.permission:administrations.create')
            ->name('migration.administrations.create');
        Route::post('/administrations/create', [LegacyAdministrationController::class, 'store'])
            ->middleware('legacy.permission:administrations.create')
            ->name('migration.administrations.store');
        Route::post('/administrations', [LegacyAdministrationController::class, 'store'])
            ->middleware('legacy.permission:administrations.create')
            ->name('migration.administrations.store.post');
        Route::get('/administrations/{administration}/edit', [LegacyAdministrationController::class, 'edit'])
            ->middleware('legacy.permission:administrations.edit')
            ->whereNumber('administration')
            ->name('migration.administrations.edit');
        Route::post('/administrations/{administration}', [LegacyAdministrationController::class, 'update'])
            ->middleware('legacy.permission:administrations.edit')
            ->whereNumber('administration')
            ->name('migration.administrations.update.post');
        Route::put('/administrations/{administration}', [LegacyAdministrationController::class, 'update'])
            ->middleware('legacy.permission:administrations.edit')
            ->whereNumber('administration')
            ->name('migration.administrations.update');
        Route::post('/administrations/{administration}/delete', [LegacyAdministrationController::class, 'destroy'])
            ->middleware('legacy.permission:administrations.delete')
            ->whereNumber('administration')
            ->name('migration.administrations.destroy.post');
        Route::delete('/administrations/{administration}', [LegacyAdministrationController::class, 'destroy'])
            ->middleware('legacy.permission:administrations.delete')
            ->whereNumber('administration')
            ->name('migration.administrations.destroy');
        Route::get('/users', [LegacyUserController::class, 'index'])
            ->middleware('legacy.permission:users.view')
            ->name('migration.users.index');
        Route::get('/users/show', [LegacyRouteCompatibilityController::class, 'usersShow'])
            ->middleware('legacy.permission:users.edit')
            ->name('migration.compat.users.show');
        Route::get('/users/edit', [LegacyRouteCompatibilityController::class, 'usersEdit'])
            ->middleware('legacy.permission:users.edit')
            ->name('migration.compat.users.edit');
        Route::get('/users/create', [LegacyUserController::class, 'create'])
            ->middleware('legacy.permission:users.create')
            ->name('migration.users.create');
        Route::post('/users/create', [LegacyUserController::class, 'store'])
            ->middleware('legacy.permission:users.create')
            ->name('migration.compat.users.store');
        Route::post('/users', [LegacyUserController::class, 'store'])
            ->middleware('legacy.permission:users.create')
            ->name('migration.users.store');
        Route::get('/users/{user}/edit', [LegacyUserController::class, 'edit'])
            ->middleware('legacy.permission:users.edit')
            ->whereNumber('user')
            ->name('migration.users.edit');
        Route::post('/users/edit', [LegacyRouteCompatibilityController::class, 'usersUpdate'])
            ->middleware('legacy.permission:users.edit')
            ->name('migration.compat.users.update');
        Route::post('/users/{user}', [LegacyUserController::class, 'update'])
            ->middleware('legacy.permission:users.edit')
            ->whereNumber('user')
            ->name('migration.users.update.post');
        Route::put('/users/{user}', [LegacyUserController::class, 'update'])
            ->middleware('legacy.permission:users.edit')
            ->whereNumber('user')
            ->name('migration.users.update');
        Route::post('/users/delete', [LegacyRouteCompatibilityController::class, 'usersDelete'])
            ->middleware('legacy.permission:users.delete')
            ->name('migration.compat.users.delete');
        Route::post('/users/{user}/delete', [LegacyUserController::class, 'destroy'])
            ->middleware('legacy.permission:users.delete')
            ->whereNumber('user')
            ->name('migration.users.destroy.post');
        Route::delete('/users/{user}', [LegacyUserController::class, 'destroy'])
            ->middleware('legacy.permission:users.delete')
            ->whereNumber('user')
            ->name('migration.users.destroy');
        Route::get('/reports/alteracoes', [LegacyReportController::class, 'changes'])
            ->middleware('legacy.permission:reports.changes.view')
            ->name('migration.reports.changes');
        Route::get('/reports/cell-editor', [LegacyReportController::class, 'editor'])
            ->middleware('legacy.permission:reports.editor')
            ->name('migration.reports.editor');
        Route::get('/cell-reports', [LegacyReportController::class, 'editor'])
            ->middleware('legacy.permission:reports.editor');
    });

        Route::get('/reports/{formulario}', [LegacyReportController::class, 'show'])
            ->middleware('legacy.permission:reports.view')
            ->name('migration.reports.show');
    });
});
