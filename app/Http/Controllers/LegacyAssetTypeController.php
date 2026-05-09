<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\LegacyAssetTypeBrowserServiceInterface;
use App\Contracts\LegacyAssetTypeManagementServiceInterface;
use App\DTO\AssetTypeFilters;
use App\Http\Requests\StoreLegacyAssetTypeRequest;
use App\Http\Requests\UpdateLegacyAssetTypeRequest;
use App\Models\Legacy\TipoBem;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class LegacyAssetTypeController extends Controller
{
    public function __construct(
        private readonly LegacyAssetTypeBrowserServiceInterface $assetTypes,
        private readonly LegacyAssetTypeManagementServiceInterface $assetTypeManager,
    ) {
    }

    public function index(Request $request): View
    {
        $filters = AssetTypeFilters::fromRequest($request);
        $paginator = $this->assetTypes->paginate($filters)->appends($filters->toQuery());

        return view('asset-types.index', [
            'filters' => $filters,
            'assetTypes' => $paginator,
            'totalAll' => $this->assetTypes->countAll(),
        ]);
    }

    public function create(): View
    {
        return view('asset-types.create');
    }

    public function store(StoreLegacyAssetTypeRequest $request): RedirectResponse
    {
        $assetType = $this->assetTypeManager->create($request->toDto());

        return redirect()
            ->route('migration.asset-types.index')
            ->with('status', 'Tipo de bem criado com sucesso.')
            ->with('status_type', 'success')
            ->with('status_detail', 'Código gerado: ' . $assetType->codigo . '.');
    }

    public function edit(TipoBem $assetType): View
    {
        return view('asset-types.edit', [
            'assetType' => $assetType,
        ]);
    }

    public function update(UpdateLegacyAssetTypeRequest $request, TipoBem $assetType): RedirectResponse
    {
        $this->assetTypeManager->update($assetType, $request->toDto());

        return redirect()
            ->route('migration.asset-types.index')
            ->with('status', 'Tipo de bem atualizado com sucesso.')
            ->with('status_type', 'success');
    }

    public function destroy(TipoBem $assetType): RedirectResponse
    {
        try {
            $this->assetTypeManager->delete($assetType);
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('migration.asset-types.index')
                ->with('status', $exception->getMessage())
                ->with('status_type', 'error');
        }

        return redirect()
            ->route('migration.asset-types.index')
            ->with('status', 'Tipo de bem excluído com sucesso.')
            ->with('status_type', 'success');
    }
}
