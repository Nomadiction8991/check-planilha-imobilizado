<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\LegacyReportServiceInterface;
use App\Contracts\LegacyTransferServiceInterface;
use App\Models\Legacy\Comum;
use App\Models\Legacy\Dependencia;
use App\Models\Legacy\Produto;
use App\Models\Legacy\Transferencia;
use App\Services\LegacyTransferFormService;
use App\Support\Modo\EstadoModo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use RuntimeException;

class LegacyTransferController extends Controller
{
    public function __construct(
        private readonly LegacyTransferServiceInterface $transferencias,
        private readonly LegacyTransferFormService $formularios,
        private readonly LegacyReportServiceInterface $reports,
        private readonly EstadoModo $estado,
    ) {
    }

    public function index(): View|RedirectResponse
    {
        $comumId = $this->igrejaAtual();

        if ($comumId <= 0) {
            return $this->semIgreja();
        }

        return view('transfers.index', [
            'igreja' => Comum::query()->find($comumId),
            'pendentes' => $this->transferencias->pendentesDeCodigo($comumId),
            'historico' => $this->transferencias->historicoDaIgreja($comumId),
        ]);
    }

    /**
     * Página de transferência a partir do próprio bem: a igreja atual é a do bem.
     */
    public function produto(Request $request, Produto $product): View|RedirectResponse
    {
        if (! $this->igrejaEstaNoEscopo((int) $product->comum_id)) {
            return redirect()
                ->route('migration.products.index')
                ->with('status', 'Este bem está fora do seu escopo de igrejas.')
                ->with('status_type', 'error');
        }

        $igrejasDeDestino = $this->reports->churchOptions()
            ->reject(static fn (object $igreja): bool => (int) $igreja->id === (int) $product->comum_id)
            ->values();

        // Só aceita como destino uma igreja da lista, para não mostrar os locais de igreja fora do escopo.
        $destino = $igrejasDeDestino->firstWhere('id', $request->integer('comum_destino_id'));

        return view('transfers.produto', [
            'bem' => $product,
            'origem' => Comum::query()->find((int) $product->comum_id),
            'igrejasDeDestino' => $igrejasDeDestino,
            'destino' => $destino,
            'dependencias' => $destino !== null
                ? Dependencia::query()->where('comum_id', $destino->id)->orderBy('descricao')->get()
                : collect(),
        ]);
    }

    public function storeProduto(Request $request, Produto $product): RedirectResponse
    {
        $dados = $request->validate([
            'comum_destino_id' => ['required', 'integer', 'min:1'],
            'dependencia_destino_id' => ['required', 'integer', 'min:1'],
        ]);

        if ($this->servidorIndisponivel()) {
            return $this->semServidor();
        }

        try {
            $transferencia = $this->transferencias->transferir(
                (int) $product->comum_id,
                (int) $dados['comum_destino_id'],
                (int) $dados['dependencia_destino_id'],
                [(int) $product->id_produto],
                (int) Session::get('usuario_id', 0),
            );
        } catch (RuntimeException $erro) {
            return back()
                ->with('status', $erro->getMessage())
                ->with('status_type', 'error');
        }

        return redirect()
            ->route('migration.transfers.show', $transferencia)
            ->with('status', 'Bem transferido. Ele ficou sem código até a igreja de destino informar o código novo.')
            ->with('status_type', 'success');
    }

    public function storeCodigo(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'produto_id' => ['required', 'integer', 'min:1'],
            'codigo' => ['required', 'string', 'max:50'],
        ]);

        if ($this->servidorIndisponivel()) {
            return $this->semServidor();
        }

        try {
            $this->transferencias->informarCodigo(
                (int) $dados['produto_id'],
                $this->igrejaAtual(),
                (string) $dados['codigo'],
            );
        } catch (RuntimeException $erro) {
            return back()
                ->with('status', $erro->getMessage())
                ->with('status_type', 'error');
        }

        return redirect()
            ->route('migration.transfers.index')
            ->with('status', 'Código salvo. O bem foi enviado para a lista de etiquetas.')
            ->with('status_type', 'success');
    }

    public function show(Transferencia $transferencia): View
    {
        return view('transfers.show', [
            'transferencia' => $transferencia->load('itens'),
            'origem' => Comum::query()->find($transferencia->comum_origem_id),
            'destino' => Comum::query()->find($transferencia->comum_destino_id),
            'local' => Dependencia::query()->find($transferencia->dependencia_destino_id),
            'igrejaAtual' => $this->igrejaAtual(),
        ]);
    }

    public function formulario(Transferencia $transferencia, string $tipo): View|RedirectResponse
    {
        // Cada formulário é da igreja que o emite: a saída é da origem, a entrada é do destino.
        $emitente = $tipo === 'saida'
            ? (int) $transferencia->comum_origem_id
            : (int) $transferencia->comum_destino_id;

        if ($this->igrejaAtual() !== $emitente) {
            return redirect()
                ->route('migration.transfers.show', $transferencia)
                ->with('status', 'Este formulário é de outra igreja. Troque de igreja para abri-lo.')
                ->with('status_type', 'error');
        }

        try {
            $previa = $this->formularios->previa($transferencia, $tipo);
        } catch (RuntimeException $erro) {
            return redirect()
                ->route('migration.transfers.show', $transferencia)
                ->with('status', $erro->getMessage())
                ->with('status_type', 'error');
        }

        return view('transfers.formulario', [
            'previa' => $previa,
            'transferencia' => $transferencia,
            'tipo' => $tipo,
        ]);
    }

    private function igrejaAtual(): int
    {
        return (int) Session::get('comum_id', 0);
    }

    private function igrejaEstaNoEscopo(int $comumId): bool
    {
        return $this->reports->churchOptions()->contains(
            static fn (object $igreja): bool => (int) $igreja->id === $comumId,
        );
    }

    private function servidorIndisponivel(): bool
    {
        return (bool) config('modo_igreja.ativo') && ! $this->estado->estaOnline();
    }

    private function semIgreja(): RedirectResponse
    {
        return redirect()
            ->route('migration.reports.index')
            ->with('status', 'Selecione uma igreja para trabalhar com transferências.')
            ->with('status_type', 'error');
    }

    private function semServidor(): RedirectResponse
    {
        return back()
            ->withInput()
            ->with('status', 'Sem conexão com o servidor. A transferência só pode ser registrada quando o servidor estiver online.')
            ->with('status_type', 'error');
    }
}
