<?php
// Autenticação
require_once dirname(__DIR__, 2) . '/bootstrap.php';

$id = $_GET['id'] ?? null;
$mensagem = '';
$tipo_mensagem = '';

if (!$id) {
    header('Location: ./usuarios_listar.php');
    exit;
}

// Nova regra: qualquer usuário só pode alterar o PRÓPRIO cadastro
$loggedId = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 0;
if ((int)$id !== $loggedId) {
    header('Location: ../../../index.php');
    exit;
}

// Buscar usuário
try {
    $stmt = $conexao->prepare('SELECT * FROM usuarios WHERE id = :id');
    $stmt->bindValue(':id', $id);
    $stmt->execute();
    $usuario = $stmt->fetch();

    if (!$usuario) {
        throw new Exception('USUÁRIO NÃO ENCONTRADO.');
    }
} catch (Exception $e) {
    $mensagem = 'Erro: ' . $e->getMessage();
    $tipo_mensagem = 'error';
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');
    $confirmar_senha = trim($_POST['confirmar_senha'] ?? '');
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    
    // Novos campos
    $cpf = trim($_POST['cpf'] ?? '');
    $rg = trim($_POST['rg'] ?? '');
    $rg_igual_cpf = isset($_POST['rg_igual_cpf']) ? 1 : 0;
    $telefone = trim($_POST['telefone'] ?? '');
    $tipo = trim($_POST['tipo'] ?? 'Administrador/Acessor');
    $assinatura = trim($_POST['assinatura'] ?? '');
    $casado = isset($_POST['casado']) ? 1 : 0;
    $nome_conjuge = trim($_POST['nome_conjuge'] ?? '');
    $cpf_conjuge = trim($_POST['cpf_conjuge'] ?? '');
    $rg_conjuge = trim($_POST['rg_conjuge'] ?? '');
    $rg_conjuge_igual_cpf = isset($_POST['rg_conjuge_igual_cpf']) ? 1 : 0;
    $telefone_conjuge = trim($_POST['telefone_conjuge'] ?? '');
    $assinatura_conjuge = trim($_POST['assinatura_conjuge'] ?? '');
    
    // Endereço
    $endereco_cep = trim($_POST['endereco_cep'] ?? '');
    $endereco_logradouro = trim($_POST['endereco_logradouro'] ?? '');
    $endereco_numero = trim($_POST['endereco_numero'] ?? '');
    $endereco_complemento = trim($_POST['endereco_complemento'] ?? '');
    $endereco_bairro = trim($_POST['endereco_bairro'] ?? '');
    $endereco_cidade = trim($_POST['endereco_cidade'] ?? '');
    $endereco_estado = trim($_POST['endereco_estado'] ?? '');

    try {
        // Validações
        if (empty($nome)) {
            throw new Exception('O NOME É OBRIGATÓRIO.');
        }

        if (empty($email)) {
            throw new Exception('O EMAIL É OBRIGATÓRIO.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('EMAIL INVÁLIDO.');
        }
        
        // Validar CPF (se preenchido)
        if (!empty($cpf)) {
            $cpf_numeros = preg_replace('/\D/', '', $cpf);
            if (strlen($cpf_numeros) !== 11) {
                throw new Exception('CPF INVÁLIDO. DEVE CONTER 11 DÍGITOS.');
            }
            
            // Verificar se CPF já existe (exceto o próprio usuário)
            $stmt = $conexao->prepare('SELECT id FROM usuarios WHERE cpf = :cpf AND id != :id');
            $stmt->bindValue(':cpf', $cpf);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
            if ($stmt->fetch()) {
                throw new Exception('ESTE CPF JÁ ESTÁ CADASTRADO POR OUTRO USUÁRIO.');
            }
        }
        
        // Validar telefone (se preenchido)
        if (!empty($telefone)) {
            $telefone_numeros = preg_replace('/\D/', '', $telefone);
            if (strlen($telefone_numeros) < 10 || strlen($telefone_numeros) > 11) {
                throw new Exception('TELEFONE INVÁLIDO. DEVE CONTER 10 OU 11 DÍGITOS.');
            }
        }

        // Formatação de RG (todos menos último + '-' + último)
        $formatarRg = function($valor){
            $d = preg_replace('/\D/','', $valor);
            if (strlen($d) <= 1) return $d; // um dígito sem hífen
            return substr($d,0,-1) . '-' . substr($d,-1);
        };
        if ($rg_igual_cpf) { $rg = $cpf; } else { $rg = $formatarRg($rg); }
        $rg_nums = preg_replace('/\D/','', $rg);
        if (strlen($rg_nums) < 2) { throw new Exception('O RG É OBRIGATÓRIO E DEVE TER AO MENOS 2 DÍGITOS.'); }

        // Endereço obrigatório
        if (empty($endereco_cep) || empty($endereco_logradouro) || empty($endereco_numero) || empty($endereco_bairro) || empty($endereco_cidade) || empty($endereco_estado)) {
            throw new Exception('TODOS OS CAMPOS DE ENDEREÇO (CEP, LOGRADOURO, NÚMERO, BAIRRO, CIDADE E ESTADO) SÃO OBRIGATÓRIOS.');
        }

        // Se casado, apenas formatar RG se fornecido (dados do cônjuge são opcionais)
        if ($casado) {
            if ($rg_conjuge_igual_cpf && !empty($cpf_conjuge)) { 
                $rg_conjuge = $cpf_conjuge; 
            } else if (!empty($rg_conjuge)) { 
                $rg_conjuge = $formatarRg($rg_conjuge); 
            }
        } else {
            $nome_conjuge = $cpf_conjuge = $rg_conjuge = $telefone_conjuge = $assinatura_conjuge = '';
            $rg_conjuge_igual_cpf = 0;
        }

        // Verificar se email já existe (exceto o próprio usuário)
        $stmt = $conexao->prepare('SELECT id FROM usuarios WHERE email = :email AND id != :id');
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        if ($stmt->fetch()) {
            throw new Exception('ESTE EMAIL JÁ ESTÁ CADASTRADO POR OUTRO USUÁRIO.');
        }

        // Atualizar dados
        if (!empty($senha)) {
            // Se senha foi informada, validar e atualizar
            if (strlen($senha) < 6) {
                throw new Exception('A SENHA DEVE TER NO MÍNIMO 6 CARACTERES.');
            }

            if ($senha !== $confirmar_senha) {
                throw new Exception('AS SENHAS NÃO CONFEREM.');
            }

            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $sql = "UPDATE usuarios SET 
                    nome = :nome, 
                    email = :email, 
                    senha = :senha, 
                    ativo = :ativo,
                    cpf = :cpf,
                    rg = :rg,
                    rg_igual_cpf = :rg_igual_cpf,
                    telefone = :telefone,
                    tipo = :tipo,
                    assinatura = :assinatura,
                    endereco_cep = :endereco_cep,
                    endereco_logradouro = :endereco_logradouro,
                    endereco_numero = :endereco_numero,
                    endereco_complemento = :endereco_complemento,
                    endereco_bairro = :endereco_bairro,
                    endereco_cidade = :endereco_cidade,
                    endereco_estado = :endereco_estado,
                    casado = :casado,
                    nome_conjuge = :nome_conjuge,
                    cpf_conjuge = :cpf_conjuge,
                    rg_conjuge = :rg_conjuge,
                    telefone_conjuge = :telefone_conjuge,
                    assinatura_conjuge = :assinatura_conjuge,
                    rg_conjuge_igual_cpf = :rg_conjuge_igual_cpf
                    WHERE id = :id";
            $stmt = $conexao->prepare($sql);
            $stmt->bindValue(':senha', $senha_hash);
        } else {
            // Sem alteração de senha
            $sql = "UPDATE usuarios SET 
                    nome = :nome, 
                    email = :email, 
                    ativo = :ativo,
                    cpf = :cpf,
                    rg = :rg,
                    rg_igual_cpf = :rg_igual_cpf,
                    telefone = :telefone,
                    tipo = :tipo,
                    assinatura = :assinatura,
                    endereco_cep = :endereco_cep,
                    endereco_logradouro = :endereco_logradouro,
                    endereco_numero = :endereco_numero,
                    endereco_complemento = :endereco_complemento,
                    endereco_bairro = :endereco_bairro,
                    endereco_cidade = :endereco_cidade,
                    endereco_estado = :endereco_estado,
                    casado = :casado,
                    nome_conjuge = :nome_conjuge,
                    cpf_conjuge = :cpf_conjuge,
                    rg_conjuge = :rg_conjuge,
                    telefone_conjuge = :telefone_conjuge,
                    assinatura_conjuge = :assinatura_conjuge,
                    rg_conjuge_igual_cpf = :rg_conjuge_igual_cpf
                    WHERE id = :id";
            $stmt = $conexao->prepare($sql);
        }

        $stmt->bindValue(':nome', $nome);
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':ativo', $ativo);
        $stmt->bindValue(':cpf', $cpf);
        $stmt->bindValue(':rg', $rg);
        $stmt->bindValue(':rg_igual_cpf', $rg_igual_cpf, PDO::PARAM_INT);
        $stmt->bindValue(':telefone', $telefone);
        $stmt->bindValue(':tipo', $tipo);
        $stmt->bindValue(':assinatura', $assinatura);
        $stmt->bindValue(':endereco_cep', $endereco_cep);
        $stmt->bindValue(':endereco_logradouro', $endereco_logradouro);
        $stmt->bindValue(':endereco_numero', $endereco_numero);
        $stmt->bindValue(':endereco_complemento', $endereco_complemento);
        $stmt->bindValue(':endereco_bairro', $endereco_bairro);
        $stmt->bindValue(':endereco_cidade', $endereco_cidade);
        $stmt->bindValue(':endereco_estado', $endereco_estado);
        $stmt->bindValue(':casado', $casado, PDO::PARAM_INT);
        $stmt->bindValue(':nome_conjuge', $nome_conjuge);
        $stmt->bindValue(':cpf_conjuge', $cpf_conjuge);
        $stmt->bindValue(':rg_conjuge', $rg_conjuge);
        $stmt->bindValue(':telefone_conjuge', $telefone_conjuge);
        $stmt->bindValue(':assinatura_conjuge', $assinatura_conjuge);
        $stmt->bindValue(':rg_conjuge_igual_cpf', $rg_conjuge_igual_cpf, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id);
        $stmt->execute();

        // Redirecionar para listagem com mensagem de sucesso
        header('Location: ./usuarios_listar.php?updated=1');
        exit;

    } catch (Exception $e) {
        $mensagem = 'ERRO: ' . $e->getMessage();
        $tipo_mensagem = 'error';
    }
}
?>
