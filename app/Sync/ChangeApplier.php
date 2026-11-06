<?php

declare(strict_types=1);

namespace App\Sync;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Aplica no servidor uma alteração vinda de um notebook.
 *
 * Regras:
 * - só aplica tabelas que sincronizam (config sincronizacao.tabelas);
 * - só mexe nos campos que o notebook mudou, e só se o servidor não mudou esses campos;
 * - se o servidor mudou o mesmo campo, vira conflito. Nas tabelas de servidor_vence,
 *   a mudança do notebook é descartada;
 * - campos cifrados chegam em texto puro: são comparados e gravados com a chave do servidor.
 */
final class ChangeApplier
{
    public const APLICADA = 'aplicada';

    public const IGNORADA = 'ignorada';

    public const CONFLITO = 'conflito';

    public const ERRO = 'erro';

    public function __construct(
        private readonly SensitiveFields $camposCifrados,
    ) {
    }

    /**
     * @param  array<string, mixed>  $alteracao
     * @return array{status: string, motivo?: string, campos?: list<string>}
     */
    public function aplicar(array $alteracao): array
    {
        $tabela = (string) $alteracao['tabela'];
        $operacao = (string) $alteracao['operacao'];

        if (! in_array($tabela, (array) config('sincronizacao.tabelas'), true)) {
            return ['status' => self::ERRO, 'motivo' => "A tabela {$tabela} não sincroniza."];
        }

        $antes = (array) ($alteracao['antes'] ?? []);
        $depois = (array) ($alteracao['depois'] ?? []);
        $chavePrimaria = TableKeys::primaryKey($tabela);
        $consulta = TableKeys::query($tabela, $chavePrimaria, (string) $alteracao['chave']);
        $linha = (clone $consulta)->first();
        $atual = $linha === null ? null : $this->camposCifrados->decifrar($tabela, (array) $linha);
        $servidorVence = in_array($tabela, (array) config('sincronizacao.servidor_vence'), true);

        return match ($operacao) {
            'insert' => $this->inserir($tabela, $chavePrimaria, $atual, $depois),
            'update' => $this->atualizar($tabela, $consulta, $chavePrimaria, $atual, $antes, $depois, $servidorVence),
            'delete' => $this->apagar($consulta, $atual, $antes, $servidorVence),
            default => ['status' => self::ERRO, 'motivo' => "Operação desconhecida: {$operacao}."],
        };
    }

    /**
     * @param  list<string>  $chavePrimaria
     * @param  array<string, mixed>|null  $atual
     * @param  array<string, mixed>  $depois
     * @return array{status: string, motivo?: string}
     */
    private function inserir(string $tabela, array $chavePrimaria, ?array $atual, array $depois): array
    {
        if ($atual !== null) {
            return $chavePrimaria === []
                ? ['status' => self::IGNORADA, 'motivo' => 'a tabela já tem o seu registro único no servidor']
                : ['status' => self::CONFLITO, 'motivo' => 'o servidor já tem um registro com esta chave'];
        }

        try {
            DB::table($tabela)->insert($this->camposCifrados->cifrar($tabela, $depois));
        } catch (QueryException $erro) {
            // O motivo para a pessoa é simples; o erro técnico completo vai para o log do servidor.
            report($erro);

            return ['status' => self::CONFLITO, 'motivo' => 'o servidor já tem um registro com o mesmo valor único'];
        }

        return ['status' => self::APLICADA];
    }

    /**
     * @param  list<string>  $chavePrimaria
     * @param  array<string, mixed>|null  $atual
     * @param  array<string, mixed>  $antes
     * @param  array<string, mixed>  $depois
     * @return array{status: string, motivo?: string, campos?: list<string>}
     */
    private function atualizar(
        string $tabela,
        Builder $consulta,
        array $chavePrimaria,
        ?array $atual,
        array $antes,
        array $depois,
        bool $servidorVence,
    ): array {
        if ($atual === null) {
            return ['status' => self::CONFLITO, 'motivo' => 'o registro não existe mais no servidor'];
        }

        $campos = array_values(array_diff($this->camposAlterados($antes, $depois), $chavePrimaria));

        if ($campos === []) {
            return ['status' => self::IGNORADA, 'motivo' => 'nada mudou'];
        }

        $conflitos = array_values(array_filter(
            $campos,
            fn (string $campo): bool => $this->normalizar($atual[$campo] ?? null) !== $this->normalizar($antes[$campo] ?? null),
        ));

        if ($conflitos !== []) {
            if ($servidorVence) {
                return ['status' => self::IGNORADA, 'motivo' => 'o servidor vence nesta tabela'];
            }

            return ['status' => self::CONFLITO, 'motivo' => 'o servidor mudou os mesmos campos', 'campos' => $conflitos];
        }

        $consulta->update($this->camposCifrados->cifrar($tabela, array_intersect_key($depois, array_flip($campos))));

        return ['status' => self::APLICADA, 'campos' => $campos];
    }

    /**
     * @param  array<string, mixed>  $antes
     * @param  array<string, mixed>|null  $atual
     * @return array{status: string, motivo?: string, campos?: list<string>}
     */
    private function apagar(Builder $consulta, ?array $atual, array $antes, bool $servidorVence): array
    {
        if ($atual === null) {
            return ['status' => self::IGNORADA, 'motivo' => 'o registro já foi apagado no servidor'];
        }

        $mudou = array_values(array_filter(
            array_keys($antes),
            fn (string $campo): bool => $this->normalizar($atual[$campo] ?? null) !== $this->normalizar($antes[$campo]),
        ));

        if ($mudou !== []) {
            if ($servidorVence) {
                return ['status' => self::IGNORADA, 'motivo' => 'o servidor vence nesta tabela'];
            }

            return ['status' => self::CONFLITO, 'motivo' => 'o servidor mudou o registro antes da exclusão', 'campos' => $mudou];
        }

        $consulta->delete();

        return ['status' => self::APLICADA];
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
            if ($this->normalizar($antes[$campo] ?? null) !== $this->normalizar($valor)) {
                $campos[] = (string) $campo;
            }
        }

        return $campos;
    }

    /**
     * Compara valores sem depender do tipo que o JSON trouxe (true, 1 e \"1\" são iguais).
     */
    private function normalizar(mixed $valor): string
    {
        if ($valor === null) {
            return 'null';
        }

        if (is_bool($valor)) {
            return $valor ? '1' : '0';
        }

        return (string) $valor;
    }
}
