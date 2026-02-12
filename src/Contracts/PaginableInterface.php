<?php

namespace App\Contracts;

/**
 * PaginableInterface - Contrato para Repositories com Paginação
 * 
 * SOLID Principles:
 * - Interface Segregation: Separada do RepositoryInterface (nem todos paginam)
 * 
 * @package App\Contracts
 */
interface PaginableInterface
{
    /**
     * Busca registros paginados
     * 
     * @param int $pagina Página atual
     * @param int $limite Itens por página
     * @param array $filtros Filtros opcionais
     * @return array ['dados' => [], 'total' => 0, 'pagina' => 1, 'totalPaginas' => 1]
     */
    public function buscarPaginado(int $pagina, int $limite, array $filtros = []): array;
}
