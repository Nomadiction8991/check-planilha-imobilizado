<?php


$appConfig = require dirname(__DIR__, 3) . '/config/app.php';
$projectRoot = $appConfig['project_root'];
require_once $projectRoot . '/src/Helpers/BootstrapLoader.php';


$pageTitle = to_uppercase('editar produto');
$pagina = $pagina ?? 1;
$filtro_nome = $filtro_nome ?? '';
$filtro_dependencia = $filtro_dependencia ?? '';
$filtro_codigo = $filtro_codigo ?? '';
$filtro_STATUS = $filtro_STATUS ?? '';
$id_produto = $id_produto ?? 0;
$comum_id = $comum_id ?? 0;
$dependencias = $dependencias ?? [];
$novo_tipo_bem_id = $novo_tipo_bem_id ?? null;
$novo_bem = $novo_bem ?? '';
$novo_complemento = $novo_complemento ?? '';
$nova_dependencia_id = $nova_dependencia_id ?? null;
$mensagem = $mensagem ?? '';
$tipo_mensagem = $tipo_mensagem ?? '';
$produto = $produto ?? [];
$backUrl = getReturnUrl($comum_id, $pagina, $filtro_nome, $filtro_dependencia, $filtro_codigo, $filtro_STATUS);

ob_start();
?>

<link href="/assets/css/produtos/produto_editar.css" rel="stylesheet">




<script>
    window._tiposBensOpcoes = <?php echo json_encode(array_reduce($tipos_bens, function ($carry, $item) {
                                    $opcoes = [];
                                    if (!empty($item['descricao'])) {
                                        $partes = explode('/', $item['descricao']);
                                        $opcoes = array_map('trim', $partes);
                                    }
                                    $carry[$item['id']] = [
                                        'codigo' => $item['codigo'],
                                        'descricao' => $item['descricao'],
                                        'opcoes' => $opcoes
                                    ];
                                    return $carry;
                                }, [])); ?>;
    window._novoBem = <?php echo json_encode(!empty($novo_bem) ? mb_strtoupper($novo_bem, 'UTF-8') : ''); ?>;
</script>
<script src="/assets/js/products/edit.js"></script>

