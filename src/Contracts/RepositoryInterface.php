<?php

namespace App\Contracts;

/**
 * RepositoryInterface - Contrato Básico para Repositories
 * 
 * SOLID Principles:
 * - Interface Segregation: Interface mínima, classes implementam apenas o necessário
 * - Dependency Inversion: Controllers dependem de abstração, não implementação
 * 
 * @package App\Contracts
 */
interface RepositoryInterface
{
    /**
     * Busca registro por ID
     * 
     * @param int $id
     * @return array|null
     */
    public function buscarPorId(int $id): ?array;

    /**
     * Busca todos os registros
     * 
     * @return array
     */
    public function buscarTodos(): array;

    /**
     * Cria novo registro
     * 
     * @param array $dados
     * @return int ID do registro criado
     */
    public function criar(array $dados): int;

    /**
     * Atualiza registro existente
     * 
     * @param int $id
     * @param array $dados
     * @return bool
     */
    public function atualizar(int $id, array $dados): bool;

    /**
     * Deleta registro
     * 
     * @param int $id
     * @return bool
     */
    public function deletar(int $id): bool;

    /**
     * Conta total de registros
     * 
     * @param string $where Condição WHERE (opcional)
     * @param array $params Parâmetros bindados
     * @return int
     */
    public function contar(string $where = '', array $params = []): int;
}
