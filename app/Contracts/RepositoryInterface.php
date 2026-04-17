<?php

declare(strict_types=1);

namespace App\Contracts;


interface RepositoryInterface
{
    
    public function buscarPorId(int $id): ?array;

    
    public function buscarTodos(): array;

    
    public function criar(array $dados): int;

    
    public function atualizar(int $id, array $dados): bool;

    
    public function deletar(int $id): bool;

    
    public function contar(string $where = '', array $params = []): int;
}
