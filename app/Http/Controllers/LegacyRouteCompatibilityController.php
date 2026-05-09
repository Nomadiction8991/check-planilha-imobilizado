<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\LegacyAuthSessionServiceInterface;
use App\Contracts\LegacyProductUtilityServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use RuntimeException;

class LegacyRouteCompatibilityController extends Controller
{
    public function __construct(
        private readonly LegacyAuthSessionServiceInterface $auth,
        private readonly LegacyProductUtilityServiceInterface $products,
    ) {
    }

    public function menu(): RedirectResponse
    {
        return redirect()->route('migration.dashboard');
    }

    public function productsView(Request $request): RedirectResponse
    {
        return redirect()->route(
            'migration.products.index',
            $this->buildProductReturnQuery($request, $this->resolveChurchId($request)),
        );
    }

    public function productsNew(Request $request): RedirectResponse
    {
        $query = $this->filteredQuery($request);
        $query['status'] = 'novos';
        $query['somente_novos'] = 1;

        return redirect()->route('migration.products.index', $query);
    }

    public function productsEdit(Request $request): RedirectResponse
    {
        $productId = $this->firstPositiveInt(
            $request->query('id_produto'),
            $request->query('id'),
            $request->query('id_PRODUTO'),
        );

        if ($productId === null) {
            return redirect()
                ->route('migration.products.index', $this->filteredQuery($request))
                ->with('status', 'Produto não informado para edição.')
                ->with('status_type', 'error');
        }

        return redirect()->route('migration.products.edit', ['product' => $productId]);
    }

    public function productsUpdate(Request $request): RedirectResponse
    {
        $productId = $this->firstPositiveInt(
            $request->input('id_produto'),
            $request->input('id'),
            $request->query('id_produto'),
            $request->query('id'),
        );

        if ($productId === null) {
            return redirect()
                ->route('migration.products.index', $this->filteredQuery($request))
                ->with('status', 'Produto não informado para atualização.')
                ->with('status_type', 'error');
        }

        return redirect()->route('migration.products.update.post', ['product' => $productId], 307);
    }

    public function labels(
        Request $request,
    ): View|RedirectResponse {
        $churchId = $this->firstPositiveInt(
            $request->input('comum_id'),
            $request->query('comum_id'),
        );
        $dependencyId = $this->firstPositiveInt($request->query('dependencia'));
        $churches = $this->auth->availableChurches();

        if ($churchId === null) {
            return view('labels.index', [
                'churchId' => null,
                'dependencyId' => $dependencyId,
                'churches' => $churches,
                'manualCodes' => [],
                'data' => [
                    'church' => null,
                    'dependencies' => [],
                    'products' => [],
                    'selected_dependency_id' => $dependencyId,
                    'total_products' => 0,
                    'unique_codes' => 0,
                    'codes' => '',
                ],
            ]);
        }

        try {
            $data = $this->products->labelCopyData(
                $churchId,
                $dependencyId,
            );
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('migration.products.index', ['comum_id' => $churchId])
                ->with('status', $exception->getMessage())
                ->with('status_type', 'error');
        }

        return view('labels.index', [
            'churchId' => $churchId,
            'dependencyId' => $dependencyId,
            'churches' => $churches,
            'manualCodes' => $this->auth->labelManualCodes($churchId, $dependencyId),
            'data' => $data,
        ]);
    }

