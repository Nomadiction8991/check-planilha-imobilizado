<?php

use App\Helpers\StringHelper;
use App\Helpers\CsvHelper;
use App\Middleware\AuthMiddleware;
use App\Core\SessionManager;

/**
 * GlobalFunctions.php
 *
 * Registra funções globais de compatibilidade que delegam para
 * as classes OOP modernas em src/. Centraliza em um único ponto
 * as funções que antes estavam espalhadas em app/helpers/.
 *
 * Carregado por config/bootstrap.php.
 *
 * @deprecated Código novo deve usar as classes diretamente:
 *   - StringHelper::toUppercase() em vez de to_uppercase()
 *   - SessionManager::isAuthenticated() em vez de isLoggedIn()
 *   - CsvHelper::normalizarEncodingCsv() em vez de ip_normalizar_csv_encoding()
 *   - LerEnv::obter() em vez de env()
 */

// ═══════════════════════════════════════════════
//  ENV HELPERS  (substituem app/helpers/env_helper.php)
// ═══════════════════════════════════════════════

if (!function_exists('loadEnv')) {
    /**
     * Carrega variáveis de um .env para $_ENV, $_SERVER e putenv().
     *
     * @deprecated Use \App\Core\LerEnv::obter($chave) para acessar variáveis.
     */
    function loadEnv(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            [$name, $value] = array_pad(explode('=', $line, 2), 2, '');
            $name  = trim($name);
            $value = trim(trim($value), "\"'");

            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name]    = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

if (!function_exists('env')) {
    /**
     * Retorna valor de variável de ambiente.
     *
     * @deprecated Use \App\Core\LerEnv::obter($chave, $padrao)
     */
    function env(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);
        return ($value === false) ? $default : $value;
    }
}

// ═══════════════════════════════════════════════
//  AUTH HELPERS  (substituem app/helpers/auth_helper.php)
// ═══════════════════════════════════════════════

if (!function_exists('isLoggedIn')) {
    /**
     * @deprecated Use SessionManager::isAuthenticated()
     */
    function isLoggedIn(): bool
    {
        return SessionManager::isAuthenticated();
    }
}

if (!function_exists('isAdmin')) {
    /**
     * @deprecated Use SessionManager::get('is_admin')
     */
    function isAdmin(): bool
    {
        return (bool) SessionManager::get('is_admin', false);
    }
}

if (!function_exists('isDoador')) {
    /**
     * @deprecated Use SessionManager::get('is_doador')
     */
    function isDoador(): bool
    {
        return (bool) SessionManager::get('is_doador', false);
    }
}

if (!function_exists('getLoginUrl')) {
    /**
     * @deprecated Roteamento agora é feito pelo front controller.
     */
    function getLoginUrl(): string
    {
        return '/login';
    }
}

// ═══════════════════════════════════════════════
//  STRING / UPPERCASE HELPERS  (substituem app/helpers/uppercase_helper.php)
// ═══════════════════════════════════════════════

if (!function_exists('to_uppercase')) {
    /**
     * @deprecated Use StringHelper::toUppercase()
     */
    function to_uppercase(mixed $value): mixed
    {
        if (empty($value) || !is_string($value)) {
            return $value;
        }
        return StringHelper::toUppercase($value);
    }
}

if (!function_exists('uppercase')) {
    /**
     * @deprecated Use StringHelper::toUppercase()
     */
    function uppercase(mixed $value): mixed
    {
        return to_uppercase($value);
    }
}

if (!function_exists('uppercase_fields')) {
    /**
     * @deprecated Use StringHelper::uppercaseFields()
     */
    function uppercase_fields(array &$data, array $fields_to_convert = []): array
    {
        return StringHelper::uppercaseFields($data, $fields_to_convert);
    }
}

if (!function_exists('normalize_text')) {
    /**
     * @deprecated Use StringHelper::normalize()
     */
    function normalize_text(string $text, bool $remove_accents = false): string
    {
        if (empty($text)) {
            return $text;
        }
        return StringHelper::normalize($text, $remove_accents);
    }
}

if (!function_exists('to_lowercase')) {
    /**
     * @deprecated Use StringHelper::toLowercase()
     */
    function to_lowercase(mixed $value): mixed
    {
        if (empty($value) || !is_string($value)) {
            return $value;
        }
        return StringHelper::toLowercase($value);
    }
}

if (!function_exists('remove_accents')) {
    /**
     * @deprecated Use StringHelper::removeAccents()
     */
    function remove_accents(string $text): string
    {
        return StringHelper::removeAccents($text);
    }
}

if (!function_exists('get_uppercase_fields')) {
    /**
     * @deprecated Use StringHelper::getUppercaseFields()
     */
    function get_uppercase_fields(?string $table = null): array
    {
        return StringHelper::getUppercaseFields($table);
    }
}

// ═══════════════════════════════════════════════
//  CSV HELPERS  (substituem app/helpers/csv_encoding_helper.php)
// ═══════════════════════════════════════════════

if (!function_exists('ip_normalizar_csv_encoding')) {
    /**
     * @deprecated Use CsvHelper::normalizarEncodingCsv()
     */
    function ip_normalizar_csv_encoding(string $filePath): void
    {
        CsvHelper::normalizarEncodingCsv($filePath);
    }
}