<?php if (!empty($mensagem)): ?>
    <div class="alert alert-<?php echo $tipo_mensagem === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
        <?php echo $mensagem; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="POST">
    <input type="hidden" name="pagina" value="<?php echo $pagina; ?>">
    <input type="hidden" name="nome" value="<?php echo htmlspecialchars($filtro_nome); ?>">
    <input type="hidden" name="dependencia" value="<?php echo htmlspecialchars($filtro_dependencia); ?>">
    <input type="hidden" name="filtro_codigo" value="<?php echo htmlspecialchars($filtro_codigo); ?>">
    <input type="hidden" name="STATUS" value="<?php echo htmlspecialchars($filtro_STATUS); ?>">

    <!-- DADOS DO PRODUTO -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-box-seam me-2"></i>
            <?php echo to_uppercase('Dados do Produto'); ?>
        </div>
        <div class="card-body">
            <!-- TIPO DE BEM -->
            <div class="mb-3">
                <label for="novo_tipo_bem_id" class="form-label">
                    <i class="bi bi-tag me-1"></i>
                    TIPO DE BEM
                </label>
                <select class="form-select" id="novo_tipo_bem_id" name="novo_tipo_bem_id">
                    <option value=""><?php echo htmlspecialchars(to_uppercase('-- Não alterar --'), ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php foreach ($tipos_bens as $tb): ?>
                        <option value="<?php echo $tb['id']; ?>"
                            <?php echo (isset($novo_tipo_bem_id) && $novo_tipo_bem_id == $tb['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($tb['codigo'] . ' - ' . $tb['descricao']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text"><?php echo htmlspecialchars(to_uppercase('Selecione o tipo de bem para desbloquear o campo "BEM"'), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>

            <!-- BEM -->
            <div class="mb-3" id="div_bem">
                <label for="novo_bem" class="form-label">
                    <i class="bi bi-box me-1"></i>
                    BEM
                </label>
                <select class="form-select text-uppercase-input" id="novo_bem" name="novo_bem" disabled>
                    <option value="">-- Escolha o TIPO DE BEM acima --</option>
                </select>
                <div class="form-text"><?php echo htmlspecialchars(to_uppercase('Fica bloqueado até selecionar o TIPO DE BEM'), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>

            <!-- COMPLEMENTO -->
            <div class="mb-3">
                <label for="novo_complemento" class="form-label">
                    <i class="bi bi-card-text me-1"></i>
                    COMPLEMENTO
                </label>
                <textarea class="form-control text-uppercase-input" id="novo_complemento" name="novo_complemento"
                    rows="3" placeholder="<?php echo htmlspecialchars(to_uppercase('Característica + Marca + Medidas'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($novo_complemento ?? ''); ?></textarea>
                <div class="form-text"><?php echo htmlspecialchars(to_uppercase('Deixe em branco para NÃO alterar. Ex: COR PRETA + MARCA XYZ + 1,80M X 0,80M'), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>

            <!-- DEPENDÊNCIA -->
            <div class="mb-3">
                <label for="nova_dependencia_id" class="form-label">
                    <i class="bi bi-building me-1"></i>
                    DEPENDÊNCIA
                </label>
                <select class="form-select" id="nova_dependencia_id" name="nova_dependencia_id">
                    <option value=""><?php echo htmlspecialchars(to_uppercase('-- Não alterar --'), ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php foreach ($dependencias as $dep): ?>
                        <option value="<?php echo $dep['id']; ?>"
                            <?php echo (isset($nova_dependencia_id) && $nova_dependencia_id == $dep['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dep['descricao']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <!-- RELATÓRIO 14.1 -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-file-earmark-text me-2"></i>
            <?php echo to_uppercase('Relatório 14.1'); ?>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="imprimir_14_1" name="imprimir_14_1" value="1"
                        <?php echo ((int)($produto['imprimir_14_1'] ?? 0) === 1) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="imprimir_14_1">
                        <?php echo to_uppercase('Imprimir no Relatório 14.1'); ?>
                    </label>
                </div>
                <div class="form-text"><?php echo htmlspecialchars(to_uppercase('Marque para incluir este produto no relatório 14.1'), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>

            <div class="mb-3">
                <label class="form-label">
                    <i class="bi bi-clipboard-check me-1"></i>
                    <?php echo to_uppercase('Situação do Bem (Formulário 14.1)'); ?>
                </label>
                <?php
                $condicaoAtual = trim($produto['condicao_14_1'] ?? '');
                $opcoes141 = [
                    '1' => 'O bem tem mais de cinco anos de uso e o documento fiscal de aquisição está anexo.',
                    '2' => 'O bem tem mais de cinco anos de uso, porém o documento fiscal de aquisição foi extraviado.',
                    '3' => 'O bem tem até cinco anos de uso e o documento fiscal de aquisição está anexo.',
                ];
                foreach ($opcoes141 as $valor => $texto): ?>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio"
                            name="condicao_14_1"
                            id="condicao_141_<?php echo $valor; ?>"
                            value="<?php echo $valor; ?>"
                            <?php echo (
                                $condicaoAtual === (string)$valor ||
                                ($condicaoAtual === '' && $valor === '2')
                            ) ? 'checked' : ''; ?>>
                        <label class="form-check-label small" for="condicao_141_<?php echo $valor; ?>">
                            <?php echo htmlspecialchars($texto, ENT_QUOTES, 'UTF-8'); ?>
                        </label>
                    </div>
                <?php endforeach; ?>
                <div class="form-text"><?php echo htmlspecialchars(to_uppercase('Situação conforme declaração de doação de bem móvel'), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
        </div>
    </div>

    <!-- NOTA FISCAL -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-receipt me-2"></i>
            <?php echo to_uppercase('Nota Fiscal'); ?>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-6">
                    <label for="nota_numero" class="form-label">
                        <?php echo to_uppercase('Número'); ?>
                    </label>
                    <input type="number" class="form-control" id="nota_numero" name="nota_numero"
                        value="<?php echo htmlspecialchars((string)($produto['nota_numero'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                        placeholder="Nº da nota">
                </div>
                <div class="col-6">
                    <label for="nota_data" class="form-label">
                        <?php echo to_uppercase('Data'); ?>
                    </label>
                    <input type="date" class="form-control" id="nota_data" name="nota_data"
                        value="<?php echo htmlspecialchars($produto['nota_data'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-6">
                    <label for="nota_valor" class="form-label">
                        <?php echo to_uppercase('Valor (R$)'); ?>
                    </label>
                    <input type="text" class="form-control" id="nota_valor" name="nota_valor"
                        value="<?php echo htmlspecialchars($produto['nota_valor'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        placeholder="0,00">
                </div>
                <div class="col-6">
                    <label for="nota_fornecedor" class="form-label">
                        <?php echo to_uppercase('Fornecedor'); ?>
                    </label>
                    <input type="text" class="form-control text-uppercase-input" id="nota_fornecedor" name="nota_fornecedor"
                        value="<?php echo htmlspecialchars($produto['nota_fornecedor'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        placeholder="Nome do fornecedor">
                </div>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary w-100">
        <i class="bi bi-check-lg me-2"></i>
        <?php echo to_uppercase('Salvar alterações'); ?>
    </button>
</form>

<div class="mt-3">
    <a href="/products/clear-edits?id=<?php echo $comum_id; ?>&comum_id=<?php echo $comum_id; ?>&id_PRODUTO=<?php echo $id_produto; ?>&pagina=<?php echo $pagina; ?>&nome=<?php echo urlencode($filtro_nome); ?>&dependencia=<?php echo urlencode($filtro_dependencia); ?>&filtro_codigo=<?php echo urlencode($filtro_codigo); ?>&status=<?php echo urlencode($filtro_STATUS); ?>"
        class="btn btn-outline-danger w-100">
        <i class="bi bi-trash3 me-2"></i>
        <?php echo htmlspecialchars(to_uppercase('Limpar Edições'), ENT_QUOTES, 'UTF-8'); ?>
    </a>
    <div class="form-text mt-1"><?php echo htmlspecialchars(to_uppercase('Remove todos os campos editados e desmarca para impressão.'), ENT_QUOTES, 'UTF-8'); ?></div>
</div>

<?php
$contentHtml = ob_get_clean();
include $projectRoot . '/src/Views/layouts/app.php';
?>