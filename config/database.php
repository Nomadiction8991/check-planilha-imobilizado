<?php
require_once __DIR__ . '/bootstrap.php';

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
        $this->host = env('DB_HOST', 'db');
        $this->db_name = env('DB_NAME', 'checkplanilha');
        $this->username = env('DB_USER', 'checkplanilha');
        $this->password = env('DB_PASS', 'checkplanilha123');
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
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset} COLLATE utf8mb4_unicode_ci",
            ];

            $this->conexao = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $exception) {
            error_log('Erro de conexao: ' . $exception->getMessage());
            if (is_ajax_request()) {
                json_response(['success' => false, 'message' => 'Erro ao conectar ao banco de dados.'], 500);
            }
            exit('Erro ao conectar ao banco de dados.');
        }

        return $this->conexao;
    }
}

// Instancia compartilhada
$database = new Database();
$conexao = $database->getConnection();
