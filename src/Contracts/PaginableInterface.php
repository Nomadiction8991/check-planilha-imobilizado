<?php

namespace App\Contracts;


interface PaginableInterface
{
    
    public function buscarPaginado(int $pagina, int $limite, array $filtros = []): array;
}
