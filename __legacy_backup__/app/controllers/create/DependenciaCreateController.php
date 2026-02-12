<?php

declare(strict_types=1);


require_once dirname(__DIR__, 2) . '/bootstrap.php';

$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = trim($_POST['codigo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');

    try {
        if ($descricao === '') {
            throw new Exception('A descricao e obrigatoria.');
        }

        // Se codigo informado, validar unicidade
        if ($codigo !== '') {
            $check = $conexao->prepare('SELECT id FROM dependencias WHERE codigo = :codigo');
            $check->bindValue(':codigo', $codigo);
            $check->execute();
            if ($check->fetch()) {
                throw new Exception('Este codigo ja esta cadastrado.');
            }
        }

        // Montar insert dinamico conforme presenca de codigo
        $fields = ['descricao'];
        $placeholders = [':descricao'];
        $params = [':descricao' => $descricao];

        if ($codigo !== '') {
            $fields[] = 'codigo';
            $placeholders[] = ':codigo';
            $params[':codigo'] = $codigo;
        }

        $sql = 'INSERT INTO dependencias (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $placeholders) . ')';
        $stmt = $conexao->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();

        // Preserve filters when returning to list
        $retQ = [];
        if (!empty($_GET['busca'])) {
            $retQ['busca'] = $_GET['busca'];
        }
        if (!empty($_GET['pagina'])) {
            $retQ['pagina'] = $_GET['pagina'];
        }
        $retQ['success'] = 1;
        header('Location: dependencias_listar.php?' . http_build_query($retQ));
        exit;
    } catch (Throwable $e) {
        $mensagem = 'Erro: ' . $e->getMessage();
        $tipo_mensagem = 'danger';
    }
}
