<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Connection\OfflineModeService;
use App\Support\Connection\QrCodeSvg;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

/**
 * Tela "Conexão": liga a rede do notebook para os celulares e mostra os QR codes.
 * Só existe no modo igreja e só para administradores (rotas com legacy.admin).
 */
final class ConexaoController extends Controller
{
    public function __construct(
        private readonly OfflineModeService $modoOffline,
    ) {
    }

    public function index(): View
    {
        $this->garantirModoIgreja();

        $status = $this->modoOffline->status();

        return view('conexao.index', [
            'status' => $status,
            'qrRede' => $status['texto_rede'] === null ? null : QrCodeSvg::de($status['texto_rede']),
            'qrEndereco' => $status['endereco_sistema'] === null ? null : QrCodeSvg::de($status['endereco_sistema']),
        ]);
    }

    public function ligar(): RedirectResponse
    {
        $this->garantirModoIgreja();

        try {
            $this->modoOffline->ligar();
        } catch (RuntimeException $erro) {
            return redirect()
                ->route('migration.conexao.index')
                ->withErrors(['conexao' => $erro->getMessage()]);
        }

        return redirect()
            ->route('migration.conexao.index')
            ->with('status', 'Modo offline ligado. Os celulares já podem se conectar.');
    }

    public function desligar(): RedirectResponse
    {
        $this->garantirModoIgreja();

        try {
            $this->modoOffline->desligar();
        } catch (RuntimeException $erro) {
            return redirect()
                ->route('migration.conexao.index')
                ->withErrors(['conexao' => $erro->getMessage()]);
        }

        return redirect()
            ->route('migration.conexao.index')
            ->with('status', 'Modo offline desligado.');
    }

    private function garantirModoIgreja(): void
    {
        abort_unless((bool) config('modo_igreja.ativo'), 404);
    }
}
