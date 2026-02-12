<?php

namespace App\Controllers;

use App\Repositories\UsuarioRepository;
use PDO;
use Exception;

/**
 * Controller de Usuários
 * Gerencia CRUD completo de usuários
 */
class UsuarioController extends BaseController
{
    private UsuarioRepository $usuarioRepo;

    public function __construct(PDO $conexao)
    {
        $this->usuarioRepo = new UsuarioRepository($conexao);
    }

    /**
     * Lista usuários com paginação e filtros
     */
    public function index(): void
    {
        $pagina = max(1, (int) $this->query('pagina', 1));
        $limite = 10;

        $filtros = [
            'busca' => trim($this->query('busca', '')),
            'status' => $this->query('status', $this->query('STATUS', ''))
        ];

        try {
            $resultado = $this->usuarioRepo->buscarPaginadoComFiltros($pagina, $limite, $filtros);

            // Dados para a view
            $dados = [
                'usuarios' => $resultado['dados'],
                'total' => $resultado['total'],
                'totalGeral' => $resultado['totalGeral'],
                'pagina' => $pagina,
                'limite' => $limite,
                'totalPaginas' => $resultado['totalPaginas'],
                'filtros' => $filtros,
                'erro' => $this->query('erro', '')
            ];

            // Renderizar compatibilidade legada (temporário)
            $this->renderizarListagemLegada($dados);
        } catch (\Throwable $e) {
            error_log('ERROR UsuarioController::index: ' . $e->getMessage());

            $this->renderizarListagemLegada([
                'usuarios' => [],
                'total' => 0,
                'totalGeral' => 0,
                'pagina' => 1,
                'limite' => 10,
                'totalPaginas' => 1,
                'filtros' => $filtros,
                'erro' => 'Erro ao buscar usuários. Verifique os logs.'
            ]);
        }
    }

    /**
     * Exibe formulário de criação de usuário
     */
    public function create(): void
    {
        if ($this->isPost()) {
            $this->store();
            return;
        }

        // Renderizar formulário (temporário - incluir view legada)
        $this->renderizarFormularioLegado([]);
    }

    /**
     * Processa criação de novo usuário
     */
    public function store(): void
    {
        try {
            // Coletar dados do formulário
            $dados = $this->coletarDadosFormulario();

            // Validar dados
            $this->validarUsuario($dados);

            // Verificar duplicações
            if ($this->usuarioRepo->emailExiste($dados['email'])) {
                throw new Exception('Este e-mail já está cadastrado.');
            }

            if ($this->usuarioRepo->cpfExiste($dados['cpf'])) {
                throw new Exception('Este CPF já está cadastrado.');
            }

            // Criar usuário
            $id = $this->usuarioRepo->criarUsuario($dados);

            // Redirecionar com sucesso
            $this->redirecionarAposOperacao('success=1', 'Usuário cadastrado com sucesso!');
        } catch (Exception $e) {
            // Renderizar formulário com erro
            $this->renderizarFormularioLegado([
                'erro' => $e->getMessage(),
                'dados' => $_POST
            ]);
        }
    }

    /**
     * Exibe formulário de edição
     */
    public function edit(): void
    {
        $id = (int) $this->query('id', 0);

        if ($id <= 0) {
            $this->redirecionar('app/views/usuarios/usuarios_listar.php?erro=ID inválido');
            return;
        }

        $usuario = $this->usuarioRepo->buscarPorId($id);

        if (!$usuario) {
            $this->redirecionar('app/views/usuarios/usuarios_listar.php?erro=Usuário não encontrado');
            return;
        }

        if ($this->isPost()) {
            $this->update($id);
            return;
        }

        // Renderizar formulário de edição (temporário)
        $this->renderizarFormularioEdicaoLegado($usuario);
    }

    /**
     * Processa atualização de usuário
     */
    public function update(int $id): void
    {
        try {
            $dados = $this->coletarDadosFormulario();

            // Validar dados
            $this->validarUsuario($dados, $id);

            // Verificar duplicações (exceto o próprio usuário)
            if ($this->usuarioRepo->emailExiste($dados['email'], $id)) {
                throw new Exception('Este e-mail já está cadastrado.');
            }

            if ($this->usuarioRepo->cpfExiste($dados['cpf'], $id)) {
                throw new Exception('Este CPF já está cadastrado.');
            }

            // Atualizar
            $this->usuarioRepo->atualizarUsuario($id, $dados);

            // Redirecionar com sucesso
            $this->redirecionarAposOperacao('success=1', 'Usuário atualizado com sucesso!');
        } catch (Exception $e) {
            $usuario = $this->usuarioRepo->buscarPorId($id);
            $this->renderizarFormularioEdicaoLegado($usuario, $e->getMessage());
        }
    }

