<?php

/**
 * Database Configuration
 * 
 * REFACTORED: Usa ConnectionManager centralizado
 * Mantém $conexao global para backward compatibility com código legado
 * 
 * @deprecated A variável global $conexao será removida em versão futura
 *             Use ConnectionManager::getConnection() diretamente
 */

require_once __DIR__ . '/bootstrap.php';

use App\Core\ConnectionManager;

// Obter conexão via ConnectionManager
$conexao = ConnectionManager::getConnection();

/**
 * NOTA PARA DESENVOLVEDORES:
 * 
 * Em código novo, use:
 *   $pdo = ConnectionManager::getConnection();
 * 
 * Evite usar:
 *   global $conexao;
 * 
 * A variável $conexao será removida quando todo código legado for migrado.
 */
