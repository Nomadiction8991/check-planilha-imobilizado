<?php


$appConfig = require dirname(__DIR__, 3) . '/config/app.php';
$projectRoot = $appConfig['project_root'];
require_once $projectRoot . '/src/Helpers/BootstrapLoader.php';


$pageTitle = 'CADASTRAR PRODUTO';
$backUrl = '/products/view?comum_id=' . urlencode($comum_id);

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
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="imprimir_14_1" name="imprimir_14_1" value="1" <?php echo (isset($_POST['imprimir_14_1']) && $_POST['imprimir_14_1'] == 1) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="imprimir_14_1">IMPRIMIR 14.1</label>
                </div>
            </div>
        </div>
    </div>

    <!-- CONDIÇÃO 14.1 -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-clipboard-check me-2"></i>
            CONDIÇÃO 14.1
        </div>
        <div class="card-body">
                    <div class="mb-3">
                <label class="form-label">CONDIÇÃO</label>
                <?php
                $condicaoPost = trim($_POST['condicao_14_1'] ?? '');
                $imprimirPost = isset($_POST['imprimir_14_1']) && (int)$_POST['imprimir_14_1'] === 1;
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
                            <?php echo ($condicaoPost === (string)$valor || ($condicaoPost === '' && $imprimirPost && $valor === '2')) ? 'checked' : ''; ?>>
                        <label class="form-check-label small" for="condicao_141_<?php echo $valor; ?>">
                            <?php echo htmlspecialchars($texto, ENT_QUOTES, 'UTF-8'); ?>
                        </label>
                    </div>
                <?php endforeach; ?>
                <div class="form-text">CONDIÇÃO ATUAL DO PRODUTO PARA O RELATÓRIO</div>
            </div>

            <script>
                (function () {
                    var imprimir = document.getElementById('imprimir_14_1');
                    if (!imprimir) return;
                    var radios = Array.from(document.querySelectorAll('input[name="condicao_14_1"]'));

                    function updateRequirement() {
                        var required = imprimir.checked;
                        radios.forEach(function (r) {
                            if (required) r.setAttribute('required', 'required');
                            else r.removeAttribute('required');
                        });

                        if (required && !radios.some(function (r) { return r.checked; })) {
                            var def = document.getElementById('condicao_141_2');
                            if (def) def.checked = true;
                        }
                    }

                    imprimir.addEventListener('change', updateRequirement);
                    // inicializar
                    updateRequirement();
                })();
            </script>
        </div>
    </div>

    <!-- NOTA FISCAL -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-receipt me-2"></i>
            NOTA FISCAL
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-6">
                    <label for="nota_numero" class="form-label">NÚMERO</label>
                    <input type="number" class="form-control" id="nota_numero" name="nota_numero"
                        value="<?php echo htmlspecialchars($_POST['nota_numero'] ?? ''); ?>"
                        placeholder="Nº da nota">
                </div>
                <div class="col-6">
                    <label for="nota_data" class="form-label">DATA</label>
                    <input type="date" class="form-control" id="nota_data" name="nota_data"
                        value="<?php echo htmlspecialchars($_POST['nota_data'] ?? ''); ?>">
                </div>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-6">
                    <label for="nota_valor" class="form-label">VALOR (R$)</label>
                    <input type="text" class="form-control" id="nota_valor" name="nota_valor"
                        value="<?php echo htmlspecialchars($_POST['nota_valor'] ?? ''); ?>"
                        placeholder="0,00">
                </div>
                <div class="col-6">
                    <label for="nota_fornecedor" class="form-label">FORNECEDOR</label>
                    <input type="text" class="form-control text-uppercase-input" id="nota_fornecedor" name="nota_fornecedor"
                        value="<?php echo htmlspecialchars($_POST['nota_fornecedor'] ?? ''); ?>"
                        placeholder="Nome do fornecedor">
                </div>
            </div>
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