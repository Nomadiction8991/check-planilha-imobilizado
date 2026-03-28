<?php

declare(strict_types=1);

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

        $dependenciaAtual = $this->dependenciaRepository->buscarPorId($id);
        if (!$dependenciaAtual) {
            throw new Exception('Dependência não encontrada.');
        }

        $descricaoNormalizada = trim(strtoupper($dados['descricao']));
        $dependenciaExistente = $this->dependenciaRepository->buscarPorDescricaoEComum(
            $descricaoNormalizada,
            (int) $dependenciaAtual['comum_id']
        );
        if ($dependenciaExistente && (int) $dependenciaExistente['id'] !== $id) {
            throw new Exception('Dependência já existe.');
        }

        $dados['descricao'] = $descricaoNormalizada;

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

    public function buscarPorIdEComum(int $id, int $comumId): ?array
    {
        return $this->dependenciaRepository->buscarPorIdEComum($id, $comumId);
    }

    public function deletarPorIdEComum(int $id, int $comumId): bool
    {
        $dependencia = $this->dependenciaRepository->buscarPorIdEComum($id, $comumId);
        if (!$dependencia) {
            throw new Exception('Dependência não encontrada para a igreja selecionada.');
        }

        return $this->dependenciaRepository->deletarPorIdEComum($id, $comumId);
    }

    private function normalizarDados(array $dados): array
    {
        $dados['descricao'] = trim(strtoupper($dados['descricao']));
        return $dados;
    }
}
