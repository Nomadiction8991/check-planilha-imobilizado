<?php

declare(strict_types=1);

namespace App\Sync;

use Illuminate\Support\Facades\DB;

/**
 * Agente do notebook: envia o que mudou aqui e recebe o que mudou no servidor.
 *
 * Envia só as alterações com origem local, a partir do último envio confirmado. Uma alteração
 * recusada pelo servidor segura o cursor: ela e as seguintes são enviadas de novo na próxima vez.
 * Recebe a partir do último cursor e aplica tudo sem gerar registro (ver NotebookChangeApplier).
 */
final class NotebookAgent
{
    public function __construct(
        private readonly SyncState $estado,
        private readonly NotebookChangeApplier $aplicador,
        private readonly SensitiveFields $camposCifrados,
    ) {
    }

    /**
     * @return array{enviadas: int, recebidas: int, erros: list<array{uuid: string, motivo: string}>}
     */
    public function executar(SyncTransport $transporte): array
    {
        [$enviadas, $erros] = $this->enviarPendencias($transporte);

        return [
            'enviadas' => $enviadas,
            'recebidas' => $this->receberMudancas($transporte),
            'erros' => $erros,
        ];
    }

    /**
     * @return array{0: int, 1: list<array{uuid: string, motivo: string}>}
     */
    private function enviarPendencias(SyncTransport $transporte): array
    {
        $linhas = DB::table((string) config('sincronizacao.tabela_registro'))
            ->where('id', '>', $this->estado->inteiro(SyncState::ULTIMO_ENVIADO))
            ->where('origem', 'local')
            ->orderBy('id')
            ->limit(SyncService::LIMITE_POR_ENVIO)
            ->get();

        if ($linhas->isEmpty()) {
            return [0, []];
        }

        $alteracoes = $linhas->map(fn (object $linha): array => $this->paraEnvio($linha))->values()->all();
        $resposta = $transporte->enviar($alteracoes);

        $recusadas = collect($resposta['erros'] ?? [])->keyBy('uuid');

        // O cursor avança só até a alteração anterior à primeira recusada.
        $cursorNovo = null;
        $recusouAlguma = false;

        foreach ($linhas as $linha) {
            if ($recusadas->has((string) $linha->uuid)) {
                $recusouAlguma = true;

                continue;
            }

            if (! $recusouAlguma) {
                $cursorNovo = (int) $linha->id;
            }
        }

        if ($cursorNovo !== null) {
            $this->estado->definir(SyncState::ULTIMO_ENVIADO, $cursorNovo);
        }

        return [count($alteracoes), array_values($recusadas->all())];
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

    /**
     * Campos cifrados saem em texto puro: o servidor os cifra com a chave dele.
     *
     * @return array<string, mixed>
     */
    private function paraEnvio(object $linha): array
    {
        $tabela = (string) $linha->tabela;
        $antes = $this->decodificar($linha->antes);
        $depois = $this->decodificar($linha->depois);

        return [
            'uuid' => (string) $linha->uuid,
            'tabela' => $tabela,
            'chave' => (string) $linha->chave,
            'operacao' => (string) $linha->operacao,
            'antes' => $antes === null ? null : $this->camposCifrados->decifrar($tabela, $antes),
            'depois' => $depois === null ? null : $this->camposCifrados->decifrar($tabela, $depois),
            'criado_em' => (string) $linha->criado_em,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodificar(mixed $valor): ?array
    {
        if ($valor === null) {
            return null;
        }

        $decodificado = is_string($valor) ? json_decode($valor, true) : $valor;

        return is_array($decodificado) ? $decodificado : null;
    }
}
