<?php

declare(strict_types=1);

namespace App\Sync;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Importa a cópia do servidor no notebook. Substitui os dados de negócio do notebook.
 *
 * Esvazia as tabelas (filhos primeiro) e grava tudo sem registrar alterações, porque
 * o que chega do servidor não é uma alteração do notebook. Ao final, o cursor de
 * recebimento começa no ponto em que a cópia foi tirada.
 */
final class CopyImporter
{
    public function __construct(
        private readonly SyncState $estado,
    ) {
    }

    /**
     * @param  iterable<int, string>  $linhas  Linhas NDJSON geradas por CopyExporter.
     */
    public function importar(iterable $linhas): void
    {
        $cursor = 0;

        DB::transaction(function () use ($linhas, &$cursor): void {
            $this->suprimirRegistro(true);

            try {
                $this->esvaziarTabelas();

                foreach ($linhas as $linha) {
                    $registro = json_decode(trim($linha), true);

                    if (! is_array($registro)) {
                        continue;
                    }

                    if (($registro['tipo'] ?? null) === 'cabecalho') {
                        $cursor = (int) ($registro['cursor'] ?? 0);

                        continue;
                    }

                    if (($registro['tipo'] ?? null) === 'linha') {
                        $this->gravar((string) $registro['tabela'], (array) $registro['dados']);
                    }
                }
            } finally {
                $this->suprimirRegistro(false);
            }
        });

        $this->estado->definir(SyncState::ULTIMO_RECEBIDO, $cursor);
        $this->estado->definir(SyncState::ULTIMO_ENVIADO, 0);
    }

    private function esvaziarTabelas(): void
    {
        foreach (array_reverse((array) config('sincronizacao.ordem_da_copia')) as $tabela) {
            DB::table((string) $tabela)->delete();
        }
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    private function gravar(string $tabela, array $dados): void
    {
        if (! in_array($tabela, (array) config('sincronizacao.tabelas'), true)) {
            return;
        }

        if ($tabela === 'configuracoes' && isset($dados['mail_password'])) {
            // Guarda a senha de novo com a chave deste notebook.
            $dados['mail_password'] = Crypt::encryptString((string) $dados['mail_password']);
        }

        DB::table($tabela)->insert($dados);
    }

    private function suprimirRegistro(bool $ligar): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET @sincronizacao_aplicando = ' . ($ligar ? '1' : 'NULL'));
        }
    }
}
