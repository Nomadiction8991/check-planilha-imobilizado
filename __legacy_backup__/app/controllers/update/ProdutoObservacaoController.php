<?php
// Autenticação
require_once dirname(__DIR__, 2) . '/bootstrap.php';

// Receber parâmetros via GET - AGORA USANDO ID
$id_produto = isset($_GET['id_produto']) ? (int) $_GET['id_produto'] : null;
$comum_id = isset($_GET['comum_id']) ? (int) $_GET['comum_id'] : (isset($_GET['id']) ? (int) $_GET['id'] : null);

// Receber filtros
$pagina = $_GET['pagina'] ?? 1;
$filtro_nome = $_GET['nome'] ?? '';
$filtro_dependencia = $_GET['dependencia'] ?? '';
$filtro_codigo = $_GET['filtro_codigo'] ?? '';
$filtro_status = $_GET['status'] ?? '';

// Validação dos parâmetros obrigatórios
if (!$id_produto || !$comum_id) {
    $query_string = http_build_query([
        'id' => $comum_id,
        'comum_id' => $comum_id,
        'pagina' => $pagina,
        'nome' => $filtro_nome,
        'dependencia' => $filtro_dependencia,
        'codigo' => $filtro_codigo,
        'status' => $filtro_status,
        'erro' => 'Parâmetros inválidos para acessar a página'
    ]);
    header('Location: ../planilhas/planilha_visualizar.php?' . $query_string);
    exit;
}

// Inicializar variáveis
$mensagem = '';
$tipo_mensagem = '';
$produto = [];
$check = [
    'checado' => 0,
    'observacoes' => '',
    'dr' => 0,
    'imprimir' => 0
];

// Buscar dados do produto POR ID
try {
    $sql_produto = "SELECT
                        p.*,
                        t1.codigo AS tipo_codigo,
                        t1.descricao AS tipo_desc,
                        d1.descricao AS dependencia_desc,
                        d2.descricao AS editado_dependencia_desc
                    FROM produtos p
                    LEFT JOIN tipos_bens t1 ON p.tipo_bem_id = t1.id
                    LEFT JOIN dependencias d1 ON p.dependencia_id = d1.id
                    LEFT JOIN dependencias d2 ON p.editado_dependencia_id = d2.id
                    WHERE id_produto = :id_produto AND comum_id = :comum_id";
    $stmt_produto = $conexao->prepare($sql_produto);
    $stmt_produto->bindValue(':id_produto', $id_produto);
    $stmt_produto->bindValue(':comum_id', $comum_id);
    $stmt_produto->execute();
    $produto = $stmt_produto->fetch();

    if (!$produto) {
        throw new Exception('Produto não encontrado na planilha.');
    }

    // Preencher informações do check com dados da própria tabela produtos
    $check = [
        'checado' => $produto['checado'] ?? 0,
        'observacoes' => $produto['observacao'] ?? '',
        'imprimir' => $produto['imprimir_etiqueta'] ?? 0
    ];

    $descricaoCompleta = montarDescricaoCompletaProduto($produto);
} catch (Exception $e) {
    $mensagem = "Erro ao carregar produto: " . $e->getMessage();
    $tipo_mensagem = 'error';
}

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $observacoes = trim($_POST['observacoes'] ?? '');
    $observacoes = to_uppercase($observacoes);

    // Receber filtros do POST
    $pagina = $_POST['pagina'] ?? 1;
    $filtro_nome = $_POST['nome'] ?? '';
    $filtro_dependencia = $_POST['dependencia'] ?? '';
    $filtro_codigo = $_POST['filtro_codigo'] ?? '';
    $filtro_status = $_POST['status'] ?? '';

    try {
        // Atualizar observações diretamente na tabela produtos - USANDO id_produto
        $sql_update = "UPDATE produtos SET observacao = :observacao WHERE id_produto = :id_produto AND comum_id = :comum_id";
        $stmt_update = $conexao->prepare($sql_update);
        $stmt_update->bindValue(':observacao', $observacoes);
        $stmt_update->bindValue(':id_produto', $id_produto, PDO::PARAM_INT);
        $stmt_update->bindValue(':comum_id', $comum_id, PDO::PARAM_INT);
        $stmt_update->execute();

        // Se requisição AJAX, retornar JSON, senão redirecionar para a view tradicional
        if (is_ajax_request()) {
            json_response([
                'success' => true,
                'produto_id' => $id_produto,
                'observacao' => $observacoes,
                'message' => 'Observações salvas com sucesso'
            ]);
            exit;
        }

        // REDIRECIONAR PARA view-planilha.php APÓS SALVAR (fallback para não-JS)
        $query_string = http_build_query([
            'id' => $comum_id,
            'comum_id' => $comum_id,
            'pagina' => $pagina,
            'nome' => $filtro_nome,
            'dependencia' => $filtro_dependencia,
            'codigo' => $filtro_codigo,
            'status' => $filtro_status,
            'sucesso' => 'Observações salvas com sucesso!'
        ]);
        header('Location: ../planilhas/planilha_visualizar.php?' . $query_string);
        exit;
    } catch (Exception $e) {
        $mensagem = "Erro ao salvar observações: " . $e->getMessage();
        $tipo_mensagem = 'error';
        error_log("ERRO SALVAR OBSERVAÇÕES: " . $e->getMessage());
    }
}



