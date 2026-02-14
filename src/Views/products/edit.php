<?php
$appConfig = require dirname(__DIR__, 3) . '/config/app.php';
$projectRoot = $appConfig['project_root'];
require_once $projectRoot . '/src/Helpers/BootstrapLoader.php';


$pageTitle = to_uppercase('editar produto');
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
<script src="/assets/js/produtos/edit.js"></script>

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

    <div class="card mb-3">
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

            <!-- BEM (sempre visível, desabilitado até escolher tipo) -->
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

            <!-- DEPENDÁŠNCIA -->
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