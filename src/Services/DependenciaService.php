<?php

namespace App\Services;

use App\Repositories\DependenciaRepository;
use Exception;

class DependenciaService
{
    private DependenciaRepository $dependenciaRepository;

    public function __construct(DependenciaRepository $dependenciaRepository)
    {
        $this->dependenciaRepository = $dependenciaRepository;
    }

    public function buscarPaginado(string $busca = '', int $limite = 10, int $offset = 0): array
    {
        return $this->dependenciaRepository->buscarPaginado($busca, $limite, $offset);
    }

    public function buscarPaginadoPorComum(int $comumId, string $busca = '', int $limite = 10, int $offset = 0): array
    {
        return $this->dependenciaRepository->buscarPaginadoPorComum($comumId, $busca, $limite, $offset);
    }

    public function contar(string $busca = ''): int
    {
        if ($busca !== '') {
            $where = "descricao LIKE ?";
            $params = ['%' . $busca . '%'];
            return $this->dependenciaRepository->contar($where, $params);
        }

        return $this->dependenciaRepository->contar();
    }

    public function contarPorComum(int $comumId, string $busca = ''): int
    {
        return $this->dependenciaRepository->contarPorComum($comumId, $busca);
    }

    public function criar(array $dados): int
    {
        if (empty($dados['descricao'])) {
            throw new Exception('Descrição é obrigatória.');
        }

        if (empty($dados['comum_id'])) {
            throw new Exception('Igreja é obrigatória.');
        }

        // Verifica se já existe dependência com essa descrição na mesma igreja
        $existente = $this->dependenciaRepository->buscarPorDescricaoEComum(
            $dados['descricao'], 
            $dados['comum_id']
        );
        
        if ($existente) {
            throw new Exception('Dependência já existe nesta igreja.');
        }

        $dados = $this->normalizarDados($dados);

        return $this->dependenciaRepository->criar($dados);
    }

    public function atualizar(int $id, array $dados): bool
    {
        if (empty($dados['descricao'])) {
            throw new Exception('Descrição é obrigatória.');
        }

        $dependenciaExistente = $this->dependenciaRepository->buscarPorDescricao($dados['descricao']);
        if ($dependenciaExistente && $dependenciaExistente['id'] !== $id) {
            throw new Exception('Dependência já existe.');
        }

        $dados = $this->normalizarDados($dados);

        return $this->dependenciaRepository->atualizar($id, $dados);
    }

    public function deletar(int $id): bool
    {
        $dependencia = $this->dependenciaRepository->buscarPorId($id);
        if (!$dependencia) {
            throw new Exception('Dependência não encontrada.');
        }

        return $this->dependenciaRepository->deletar($id);
    }

    public function buscarPorId(int $id): ?array
    {
        return $this->dependenciaRepository->buscarPorId($id);
    }

    private function normalizarDados(array $dados): array
    {
        $dados['descricao'] = trim(strtoupper($dados['descricao']));
        return $dados;
    }
}
