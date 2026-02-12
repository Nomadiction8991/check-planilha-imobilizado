<?php
define('SKIP_AUTH', true);

// Copiar a classe Database sem bootstrap
class Database {
    private string $host;
    private string $db_name;
    private string $username;
    private string $password;
    private string $charset = 'utf8mb4';

    public ?PDO $conexao = null;

    public function __construct()
    {
        $this->host = getenv('DB_HOST') ?: 'anvy.com.br';
        $this->db_name = getenv('DB_NAME') ?: 'anvycomb_checkplanilha';
        $this->username = getenv('DB_USER') ?: 'anvycomb_checkplanilha';
        $this->password = getenv('DB_PASS') ?: 'uGyzaCndm7EDahptkBZd';
    }

    public function getConnection(): PDO
    {
        if ($this->conexao instanceof PDO) {
            return $this->conexao;
        }

        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                $this->host,
                $this->db_name,
                $this->charset
            );

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $this->conexao = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $exception) {
            echo 'Erro de conexao: ' . $exception->getMessage() . "\n";
            exit(1);
        }

        return $this->conexao;
    }
}

$db = new Database();
$conn = $db->getConnection();

// Listar todas as tabelas
$tablesQuery = $conn->query("SHOW TABLES");
$tables = $tablesQuery->fetchAll(PDO::FETCH_COLUMN);

echo "Tabelas encontradas:\n";
foreach ($tables as $table) {
    echo "- $table\n";
}

echo "\nÍndices por tabela:\n";
foreach ($tables as $table) {
    echo "\nTabela: $table\n";
    try {
        $indexQuery = $conn->query("SHOW INDEX FROM `$table`");
        $indexes = $indexQuery->fetchAll(PDO::FETCH_ASSOC);
        if (empty($indexes)) {
            echo "  Nenhum índice encontrado.\n";
        } else {
            foreach ($indexes as $index) {
                echo "  - Nome: " . $index['Key_name'] . ", Coluna: " . $index['Column_name'] . ", Único: " . ($index['Non_unique'] == 0 ? 'Sim' : 'Não') . "\n";
            }
        }
    } catch (PDOException $e) {
        echo "  Erro ao obter índices: " . $e->getMessage() . "\n";
    }
}

// Adicionar índices sugeridos se não existirem
$indexesToAdd = [
    'usuarios' => ['cpf'],
    'produtos' => ['codigo', 'comum_id', 'tipo_bem_id', 'dependencia_id']
];

echo "\nAdicionando índices sugeridos:\n";
foreach ($indexesToAdd as $table => $columns) {
    echo "\nTabela: $table\n";
    foreach ($columns as $column) {
        $indexName = "idx_{$table}_{$column}";
        try {
            // Verificar se o índice já existe
            $checkIndex = $conn->prepare("SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?");
            $checkIndex->execute([$table, $indexName]);
            if ($checkIndex->fetch()) {
                echo "  Índice $indexName já existe.\n";
            } else {
                $conn->exec("CREATE INDEX $indexName ON $table ($column)");
                echo "  Índice $indexName criado em $column.\n";
            }
        } catch (PDOException $e) {
            echo "  Erro ao criar índice $indexName: " . $e->getMessage() . "\n";
        }
    }
}
?>