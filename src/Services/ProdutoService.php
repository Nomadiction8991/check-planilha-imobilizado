<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ProdutoRepository;
use App\Repositories\TipoBemRepository;
use App\Repositories\DependenciaRepository;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;

class ProdutoService
{
    private ProdutoRepository $produtoRepository;
    private TipoBemRepository $tipoBemRepository;
    private DependenciaRepository $dependenciaRepository;

    public function __construct(
        ProdutoRepository $produtoRepository,
        TipoBemRepository $tipoBemRepository,
        DependenciaRepository $dependenciaRepository
    ) {
        $this->produtoRepository = $produtoRepository;
        $this->tipoBemRepository = $tipoBemRepository;
        $this->dependenciaRepository = $dependenciaRepository;
    }

    public function listarPaginado(int $comumId, int $pagina, int $limite, array $filtros = []): array
    {
        return $this->produtoRepository->buscarPorComumPaginado($comumId, $pagina, $limite, $filtros);
    }

    public function salvarObservacao(int $produtoId, int $comumId, string $observacao): void
    {
        if ($produtoId <= 0) {
            throw new ValidationException('Produto inválido.');
        }

        $this->produtoRepository->atualizarObservacao($produtoId, $comumId, $observacao);
    }

    public function atualizarCheck(int $produtoId, int $comumId, int $checado): void
    {
        if ($produtoId <= 0) {
            throw new ValidationException('Produto inválido.');
        }

        $this->produtoRepository->atualizarChecado($produtoId, $comumId, $checado);
    }

    public function atualizarEtiqueta(int $produtoId, int $comumId, int $imprimir): void
    {
        if ($produtoId <= 0) {
            throw new ValidationException('Produto inválido.');
        }

        $this->produtoRepository->atualizarEtiqueta($produtoId, $comumId, $imprimir);
    }

    public function buscarPorId(int $id): array
    {
        $produto = $this->produtoRepository->buscarPorId($id);
        if (!$produto) {
            throw new NotFoundException('Produto não encontrado.');
        }
        return $produto;
    }

    public function buscarTiposBens(): array
    {
        return $this->tipoBemRepository->buscarTodos();
    }

    public function buscarDependencias(int $comumId): array
    {
        return $this->dependenciaRepository->buscarPaginadoPorComum($comumId, '', 1000, 0);
    }

    public function contarPorComum(int $comumId): int
    {
        return $this->produtoRepository->contarPorComum($comumId);
    }

    public function buscarDistintosCodigos(int $comumId): array
    {
        return $this->produtoRepository->buscarDistintosCodigos($comumId);
    }
}
