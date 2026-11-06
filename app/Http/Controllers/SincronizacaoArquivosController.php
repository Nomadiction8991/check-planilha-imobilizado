<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Sync\ArquivoCorrompidoException;
use App\Sync\AuditLog;
use App\Sync\ConflitoDeArquivoException;
use App\Sync\SpreadsheetStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InvalidArgumentException;
use RuntimeException;

/**
 * Planilhas e histórico de auditoria da sincronização. O middleware de aparelho já garantiu o acesso.
 */
final class SincronizacaoArquivosController extends Controller
{
    public function listarPlanilhas(): JsonResponse
    {
        return response()->json(['arquivos' => $this->planilhas()->listar()]);
    }

    public function gravarPlanilha(Request $request, string $nome): JsonResponse
    {
        try {
            $status = $this->planilhas()->gravar($nome, $request->getContent(), (string) $request->header('X-Sha256', ''));
        } catch (ArquivoCorrompidoException $erro) {
            return response()->json(['erro' => $erro->getMessage()], 422);
        } catch (ConflitoDeArquivoException $erro) {
            return response()->json(['erro' => $erro->getMessage()], 409);
        } catch (InvalidArgumentException $erro) {
            return response()->json(['erro' => $erro->getMessage()], 422);
        }

        return response()->json(['status' => $status]);
    }

    public function baixarPlanilha(string $nome): JsonResponse|Response
    {
        try {
            $arquivo = $this->planilhas()->ler($nome);
        } catch (InvalidArgumentException | RuntimeException) {
            return response()->json(['erro' => 'Planilha não encontrada.'], 404);
        }

        return response($arquivo['conteudo'], 200, [
            'Content-Type' => 'application/octet-stream',
            'X-Sha256' => $arquivo['sha256'],
        ]);
    }

    public function lerAuditoria(): JsonResponse
    {
        return response()->json(['linhas' => $this->auditoria()->linhas()]);
    }

    public function gravarAuditoria(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'linhas' => ['required', 'array', 'max:5000'],
            'linhas.*.sha256' => ['required', 'string', 'size:64'],
            'linhas.*.linha' => ['required', 'string', 'max:20000'],
        ]);

        try {
            $recebidas = $this->auditoria()->acrescentar($dados['linhas']);
        } catch (InvalidArgumentException $erro) {
            return response()->json(['erro' => $erro->getMessage()], 422);
        }

        return response()->json(['recebidas' => $recebidas]);
    }

    private function planilhas(): SpreadsheetStore
    {
        return new SpreadsheetStore((string) config('sincronizacao.pasta_planilhas'));
    }

    private function auditoria(): AuditLog
    {
        return new AuditLog((string) config('sincronizacao.arquivo_auditoria'));
    }
}
