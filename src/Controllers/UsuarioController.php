<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\SessionManager;
use App\Services\UsuarioService;
use App\Repositories\UsuarioRepository;
use App\Core\ViewRenderer;
use App\Core\ConnectionManager;
use PDO;
use Exception;

class UsuarioController extends BaseController
{
    private UsuarioService $usuarioService;
    private UsuarioRepository $usuarioRepository;
    private PDO $conexao;

    public function __construct(?PDO $conexao = null)
    {
        if ($conexao === null) {
            $conexao = ConnectionManager::getConnection();
        }

        $this->conexao = $conexao;
        $this->usuarioRepository = new UsuarioRepository($conexao);
        $this->usuarioService = new UsuarioService($this->usuarioRepository);
    }

    public function index(): void
    {
        $pagina = max(1, (int) $this->query('pagina', 1));
        $limite = 20;

        $filtros = [
            'busca' => trim($this->query('busca', '')),
            'status' => $this->query('status', $this->query('STATUS', ''))
        ];

        try {
            $resultado = $this->usuarioService->buscarPaginado($pagina, $limite, $filtros);

            ViewRenderer::render('users/index', [
                'pageTitle' => 'USUÁRIOS',
                'backUrl' => '/churches',
                'headerActions' => '<a href="/users/create" class="btn-header-action" title="NOVO USUÁRIO"><i class="bi bi-plus-lg"></i></a>',
                'usuarios' => $resultado['dados'],
                'total' => $resultado['total'],
                'pagina' => $pagina,
                'limite' => $limite,
                'totalPaginas' => $resultado['totalPaginas'],
                'busca' => $filtros['busca'],
                'status' => $filtros['status']
            ]);
        } catch (\Throwable $e) {
            error_log('ERROR UsuarioController::index: ' . $e->getMessage());

            ViewRenderer::render('users/index', [
                'pageTitle' => 'USUÁRIOS',
                'backUrl' => '/churches',
                'headerActions' => '<a href="/users/create" class="btn-header-action" title="NOVO USUÁRIO"><i class="bi bi-plus-lg"></i></a>',
                'usuarios' => [],
                'total' => 0,
                'pagina' => 1,
                'limite' => 10,
                'totalPaginas' => 1,
                'busca' => $filtros['busca'],
                'status' => $filtros['status']
            ]);
        }
    }

    public function create(): void
    {
        if ($this->isPost()) {
            $this->store();
            return;
        }

        ViewRenderer::render('users/create', [
            'pageTitle' => 'NOVO USUÁRIO',
            'backUrl' => '/users',
            'headerActions' => '',
            'publicRegister' => false,
            'errors' => [],
            'old' => $_SESSION['old_input'] ?? []
        ]);

        unset($_SESSION['old_input']);
    }

    public function store(): void
    {
        try {
            $dados = $this->coletarDadosFormulario();
        // ao cadastrar novo usuário garantimos ativo=1 se não informado
        if (!isset($dados['ativo'])) {
            $dados['ativo'] = 1;
        }

            $this->validarUsuario($dados);

            $id = $this->usuarioService->criar($dados);

            $this->redirecionarAposOperacao('success=1', 'Usuário cadastrado com sucesso!');
        } catch (Exception $e) {
            $this->renderizarFormularioLegado([
                'erro' => $e->getMessage(),
                'dados' => $_POST
            ]);
        }
    }

    public function edit(): void
    {
        $id = (int) $this->query('id', 0);

        if ($id <= 0) {
            $this->redirecionar('/users?erro=ID inválido');
            return;
        }

        $usuario = $this->usuarioService->buscarPorId($id);

        if (!$usuario) {
            $this->redirecionar('/users?erro=Usuário não encontrado');
            return;
        }

        if ($this->isPost()) {
            $this->update($id);
            return;
        }

        $this->renderizarFormularioEdicaoLegado($usuario);
    }

    public function update(int $id): void
    {
        try {
            $dados = $this->coletarDadosFormulario();

            $this->validarUsuario($dados, $id);

            $this->usuarioService->atualizar($id, $dados);

            $this->redirecionarAposOperacao('success=1', 'Usuário atualizado com sucesso!');
        } catch (Exception $e) {
            $usuario = $this->usuarioService->buscarPorId($id);
            $this->renderizarFormularioEdicaoLegado($usuario, $e->getMessage());
        }
    }

    public function delete(): void
    {
        $id = (int) $this->post('id', 0);

        try {
            if ($id <= 0) {
                throw new Exception('ID inválido.');
            }

            $usuario = $this->usuarioService->buscarPorId($id);
            if (!$usuario) {
                throw new Exception('Usuário não encontrado.');
            }

            $this->usuarioService->deletar($id);

            $this->setMensagem('Usuário deletado com sucesso!', 'success');
            $this->redirecionar('/users');
        } catch (Exception $e) {
            $this->setMensagem('Erro ao deletar usuário: ' . $e->getMessage(), 'danger');
            $this->redirecionar('/users');
        }
    }

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

        $senha = trim($this->post('senha', ''));
        if ($senha !== '') {
            $dados['senha'] = $senha;
        }

