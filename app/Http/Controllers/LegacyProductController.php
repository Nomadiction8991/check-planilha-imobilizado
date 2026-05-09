<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\LegacyProductUtilityServiceInterface;
use App\Contracts\LegacyProductManagementServiceInterface;
use App\Contracts\LegacyProductBrowserServiceInterface;
use App\DTO\ProductFilters;
use App\Http\Requests\SyncProductVerificationRowRequest;
use App\Http\Requests\StoreLegacyProductRequest;
use App\Http\Requests\StoreProductVerificationRequest;
use App\Http\Requests\UpdateLegacyProductRequest;
use App\Models\Legacy\Produto;
use App\Support\LegacyProductTypeOptionSupport;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use RuntimeException;

class LegacyProductController extends Controller
{
    public function __construct(
        private readonly LegacyProductBrowserServiceInterface $products,
        private readonly LegacyProductManagementServiceInterface $productManager,
        private readonly LegacyProductUtilityServiceInterface $productUtility,
    ) {
    }

    public function index(Request $request): View
    {
        if ($request->integer('comum_id') <= 0 && (int) Session::get('comum_id', 0) > 0) {
            $request->merge(['comum_id' => (int) Session::get('comum_id')]);
        }

        $filters = ProductFilters::fromRequest($request);
        $paginator = $this->products->paginate($filters)->appends($filters->toQuery());
        $assetTypes = $this->products->assetTypeOptions();

        return view('products.index', [
            'filters' => $filters,
            'products' => $paginator,
            'churches' => $this->products->churchOptions(),
            'dependencies' => $this->products->dependencyOptions($filters->comumId),
            'assetTypes' => $assetTypes,
            'statusOptions' => $this->products->statusOptions(),
        ]);
    }

    public function verification(Request $request): View
    {
        if ($request->integer('comum_id') <= 0 && (int) Session::get('comum_id', 0) > 0) {
            $request->merge(['comum_id' => (int) Session::get('comum_id')]);
        }

        $filters = ProductFilters::fromRequest($request);
        $paginator = $this->products->paginate($filters)->appends($filters->toQuery());
        $rows = $paginator->getCollection();

        $stats = [
            'total_products' => $paginator->total(),
            'selected_for_print' => $rows->filter(
                static fn ($product): bool => (int) data_get($product, 'imprimir_etiqueta', 0) === 1
            )->count(),
            'with_observation' => $rows->filter(
                static fn ($product): bool => trim((string) data_get($product, 'observacao', '')) !== ''
            )->count(),
            'checked' => $rows->filter(
                static fn ($product): bool => (int) data_get($product, 'checado', 0) === 1
            )->count(),
        ];

        return view('products.verification', [
            'filters' => $filters,
            'products' => $paginator,
            'churches' => $this->products->churchOptions(),
            'dependencies' => $this->products->dependencyOptions($filters->comumId),
            'assetTypes' => $this->products->assetTypeOptions(),
            'statusOptions' => $this->products->statusOptions(),
            'stats' => $stats,
        ]);
    }

    public function create(Request $request): View
    {
        $selectedChurchId = max(0, (int) $request->query('comum_id', (int) Session::get('comum_id', 0)));
        $churches = $this->products->churchOptions();
        $dependencies = $this->products->dependencyOptions(null);
        $assetTypes = $this->products->assetTypeOptions();

        return view('products.create', [
            'selectedChurchId' => $selectedChurchId > 0 ? $selectedChurchId : null,
            'churches' => $churches,
            'dependencies' => $dependencies,
            'assetTypes' => $assetTypes,
            'assetTypeOptionMap' => LegacyProductTypeOptionSupport::buildMap($assetTypes),
            'dependencyOptionMap' => $dependencies->groupBy('comum_id')->map(
                static fn ($items) => $items->map(fn ($dependency) => [
                    'id' => (int) $dependency->id,
                    'descricao' => (string) $dependency->descricao,
                ])->values()->all()
            )->all(),
        ]);
    }

    public function store(StoreLegacyProductRequest $request): RedirectResponse
    {
        if ($request->integer('comum_id') > 0) {
            Session::put('comum_id', $request->integer('comum_id'));
        }

        try {
            $createdCount = $this->productManager->createMany($request->toDto());
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('migration.products.create', ['comum_id' => $request->integer('comum_id') ?: null])
                ->withInput()
                ->with('status', $exception->getMessage())
                ->with('status_type', 'error');
        }

        return redirect()
            ->route('migration.products.index', ['comum_id' => $request->integer('comum_id')])
            ->with('status', $createdCount > 1 ? $createdCount . ' produtos cadastrados com sucesso.' : 'Produto cadastrado com sucesso.')
            ->with('status_type', 'success');
    }

    public function edit(Produto $product): View
    {
        $product->loadMissing([
            'comum:id,codigo,descricao',
            'dependencia:id,descricao',
            'tipoBem:id,codigo,descricao',
        ]);

        $assetTypes = $this->products->assetTypeOptions();
        $dependencies = $this->products->dependencyOptions((int) $product->comum_id);

        return view('products.edit', [
            'product' => $product,
            'assetTypes' => $assetTypes,
            'dependencies' => $dependencies,
            'assetTypeOptionMap' => LegacyProductTypeOptionSupport::buildMap($assetTypes),
        ]);
    }

    public function update(UpdateLegacyProductRequest $request, Produto $product): RedirectResponse
    {
        try {
            $this->productManager->update($product, $request->toDto());
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('migration.products.edit', ['product' => $product->id_produto])
                ->withInput()
                ->with('status', $exception->getMessage())
                ->with('status_type', 'error');
        }

        return redirect()
            ->route('migration.products.index', ['comum_id' => $product->comum_id])
            ->with('status', 'Produto atualizado com sucesso.')
            ->with('status_type', 'success');
    }

    public function storeVerification(StoreProductVerificationRequest $request): RedirectResponse
    {
        if ($request->integer('comum_id') > 0) {
            Session::put('comum_id', $request->integer('comum_id'));
        }

        $this->productUtility->saveVerificationChecklist(
            $request->integer('comum_id'),
            $request->toItems(),
        );

        return redirect()
            ->route('migration.products.verification', $request->toReturnQuery())
            ->with('status', 'Checklist salvo com sucesso.')
            ->with('status_type', 'success');
    }

    public function syncVerification(SyncProductVerificationRowRequest $request): JsonResponse
    {
        $product = $this->productUtility->findForChurch($request->productId(), $request->churchId());

        if ($product === null) {
            return response()->json([
                'success' => false,
                'message' => 'O produto informado não pertence à igreja selecionada.',
            ], 404);
        }

        $this->productUtility->saveVerificationChecklist($request->churchId(), [$request->toItem()]);

        $updatedProduct = $this->productUtility->findForChurch($request->productId(), $request->churchId());

        return response()->json([
            'success' => true,
            'message' => 'Produto atualizado automaticamente.',
            'product_id' => $request->productId(),
            'checked' => (int) data_get($updatedProduct, 'checado', 0) === 1,
            'print_label' => (int) data_get($updatedProduct, 'imprimir_etiqueta', 0) === 1,
        ]);
    }
}
