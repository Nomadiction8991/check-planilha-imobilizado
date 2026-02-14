<?php

declare(strict_types=1);

$appConfig = require dirname(__DIR__, 3) . '/config/app.php';
$projectRoot = $appConfig['project_root'];
require_once $projectRoot . '/src/Helpers/BootstrapLoader.php';


$pageTitle = 'CADASTRAR PRODUTO';
$backUrl = '/products?comum_id=' . urlencode($comum_id) . '&' . gerarParametrosFiltro();

ob_start();
?>

<?php if (!empty($erros)): ?>
    <div class="alert alert-danger">
        <strong>Erros encontrados:</strong>
        <ul class="mb-0">
            <?php foreach ($erros as $erro): ?>
                <li><?php echo htmlspecialchars($erro); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" id="form-PRODUTO" class="needs-validation" novalidate>
    <div class="card mb-3">
        <div class="card-body">
            <div class="mb-3">
                <label for="codigo" class="form-label">CÓDIGO <span class="text-muted">(opcional)</span></label>
                <input type="text" id="codigo" name="codigo" class="form-control" value="<?php echo htmlspecialchars($_POST['codigo'] ?? ''); ?>" placeholder="CÓDIGO gerado por outro sistema">
                <div class="form-text">Campo opcional. CÓDIGO externo que NÃO SERÁ INCLUÍDO na DESCRIÇÃO completa.</div>
            </div>

            <div class="mb-3">
                <label for="multiplicador" class="form-label">Multiplicador</label>
                <input type="number" id="multiplicador" name="multiplicador" class="form-control" min="1" value="<?php echo htmlspecialchars($_POST['multiplicador'] ?? '1'); ?>" required>
                <div class="invalid-feedback">Informe o multiplicador.</div>
            </div>

            <div class="mb-3">
                <label for="id_tipo_ben" class="form-label">Tipos de Bens</label>
                <select id="id_tipo_ben" name="id_tipo_ben" class="form-select" required>
                    <option value="">SELECIONE UM TIPO DE BEM</option>
                    <?php foreach ($tipos_bens as $tipo): ?>
                        <option value="<?php echo $tipo['id']; ?>" data-descricao="<?php echo htmlspecialchars($tipo['descricao']); ?>"
                            <?php echo (isset($_POST['id_tipo_ben']) && $_POST['id_tipo_ben'] == $tipo['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($tipo['codigo'] . ' - ' . $tipo['descricao']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback">SELECIONE UM TIPO DE BEM.</div>
            </div>

            <div class="mb-3">
                <label for="tipo_ben" class="form-label">Bem</label>
                <select id="tipo_ben" name="tipo_ben" class="form-select" required>
                    <option value="">PRIMEIRO SELECIONE UM TIPO DE BEM</option>
                </select>
                <div class="invalid-feedback">SELECIONE UM BEM.</div>
            </div>

            <div class="mb-3">
                <label for="complemento" class="form-label">COMPLEMENTO</label>
                <textarea id="complemento" name="complemento" class="form-control" rows="3" placeholder="Digite o complemento do PRODUTO" required><?php echo htmlspecialchars($_POST['complemento'] ?? ''); ?></textarea>
                <div class="invalid-feedback">Informe o complemento.</div>
            </div>

            <div class="mb-3">
                <label for="id_dependencia" class="form-label">DEPENDÊNCIA</label>
                <select id="id_dependencia" name="id_dependencia" class="form-select" required>
                    <option value="">Selecione uma DEPENDÊNCIA</option>
                    <?php foreach ($dependencias as $dep): ?>
                        <option value="<?php echo $dep['id']; ?>" <?php echo (isset($_POST['id_dependencia']) && $_POST['id_dependencia'] == $dep['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dep['descricao']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback">Selecione a DEPENDÊNCIA.</div>
            </div>

            <div class="mb-2">
                <label class="form-label">STATUS</label>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="imprimir_14_1" name="imprimir_14_1" value="1" <?php echo (isset($_POST['imprimir_14_1']) && $_POST['imprimir_14_1'] == 1) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="imprimir_14_1">IMPRIMIR 14.1</label>
                </div>
            </div>

            <!-- Campos de CONDIÇÁO 14.1 e Nota Fiscal removidos a pedido -->
        </div>
    </div>

    <button type="submit" class="btn btn-primary w-100">
        <i class="bi bi-save me-2"></i>
        CADASTRAR PRODUTO
    </button>
</form>

<script>
    window._postTipoBen = <?php echo json_encode($_POST['tipo_ben'] ?? null); ?>;
    window._postIdTipoBen = <?php echo json_encode($_POST['id_tipo_ben'] ?? null); ?>;
</script>
<script src="/assets/js/produtos/create.js"></script>

<?php
$contentHtml = ob_get_clean();
include $projectRoot . '/src/Views/layouts/app.php';
?>