    /**
     * Deleta usuário
     */
    public function delete(): void
    {
        $id = (int) $this->post('id', 0);

        try {
            if ($id <= 0) {
                throw new Exception('ID inválido.');
            }

            $usuario = $this->usuarioRepo->buscarPorId($id);
            if (!$usuario) {
                throw new Exception('Usuário não encontrado.');
            }

            $this->usuarioRepo->deletar($id);

            $this->setMensagem('Usuário deletado com sucesso!', 'success');
            $this->redirecionar('app/views/usuarios/usuarios_listar.php');
        } catch (Exception $e) {
            $this->setMensagem('Erro ao deletar usuário: ' . $e->getMessage(), 'danger');
            $this->redirecionar('app/views/usuarios/usuarios_listar.php');
        }
    }

    /**
     * Coleta dados do formulário
     */
    private function coletarDadosFormulario(): array
    {
        $formatarRg = function ($valor) {
            $d = preg_replace('/\D/', '', $valor);
            if (strlen($d) <= 1) return $d;
            return substr($d, 0, -1) . '-' . substr($d, -1);
        };

        $dados = [
            'nome' => trim($this->post('nome', '')),
            'email' => mb_strtoupper(trim($this->post('email', '')), 'UTF-8'),
            'ativo' => $this->post('ativo') ? 1 : 0,
            'cpf' => trim($this->post('cpf', '')),
            'rg' => trim($this->post('rg', '')),
            'rg_igual_cpf' => $this->post('rg_igual_cpf') ? 1 : 0,
            'telefone' => trim($this->post('telefone', '')),
            'casado' => $this->post('casado') ? 1 : 0,
            'nome_conjuge' => trim($this->post('nome_conjuge', '')),
            'cpf_conjuge' => trim($this->post('cpf_conjuge', '')),
            'rg_conjuge' => trim($this->post('rg_conjuge', '')),
            'rg_conjuge_igual_cpf' => $this->post('rg_conjuge_igual_cpf') ? 1 : 0,
            'telefone_conjuge' => trim($this->post('telefone_conjuge', '')),
            'endereco_cep' => trim($this->post('endereco_cep', '')),
            'endereco_logradouro' => trim($this->post('endereco_logradouro', '')),
            'endereco_numero' => trim($this->post('endereco_numero', '')),
            'endereco_complemento' => trim($this->post('endereco_complemento', '')),
            'endereco_bairro' => trim($this->post('endereco_bairro', '')),
            'endereco_cidade' => trim($this->post('endereco_cidade', '')),
            'endereco_estado' => trim($this->post('endereco_estado', ''))
        ];

        // Senha (apenas se fornecida)
        $senha = trim($this->post('senha', ''));
        if ($senha !== '') {
            $dados['senha'] = $senha;
        }

        // Formatar RG
        if ($dados['rg_igual_cpf']) {
            $dados['rg'] = $dados['cpf'];
        } else {
            $dados['rg'] = $formatarRg($dados['rg']);
        }

        // Formatar RG do cônjuge
        if ($dados['casado']) {
            if ($dados['rg_conjuge_igual_cpf'] && !empty($dados['cpf_conjuge'])) {
                $dados['rg_conjuge'] = $dados['cpf_conjuge'];
            } elseif (!empty($dados['rg_conjuge'])) {
                $dados['rg_conjuge'] = $formatarRg($dados['rg_conjuge']);
            }
        } else {
            // Se não casado, limpar campos de cônjuge
            $dados['nome_conjuge'] = '';
            $dados['cpf_conjuge'] = '';
            $dados['rg_conjuge'] = '';
            $dados['telefone_conjuge'] = '';
        }

        return $dados;
    }

    /**
     * Valida dados do usuário
     */
    private function validarUsuario(array $dados, ?int $ignorarId = null): void
    {
        // Nome obrigatório
        if (empty($dados['nome'])) {
            throw new Exception('O nome é obrigatório.');
        }

        // Email obrigatório e válido
        if (empty($dados['email'])) {
            throw new Exception('O e-mail é obrigatório.');
        }

        if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('E-mail inválido.');
        }

        // Senha (apenas se fornecida ou em criação)
        if (isset($dados['senha'])) {
            if (strlen($dados['senha']) < 6) {
                throw new Exception('A senha deve ter no mínimo 6 caracteres.');
            }

            $confirmarSenha = trim($this->post('confirmar_senha', ''));
            if ($dados['senha'] !== $confirmarSenha) {
                throw new Exception('As senhas não conferem.');
            }
        } elseif ($ignorarId === null) {
            // Criação: senha obrigatória
            throw new Exception('A senha é obrigatória.');
        }

        // CPF obrigatório e válido
        if (empty($dados['cpf'])) {
            throw new Exception('O CPF é obrigatório.');
        }

