<?php

declare(strict_types=1);

$mysql = new PDO(
    sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        getenv('MYSQL_HOST') ?: '127.0.0.1',
        (int) (getenv('MYSQL_PORT') ?: 3306),
        getenv('MYSQL_DATABASE') ?: 'checkplanilha_source'
    ),
    getenv('MYSQL_USERNAME') ?: 'checkplanilha_migrator',
    getenv('MYSQL_PASSWORD') ?: '',
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_CASE => PDO::CASE_UPPER,
    ]
);

$pgsql = new PDO(
    sprintf(
        'pgsql:host=%s;port=%d;dbname=%s',
        getenv('PG_HOST') ?: '127.0.0.1',
        (int) (getenv('PG_PORT') ?: 5432),
        getenv('PG_DATABASE') ?: 'checkplanilha'
    ),
    getenv('PG_USERNAME') ?: 'checkplanilha',
    getenv('PG_PASSWORD') ?: '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

function qi(string $identifier): string
{
    return '"' . str_replace('"', '""', $identifier) . '"';
}

function ql(string $value): string
{
    return "'" . str_replace("'", "''", $value) . "'";
}

function constraintName(string $prefix, string $table, string $suffix): string
{
    $name = preg_replace('/[^a-zA-Z0-9_]+/', '_', strtolower($prefix . '_' . $table . '_' . $suffix));

    return substr((string) $name, 0, 58);
}

function pgType(array $column): string
{
    $type = strtolower((string) $column['DATA_TYPE']);
    $columnType = strtolower((string) $column['COLUMN_TYPE']);
    $length = $column['CHARACTER_MAXIMUM_LENGTH'];
    $precision = $column['NUMERIC_PRECISION'];
    $scale = $column['NUMERIC_SCALE'];

    if ($type === 'tinyint' && str_starts_with($columnType, 'tinyint(1)')) {
        return 'boolean';
    }

    return match ($type) {
        'int', 'integer', 'mediumint' => 'integer',
        'bigint' => 'bigint',
        'smallint' => 'smallint',
        'tinyint' => 'smallint',
        'varchar' => 'varchar(' . (int) $length . ')',
        'char' => 'char(' . (int) $length . ')',
        'text', 'mediumtext', 'longtext', 'tinytext' => 'text',
        'date' => 'date',
        'datetime', 'timestamp' => 'timestamp without time zone',
        'decimal' => 'numeric(' . (int) $precision . ',' . (int) $scale . ')',
        'double', 'float' => 'double precision',
        'json' => 'jsonb',
        'enum' => 'varchar(32)',
        default => 'text',
    };
}

function pgDefault(?string $default, string $pgType): ?string
{
    if ($default === null || strtoupper($default) === 'NULL') {
        return null;
    }

    $upper = strtoupper($default);
    if (str_contains($upper, 'CURRENT_TIMESTAMP')) {
        return 'CURRENT_TIMESTAMP';
    }

    if ($pgType === 'boolean') {
        return in_array(trim($default, "'"), ['1', 'true', 'TRUE'], true) ? 'true' : 'false';
    }

    if (preg_match('/^(smallint|integer|bigint|numeric|double precision)/', $pgType) && is_numeric($default)) {
        return $default;
    }

    return ql(trim($default, "'"));
}

$tables = $mysql->query(
    "SELECT table_name FROM information_schema.tables
     WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE'
     ORDER BY table_name"
)->fetchAll(PDO::FETCH_COLUMN);

$pgsql->beginTransaction();

foreach ($tables as $table) {
    $pgsql->exec('DROP TABLE IF EXISTS ' . qi($table) . ' CASCADE');
}

$autoIncrementColumns = [];
$booleanColumns = [];

foreach ($tables as $table) {
    $columns = $mysql->prepare(
        "SELECT column_name, data_type, column_type, is_nullable, column_default, extra,
                character_maximum_length, numeric_precision, numeric_scale, ordinal_position
         FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = ?
         ORDER BY ordinal_position"
    );
    $columns->execute([$table]);

    $definitions = [];
    foreach ($columns->fetchAll() as $column) {
        $pgType = pgType($column);
        $definition = qi($column['COLUMN_NAME']) . ' ' . $pgType;
        $default = pgDefault($column['COLUMN_DEFAULT'], $pgType);

        if ($default !== null && ! str_contains(strtolower((string) $column['EXTRA']), 'auto_increment')) {
            $definition .= ' DEFAULT ' . $default;
        }

        if ($column['IS_NULLABLE'] === 'NO') {
            $definition .= ' NOT NULL';
        }

        if (str_contains(strtolower((string) $column['EXTRA']), 'auto_increment')) {
            $autoIncrementColumns[$table] = $column['COLUMN_NAME'];
        }

        if ($pgType === 'boolean') {
            $booleanColumns[strtolower($table)][] = strtolower((string) $column['COLUMN_NAME']);
        }

        $definitions[] = $definition;
    }

    $pgsql->exec('CREATE TABLE ' . qi($table) . " (\n  " . implode(",\n  ", $definitions) . "\n)");
}

foreach ($tables as $table) {
    if (getenv('DEBUG_BOOLEAN_COLUMNS') === '1' && strtolower($table) === 'produtos') {
        fwrite(STDERR, 'Boolean columns for produtos: ' . json_encode($booleanColumns[strtolower($table)] ?? []) . PHP_EOL);
    }

    $rows = $mysql->query('SELECT * FROM `' . str_replace('`', '``', $table) . '`');
    $first = $rows->fetch();
    if ($first === false) {
        continue;
    }

    $sourceColumns = array_keys($first);
    $targetColumns = array_map(static fn (string $column): string => strtolower($column), $sourceColumns);
    $placeholders = array_map(static fn (int $index): string => ':p' . $index, array_keys($sourceColumns));
    $insert = $pgsql->prepare(
        'INSERT INTO ' . qi($table)
        . ' (' . implode(', ', array_map('qi', $targetColumns)) . ') VALUES ('
        . implode(', ', $placeholders)
        . ')'
    );

    do {
        $params = [];
        foreach ($sourceColumns as $index => $sourceColumn) {
            $targetColumn = $targetColumns[$index];
            $value = $first[$sourceColumn];
            if (in_array($targetColumn, $booleanColumns[strtolower($table)] ?? [], true) && $value !== null) {
                $value = ($value !== '' && (bool) $value) ? 'true' : 'false';
            }
            $params['p' . $index] = $value;
        }
        try {
            $insert->execute($params);
        } catch (Throwable $exception) {
            fwrite(STDERR, 'Erro inserindo tabela ' . $table . ': ' . $exception->getMessage() . PHP_EOL);
            throw $exception;
        }
    } while (($first = $rows->fetch()) !== false);
}

$constraints = $mysql->query(
    "SELECT tc.table_name, tc.constraint_name, tc.constraint_type, kcu.column_name, kcu.ordinal_position
     FROM information_schema.table_constraints tc
     JOIN information_schema.key_column_usage kcu
       ON kcu.table_schema = tc.table_schema
      AND kcu.table_name = tc.table_name
      AND kcu.constraint_name = tc.constraint_name
     WHERE tc.table_schema = DATABASE()
       AND tc.constraint_type IN ('PRIMARY KEY', 'UNIQUE')
     ORDER BY tc.table_name, tc.constraint_name, kcu.ordinal_position"
)->fetchAll();

$grouped = [];
foreach ($constraints as $constraint) {
    $key = $constraint['TABLE_NAME'] . "\0" . $constraint['CONSTRAINT_NAME'] . "\0" . $constraint['CONSTRAINT_TYPE'];
    $grouped[$key]['table'] = $constraint['TABLE_NAME'];
    $grouped[$key]['name'] = $constraint['CONSTRAINT_NAME'];
    $grouped[$key]['type'] = $constraint['CONSTRAINT_TYPE'];
    $grouped[$key]['columns'][] = $constraint['COLUMN_NAME'];
}

foreach ($grouped as $constraint) {
    $type = $constraint['type'] === 'PRIMARY KEY' ? 'PRIMARY KEY' : 'UNIQUE';
    $name = $constraint['type'] === 'PRIMARY KEY'
        ? constraintName('pk', $constraint['table'], 'id')
        : constraintName('uk', $constraint['table'], $constraint['name']);

    $pgsql->exec(
        'ALTER TABLE ' . qi($constraint['table'])
        . ' ADD CONSTRAINT ' . qi($name)
        . ' ' . $type . ' (' . implode(', ', array_map('qi', $constraint['columns'])) . ')'
    );
}

$indexes = $mysql->query(
    "SELECT table_name, index_name, column_name, seq_in_index
     FROM information_schema.statistics
     WHERE table_schema = DATABASE() AND non_unique = 1
     ORDER BY table_name, index_name, seq_in_index"
)->fetchAll();

$groupedIndexes = [];
foreach ($indexes as $index) {
    $key = $index['TABLE_NAME'] . "\0" . $index['INDEX_NAME'];
    $groupedIndexes[$key]['table'] = $index['TABLE_NAME'];
    $groupedIndexes[$key]['name'] = $index['INDEX_NAME'];
    $groupedIndexes[$key]['columns'][] = $index['COLUMN_NAME'];
}

foreach ($groupedIndexes as $index) {
    $name = constraintName('idx', $index['table'], $index['name']);
    $pgsql->exec(
        'CREATE INDEX IF NOT EXISTS ' . qi($name)
        . ' ON ' . qi($index['table'])
        . ' (' . implode(', ', array_map('qi', $index['columns'])) . ')'
    );
}

$foreignKeys = $mysql->query(
    "SELECT kcu.table_name, kcu.constraint_name, kcu.column_name,
            kcu.referenced_table_name, kcu.referenced_column_name,
            rc.delete_rule, rc.update_rule, kcu.ordinal_position
     FROM information_schema.key_column_usage kcu
     JOIN information_schema.referential_constraints rc
       ON rc.constraint_schema = kcu.table_schema
      AND rc.constraint_name = kcu.constraint_name
      AND rc.table_name = kcu.table_name
     WHERE kcu.table_schema = DATABASE()
       AND kcu.referenced_table_name IS NOT NULL
     ORDER BY kcu.table_name, kcu.constraint_name, kcu.ordinal_position"
)->fetchAll();

$groupedForeignKeys = [];
foreach ($foreignKeys as $foreignKey) {
    $key = $foreignKey['TABLE_NAME'] . "\0" . $foreignKey['CONSTRAINT_NAME'];
    $groupedForeignKeys[$key]['table'] = $foreignKey['TABLE_NAME'];
    $groupedForeignKeys[$key]['name'] = $foreignKey['CONSTRAINT_NAME'];
    $groupedForeignKeys[$key]['referenced_table'] = $foreignKey['REFERENCED_TABLE_NAME'];
    $groupedForeignKeys[$key]['delete_rule'] = $foreignKey['DELETE_RULE'];
    $groupedForeignKeys[$key]['update_rule'] = $foreignKey['UPDATE_RULE'];
    $groupedForeignKeys[$key]['columns'][] = $foreignKey['COLUMN_NAME'];
    $groupedForeignKeys[$key]['referenced_columns'][] = $foreignKey['REFERENCED_COLUMN_NAME'];
}

foreach ($groupedForeignKeys as $foreignKey) {
    $sql = 'ALTER TABLE ' . qi($foreignKey['table'])
        . ' ADD CONSTRAINT ' . qi(constraintName('fk', $foreignKey['table'], $foreignKey['name']))
        . ' FOREIGN KEY (' . implode(', ', array_map('qi', $foreignKey['columns'])) . ')'
        . ' REFERENCES ' . qi($foreignKey['referenced_table'])
        . ' (' . implode(', ', array_map('qi', $foreignKey['referenced_columns'])) . ')';

    if ($foreignKey['delete_rule'] !== 'RESTRICT') {
        $sql .= ' ON DELETE ' . str_replace('NO ACTION', 'NO ACTION', $foreignKey['delete_rule']);
    }

    if ($foreignKey['update_rule'] !== 'RESTRICT') {
        $sql .= ' ON UPDATE ' . str_replace('NO ACTION', 'NO ACTION', $foreignKey['update_rule']);
    }

    $pgsql->exec($sql);
}

foreach ($autoIncrementColumns as $table => $column) {
    $sequence = $table . '_' . $column . '_seq';
    $pgsql->exec('CREATE SEQUENCE IF NOT EXISTS ' . qi($sequence) . ' OWNED BY ' . qi($table) . '.' . qi($column));
    $pgsql->exec(
        'SELECT setval(' . ql($sequence) . ', COALESCE((SELECT MAX(' . qi($column) . ') FROM ' . qi($table) . '), 0) + 1, false)'
    );
    $pgsql->exec(
        'ALTER TABLE ' . qi($table)
        . ' ALTER COLUMN ' . qi($column)
        . ' SET DEFAULT nextval(' . ql($sequence) . ')'
    );
}

$pgsql->commit();

foreach ($tables as $table) {
    $count = $pgsql->query('SELECT COUNT(*) FROM ' . qi($table))->fetchColumn();
    echo $table . ': ' . $count . PHP_EOL;
}
