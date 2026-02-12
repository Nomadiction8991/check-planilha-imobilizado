<?php

namespace App\Services;

use App\Repositories\UsuarioRepository;
use App\Core\SessionManager;
use Exception;

/**
 * UsuarioService - Serviço de Lógica de Negócio para Usuários
 * 
 * SOLID Principles:
 * - Single Responsibility: Gerencia APENAS lógica de negócio de usuários
 * - Dependency Inversion: Depende de UsuarioRepository (abstração)
 * - Open/Closed: Extensível sem modificar código existente
 * 
 * Responsabilidades:
 * - Validações de negócio (CPF duplicado, email duplicado)
 * - Regras de criação/atualização de usuários
 * - Transformações de dados (uppercase, hash senha)
 * - Orquestração entre Repository e Controller
 * 
 * @package App\Services
 */
class UsuarioService
{
    private UsuarioRepository $usuarioRepository;

    public function __construct(UsuarioRepository $usuarioRepository)
    {
        $this->usuarioRepository = $usuarioRepository;
    }

    /**
     * Cria novo usuário com validações completas
     * 
     * @param array $dados
     * @return int ID do usuário criado
     * @throws Exception Se validação falhar
     */
    public function criar(array $dados): int
    {
        // Validação: Email obrigatório
        if (empty($dados['email'])) {
            throw new Exception('E-mail é obrigatório.');
        }

        // Validação: Email único
        if ($this->usuarioRepository->emailExiste($dados['email'])) {
            throw new Exception('E-mail já cadastrado.');
        }

        // Validação: CPF único (se fornecido)
        if (!empty($dados['cpf']) && $this->usuarioRepository->cpfExiste($dados['cpf'])) {
            throw new Exception('CPF já cadastrado.');
        }

        // Validação: Senha obrigatória
        if (empty($dados['senha'])) {
            throw new Exception('Senha é obrigatória.');
        }

        // Regra de negócio: Normalizar campos para uppercase (exceto senha)
        $dados = $this->normalizarDados($dados);

        // Delega para Repository
        return $this->usuarioRepository->criarUsuario($dados);
    }

    /**
     * Atualiza usuário existente com validações
     * 
     * @param int $id
     * @param array $dados
     * @return bool
     * @throws Exception Se validação falhar
     */
    public function atualizar(int $id, array $dados): bool
    {
        // Validação: Usuário existe
        $usuarioExistente = $this->usuarioRepository->buscarPorId($id);
        if (!$usuarioExistente) {
            throw new Exception('Usuário não encontrado.');
        }

        // Validação: Email único (ignorando o próprio)
        if (!empty($dados['email']) && $this->usuarioRepository->emailExiste($dados['email'], $id)) {
            throw new Exception('E-mail já cadastrado por outro usuário.');
        }

        // Validação: CPF único (ignorando o próprio)
        if (!empty($dados['cpf']) && $this->usuarioRepository->cpfExiste($dados['cpf'], $id)) {
            throw new Exception('CPF já cadastrado por outro usuário.');
        }

        // Regra de negócio: Normalizar campos
        $dados = $this->normalizarDados($dados);

        // Delega para Repository
        return $this->usuarioRepository->atualizarUsuario($id, $dados);
    }

    /**
     * Deleta usuário
     * 
     * @param int $id
     * @return bool
     * @throws Exception Se usuário não pode ser deletado
     */
    public function deletar(int $id): bool
    {
        // Regra de negócio: Não pode deletar próprio usuário
        if (SessionManager::getUserId() === $id) {
            throw new Exception('Você não pode deletar sua própria conta.');
        }

        // Validação: Usuário existe
        $usuario = $this->usuarioRepository->buscarPorId($id);
        if (!$usuario) {
            throw new Exception('Usuário não encontrado.');
        }

        return $this->usuarioRepository->deletar($id);
    }

    /**
     * Busca usuários com paginação e filtros
     * 
     * @param int $pagina
     * @param int $limite
     * @param array $filtros
     * @return array
     */
    public function buscarPaginado(int $pagina, int $limite, array $filtros = []): array
    {
        return $this->usuarioRepository->buscarPaginadoComFiltros($pagina, $limite, $filtros);
    }

    /**
     * Busca usuário por ID
     * 
     * @param int $id
     * @return array|null
     */
    public function buscarPorId(int $id): ?array
    {
        return $this->usuarioRepository->buscarPorId($id);
    }

    /**
     * Normaliza dados para uppercase (regra de negócio do sistema)
     * 
     * @param array $dados
     * @return array
     */
    private function normalizarDados(array $dados): array
    {
        $camposUppercase = [
            'nome',
            'email',
            'telefone',
            'endereco',
            'cidade',
            'estado',
            'rg',
            'cidade_natural',
            'estado_natural',
            'nome_conjuge'
        ];

        foreach ($camposUppercase as $campo) {
            if (isset($dados[$campo]) && is_string($dados[$campo])) {
                $dados[$campo] = mb_strtoupper($dados[$campo], 'UTF-8');
            }
        }

        return $dados;
    }

    /**
     * Valida CPF (regra de negócio)
     * 
     * @param string $cpf
     * @return bool
     */
    public function validarCpf(string $cpf): bool
    {
        $cpf = preg_replace('/\D/', '', $cpf);

        if (strlen($cpf) != 11) {
            return false;
        }

        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            $d = 0;
            for ($c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }

        return true;
    }
}
