<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\TipoBemRepository;
use Exception;

class TipoBemService
{
    private TipoBemRepository $tipoBemRepository;

    public function __construct(TipoBemRepository $tipoBemRepository)
    {
        $this->tipoBemRepository = $tipoBemRepository;
    }

    public function criar(array $dados): int
    {
        if (empty($dados['codigo'])) {
            throw new Exception('Código é obrigatório.');
        }

        if (empty($dados['descricao'])) {
            throw new Exception('Descrição é obrigatória.');
        }

        // Verifica se já existe tipo com este código
        if ($this->tipoBemRepository->buscarPorCodigo((int)$dados['codigo'])) {
            throw new Exception('Código já existe.');
        }

        return $this->tipoBemRepository->criar($dados);
    }

    public function atualizar(int $id, array $dados): bool
    {
        $tipoExistente = $this->tipoBemRepository->buscarPorId($id);
        if (!$tipoExistente) {
            throw new Exception('Tipo de bem não encontrado.');
        }

        if (empty($dados['codigo'])) {
            throw new Exception('Código é obrigatório.');
        }

        if (empty($dados['descricao'])) {
            throw new Exception('Descrição é obrigatória.');
        }

        // Verifica se código já existe (exceto para o próprio registro)
        if (isset($dados['codigo']) && (int)$dados['codigo'] !== (int)$tipoExistente['codigo']) {
            if ($this->tipoBemRepository->buscarPorCodigo((int)$dados['codigo'])) {
                throw new Exception('Código já existe.');
            }
        }

        return $this->tipoBemRepository->atualizar($id, $dados);
    }

    public function deletar(int $id): bool
    {
        $tipo = $this->tipoBemRepository->buscarPorId($id);
        if (!$tipo) {
            throw new Exception('Tipo de bem não encontrado.');
        }

        return $this->tipoBemRepository->deletar($id);
    }

    public function buscarPorId(int $id): ?array
    {
        return $this->tipoBemRepository->buscarPorId($id);
    }

    public function buscarPaginado(string $busca = '', int $limite = 20, int $offset = 0): array
    {
        return $this->tipoBemRepository->buscarPaginado($busca, $limite, $offset);
    }

    public function contar(string $busca = ''): int
    {
        return $this->tipoBemRepository->contarComFiltro($busca);
    }

    public function buscarTodos(): array
    {
        return $this->tipoBemRepository->buscarTodos();
    }
}
