<?php

declare(strict_types=1);

namespace App\Sync;

use Generator;
use Illuminate\Support\Facades\DB;

/**
 * Gera a cópia completa do servidor: uma linha JSON por registro (formato NDJSON).
 *
 * A primeira linha diz até onde a cópia vai (cursor do registro de alterações), e a última
 * marca o fim. Se a conexão cair no meio, o notebook não recebe a marca de fim e desfaz tudo.
 * Campos cifrados saem em texto puro, dentro do HTTPS, e o notebook os cifra com a própria chave.
 */
final class CopyExporter
{
    public function __construct(
        private readonly SensitiveFields $camposCifrados,
    ) {
    }

    /**
     * @return Generator<int, string>
     */
    public function linhas(): Generator
    {
        $cursor = (int) DB::table((string) config('sincronizacao.tabela_registro'))->max('id');

        yield $this->linha(['tipo' => 'cabecalho', 'versao' => 1, 'cursor' => $cursor]);

        $total = 0;

        foreach ((array) config('sincronizacao.ordem_da_copia') as $tabela) {
            $consulta = DB::table((string) $tabela);

            foreach (TableKeys::primaryKey((string) $tabela) as $coluna) {
                $consulta->orderBy($coluna);
            }

            foreach ($consulta->cursor() as $registro) {
                $dados = $this->camposCifrados->decifrar((string) $tabela, (array) $registro);

                yield $this->linha(['tipo' => 'linha', 'tabela' => $tabela, 'dados' => $dados]);
                $total++;
            }
        }

        yield $this->linha(['tipo' => 'fim', 'linhas' => $total]);
    }

    /**
     * @param  array<string, mixed>  $registro
     */
    private function linha(array $registro): string
    {
        return json_encode($registro, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
    }
}
