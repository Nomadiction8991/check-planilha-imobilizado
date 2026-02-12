<?php

namespace App\Core;

use PDO;

/**
 * Database (DEPRECATED)
 * 
 * @deprecated Use ConnectionManager::getConnection() instead
 * @see ConnectionManager
 * 
 * Esta classe é mantida apenas para backward compatibility.
 * Será removida na próxima versão major.
 * 
 * MIGRAÇÃO:
 * Antes: $pdo = Database::getConnection();
 * Depois: $pdo = ConnectionManager::getConnection();
 */
class Database
{
    /**
     * @deprecated Use ConnectionManager::getConnection()
     */
    public static function getConnection(): PDO
    {
        return ConnectionManager::getConnection();
    }
}