    public function labelsManualStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'comum_id' => ['required', 'integer', 'min:1'],
            'dependencia_id' => ['nullable', 'integer', 'min:1'],
            'numero' => ['required', 'string', 'regex:/^\d{1,6}$/'],
            'action' => ['required', 'in:add,remove'],
        ]);

        $churchId = (int) $validated['comum_id'];
        $dependencyId = isset($validated['dependencia_id']) ? (int) $validated['dependencia_id'] : null;
        $churchCode = $this->resolveChurchCode($churchId);

        if ($churchCode === null) {
            return response()->json([
                'success' => false,
                'message' => 'Igreja não encontrada.',
            ], 422);
        }

        $currentCodes = $this->auth->labelManualCodes($churchId, $dependencyId);
        $formattedCode = $this->formatManualLabelCode($churchCode, (string) $validated['numero']);

        if ($validated['action'] === 'add' && !in_array($formattedCode, $currentCodes, true)) {
            $currentCodes[] = $formattedCode;
        }

        if ($validated['action'] === 'remove') {
            $currentCodes = array_values(array_filter(
                $currentCodes,
                static fn (string $code): bool => $code !== $formattedCode,
            ));
        }

        try {
            $this->auth->saveLabelManualCodes($churchId, $dependencyId, $currentCodes);
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'codes' => $currentCodes,
            'manual_labels' => implode(', ', $currentCodes),
            'code' => $formattedCode,
        ]);
    }

    public function productsCopyLabels(
        Request $request,
    ): RedirectResponse {
        return redirect()->route('migration.labels.index', $request->query());
    }

    public function productsObservation(
        Request $request,
        LegacyProductUtilityServiceInterface $products,
    ): View|RedirectResponse {
        $churchId = $this->resolveChurchId($request);

        if ($churchId === null) {
            return redirect()
                ->route('migration.products.index')
                ->with('status', 'Selecione uma igreja para observar o produto.')
                ->with('status_type', 'error');
        }

        $productId = $this->firstPositiveInt(
            $request->query('id_produto'),
            $request->query('produto_id'),
            $request->query('id'),
        );

        if ($productId === null) {
            return redirect()
                ->route('migration.products.index', ['comum_id' => $churchId])
                ->with('status', 'Produto inválido.')
                ->with('status_type', 'error');
        }

        $product = $products->findForChurch($productId, $churchId);

        if ($product === null) {
            return redirect()
                ->route('migration.products.index', ['comum_id' => $churchId])
                ->with('status', 'Produto não encontrado para a igreja selecionada.')
                ->with('status_type', 'error');
        }

        return view('products.observation', [
            'product' => $product,
            'churchId' => $churchId,
            'filters' => [
                'pagina' => max(1, (int) $request->query('pagina', '1')),
                'busca' => $this->resolveProductSearch($request),
                'dependencia_id' => $this->firstPositiveInt($request->query('dependencia_id'), $request->query('dependencia')),
                'status' => trim((string) $request->query('status', '')),
                'somente_novos' => $request->boolean('somente_novos'),
            ],
        ]);
    }

    public function storeProductsObservation(
        Request $request,
        LegacyProductUtilityServiceInterface $products,
    ): JsonResponse|RedirectResponse {
        $churchId = $this->resolveChurchId($request);
        $productId = $this->firstPositiveInt(
            $request->input('produto_id'),
            $request->input('id_produto'),
            $request->input('id'),
        );

        if ($churchId === null || $productId === null) {
            return $this->productJsonOrRedirectError($request, 'Parâmetros inválidos.', 400);
        }

        $updated = $products->updateObservation(
            $productId,
            $churchId,
            (string) $request->input('observacoes', $request->input('observacao', '')),
        );

        if (!$updated) {
            return $this->productJsonOrRedirectError($request, 'Produto não encontrado para a igreja selecionada.', 404);
        }

        return $this->productJsonOrRedirectSuccess(
            $request,
            'Observação salva com sucesso',
            $this->buildProductReturnQuery($request, $churchId),
        );
    }

    public function productsCheck(
        Request $request,
        LegacyProductUtilityServiceInterface $products,
    ): JsonResponse|RedirectResponse {
        return $this->updateProductFlag(
            $request,
            $products,
            fn (int $productId, int $churchId, bool $value): bool => $products->updateCheck($productId, $churchId, $value),
            'Status atualizado com sucesso',
        );
    }

    public function productsLabel(
        Request $request,
        LegacyProductUtilityServiceInterface $products,
    ): JsonResponse|RedirectResponse {
        return $this->updateProductFlag(
            $request,
            $products,
            fn (int $productId, int $churchId, bool $value): bool => $products->updateLabel($productId, $churchId, $value),
            'Etiqueta atualizada com sucesso',
        );
    }

    public function productsSign(
        Request $request,
        LegacyProductUtilityServiceInterface $products,
    ): JsonResponse {
        $churchId = $this->resolveChurchId($request);
        $userId = (int) $request->session()->get('usuario_id', 0);

        if ($churchId === null || $userId <= 0) {
            return response()->json(['success' => false, 'message' => 'Sessão inválida.'], 400);
        }

        try {
            $updated = $products->signProducts(
                (array) $request->input('PRODUTOS', []),
                $churchId,
                $userId,
                (string) $request->input('acao', ''),
            );
        } catch (RuntimeException $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 422);
        }

        $action = (string) $request->input('acao', '');
        $message = $action === 'desassinar'
            ? 'Assinatura removida com sucesso.'
            : 'Assinatura aplicada com sucesso.';

        if ($updated === 0) {
            $message = 'Nenhum produto elegível foi atualizado para a igreja selecionada.';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'updated' => $updated,
        ]);
    }

    public function productsClearEdits(
        Request $request,
        LegacyProductUtilityServiceInterface $products,
    ): RedirectResponse {
        $churchId = $this->resolveChurchId($request);
        $productId = $this->firstPositiveInt(
            $request->input('id_PRODUTO'),
            $request->input('id_produto'),
            $request->input('id'),
        );

        if ($churchId === null || $productId === null) {
            return redirect()
                ->route('migration.products.index', $this->buildProductReturnQuery($request, $churchId))
                ->with('status', 'Parâmetros inválidos.')
                ->with('status_type', 'error');
        }

        $products->clearEdits($productId, $churchId);

        return redirect()
            ->route('migration.products.index', $this->buildProductReturnQuery($request, $churchId))
            ->with('status', 'Edições limpas com sucesso!')
            ->with('status_type', 'success');
    }

    public function churchesEdit(Request $request): RedirectResponse
    {
        $churchId = $this->firstPositiveInt($request->query('id'));

        if ($churchId === null) {
            return redirect()
                ->route('migration.churches.index')
                ->with('status', 'Igreja não informada para edição.')
                ->with('status_type', 'error');
        }

        return redirect()->route('migration.churches.edit', ['church' => $churchId]);
    }

    public function churchesUpdate(Request $request): RedirectResponse
    {
        $churchId = $this->firstPositiveInt($request->input('id'), $request->query('id'));

        if ($churchId === null) {
            return redirect()
                ->route('migration.churches.index')
                ->with('status', 'Igreja não informada para atualização.')
                ->with('status_type', 'error');
        }

        return redirect()->route('migration.churches.update.post', ['church' => $churchId], 307);
    }

    public function departmentsEdit(Request $request): RedirectResponse
    {
        $departmentId = $this->firstPositiveInt($request->query('id'));

        if ($departmentId === null) {
            return redirect()
                ->route('migration.departments.index')
                ->with('status', 'Dependência não informada para edição.')
                ->with('status_type', 'error');
        }

        return redirect()->route('migration.departments.edit', ['department' => $departmentId]);
    }

    public function departmentsUpdate(Request $request): RedirectResponse
    {
        $departmentId = $this->firstPositiveInt($request->input('id'), $request->query('id'));

        if ($departmentId === null) {
            return redirect()
                ->route('migration.departments.index')
                ->with('status', 'Dependência não informada para atualização.')
                ->with('status_type', 'error');
        }

        return redirect()->route('migration.departments.update.post', ['department' => $departmentId], 307);
    }

    public function departmentsDelete(Request $request): RedirectResponse
    {
        $departmentId = $this->firstPositiveInt($request->input('id'), $request->query('id'));

        if ($departmentId === null) {
            return redirect()
                ->route('migration.departments.index')
                ->with('status', 'Dependência não informada para exclusão.')
                ->with('status_type', 'error');
        }

        return redirect()->route('migration.departments.destroy.post', ['department' => $departmentId], 307);
    }

    public function usersEdit(Request $request): RedirectResponse
    {
        $userId = $this->firstPositiveInt($request->query('id'));

        if ($userId === null) {
            return redirect()
                ->route('migration.users.index')
                ->with('status', 'Usuário não informado para edição.')
                ->with('status_type', 'error');
        }

        return redirect()->route('migration.users.edit', ['user' => $userId]);
    }

    public function usersUpdate(Request $request): RedirectResponse
    {
        $userId = $this->firstPositiveInt($request->input('id'), $request->query('id'));

        if ($userId === null) {
            return redirect()
                ->route('migration.users.index')
                ->with('status', 'Usuário não informado para atualização.')
                ->with('status_type', 'error');
        }

        return redirect()->route('migration.users.update.post', ['user' => $userId], 307);
    }

    public function usersDelete(Request $request): RedirectResponse
    {
        $userId = $this->firstPositiveInt($request->input('id'), $request->query('id'));

        if ($userId === null) {
            return redirect()
                ->route('migration.users.index')
                ->with('status', 'Usuário não informado para exclusão.')
                ->with('status_type', 'error');
        }

        return redirect()->route('migration.users.destroy.post', ['user' => $userId], 307);
    }

    public function assetTypesDelete(Request $request): RedirectResponse
    {
        $assetTypeId = $this->firstPositiveInt($request->input('id'), $request->query('id'));

        if ($assetTypeId === null) {
            return redirect()
                ->route('migration.asset-types.index')
                ->with('status', 'Tipo de bem não informado para exclusão.')
                ->with('status_type', 'error');
        }

        return redirect()->route('migration.asset-types.destroy.post', ['assetType' => $assetTypeId], 307);
    }

    public function usersShow(Request $request): RedirectResponse
    {
        $userId = $this->firstPositiveInt($request->query('id'));

        if ($userId === null) {
            return redirect()
                ->route('migration.users.index')
                ->with('status', 'Usuário não informado.')
                ->with('status_type', 'error');
        }

        return redirect()->route('migration.users.edit', ['user' => $userId]);
    }

    public function usersSelectChurch(
        Request $request,
        LegacyAuthSessionServiceInterface $auth,
    ): JsonResponse {
        $payload = $request->json()->all();
        $churchId = (int) ($payload['comum_id'] ?? $request->input('comum_id', 0));

        if ($churchId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'ID da comum inválido',
            ], 400);
        }

        try {
            $auth->switchChurch($churchId);
            $request->session()->put('comum_id', $churchId);
        } catch (\RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Comum selecionada com sucesso',
        ]);
    }

    public function reportsView(Request $request): RedirectResponse
    {
        $formulario = str_replace('-', '.', trim((string) $request->query('formulario', '')));

        if ($formulario === '') {
            return redirect()
                ->route('migration.reports.index', $this->filteredQuery($request))
                ->with('status', 'Formulário não informado.')
                ->with('status_type', 'error');
        }

        $query = $this->filteredQuery($request);
        unset($query['formulario']);

        return redirect()->route('migration.reports.show', [
            'formulario' => $formulario,
            ...$query,
        ]);
    }

    public function spreadsheetsView(Request $request): RedirectResponse
    {
        return redirect()->route('migration.products.index', $this->filteredQuery($request));
    }

    public function spreadsheetsPreview(Request $request): RedirectResponse
    {
        $importacaoId = $this->firstPositiveInt($request->query('id'));

        if ($importacaoId === null) {
            return redirect()
                ->route('migration.spreadsheets.create')
                ->with('status', 'Importação não informada.')
                ->with('status_type', 'error');
        }

        return redirect()->route('migration.spreadsheets.preview', ['importacao' => $importacaoId]);
    }

    /**
     * @return array<string, scalar>
     */
    private function filteredQuery(Request $request): array
    {
        return array_filter(
            $request->query(),
            static fn (mixed $value): bool => is_scalar($value) && $value !== '',
        );
    }

    private function firstPositiveInt(mixed ...$values): ?int
    {
        foreach ($values as $value) {
            $candidate = (int) $value;
            if ($candidate > 0) {
                return $candidate;
            }
        }

        return null;
    }

    private function resolveChurchId(Request $request): ?int
    {
        return $this->firstPositiveInt(
            $request->input('comum_id'),
            $request->query('comum_id'),
            $request->session()->get('comum_id'),
        );
    }

    /**
     * @return array<string, scalar>
     */
    private function buildProductReturnQuery(Request $request, ?int $churchId): array
    {
        $page = max(1, (int) $request->input('pagina', $request->query('pagina', '1')));

        return array_filter([
            'comum_id' => $churchId,
            'pagina' => $page > 1 ? $page : null,
            'busca' => $this->resolveProductSearch($request),
            'dependencia_id' => $this->firstPositiveInt($request->input('dependencia_id'), $request->input('dependencia'), $request->query('dependencia_id'), $request->query('dependencia')),
            'tipo_bem_id' => $this->firstPositiveInt($request->input('tipo_bem_id'), $request->query('tipo_bem_id')),
            'status' => trim((string) $request->input('status', $request->query('status', ''))),
            'somente_novos' => $request->boolean('somente_novos') ? 1 : null,
        ], static fn (mixed $value): bool => is_scalar($value) && $value !== '');
    }

    private function resolveProductSearch(Request $request): string
    {
        $search = trim((string) $request->input('busca', $request->query('busca', '')));

        if ($search !== '') {
            return $search;
        }

        $search = trim((string) $request->input('nome', $request->query('nome', '')));

        if ($search !== '') {
            return $search;
        }

        return trim((string) $request->input('codigo', $request->query('codigo', $request->query('filtro_codigo', ''))));
    }

    private function resolveChurchCode(int $churchId): ?string
    {
        if ($churchId <= 0) {
            return null;
        }

        $church = $this->auth->availableChurches()->firstWhere('id', $churchId);

        if ($church === null) {
            return null;
        }

        $code = trim((string) ($church->codigo ?? ''));

        return $code !== '' ? $code : null;
    }

    private function formatManualLabelCode(string $churchCode, string $number): string
    {
        $normalizedNumber = str_pad(trim($number), 6, '0', STR_PAD_LEFT);

        return strtoupper(trim($churchCode)) . '/' . $normalizedNumber;
    }

    private function productJsonOrRedirectError(Request $request, string $message, int $statusCode): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], $statusCode);
        }

        return redirect()
            ->route('migration.products.index', $this->buildProductReturnQuery($request, $this->resolveChurchId($request)))
            ->with('status', $message)
            ->with('status_type', 'error');
    }

    /**
     * @param array<string, scalar> $returnQuery
     */
    private function productJsonOrRedirectSuccess(Request $request, string $message, array $returnQuery): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'sucesso' => true,
                'mensagem' => $message,
            ]);
        }

        return redirect()
            ->route('migration.products.index', $returnQuery)
            ->with('status', $message)
            ->with('status_type', 'success');
    }

    /**
     * @param callable(int, int, bool): bool $callback
     */
    private function updateProductFlag(
        Request $request,
        LegacyProductUtilityServiceInterface $products,
        callable $callback,
        string $message,
    ): JsonResponse|RedirectResponse {
        $churchId = $this->resolveChurchId($request);
        $productId = $this->firstPositiveInt($request->input('produto_id'));

        if ($churchId === null || $productId === null) {
            return $this->productJsonOrRedirectError($request, 'Parâmetros inválidos.', 400);
        }

        $rawValue = $request->input('checado', $request->input('imprimir', 0));
        $updated = $callback($productId, $churchId, (int) $rawValue === 1);

        if (!$updated) {
            return $this->productJsonOrRedirectError($request, 'Produto não encontrado para a igreja selecionada.', 404);
        }

        return $this->productJsonOrRedirectSuccess(
            $request,
            $message,
            $this->buildProductReturnQuery($request, $churchId),
        );
    }
}
