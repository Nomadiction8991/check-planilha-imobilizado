<?php

namespace App\Services;

use App\Repositories\ComumRepository;
use App\Helpers\CnpjValidator;
use Exception;

/**
 * ComumService - Serviço de Lógica de Negócio para Comuns
 * 
 * SOLID Principles:
 * - Single Responsibility: Gerencia APENAS lógica de negócio de comuns
 * - Dependency Inversion: Depende de ComumRepository (abstração)
 * - Open/Closed: Extensível sem modificar código existente
 * 
 * Responsabilidades:
 * - Validações de negócio (código único, CNPJ válido)
 * - Regras de criação/atualização de comuns
 * - Geração automática de CNPJs únicos
 * - Garantia de existência de comuns por código
 * 
 * @package App\Services
 */
class ComumService
{
    private ComumRepository $comumRepository;

    public function __construct(ComumRepository $comumRepository)
    {
        $this->comumRepository = $comumRepository;
    }

    /**
     * Cria novo comum com validações
     * 
     * @param array $dados
     * @return int ID do comum criado
     * @throws Exception Se validação falhar
     */
    public function criar(array $dados): int
    {
        // Validação: Código obrigatório
        if (empty($dados['codigo'])) {
            throw new Exception('Código é obrigatório.');
        }

        // Validação: Código único
        if ($this->comumRepository->buscarPorCodigo((int)$dados['codigo'])) {
            throw new Exception('Código já existe.');
        }

        // Validação: CNPJ válido (se fornecido)
        if (!empty($dados['cnpj'])) {
            try {
                $cnpj = CnpjValidator::validaCnpj($dados['cnpj']);
            } catch (\InvalidArgumentException $e) {
                throw new Exception('CNPJ inválido: ' . $e->getMessage());
            }
        }


        // Regra de negócio: Gerar CNPJ único se necessário
        if (isset($dados['cnpj'])) {
            $dados['cnpj'] = $this->comumRepository->gerarCnpjUnico(
                $dados['cnpj'],
                (int)$dados['codigo']
            );
        }

        // Regra de negócio: Normalizar campos para uppercase
        $dados = $this->normalizarDados($dados);

        return $this->comumRepository->criar($dados);
    }

    /**
     * Atualiza comum existente
     * 
     * @param int $id
     * @param array $dados
     * @return bool
     * @throws Exception Se validação falhar
     */
    public function atualizar(int $id, array $dados): bool
    {
        // Validação: Comum existe
        $comumExistente = $this->comumRepository->buscarPorId($id);
        if (!$comumExistente) {
            throw new Exception('Comum não encontrado.');
        }

        // Validação: Código único (se alterado)
        if (isset($dados['codigo']) && (int)$dados['codigo'] !== (int)$comumExistente['codigo']) {
            if ($this->comumRepository->buscarPorCodigo((int)$dados['codigo'])) {
                throw new Exception('Código já existe.');
            }
        }

        // Validação: CNPJ válido (se fornecido)
        if (!empty($dados['cnpj'])) {
            try {
                $cnpj = CnpjValidator::validaCnpj($dados['cnpj']);
            } catch (\InvalidArgumentException $e) {
                throw new Exception('CNPJ inválido: ' . $e->getMessage());
            }

            // Regra de negócio: Gerar CNPJ único
            $dados['cnpj'] = $this->comumRepository->gerarCnpjUnico(
                $dados['cnpj'],
                (int)($dados['codigo'] ?? $comumExistente['codigo']),
                $id
            );
        }

        // Regra de negócio: Normalizar campos
        $dados = $this->normalizarDados($dados);

        return $this->comumRepository->atualizar($id, $dados);
    }

    /**
     * Deleta comum
     * 
     * @param int $id
     * @return bool
     * @throws Exception Se comum não pode ser deletado
     */
    public function deletar(int $id): bool
    {
        // Validação: Comum existe
        $comum = $this->comumRepository->buscarPorId($id);
        if (!$comum) {
            throw new Exception('Comum não encontrado.');
        }

        // TODO: Validar se comum tem produtos vinculados (regra de negócio futura)

        return $this->comumRepository->deletar($id);
    }

    /**
     * Busca comuns com paginação e filtros
     * 
     * @param string $busca
     * @param int $limite
     * @param int $offset
     * @return array
     */
    public function buscarPaginado(string $busca = '', int $limite = 10, int $offset = 0): array
    {
        return $this->comumRepository->buscarPaginado($busca, $limite, $offset);
    }

    /**
     * Conta comuns com filtro
     * 
     * @param string $busca
     * @return int
     */
    public function contar(string $busca = ''): int
    {
        return $this->comumRepository->contarComFiltro($busca);
    }

    /**
     * Garante que existe um comum com o código informado
     * Se não existir, cria com dados básicos
     * 
     * @param int $codigo
     * @param array $dados Dados opcionais para criação
     * @return int ID do comum
     * @throws Exception
     */
    public function garantirPorCodigo(int $codigo, array $dados = []): int
    {
        return $this->comumRepository->garantirPorCodigo($codigo, $dados);
    }

    /**
     * Busca comum por código
     * 
     * @param int $codigo
     * @return array|null
     */
    public function buscarPorCodigo(int $codigo): ?array
    {
        return $this->comumRepository->buscarPorCodigo($codigo);
    }

    /**
     * Busca comum por ID
     * 
     * @param int $id
     * @return array|null
     */
    public function buscarPorId(int $id): ?array
    {
        return $this->comumRepository->buscarPorId($id);
    }

    /**
     * Normaliza dados para uppercase (regra de negócio do sistema)
     * 
     * @param array $dados
     * @return array
     */
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
