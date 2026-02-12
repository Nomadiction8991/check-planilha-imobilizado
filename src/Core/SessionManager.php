<?php

namespace App\Core;


class SessionManager
{
    private static bool $started = false;

    
    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_start([
            'cookie_httponly' => true,
            'cookie_secure' => isset($_SERVER['HTTPS']),
            'cookie_samesite' => 'Strict'
        ]);

        self::$started = true;
    }

    
    public static function set(string $key, $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    
    public static function get(string $key, $default = null)
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    
    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    
    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    
    public static function flash(string $key, $value): void
    {
        self::start();
        $_SESSION['_flash'][$key] = $value;
    }

    
    public static function getFlash(string $key, $default = null)
    {
        self::start();
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    
    public static function isAuthenticated(): bool
    {
        return self::has('usuario_id');
    }

    
    public static function getUserId(): ?int
    {
        $id = self::get('usuario_id');
        return $id ? (int)$id : null;
    }

    
    public static function getUserName(): ?string
    {
        return self::get('usuario_nome');
    }

    
    public static function getUserEmail(): ?string
    {
        return self::get('usuario_email');
    }

    
    public static function setUser(int $id, string $nome, string $email): void
    {
        self::set('usuario_id', $id);
        self::set('usuario_nome', $nome);
        self::set('usuario_email', $email);
    }

    
    public static function clearUser(): void
    {
        self::remove('usuario_id');
        self::remove('usuario_nome');
        self::remove('usuario_email');
    }

    
    public static function destroy(): void
    {
        self::start();
        session_destroy();
        self::$started = false;
    }

    
    public static function regenerate(): void
    {
        self::start();
        session_regenerate_id(true);
    }

    
    public static function all(): array
    {
        self::start();
        return $_SESSION;
    }

    
    public static function clear(): void
    {
        self::start();
        $_SESSION = [];
    }

    /**
     * Garante que o usuário logado tenha uma comum_id definida na sessão.
     * Se não houver, define a primeira comum disponível como padrão e persiste no DB.
     * @return int|null ID da comum ativa, ou null se não houver comuns disponíveis
     */
    public static function ensureComumId(): ?int
    {
        self::start();
        
        // Se já existe comum_id na sessão, retorna
        if (isset($_SESSION['comum_id']) && (int)$_SESSION['comum_id'] > 0) {
            return (int)$_SESSION['comum_id'];
        }

        // Verifica se usuário está logado
        if (!self::isAuthenticated()) {
            return null;
        }

        try {
            $conexao = ConnectionManager::getConnection();
            
            // Buscar comum_id do usuário no banco
            $stmt = $conexao->prepare("SELECT comum_id FROM usuarios WHERE id = :id");
            $stmt->bindValue(':id', self::getUserId(), \PDO::PARAM_INT);
            $stmt->execute();
            $usuario = $stmt->fetch(\PDO::FETCH_ASSOC);
            $comumId = $usuario['comum_id'] ?? null;

            // Se não houver comum definida, busca a primeira disponível
            if ((int)$comumId <= 0) {
                $stmtComum = $conexao->query("SELECT id FROM comums ORDER BY id LIMIT 1");
                $primeiraComum = $stmtComum->fetch(\PDO::FETCH_ASSOC);
                
                if ($primeiraComum) {
                    $comumId = (int)$primeiraComum['id'];
                    
                    // Persiste a comum padrão no banco
                    $uStmt = $conexao->prepare("UPDATE usuarios SET comum_id = :comum_id WHERE id = :id");
                    $uStmt->bindValue(':comum_id', $comumId, \PDO::PARAM_INT);
                    $uStmt->bindValue(':id', self::getUserId(), \PDO::PARAM_INT);
                    $uStmt->execute();
                } else {
                    return null; // Não há comuns cadastradas
                }
            }

            // Define na sessão
            $_SESSION['comum_id'] = $comumId;
            return $comumId;
            
        } catch (\Exception $e) {
            error_log('Erro ao garantir comum_id: ' . $e->getMessage());
            return null;
        }
    }
}