        if ($dados['rg_igual_cpf']) {
            $dados['rg'] = $dados['cpf'];
        } else {
            $dados['rg'] = $formatarRg($dados['rg']);
        }

        if ($dados['casado']) {
            if ($dados['rg_conjuge_igual_cpf'] && !empty($dados['cpf_conjuge'])) {
                $dados['rg_conjuge'] = $dados['cpf_conjuge'];
            } elseif (!empty($dados['rg_conjuge'])) {
                $dados['rg_conjuge'] = $formatarRg($dados['rg_conjuge']);
            }
        } else {
            $dados['nome_conjuge'] = '';
            $dados['cpf_conjuge'] = '';
            $dados['rg_conjuge'] = '';
            $dados['telefone_conjuge'] = '';
        }

        return $dados;
    }

    private function validarUsuario(array $dados, ?int $ignorarId = null): void
    {
        if (empty($dados['nome'])) {
            throw new Exception('O nome é obrigatório.');
        }

        if (empty($dados['email'])) {
            throw new Exception('O e-mail é obrigatório.');
        }

        if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('E-mail inválido.');
        }

        if (isset($dados['senha'])) {
            if (strlen($dados['senha']) < 6) {
                throw new Exception('A senha deve ter no mínimo 6 caracteres.');
            }

            $confirmarSenha = trim($this->post('confirmar_senha', ''));
            if ($dados['senha'] !== $confirmarSenha) {
                throw new Exception('As senhas não conferem.');
            }
        } elseif ($ignorarId === null) {
            throw new Exception('A senha é obrigatória.');
        }

        if (empty($dados['cpf'])) {
            throw new Exception('O CPF é obrigatório.');
        }

        $cpfNumeros = preg_replace('/\D/', '', $dados['cpf']);
        if (strlen($cpfNumeros) !== 11) {
            throw new Exception('CPF inválido. Deve conter 11 dígitos.');
        }

        $rgNumeros = preg_replace('/\D/', '', $dados['rg']);
        if (strlen($rgNumeros) < 2) {
            throw new Exception('O RG é obrigatório e deve ter ao menos 2 dígitos.');
        }

        if (empty($dados['telefone'])) {
            throw new Exception('O telefone é obrigatório.');
        }

        $telefoneNumeros = preg_replace('/\D/', '', $dados['telefone']);
        if (strlen($telefoneNumeros) < 10 || strlen($telefoneNumeros) > 11) {
            throw new Exception('Telefone inválido.');
        }

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
        $this->redirecionar('/users?' . $query);
    }

    private function renderizarListagemLegada(array $dados): void
    {
        extract($dados);

        $conexao = ConnectionManager::getConnection();

        $usuarios = $dados['usuarios'];
        $total_registros = $dados['total'];
        $total_registros_all = $dados['totalGeral'];
        $total_paginas = $dados['totalPaginas'];
        $pagina = $dados['pagina'];
        $filtroNome = $dados['filtros']['busca'];
        $filtroStatus = $dados['filtros']['status'];
        $erro = $dados['erro'];

        require __DIR__ . '/../Views/users/list.php';
    }

    private function renderizarFormularioLegado(array $dados): void
    {
        $mensagem = $dados['erro'] ?? '';
        $tipo_mensagem = $mensagem ? 'error' : '';

        require __DIR__ . '/../Views/users/create-legacy.php';
    }

    private function renderizarFormularioEdicaoLegado(array $usuario, string $erro = ''): void
    {
        $mensagem = $erro;
        $tipo_mensagem = $erro ? 'error' : '';

        require __DIR__ . '/../Views/users/edit.php';
    }

    /**
     * Exibe detalhes de um usuário (GET /users/show?id=X)
     */
    public function show(): void
    {
        $id = (int) $this->query('id', 0);

        if ($id <= 0) {
            $this->redirecionar('/users?erro=ID inválido');
            return;
        }

        $conexao = $this->conexao;
        require __DIR__ . '/../Views/users/show.php';
    }

    /**
     * Seleciona a comum de trabalho do usuário (POST /users/select-church)
     */
    public function selecionarComum(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->isPost()) {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            exit;
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'JSON inválido: ' . json_last_error_msg()]);
                exit;
            }

            $comumId = (int) ($input['comum_id'] ?? 0);

            if ($comumId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID da comum inválido']);
                exit;
            }

            $usuarioId = $_SESSION['usuario_id'] ?? 0;
            if ($usuarioId <= 0) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
                exit;
            }

            // Atualizar comum_id do usuário
            $resultado = $this->usuarioRepository->atualizarUsuario($usuarioId, ['comum_id' => $comumId]);

            if (!$resultado) {
                throw new \Exception('Falha ao atualizar comum_id no banco de dados');
            }

            // Atualizar sessão
            SessionManager::setComumId($comumId);

            echo json_encode(['success' => true, 'message' => 'Comum selecionada com sucesso']);
        } catch (\Exception $e) {
            error_log('ERROR UsuarioController::selecionarComum: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao selecionar comum: ' . $e->getMessage()]);
        }
        exit;
    }
}
