<?php

declare(strict_types=1);

namespace App\Sync;

use Closure;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Cria os gatilhos do banco que registram cada alteração na tabela de registro.
 * Funciona só com MySQL.
 *
 * Para cada tabela sincronizada são criados quatro gatilhos:
 * - AFTER INSERT, AFTER UPDATE e AFTER DELETE, que registram a própria linha;
 * - BEFORE DELETE, que registra os filhos apagados em cascata. O MySQL não dispara
 *   gatilhos nas ações de cascata, então o pai registra os filhos antes da exclusão.
 *
 * Quando a sincronização aplica uma mudança vinda de fora, ela liga a variável
 * @sincronizacao_aplicando na conexão. Com a variável ligada, os gatilhos não
 * gravam nada, e a mudança não volta como se fosse nova.
 */
final class TriggerGenerator
{
    private const VARIAVEL_SUPRESSAO = '@sincronizacao_aplicando';

    private const TAMANHO_MAXIMO_NOME_GATILHO = 64;

    private const PROFUNDIDADE_MAXIMA = 5;

    /** @var list<string> */
    private const SUFIXOS = ['ai', 'au', 'ad', 'bd'];

    public function __construct(
        private readonly string $registryTable = 'sincronizacao_alteracoes',
    ) {
    }

    /**
     * @param  list<string>  $tables
     */
    public function generateFor(array $tables): void
    {
        foreach ($tables as $table) {
            $this->generateForTable($table, $tables);
        }
    }

    /**
     * @param  list<string>  $tables
     */
    public function dropFor(array $tables): void
    {
        foreach ($tables as $table) {
            $this->assertIdentifier($table);

            foreach (self::SUFIXOS as $suffix) {
                DB::unprepared('DROP TRIGGER IF EXISTS `' . $this->triggerName($table, $suffix) . '`');
            }
        }
    }

    /**
     * @param  list<string>  $syncedTables  Tabelas que sincronizam. Cascatas só são registradas para elas.
     */
    public function generateForTable(string $table, array $syncedTables): void
    {
        $this->assertIdentifier($table);

        if ($table === $this->registryTable) {
            throw new InvalidArgumentException('A tabela de registro não pode ter gatilhos de registro.');
        }

        $columns = $this->columnsOf($table);
        $key = $this->primaryKeyOf($table);

        $this->dropFor([$table]);

        DB::unprepared($this->triggerSql(
            $table,
            'ai',
            'AFTER INSERT',
            'insert',
            $this->keyExpression($key, 'NEW'),
            'NULL',
            $this->rowExpression($columns, 'NEW'),
        ));

        DB::unprepared($this->triggerSql(
            $table,
            'au',
            'AFTER UPDATE',
            'update',
            $this->keyExpression($key, 'OLD'),
            $this->rowExpression($columns, 'OLD'),
            $this->rowExpression($columns, 'NEW'),
        ));

        DB::unprepared($this->triggerSql(
            $table,
            'ad',
            'AFTER DELETE',
            'delete',
            $this->keyExpression($key, 'OLD'),
            $this->rowExpression($columns, 'OLD'),
            'NULL',
        ));

        $cascata = $this->cascadeStatements(
            $table,
            static fn (string $referencedColumn): string => 'OLD.`' . $referencedColumn . '`',
            $syncedTables,
            0,
        );

        if ($cascata !== []) {
            DB::unprepared($this->beforeDeleteSql($table, $cascata));
        }
    }

    private function triggerSql(
        string $table,
        string $suffix,
        string $timing,
        string $operation,
        string $key,
        string $before,
        string $after,
    ): string {
        $name = $this->triggerName($table, $suffix);
        $registry = $this->registryTable;
        $suppression = self::VARIAVEL_SUPRESSAO;

        return "CREATE TRIGGER `{$name}` {$timing} ON `{$table}` FOR EACH ROW\n"
            . "BEGIN\n"
            . "  IF {$suppression} IS NULL THEN\n"
            . "    INSERT INTO `{$registry}` (`uuid`, `tabela`, `chave`, `operacao`, `antes`, `depois`, `origem`)\n"
            . "    VALUES (UUID(), '{$table}', CAST({$key} AS CHAR(255)), '{$operation}', {$before}, {$after}, COALESCE(@sincronizacao_origem, 'local'));\n"
            . "  END IF;\n"
            . 'END';
    }

    /**
     * @param  list<string>  $statements
     */
    private function beforeDeleteSql(string $table, array $statements): string
    {
        $name = $this->triggerName($table, 'bd');
        $suppression = self::VARIAVEL_SUPRESSAO;
        $corpo = implode("\n", array_map(static fn (string $statement): string => '    ' . $statement, $statements));

        return "CREATE TRIGGER `{$name}` BEFORE DELETE ON `{$table}` FOR EACH ROW\n"
            . "BEGIN\n"
            . "  IF {$suppression} IS NULL THEN\n"
            . $corpo . "\n"
            . "  END IF;\n"
            . 'END';
    }

