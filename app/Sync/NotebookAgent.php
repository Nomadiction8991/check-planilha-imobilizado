<?php

declare(strict_types=1);

namespace App\Sync;

use Illuminate\Support\Facades\DB;

/**
 * Agente do notebook: envia o que mudou aqui e recebe o que mudou no servidor.
 *
 * Envia só as alterações com origem local, a partir do último envio. Recebe a partir do
 * último cursor e aplica tudo sem gerar registro (ver NotebookChangeApplier).
 */
final class NotebookAgent
{
    public function __construct(
        private readonly SyncState $estado,
        private readonly NotebookChangeApplier $aplicador,
    ) {
    }

    /**
     * @return array{enviadas: int, recebidas: int}
     */
    public function executar(SyncTransport $transporte): array
    {
        return [
            'enviadas' => $this->enviarPendencias($transporte),
            'recebidas' => $this->receberMudancas($transporte),
        ];
    }

    private function enviarPendencias(SyncTransport $transporte): int
    {
        $linhas = DB::table('sincronizacao_alteracoes')
            ->where('id', '>', $this->estado->inteiro(SyncState::ULTIMO_ENVIADO))
            ->where('origem', 'local')
            ->orderBy('id')
            ->limit(SyncService::LIMITE_POR_ENVIO)
            ->get();

        if ($linhas->isEmpty()) {
            return 0;
        }

        $alteracoes = $linhas->map(fn (object $linha): array => [
            'uuid' => (string) $linha->uuid,
            'tabela' => (string) $linha->tabela,
            'chave' => (string) $linha->chave,
            'operacao' => (string) $linha->operacao,
            'antes' => $this->decodificar($linha->antes),
            'depois' => $this->decodificar($linha->depois),
            'criado_em' => (string) $linha->criado_em,
        ])->values()->all();

        $transporte->enviar($alteracoes);

        $this->estado->definir(SyncState::ULTIMO_ENVIADO, (int) $linhas->last()->id);

        return count($alteracoes);
    }

    private function receberMudancas(SyncTransport $transporte): int
    {
        $total = 0;

        do {
            $cursorAntes = $this->estado->inteiro(SyncState::ULTIMO_RECEBIDO);
            $resposta = $transporte->receber($cursorAntes);

            $this->aplicador->aplicarLote($resposta['alteracoes']);
            $total += count($resposta['alteracoes']);

            $this->estado->definir(SyncState::ULTIMO_RECEBIDO, (int) $resposta['cursor']);

            // Sem avanço no cursor, parar: evita laço infinito se o servidor repetir a página.
            $avancou = (int) $resposta['cursor'] > $cursorAntes;
        } while ($resposta['mais'] && $avancou);

        return $total;
    }

    private function decodificar(mixed $valor): ?array
    {
        if ($valor === null) {
            return null;
        }

        $decodificado = is_string($valor) ? json_decode($valor, true) : $valor;

        return is_array($decodificado) ? $decodificado : null;
    }
}
