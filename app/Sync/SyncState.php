<?php

declare(strict_types=1);

namespace App\Sync;

use Illuminate\Support\Facades\DB;

/**
 * Cursores do notebook: até onde já enviou e até onde já recebeu.
 * Fica na tabela sincronizacao_estado, que é só do notebook e não sincroniza.
 */
final class SyncState
{
    public const ULTIMO_ENVIADO = 'ultimo_enviado';

    public const ULTIMO_RECEBIDO = 'ultimo_recebido';

    public function inteiro(string $chave): int
    {
        $valor = DB::table('sincronizacao_estado')->where('chave', $chave)->value('valor');

        return (int) ($valor ?? 0);
    }

    public function definir(string $chave, int $valor): void
    {
        DB::table('sincronizacao_estado')->updateOrInsert(
            ['chave' => $chave],
            ['valor' => (string) $valor],
        );
    }
}
