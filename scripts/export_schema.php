<?php
define('SKIP_AUTH', true);

// Copiar a classe Database sem bootstrap
class Database
{
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

echo "Estrutura das tabelas:\n\n";
foreach ($tables as $table) {
    echo "-- Tabela: $table\n";
    $createQuery = $conn->query("SHOW CREATE TABLE `$table`");
    $create = $createQuery->fetch(PDO::FETCH_ASSOC);
    echo $create['Create Table'] . ";\n\n";
}

// Dados iniciais: usuário admin
echo "-- Dados iniciais\n";
echo "INSERT INTO usuarios (email, senha, tipo, ativo, nome) VALUES ('admin@example.com', '\$2y\$10\$examplehashedpassword', 'Administrador', 1, 'Administrador');\n";
