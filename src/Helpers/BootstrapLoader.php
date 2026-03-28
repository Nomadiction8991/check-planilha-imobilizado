<?php

declare(strict_types=1);

/**
 * Bootstrap Loader Helper
 *
 * Centraliza o carregamento do bootstrap.php para arquivos de view.
 *
 * Esta classe resolve o problema de carregamento duplicado do bootstrap.php
 * e padroniza o acesso à configuração da aplicação em todos os arquivos de view.
 *
 * Funcionamento:
 * - Quando chamado pelo index.php: bootstrap já foi carregado, define BOOTSTRAP_LOADED
 * - Quando chamado por views diretamente: carrega bootstrap se necessário
 * - Previne carregamentos duplicados usando a constante BOOTSTRAP_LOADED
 *
 * @package App\Helpers
 */

// Caminho absoluto para o bootstrap.php (funciona tanto local quanto no container)
$bootstrapPath = __DIR__ . '/../../config/bootstrap.php';

// Se o bootstrap já foi carregado (definido no index.php), não faz nada
if (!defined('BOOTSTRAP_LOADED')) {
    require_once $bootstrapPath;
    define('BOOTSTRAP_LOADED', true);
}