function montarDescricaoCompletaProduto(array $produto): string
{
    $descricaoEdicao = trim($produto['editado_descricao_completa'] ?? '');
    $descricaoBase = trim($produto['descricao_completa'] ?? '');
    $descricaoOriginal = trim($produto['descricao'] ?? '');

    $descricao = $descricaoEdicao ?: $descricaoBase ?: $descricaoOriginal;
    if ($descricao !== '') {
        return to_uppercase($descricao);
    }

    $partes = [];
    $tipoCodigo = trim($produto['tipo_codigo'] ?? '');
    $tipoDesc = trim($produto['tipo_desc'] ?? '');
    if ($tipoCodigo !== '' && $tipoDesc !== '') {
        $partes[] = strtoupper(trim("{$tipoCodigo} - {$tipoDesc}"));
    } elseif ($tipoDesc !== '') {
        $partes[] = strtoupper($tipoDesc);
    } elseif ($tipoCodigo !== '') {
        $partes[] = strtoupper($tipoCodigo);
    }

    $bem = trim($produto['bem'] ?? '');
    if ($bem !== '') {
        $partes[] = strtoupper($bem);
    }

    $complemento = trim($produto['complemento'] ?? '');
    if ($complemento !== '') {
        $complementoTmp = strtoupper($complemento);
        if ($bem !== '' && strpos($complementoTmp, strtoupper($bem)) === 0) {
            $complementoTmp = trim(substr($complementoTmp, strlen(strtoupper($bem))));
            $complementoTmp = preg_replace('/^[\s\-\/]+/', '', $complementoTmp);
        }
        if ($complementoTmp !== '') {
            $partes[] = $complementoTmp;
        }
    }

    $dependencia = trim($produto['editado_dependencia_desc'] ?? $produto['dependencia_desc'] ?? '');
    if ($dependencia !== '') {
        $partes[] = '(' . strtoupper($dependencia) . ')';
    }

    if (!empty($partes)) {
        return to_uppercase(implode(' - ', $partes));
    }

    $fallback = trim($produto['codigo'] ?? $produto['bem'] ?? '');
    return to_uppercase($fallback ?: 'PRODUTO');
}

// Função para gerar URL de retorno com filtros - CORRIGIDA
function getReturnUrl($comum_id, $pagina, $filtro_nome, $filtro_dependencia, $filtro_codigo, $filtro_status)
{
    $params = [
        'id' => $comum_id, // CORRETO: view-planilha.php usa 'id' como parâmetro
        'comum_id' => $comum_id,
        'pagina' => $pagina,
        'nome' => $filtro_nome,
        'dependencia' => $filtro_dependencia,
        'codigo' => $filtro_codigo,
        'status' => $filtro_status
    ];
    return '../planilhas/planilha_visualizar.php?' . http_build_query($params);
}
