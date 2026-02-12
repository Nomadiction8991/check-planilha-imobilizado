<?php
// Autenticação
require_once dirname(__DIR__, 2) . '/bootstrap.php';

$pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$limite = 10;
$offset = ($pagina - 1) * $limite;

$filtroNome = trim((string)($_GET['busca'] ?? ''));
// Aceitar 'status' em lowercase ou uppercase por compatibilidade
if (isset($_GET['status'])) {
    $filtroStatus = $_GET['status'];
} elseif (isset($_GET['STATUS'])) {
    $filtroStatus = $_GET['STATUS'];
} else {
    $filtroStatus = '';
}

$where = [];
$params = [];

if ($filtroNome !== '') {
	// Use separate placeholders for nome and email to avoid PDO "Invalid parameter number" when
	// the same named parameter appears multiple times in the SQL (some drivers don't allow duplication)
	$where[] = '(LOWER(nome) LIKE :busca_nome OR LOWER(email) LIKE :busca_email)';
	$params[':busca_nome'] = '%' . mb_strtolower($filtroNome, 'UTF-8') . '%';
	$params[':busca_email'] = '%' . mb_strtolower($filtroNome, 'UTF-8') . '%';
}

if ($filtroStatus !== '' && in_array($filtroStatus, ['0', '1'], true)) {
	$where[] = 'ativo = :status';
	$params[':status'] = $filtroStatus;
}

$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

// Global total (independent of current filters)
$sql_all_count = 'SELECT COUNT(*) FROM usuarios';
$total_registros_all = (int) $conexao->query($sql_all_count)->fetchColumn();

// Contagem com filtros aplicados (usada para paginação)
$sql_count = "SELECT COUNT(*) FROM usuarios" . $whereSql;
try {
	error_log('DEBUG UsuarioListController: SQL_COUNT=' . $sql_count . ' PARAMS=' . json_encode($params) . ' SQL=' . ($sql ?? 'N/A'));
	$stmt = $conexao->prepare($sql_count);
	foreach ($params as $key => $value) {
		$stmt->bindValue($key, $value);
	}
	$stmt->execute();
	$total_registros = (int)$stmt->fetchColumn();
	$total_paginas = (int)ceil($total_registros / $limite);

	// Busca paginada
	$sql = "SELECT * FROM usuarios" . $whereSql . " ORDER BY nome ASC LIMIT :limite OFFSET :offset";
	$stmt = $conexao->prepare($sql);
	$stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
	$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
	foreach ($params as $key => $value) {
		$stmt->bindValue($key, $value);
	}
	$stmt->execute();
	$usuarios = $stmt->fetchAll();
} catch (PDOException $e) {
	// Log detalhado e retornar lista vazia para evitar tela branca
	error_log('ERROR UsuarioListController: ' . $e->getMessage() . ' SQL_COUNT=' . $sql_count . ' PARAMS=' . json_encode($params));
	$total_registros = 0;
	$total_paginas = 1;
	$usuarios = [];
	$erro = 'Erro ao buscar usuários. Verifique os logs e tente novamente.';
	// Ensure global total exists even on error
	$total_registros_all = 0;
}

?>