    /**
     * Gera os INSERT ... SELECT que registram os filhos afetados por cascata.
     *
     * @param  Closure(string): string  $parentValues  Dado a coluna referenciada do pai, devolve a expressão com os valores dela.
     * @param  list<string>  $syncedTables
     * @return list<string>
     */
    private function cascadeStatements(string $parent, Closure $parentValues, array $syncedTables, int $depth): array
    {
        if ($depth >= self::PROFUNDIDADE_MAXIMA) {
            return [];
        }

        $statements = [];

        foreach ($this->relationsOf($parent) as $relation) {
            $child = $relation['child'];
            $regra = $relation['rule'];

            if (! in_array($child, $syncedTables, true) || ! in_array($regra, ['CASCADE', 'SET NULL'], true)) {
                continue;
            }

            $childColumn = $relation['childColumn'];
            $condition = "`{$child}`.`{$childColumn}` IN (" . ($parentValues)($relation['parentColumn']) . ')';

            $childColumns = $this->columnsOf($child);
            $childPrefix = '`' . $child . '`';
            $childKey = $this->keyExpression($this->primaryKeyOf($child), $childPrefix);
            $before = $this->rowExpression($childColumns, $childPrefix);

            if ($regra === 'CASCADE') {
                $operation = 'delete';
                $after = 'NULL';
            } else {
                $operation = 'update';
                $after = $this->rowExpression($childColumns, $childPrefix, [$childColumn => 'NULL']);
            }

            $statements[] = "INSERT INTO `{$this->registryTable}` (`uuid`, `tabela`, `chave`, `operacao`, `antes`, `depois`, `origem`) "
                . "SELECT UUID(), '{$child}', CAST({$childKey} AS CHAR(255)), '{$operation}', {$before}, {$after}, "
                . "COALESCE(@sincronizacao_origem, 'local') "
                . "FROM `{$child}` WHERE {$condition};";

            if ($regra === 'CASCADE') {
                $childValues = static fn (string $referencedColumn): string => "SELECT `{$child}`.`{$referencedColumn}` "
                    . "FROM `{$child}` WHERE {$condition}";

                array_push(
                    $statements,
                    ...$this->cascadeStatements($child, $childValues, $syncedTables, $depth + 1),
                );
            }
        }

        return $statements;
    }

    /**
     * @param  list<string>  $columns
     * @param  array<string, string>  $overrides  Coluna => expressão SQL que substitui o valor da coluna.
     */
    private function rowExpression(array $columns, string $prefix, array $overrides = []): string
    {
        $pairs = array_map(
            static fn (string $column): string => "'{$column}', " . ($overrides[$column] ?? "{$prefix}.`{$column}`"),
            $columns,
        );

        return 'JSON_OBJECT(' . implode(', ', $pairs) . ')';
    }

    /**
     * Chave da linha: a própria chave primária. Chave composta vira JSON.
     * Tabela sem chave primária (configuracoes) é tratada como registro único.
     *
     * @param  list<string>  $key
     */
    private function keyExpression(array $key, string $prefix): string
    {
        if ($key === []) {
            return "'*'";
        }

        if (count($key) === 1) {
            return "{$prefix}.`{$key[0]}`";
        }

        $pairs = array_map(
            static fn (string $column): string => "'{$column}', {$prefix}.`{$column}`",
            $key,
        );

        return 'JSON_OBJECT(' . implode(', ', $pairs) . ')';
    }

    /**
     * Relações de chave estrangeira em que $parent é a tabela referenciada.
     *
     * @return list<array{child: string, childColumn: string, parentColumn: string, rule: string}>
     */
    private function relationsOf(string $parent): array
    {
        $rows = DB::select(
            'SELECT k.TABLE_NAME AS filho, k.COLUMN_NAME AS coluna_filho, '
            . 'k.REFERENCED_COLUMN_NAME AS coluna_pai, r.DELETE_RULE AS regra '
            . 'FROM information_schema.KEY_COLUMN_USAGE k '
            . 'INNER JOIN information_schema.REFERENTIAL_CONSTRAINTS r '
            . 'ON r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA '
            . 'AND r.CONSTRAINT_NAME = k.CONSTRAINT_NAME '
            . 'AND r.TABLE_NAME = k.TABLE_NAME '
            . 'WHERE k.TABLE_SCHEMA = DATABASE() AND k.REFERENCED_TABLE_NAME = ? '
            . 'ORDER BY k.TABLE_NAME, k.COLUMN_NAME',
            [$parent],
        );

        return array_map(fn (object $row): array => [
            'child' => $this->assertIdentifier((string) $row->filho),
            'childColumn' => $this->assertIdentifier((string) $row->coluna_filho),
            'parentColumn' => $this->assertIdentifier((string) $row->coluna_pai),
            'rule' => (string) $row->regra,
        ], $rows);
    }

    /**
     * @return list<string>
     */
    private function columnsOf(string $table): array
    {
        $rows = DB::select(
            'SELECT COLUMN_NAME AS nome FROM information_schema.COLUMNS '
            . 'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION',
            [$table],
        );

        if ($rows === []) {
            throw new InvalidArgumentException("Tabela não encontrada: {$table}");
        }

        return array_map(fn (object $row): string => $this->assertIdentifier((string) $row->nome), $rows);
    }

    /**
     * @return list<string>
     */
    private function primaryKeyOf(string $table): array
    {
        $rows = DB::select(
            "SELECT COLUMN_NAME AS nome FROM information_schema.STATISTICS "
            . "WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = 'PRIMARY' "
            . 'ORDER BY SEQ_IN_INDEX',
            [$table],
        );

        return array_map(fn (object $row): string => $this->assertIdentifier((string) $row->nome), $rows);
    }

    private function triggerName(string $table, string $suffix): string
    {
        $name = "sinc_{$table}_{$suffix}";

        if (strlen($name) > self::TAMANHO_MAXIMO_NOME_GATILHO) {
            throw new InvalidArgumentException("Nome de gatilho longo demais: {$name}");
        }

        return $name;
    }

    /**
     * Só aceita letras, números e sublinhado. Os nomes vêm do próprio banco ou da
     * configuração, mas são validados antes de entrar no SQL.
     */
    private function assertIdentifier(string $name): string
    {
        if (preg_match('/^[A-Za-z0-9_]{1,64}$/', $name) !== 1) {
            throw new InvalidArgumentException("Nome inválido para o banco: {$name}");
        }

        return $name;
    }
}
