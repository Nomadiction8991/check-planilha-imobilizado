<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ComumRepository;
use App\Helpers\CnpjValidator;
use Exception;

class ComumService
{
    private ComumRepository $comumRepository;

    public function __construct(ComumRepository $comumRepository)
    {
        $this->comumRepository = $comumRepository;
    }

    public function criar(array $dados): int
    {
        if (empty($dados['codigo'])) {
            throw new Exception('Código é obrigatório.');
        }

        if ($this->comumRepository->buscarPorCodigo((int)$dados['codigo'])) {
            throw new Exception('Código já existe.');
        }

        if (!empty($dados['cnpj'])) {
            try {
                $cnpj = CnpjValidator::validaCnpj($dados['cnpj']);
            } catch (\InvalidArgumentException $e) {
                throw new Exception('CNPJ inválido: ' . $e->getMessage());
            }
        }


        if (isset($dados['cnpj'])) {
            $dados['cnpj'] = $this->comumRepository->gerarCnpjUnico(
                $dados['cnpj'],
                (int)$dados['codigo']
            );
        }

        $dados = $this->normalizarDados($dados);

        return $this->comumRepository->criar($dados);
    }

    public function atualizar(int $id, array $dados): bool
    {
        $comumExistente = $this->comumRepository->buscarPorId($id);
        if (!$comumExistente) {
            throw new Exception('Comum não encontrado.');
        }

        if (isset($dados['codigo']) && (int)$dados['codigo'] !== (int)$comumExistente['codigo']) {
            if ($this->comumRepository->buscarPorCodigo((int)$dados['codigo'])) {
                throw new Exception('Código já existe.');
            }
        }

        if (!empty($dados['cnpj'])) {
            try {
                $cnpj = CnpjValidator::validaCnpj($dados['cnpj']);
            } catch (\InvalidArgumentException $e) {
                throw new Exception('CNPJ inválido: ' . $e->getMessage());
            }

            $dados['cnpj'] = $this->comumRepository->gerarCnpjUnico(
                $dados['cnpj'],
                (int)($dados['codigo'] ?? $comumExistente['codigo']),
                $id
            );
        }

        $dados = $this->normalizarDados($dados);

        return $this->comumRepository->atualizar($id, $dados);
    }

    public function deletar(int $id): bool
    {
        $comum = $this->comumRepository->buscarPorId($id);
        if (!$comum) {
            throw new Exception('Comum não encontrado.');
        }

        return $this->comumRepository->deletar($id);
    }

    public function buscarPaginado(string $busca = '', int $limite = 10, int $offset = 0): array
    {
        return $this->comumRepository->buscarPaginado($busca, $limite, $offset);
    }

    public function contar(string $busca = ''): int
    {
        return $this->comumRepository->contarComFiltro($busca);
    }

    public function garantirPorCodigo(int $codigo, array $dados = []): int
    {
        return $this->comumRepository->garantirPorCodigo($codigo, $dados);
    }

    public function buscarPorCodigo(int $codigo): ?array
    {
        return $this->comumRepository->buscarPorCodigo($codigo);
    }

    public function buscarPorId(int $id): ?array
    {
        return $this->comumRepository->buscarPorId($id);
    }

    private function normalizarDados(array $dados): array
    {
        $camposUppercase = ['descricao', 'administracao', 'cidade'];

        foreach ($camposUppercase as $campo) {
            if (isset($dados[$campo]) && is_string($dados[$campo])) {
                $dados[$campo] = mb_strtoupper($dados[$campo], 'UTF-8');
            }
        }

        return $dados;
    }
}
