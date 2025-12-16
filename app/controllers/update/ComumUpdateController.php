<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';

if (!isAdmin()) {
    header('Location: ../../../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../index.php');
    exit;
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$descricao = trim((string) ($_POST['descricao'] ?? ''));
$cnpj = preg_replace('/\D+/', '', (string) ($_POST['cnpj'] ?? ''));
$administracao = trim((string) ($_POST['administracao'] ?? ''));
$cidade = trim((string) ($_POST['cidade'] ?? ''));
$setor = trim((string) ($_POST['setor'] ?? ''));

$paginaParam = isset($_REQUEST['pagina']) ? max(1, (int) $_REQUEST['pagina']) : 1;
$submittedBusca = trim((string) ($_REQUEST['busca'] ?? ''));
$filterString = trim((string) ($_REQUEST['filters'] ?? ''));
$filters = [];
if ($filterString !== '') {
    parse_str($filterString, $filters);
}
foreach (['success', 'ajax'] as $exclude) {
    unset($filters[$exclude]);
}

try {
    if ($id <= 0) {
        throw new Exception('ID inválido.');
    }
    if ($descricao === '') {
        throw new Exception('Descrição é obrigatória.');
    }
    if ($cnpj === '' || strlen($cnpj) !== 14) {
        throw new Exception('CNPJ é obrigatório e deve ter 14 dígitos.');
    }
    if ($administracao === '') {
        throw new Exception('Administração é obrigatória.');
    }
    if ($cidade === '') {
        throw new Exception('Cidade é obrigatória.');
    }

    // Padronizar dados em maiúsculas antes de salvar
    $descricao = mb_strtoupper($descricao, 'UTF-8');
    $administracao = mb_strtoupper($administracao, 'UTF-8');
    $cidade = mb_strtoupper($cidade, 'UTF-8');
    $setor = $setor !== '' ? mb_strtoupper($setor, 'UTF-8') : '';

    // Garantir unicidade do CNPJ
    $stmtCheck = $conexao->prepare('SELECT id FROM comums WHERE cnpj = :cnpj AND id != :id');
    $stmtCheck->bindValue(':cnpj', $cnpj);
    $stmtCheck->bindValue(':id', $id, PDO::PARAM_INT);
    $stmtCheck->execute();
    if ($stmtCheck->fetch()) {
        throw new Exception('Já existe um comum com este CNPJ.');
    }

    $stmt = $conexao->prepare('UPDATE comums 
                               SET descricao = :descricao,
                                   cnpj = :cnpj,
                                   administracao = :administracao,
                                   cidade = :cidade,
                                   setor = :setor
                               WHERE id = :id');
    $stmt->bindValue(':descricao', $descricao);
    $stmt->bindValue(':cnpj', $cnpj);
    $stmt->bindValue(':administracao', $administracao);
    $stmt->bindValue(':cidade', $cidade);
    $stmt->bindValue(':setor', $setor !== '' ? $setor : null, $setor !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    $_SESSION['mensagem'] = 'Comum atualizada com sucesso!';
    $_SESSION['tipo_mensagem'] = 'success';
    // Rebuild the return query string while reusing the serialized filters
    $retParams = $filters;
    if (!array_key_exists('busca', $retParams) && $submittedBusca !== '') {
        $retParams['busca'] = $submittedBusca;
    }
    if (!array_key_exists('pagina', $retParams) && $paginaParam > 1) {
        $retParams['pagina'] = $paginaParam;
    }
    $retParams['success'] = 1;
    header('Location: ../../../index.php?' . http_build_query($retParams));
    exit;
} catch (Throwable $e) {
    $_SESSION['mensagem'] = 'Erro ao salvar: ' . $e->getMessage();
    $_SESSION['tipo_mensagem'] = 'danger';
    // Redirect back to the edit page preserving incoming filters if provided
    $backParams = $filters;
    if (!array_key_exists('busca', $backParams) && $submittedBusca !== '') {
        $backParams['busca'] = $submittedBusca;
    }
    if (!array_key_exists('pagina', $backParams) && $paginaParam > 1) {
        $backParams['pagina'] = $paginaParam;
    }
    $backQuery = $backParams ? ('?' . http_build_query($backParams)) : '';
    $backUrl = '../../views/comuns/comum_editar.php?id=' . urlencode((string) $id) . $backQuery;
    header('Location: ' . $backUrl);
    exit;
}
