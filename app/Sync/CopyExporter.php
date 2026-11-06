<?php

declare(strict_types=1);

namespace App\Sync;

use Generator;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Gera a cópia completa do servidor: uma linha JSON por registro (formato NDJSON).
 *
 * A primeira linha diz até onde a cópia vai (cursor do registro de alterações).
 * Assim o notebook continua a sincronização dali, sem perder nem repetir nada.
 * A senha do e-mail sai em texto dentro do HTTPS, porque cada instalação tem a própria chave.
 */
final class CopyExporter
{
    /**
     * @return Generator<int, string>
     */
    public function linhas(): Generator
    {
        $cursor = (int) DB::table((string) config('sincronizacao.tabela_registro'))->max('id');

        yield $this->linha(['tipo' => 'cabecalho', 'versao' => 1, 'cursor' => $cursor]);

        foreach ((array) config('sincronizacao.ordem_da_copia') as $tabela) {
            $consulta = DB::table((string) $tabela);

            foreach (TableKeys::primaryKey((string) $tabela) as $coluna) {
                $consulta->orderBy($coluna);
            }

            foreach ($consulta->cursor() as $registro) {
                $dados = (array) $registro;

                if ($tabela === 'configuracoes' && isset($dados['mail_password'])) {
                    $dados['mail_password'] = $this->decifrar((string) $dados['mail_password']);
                }

                yield $this->linha(['tipo' => 'linha', 'tabela' => $tabela, 'dados' => $dados]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $registro
     */
    private function linha(array $registro): string
    {
        return json_encode($registro, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
    }

    private function decifrar(string $valor): ?string
    {
        try {
            return Crypt::decryptString($valor);
        } catch (DecryptException) {
            // Valor que não abre com a chave do servidor não deve sair da cópia.
            return null;
        }
    }
}
