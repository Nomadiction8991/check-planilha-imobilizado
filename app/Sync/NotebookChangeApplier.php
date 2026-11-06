<?php

declare(strict_types=1);

namespace App\Sync;

use Illuminate\Support\Facades\DB;

/**
 * Aplica no notebook as mudanças que chegaram do servidor.
 *
 * Com a variável @sincronizacao_aplicando ligada, os gatilhos não registram nada,
 * então a mudança recebida não volta ao servidor como se tivesse sido feita aqui.
 * O servidor já decidiu os conflitos, então o valor recebido é o que vale.
 * Campos cifrados chegam em texto puro e são cifrados com a chave deste notebook.
 */
final class NotebookChangeApplier
{
    public function __construct(
        private readonly SensitiveFields $camposCifrados,
    ) {
    }

    /**
     * @param  list<array<string, mixed>>  $alteracoes
     */
    public function aplicarLote(array $alteracoes): void
    {
        if ($alteracoes === []) {
            return;
        }

        DB::transaction(function () use ($alteracoes): void {
            $this->suprimirRegistro(true);

            try {
                foreach ($alteracoes as $alteracao) {
                    $this->aplicar($alteracao);
                }
            } finally {
                $this->suprimirRegistro(false);
            }
        });
    }

    /**
     * @param  array<string, mixed>  $alteracao
     */
    private function aplicar(array $alteracao): void
    {
        $tabela = (string) $alteracao['tabela'];

        if (! in_array($tabela, (array) config('sincronizacao.tabelas'), true)) {
            return;
        }

        $operacao = (string) $alteracao['operacao'];
        $antes = (array) ($alteracao['antes'] ?? []);
        $depois = (array) ($alteracao['depois'] ?? []);
        $chavePrimaria = TableKeys::primaryKey($tabela);
        $consulta = TableKeys::query($tabela, $chavePrimaria, (string) $alteracao['chave']);

        if ($operacao === 'delete') {
            $consulta->delete();

            return;
        }

        $atual = (clone $consulta)->first();

        if ($atual === null) {
            DB::table($tabela)->insert($this->camposCifrados->cifrar($tabela, $depois));

            return;
        }

        $campos = $operacao === 'insert'
            ? array_keys($depois)
            : $this->camposAlterados($antes, $depois);

        $campos = array_values(array_diff($campos, $chavePrimaria));

        if ($campos === []) {
            return;
        }

        $consulta->update($this->camposCifrados->cifrar($tabela, array_intersect_key($depois, array_flip($campos))));
    }

    /**
     * @param  array<string, mixed>  $antes
     * @param  array<string, mixed>  $depois
     * @return list<string>
     */
    private function camposAlterados(array $antes, array $depois): array
    {
        $campos = [];

        foreach ($depois as $campo => $valor) {
            if (! array_key_exists($campo, $antes) || (string) $antes[$campo] !== (string) $valor) {
                $campos[] = (string) $campo;
            }
        }

        return $campos;
    }

    private function suprimirRegistro(bool $ligar): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET @sincronizacao_aplicando = ' . ($ligar ? '1' : 'NULL'));
        }
    }
}
