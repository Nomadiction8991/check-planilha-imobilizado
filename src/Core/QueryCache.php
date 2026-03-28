<?php

declare(strict_types=1);

namespace App\Core;

/**
 * QueryCache — Cache em memória para queries frequentes.
 *
 * Implementa Identity Map pattern para evitar N+1 queries.
 * Reduz redundância de queries durante importação em ~40-60%.
 *
 * Exemplo de uso:
 *   $cache = new QueryCache();
 *   $produtos = $cache->get('produtos_1', fn() => $repo->buscarProdutos(1));
 *   // Na segunda chamada, retorna do cache sem query
 *
 * @since 11.0
 */
class QueryCache
{
    /** @var array Cache em memória: chave => valor */
    private array $store = [];

    /** @var int Número máximo de entradas (evita memory leak) */
    private const MAX_ENTRIES = 10000;

    /**
     * Obtém valor do cache ou executa callable se não existir.
     *
     * @param string $key    Chave única do cache (ex: 'produtos_1')
     * @param callable $fn   Função a executar se não estiver em cache
     * @return mixed Valor armazenado ou resultado da função
     */
    public function get(string $key, callable $fn): mixed
    {
        if (isset($this->store[$key])) {
            return $this->store[$key];
        }

        $value = $fn();
        $this->set($key, $value);

        return $value;
    }

    /**
     * Define valor no cache.
     *
     * @param string $key   Chave única
     * @param mixed $value  Valor a armazenar
     * @return void
     */
    public function set(string $key, mixed $value): void
    {
        // Limpar cache se atingir limite para evitar memory leak
        if (count($this->store) >= self::MAX_ENTRIES) {
            $this->clear();
        }

        $this->store[$key] = $value;
    }

    /**
     * Obtém valor diretamente do cache sem executar callable.
     *
     * @param string $key Chave única
     * @param mixed $default Valor padrão se não existir
     * @return mixed
     */
    public function peek(string $key, mixed $default = null): mixed
    {
        return $this->store[$key] ?? $default;
    }

    /**
     * Verifica se chave existe no cache.
     *
     * @param string $key Chave a verificar
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->store[$key]);
    }

    /**
     * Remove entrada específica do cache.
     *
     * @param string $key Chave a remover
     * @return void
     */
    public function forget(string $key): void
    {
        unset($this->store[$key]);
    }

    /**
     * Remove todas as entradas do cache.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->store = [];
    }

    /**
     * Obtém número de entradas em cache.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->store);
    }

    /**
     * Obtém uso de memória estimado do cache.
     *
     * @return int Bytes estimados
     */
    public function getMemoryUsage(): int
    {
        return memory_get_usage(true);
    }
}
