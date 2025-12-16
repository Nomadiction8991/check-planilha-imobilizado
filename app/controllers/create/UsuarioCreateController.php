<?php
// Se for registro pÃºblico, nÃ£o exige autenticaÃ§Ã£o
if (!defined('PUBLIC_REGISTER')) {
     // AutenticaÃ§Ã£o apenas para admins
}

require_once dirname(__DIR__, 2) . '/bootstrap.php';

$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    // Normalize email to uppercase for consistency (password must not be changed)
    $email = to_uppercase(trim($_POST['email'] ?? ''));
    $senha = trim($_POST['senha'] ?? '');
    $confirmar_senha = trim($_POST['confirmar_senha'] ?? '');
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    
    // Novos campos
    $cpf = trim($_POST['cpf'] ?? '');
    $rg = trim($_POST['rg'] ?? '');
    $rg_igual_cpf = isset($_POST['rg_igual_cpf']) ? 1 : 0;
    $telefone = trim($_POST['telefone'] ?? '');

    
    // Estado civil e cÃ´njuge
    $casado = isset($_POST['casado']) ? 1 : 0;
    $nome_conjuge = trim($_POST['nome_conjuge'] ?? '');
    $cpf_conjuge = trim($_POST['cpf_conjuge'] ?? '');
    $rg_conjuge = trim($_POST['rg_conjuge'] ?? '');
    $rg_conjuge_igual_cpf = isset($_POST['rg_conjuge_igual_cpf']) ? 1 : 0;
    $telefone_conjuge = trim($_POST['telefone_conjuge'] ?? '');
    $assinatura_conjuge = trim($_POST['assinatura_conjuge'] ?? '');
    
    // EndereÃ§o
    $endereco_cep = trim($_POST['endereco_cep'] ?? '');
    $endereco_logradouro = trim($_POST['endereco_logradouro'] ?? '');
    $endereco_numero = trim($_POST['endereco_numero'] ?? '');
    $endereco_complemento = trim($_POST['endereco_complemento'] ?? '');
    $endereco_bairro = trim($_POST['endereco_bairro'] ?? '');
    $endereco_cidade = trim($_POST['endereco_cidade'] ?? '');
    $endereco_estado = trim($_POST['endereco_estado'] ?? '');

    try {
        // ValidaÃ§Ãµes
        if (empty($nome)) {
            throw new Exception('O nome é obrigatório.');
        }

        if (empty($email)) {
            throw new Exception('O e-mail é obrigatório.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('E-mail inválido.');
        }

        if (empty($senha)) {
            throw new Exception('A senha é obrigatória.');
        }

        if (strlen($senha) < 6) {
            throw new Exception('A senha deve ter no mínimo 6 caracteres.');
        }

        if ($senha !== $confirmar_senha) {
            throw new Exception('As senhas não conferem.');
        }
        
        // Validar CPF (bÃ¡sico: apenas formato)
        if (empty($cpf)) {
            throw new Exception('O CPF é obrigatório.');
        }

        $cpf_numeros = preg_replace('/\D/', '', $cpf);
        if (strlen($cpf_numeros) !== 11) {
            throw new Exception('CPF inválido. Deve conter 11 dígitos.');
        }
        
        // FunÃ§Ã£o para formatar RG (todos menos Ãºltimo + '-' + Ãºltimo)
        $formatarRg = function($valor){
            $d = preg_replace('/\D/','', $valor);
            if (strlen($d) <= 1) return $d; // um dÃ­gito sem hÃ­fen
            return substr($d,0,-1) . '-' . substr($d,-1);
        };
        if ($rg_igual_cpf) {
            // Se RG igual CPF, mantÃ©m exatamente o CPF informado (com mÃ¡scara) para RG
            $rg = $cpf;
        } else {
            $rg = $formatarRg($rg);
        }
        $rg_numeros = preg_replace('/\D/','', $rg);
        if (strlen($rg_numeros) < 2) {
            throw new Exception('O RG é obrigatório e deve ter ao menos 2 dígitos.');
        }

        // Validar telefone (bÃ¡sico: formato)
        if (empty($telefone)) {
            throw new Exception('O telefone é obrigatório.');
        }

        $telefone_numeros = preg_replace('/\D/', '', $telefone);
        if (strlen($telefone_numeros) < 10 || strlen($telefone_numeros) > 11) {
            throw new Exception('Telefone inválido.');
        }

        // Verificar se email jÃ¡ existe
        $stmt = $conexao->prepare('SELECT id FROM usuarios WHERE email = :email');
        $stmt->bindValue(':email', $email);
        $stmt->execute();
        if ($stmt->fetch()) {
            throw new Exception('Este e-mail já está cadastrado.');
        }
        
        // Verificar se CPF jÃ¡ existe
        $stmt = $conexao->prepare('SELECT id FROM usuarios WHERE cpf = :cpf');
        $stmt->bindValue(':cpf', $cpf);
        $stmt->execute();
        if ($stmt->fetch()) {
            throw new Exception('Este CPF já está cadastrado.');
        }

        // EndereÃ§o obrigatÃ³rio (CEP, logradouro, numero, bairro, cidade, estado)
        if (empty($endereco_cep) || empty($endereco_logradouro) || empty($endereco_numero) || empty($endereco_bairro) || empty($endereco_cidade) || empty($endereco_estado)) {
            throw new Exception('Todos os campos de endereço (CEP, logradouro, número, bairro, cidade e estado) são obrigatórios.');
        }



        // Se casado, apenas formatar RG se fornecido (dados do cônjuge são opcionais)
        if ($casado) {
            if ($rg_conjuge_igual_cpf && !empty($cpf_conjuge)) {
                $rg_conjuge = $cpf_conjuge; // mantém máscara de CPF no RG do cônjuge
            } else if (!empty($rg_conjuge)) {
                $rg_conjuge = $formatarRg($rg_conjuge);
            }
        } else {
            // Se não casado, limpar campos de cônjuge para evitar dados órfãos
            $nome_conjuge = $cpf_conjuge = $rg_conjuge = $telefone_conjuge = '';

        // INSERT: campos atualizados (removidas colunas de assinatura)
        $sql = "INSERT INTO usuarios (
                    nome, email, senha, ativo, cpf, rg, rg_igual_cpf, telefone,
                    endereco_cep, endereco_logradouro, endereco_numero, endereco_complemento,
                    endereco_bairro, endereco_cidade, endereco_estado,
                    casado, nome_conjuge, cpf_conjuge, rg_conjuge, rg_conjuge_igual_cpf, telefone_conjuge
                ) VALUES (
                    :nome, :email, :senha, :ativo, :cpf, :rg, :rg_igual_cpf, :telefone,
                    :endereco_cep, :endereco_logradouro, :endereco_numero, :endereco_complemento,
                    :endereco_bairro, :endereco_cidade, :endereco_estado,
                    :casado, :nome_conjuge, :cpf_conjuge, :rg_conjuge, :rg_conjuge_igual_cpf, :telefone_conjuge
                )";
        
        $stmt = $conexao->prepare($sql);
        $stmt->bindValue(':nome', $nome);
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':senha', $senha_hash);
        $stmt->bindValue(':ativo', $ativo, PDO::PARAM_INT);
        $stmt->bindValue(':cpf', $cpf);
    $stmt->bindValue(':rg', $rg);
    $stmt->bindValue(':rg_igual_cpf', $rg_igual_cpf, PDO::PARAM_INT);
        $stmt->bindValue(':telefone', $telefone);
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
    $stmt->bindValue(':rg_conjuge_igual_cpf', $rg_conjuge_igual_cpf, PDO::PARAM_INT);
    $stmt->bindValue(':telefone_conjuge', $telefone_conjuge);
        $stmt->execute();

        $mensagem = 'Usuário cadastrado com sucesso!';
        $tipo_mensagem = 'success';

        // Redirecionar apÃ³s sucesso
        if (defined('PUBLIC_REGISTER')) {
            // Registro pÃºblico (doador se cadastrando): redireciona para login
            header('Location: ../../../login.php?registered=1');
        } else {
            // Admin cadastrando usuÃ¡rio: redireciona para listagem, independente do tipo
            header('Location: ../../views/usuarios/usuarios_listar.php?success=1');
        }
        exit;

    } catch (Exception $e) {
        $mensagem = 'Erro: ' . $e->getMessage();
        $tipo_mensagem = 'error';
    }
}
?>



