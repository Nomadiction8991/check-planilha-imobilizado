<?php

namespace App\Services;

use App\Repositories\UsuarioRepository;
use App\Core\SessionManager;
use Exception;

class UsuarioService
{
    private UsuarioRepository $usuarioRepository;

    public function __construct(UsuarioRepository $usuarioRepository)
    {
        $this->usuarioRepository = $usuarioRepository;
    }

    public function criar(array $dados): int
    {
        if (empty($dados['email'])) {
            throw new Exception('E-mail é obrigatório.');
        }

        if ($this->usuarioRepository->emailExiste($dados['email'])) {
            throw new Exception('E-mail já cadastrado.');
        }

        if (!empty($dados['cpf']) && $this->usuarioRepository->cpfExiste($dados['cpf'])) {
            throw new Exception('CPF já cadastrado.');
        }

        if (empty($dados['senha'])) {
            throw new Exception('Senha é obrigatória.');
        }

        $dados = $this->normalizarDados($dados);

        return $this->usuarioRepository->criarUsuario($dados);
    }

    public function atualizar(int $id, array $dados): bool
    {
        $usuarioExistente = $this->usuarioRepository->buscarPorId($id);
        if (!$usuarioExistente) {
            throw new Exception('Usuário não encontrado.');
        }

        if (!empty($dados['email']) && $this->usuarioRepository->emailExiste($dados['email'], $id)) {
            throw new Exception('E-mail já cadastrado por outro usuário.');
        }

        if (!empty($dados['cpf']) && $this->usuarioRepository->cpfExiste($dados['cpf'], $id)) {
            throw new Exception('CPF já cadastrado por outro usuário.');
        }

        $dados = $this->normalizarDados($dados);

        return $this->usuarioRepository->atualizarUsuario($id, $dados);
    }

    public function deletar(int $id): bool
    {
        if (SessionManager::getUserId() === $id) {
            throw new Exception('Você não pode deletar sua própria conta.');
        }

        $usuario = $this->usuarioRepository->buscarPorId($id);
        if (!$usuario) {
            throw new Exception('Usuário não encontrado.');
        }

        return $this->usuarioRepository->deletar($id);
    }

    public function buscarPaginado(int $pagina, int $limite, array $filtros = []): array
    {
        return $this->usuarioRepository->buscarPaginadoComFiltros($pagina, $limite, $filtros);
    }

    public function buscarPorId(int $id): ?array
    {
        return $this->usuarioRepository->buscarPorId($id);
    }

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
