<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Repositories\ProdutoRepository;
use App\Core\SessionManager;

/**
 * Helper para validação de produtos e comuns.
 * QUALIDADE: reduz duplicação de código nos controllers
 *
 * Exemplo:
 *   $helper = new ProdutoValidationHelper($produtoRepo);
 *   $produto = $helper->buscarOuRedirecionarComErro($idProduto, $comumId);
 *   // Retorna o produto ou redireciona com erro
 */
class ProdutoValidationHelper
{
    private ProdutoRepository $produtoRepository;

    public function __construct(ProdutoRepository $produtoRepository)
    {
        $this->produtoRepository = $produtoRepository;
    }

    /**
     * Valida e obtém ID da comum da sessão ou retorna null para redirecionamento.
     * Se inválida, já armazena mensagem para exibição.
     */
    public function obterComumIdValido(string $mensagemErro = 'Selecione uma igreja'): ?int
    {
        $comumId = SessionManager::getComumId();

        if (!$comumId || $comumId <= 0) {
            return null;
        }

        return $comumId;
    }

    /**
     * Busca um produto por ID e valida propriedade (comum).
     * Retorna null se não encontrado ou não pertence à comum.
     */
    public function buscarProdutoOuNull(int $idProduto, int $comumId): ?array
    {
        if ($idProduto <= 0) {
            return null;
        }

        $produto = $this->produtoRepository->buscarPorId($idProduto);

        // Validar que o produto pertence à comum
        if (!$produto || ((int) ($produto['comum_id'] ?? 0)) !== $comumId) {
            return null;
        }

        return $produto;
    }
}