if (!function_exists('ip_fix_text_encoding')) {
    /**
     * @deprecated Use CsvHelper::fixTextEncoding()
     */
    function ip_fix_text_encoding(?string $valor): ?string
    {
        return CsvHelper::fixTextEncoding($valor);
    }
}

// ═══════════════════════════════════════════════
//  COMUM HELPERS  (substituem funções do antigo app/helpers/comum_helper.php)
// ═══════════════════════════════════════════════

if (!function_exists('contar_comuns')) {
    /**
     * Conta o total de comuns com filtro de busca.
     * @deprecated Use ComumRepository::contarComFiltro()
     */
    function contar_comuns(PDO $conexao, string $busca = ''): int
    {
        $repo = new \App\Repositories\ComumRepository($conexao);
        return $repo->contarComFiltro($busca);
    }
}

if (!function_exists('buscar_comuns_paginated')) {
    /**
     * Busca comuns paginados.
     * @deprecated Use ComumRepository::buscarPaginado()
     */
    function buscar_comuns_paginated(PDO $conexao, string $busca = '', int $limite = 20, int $offset = 0): array
    {
        $repo = new \App\Repositories\ComumRepository($conexao);
        return $repo->buscarPaginado($busca, $limite, $offset);
    }
}

if (!function_exists('contar_PRODUTOS_por_comum')) {
    /**
     * Conta produtos associados a um comum.
     * @deprecated Migrar para ProdutoRepository
     */
    function contar_PRODUTOS_por_comum(PDO $conexao, int $comum_id): int
    {
        $stmt = $conexao->prepare("SELECT COUNT(*) FROM produtos WHERE comum_id = :comum_id");
        $stmt->bindValue(':comum_id', $comum_id, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }
}

if (!function_exists('obter_comum_por_id')) {
    /**
     * Obtém um comum pelo ID.
     * @deprecated Use ComumRepository::buscarPorId()
     */
    function obter_comum_por_id(PDO $conexao, int $id): ?array
    {
        $repo = new \App\Repositories\ComumRepository($conexao);
        return $repo->buscarPorId($id);
    }
}

// ═══════════════════════════════════════════════
//  URL / FILTRO HELPERS  (substituem funções do antigo app/helpers/)
// ═══════════════════════════════════════════════

if (!function_exists('gerarParametrosFiltro')) {
    /**
     * Gera query string de filtros a partir de $_GET.
     * @param bool $includeComumId Se true, inclui comum_id nos parâmetros
     * @deprecated Migrar lógica de filtro para Controllers
     */
    function gerarParametrosFiltro(bool $includeComumId = false): string
    {
        $params = [];
        $filterKeys = [
            'busca',
            'pagina',
            'filtro_nome',
            'filtro_dependencia',
            'filtro_codigo',
            'filtro_status',
            'filtro_STATUS',
            'id',
            'status'
        ];

        if ($includeComumId) {
            $filterKeys[] = 'comum_id';
        }

        foreach ($filterKeys as $key) {
            if (isset($_GET[$key]) && $_GET[$key] !== '') {
                $params[$key] = $_GET[$key];
            }
        }

        return http_build_query($params);
    }
}

if (!function_exists('getReturnUrl')) {
    /**
     * Gera URL de retorno para listagem de produtos com filtros preservados.
     * @deprecated Migrar para ProdutoController
     */
    function getReturnUrl(
        $comum_id = null,
        $pagina = null,
        $filtro_nome = null,
        $filtro_dependencia = null,
        $filtro_codigo = null,
        $filtro_status = null
    ): string {
        $params = [];
        if ($comum_id) $params['comum_id'] = $comum_id;
        if ($comum_id) $params['id'] = $comum_id;
        if ($pagina) $params['pagina'] = $pagina;
        if ($filtro_nome) $params['filtro_nome'] = $filtro_nome;
        if ($filtro_dependencia) $params['filtro_dependencia'] = $filtro_dependencia;
        if ($filtro_codigo) $params['filtro_codigo'] = $filtro_codigo;
        if ($filtro_status) $params['filtro_STATUS'] = $filtro_status;

        $qs = http_build_query($params);
        return '/products/view' . ($qs ? '?' . $qs : '');
    }
}

if (!function_exists('detectar_tabela_comuns')) {
    /**
     * Detecta o nome da tabela de comuns no banco de dados.
     * Verifica se existe 'comums' ou 'comuns'.
     * @deprecated Padronizar nome da tabela na migração
     */
    function detectar_tabela_comuns(PDO $conexao): string
    {
        try {
            $stmt = $conexao->query("SHOW TABLES LIKE 'comums'");
            if ($stmt->rowCount() > 0) {
                return 'comums';
            }
        } catch (\Exception $e) {
            // ignora
        }

        try {
            $stmt = $conexao->query("SHOW TABLES LIKE 'comuns'");
            if ($stmt->rowCount() > 0) {
                return 'comuns';
            }
        } catch (\Exception $e) {
            // ignora
        }

        return 'comums'; // fallback padrão
    }
}