        $cpfNumeros = preg_replace('/\D/', '', $dados['cpf']);
        if (strlen($cpfNumeros) !== 11) {
            throw new Exception('CPF inválido. Deve conter 11 dígitos.');
        }

        // RG obrigatório
        $rgNumeros = preg_replace('/\D/', '', $dados['rg']);
        if (strlen($rgNumeros) < 2) {
            throw new Exception('O RG é obrigatório e deve ter ao menos 2 dígitos.');
        }

        // Telefone obrigatório
        if (empty($dados['telefone'])) {
            throw new Exception('O telefone é obrigatório.');
        }

        $telefoneNumeros = preg_replace('/\D/', '', $dados['telefone']);
        if (strlen($telefoneNumeros) < 10 || strlen($telefoneNumeros) > 11) {
            throw new Exception('Telefone inválido.');
        }

        // Endereço obrigatório
        if (
            empty($dados['endereco_cep']) || empty($dados['endereco_logradouro']) ||
            empty($dados['endereco_numero']) || empty($dados['endereco_bairro']) ||
            empty($dados['endereco_cidade']) || empty($dados['endereco_estado'])
        ) {
            throw new Exception('Todos os campos de endereço (CEP, logradouro, número, bairro, cidade e estado) são obrigatórios.');
        }

        // Se casado, validar dados do cônjuge
        if ($dados['casado']) {
            if (empty($dados['nome_conjuge'])) {
                throw new Exception('O nome do cônjuge é obrigatório.');
            }

            if (empty($dados['cpf_conjuge'])) {
                throw new Exception('O CPF do cônjuge é obrigatório.');
            }

            $cpfConjugeNumeros = preg_replace('/\D/', '', $dados['cpf_conjuge']);
            if (strlen($cpfConjugeNumeros) !== 11) {
                throw new Exception('CPF do cônjuge inválido. Deve conter 11 dígitos.');
            }

            if (empty($dados['telefone_conjuge'])) {
                throw new Exception('O telefone do cônjuge é obrigatório.');
            }

            $telefoneConjugeNumeros = preg_replace('/\D/', '', $dados['telefone_conjuge']);
            if (strlen($telefoneConjugeNumeros) < 10 || strlen($telefoneConjugeNumeros) > 11) {
                throw new Exception('Telefone do cônjuge inválido. Deve conter 10 ou 11 dígitos.');
            }
        }
    }

    /**
     * Redireciona após operação preservando filtros
     */
    private function redirecionarAposOperacao(string $queryExtra, string $mensagem = ''): void
    {
        $retQ = [];

        if (!empty($_REQUEST['busca'])) {
            $retQ['busca'] = $_REQUEST['busca'];
        }
        if (isset($_REQUEST['status']) && $_REQUEST['status'] !== '') {
            $retQ['status'] = $_REQUEST['status'];
        }
        if (!empty($_REQUEST['pagina'])) {
            $retQ['pagina'] = $_REQUEST['pagina'];
        }

        if ($mensagem) {
            $this->setMensagem($mensagem, 'success');
        }

        $query = http_build_query($retQ) . ($queryExtra ? '&' . $queryExtra : '');
        $this->redirecionar('app/views/usuarios/usuarios_listar.php?' . $query);
    }

    /**
     * TEMPORÁRIO: Renderiza listagem legada
     * TODO: Criar src/Views/usuarios/index.php
     */
    private function renderizarListagemLegada(array $dados): void
    {
        extract($dados);
        global $conexao;

        // Importar variáveis para compatibilidade
        $usuarios = $dados['usuarios'];
        $total_registros = $dados['total'];
        $total_registros_all = $dados['totalGeral'];
        $total_paginas = $dados['totalPaginas'];
        $pagina = $dados['pagina'];
        $filtroNome = $dados['filtros']['busca'];
        $filtroStatus = $dados['filtros']['status'];
        $erro = $dados['erro'];

        require __DIR__ . '/../../app/views/usuarios/usuarios_listar.php';
    }

    /**
     * TEMPORÁRIO: Renderiza formulário de criação legado
     */
    private function renderizarFormularioLegado(array $dados): void
    {
        $mensagem = $dados['erro'] ?? '';
        $tipo_mensagem = $mensagem ? 'error' : '';

        require __DIR__ . '/../../app/views/usuarios/usuario_criar.php';
    }

    /**
     * TEMPORÁRIO: Renderiza formulário de edição legado
     */
    private function renderizarFormularioEdicaoLegado(array $usuario, string $erro = ''): void
    {
        $mensagem = $erro;
        $tipo_mensagem = $erro ? 'error' : '';

        require __DIR__ . '/../../app/views/usuarios/usuario_editar.php';
    }
}
