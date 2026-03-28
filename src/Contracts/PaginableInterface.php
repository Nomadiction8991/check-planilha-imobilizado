<?php

declare(strict_types=1);

namespace App\Contracts;


interface PaginableInterface
{
    
    public function buscarPaginado(int $pagina, int $limite, array $filtros = []): array;
}
