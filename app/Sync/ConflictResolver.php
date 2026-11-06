<?php

declare(strict_types=1);

namespace App\Sync;

use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Resolve os conflitos da sincronização: quando os dois lados mudaram o mesmo dado,
 * uma pessoa escolhe qual valor fica.
 *
 * - Manter o do notebook: o valor do notebook vai para o servidor. A mudança entra no
 *   registro do servidor e chega aos outros notebooks normalmente.
 * - Manter o do servidor: o servidor não muda. Como a mudança dele ainda não chegou
 *   a este notebook, o notebook recebe esse valor no próximo recebimento.
 */
final class ConflictResolver
{
    public const MANTIDO_NOTEBOOK = 'mantido_notebook';

    public const MANTIDO_SERVIDOR = 'mantido_servidor';

    private const VALOR_PROTEGIDO = '(valor protegido)';

    public function __construct(
        private readonly SensitiveFields $camposCifrados,
    ) {
    }

    /**
     * Conflitos ainda sem decisão, com os valores lado a lado.
     *
     * @return Collection<int, array{id: int, tabela: string, chave: string, motivo: string, campos: list<array{campo: string, servidor: ?string, notebook: ?string}>}>
     */
    public function pendentes(): Collection
    {
        return DB::table('sincronizacao_conflitos')
            ->where('status', 'pendente')
            ->orderBy('id')
            ->get()
            ->map(fn (object $conflito): array => $this->resumo($conflito));
    }

    /**
     * Aplica no servidor o valor do notebook. Devolve false se o conflito não existe ou já foi resolvido.
     *
     * @throws ConflitoNaoAplicavelException quando o servidor recusa a inclusão de um registro novo
     */
    public function manterDoNotebook(int $conflitoId): bool
    {
        return DB::transaction(function () use ($conflitoId): bool {
            $conflito = $this->pendente($conflitoId);

            if ($conflito === null) {
                return false;
            }

            $tabela = (string) $conflito->tabela;
            $chavePrimaria = TableKeys::primaryKey($tabela);
            $consulta = TableKeys::query($tabela, $chavePrimaria, (string) $conflito->chave);

            if ($conflito->operacao === 'delete') {
                $consulta->delete();
            } elseif ($conflito->operacao === 'insert') {
                // O servidor recusou a inclusão. Manter o valor do notebook é tentar incluir de novo.
                $this->incluirDoNotebook($tabela, $this->decodificar($conflito->depois) ?? []);
            } else {
                $antes = $this->decodificar($conflito->antes) ?? [];
                $depois = $this->decodificar($conflito->depois) ?? [];
                $campos = array_values(array_diff($this->camposAlterados($antes, $depois), $chavePrimaria));

                if ($campos !== []) {
                    $consulta->update($this->camposCifrados->cifrar($tabela, array_intersect_key($depois, array_flip($campos))));
                }
            }

            $this->marcar($conflitoId, self::MANTIDO_NOTEBOOK);

            return true;
        });
    }

    /**
     * Mantém o valor do servidor. Devolve false se o conflito não existe ou já foi resolvido.
     */
    public function manterDoServidor(int $conflitoId): bool
    {
        return DB::transaction(function () use ($conflitoId): bool {
            if ($this->pendente($conflitoId) === null) {
                return false;
            }

            $this->marcar($conflitoId, self::MANTIDO_SERVIDOR);

            return true;
        });
    }

    /**
     * Tenta incluir o registro do notebook. Se o servidor continuar recusando, o conflito fica pendente.
     *
     * @param  array<string, mixed>  $depois
     *
     * @throws ConflitoNaoAplicavelException quando o servidor recusa a inclusão
     */
    private function incluirDoNotebook(string $tabela, array $depois): void
    {
        try {
            DB::table($tabela)->insert($this->camposCifrados->cifrar($tabela, $depois));
        } catch (QueryException $erro) {
            report($erro);

            throw new ConflitoNaoAplicavelException(
                'O servidor não aceitou este valor. Mantenha o valor do servidor; para incluir o item de novo, cadastre-o outra vez no sistema.',
            );
        }
    }

    private function pendente(int $conflitoId): ?object
    {
        return DB::table('sincronizacao_conflitos')
            ->where('id', $conflitoId)
            ->where('status', 'pendente')
            ->lockForUpdate()
            ->first();
    }

    private function marcar(int $conflitoId, string $status): void
    {
        DB::table('sincronizacao_conflitos')
            ->where('id', $conflitoId)
            ->update(['status' => $status, 'updated_at' => now()]);
    }

    /**
     * @return array{id: int, tabela: string, chave: string, motivo: string, campos: list<array{campo: string, servidor: ?string, notebook: ?string}>}
     */
    private function resumo(object $conflito): array
    {
        $tabela = (string) $conflito->tabela;
        $chavePrimaria = TableKeys::primaryKey($tabela);
        $linha = TableKeys::query($tabela, $chavePrimaria, (string) $conflito->chave)->first();
        $servidor = $linha === null ? [] : (array) $linha;
        $antes = $this->decodificar($conflito->antes) ?? [];
        $depois = $this->decodificar($conflito->depois) ?? [];
        $campos = array_values(array_diff($this->camposAlterados($antes, $depois), $chavePrimaria));
        $protegidos = $this->camposCifrados->campos($tabela);

        return [
            'id' => (int) $conflito->id,
            'tabela' => $tabela,
            'chave' => (string) $conflito->chave,
            'motivo' => (string) $conflito->motivo,
            'campos' => array_map(fn (string $campo): array => [
                'campo' => $campo,
                'servidor' => $this->mostrar($servidor[$campo] ?? null, in_array($campo, $protegidos, true)),
                'notebook' => $this->mostrar($depois[$campo] ?? null, in_array($campo, $protegidos, true)),
            ], $campos),
        ];
    }

    private function mostrar(mixed $valor, bool $protegido): ?string
    {
        if ($valor === null) {
            return null;
        }

        return $protegido ? self::VALOR_PROTEGIDO : (string) $valor;
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
