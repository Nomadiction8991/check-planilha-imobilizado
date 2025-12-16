<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
 
$pageTitle = 'Importar Planilha';
$backUrl = '../../../index.php';
 
ob_start();
?>
 
<form action="../../../app/controllers/create/ImportacaoPlanilhaController.php" method="POST" enctype="multipart/form-data">
    <!-- Arquivo CSV -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-file-earmark-arrow-up me-2"></i>
            <?php echo htmlspecialchars(to_uppercase('Arquivo CSV'), ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <div class="card-body">
            <label for="arquivo_csv" class="form-label text-uppercase">Arquivo CSV <span class="text-danger">*</span></label>
            <input type="file" class="form-control text-uppercase" id="arquivo_csv" name="arquivo_csv" accept=".csv" required>
        </div>
    </div>
 
    <!-- Configurações Básicas -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-gear me-2"></i>
            <?php echo htmlspecialchars(to_uppercase('Configurações básicas'), ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="pulo_linhas" class="form-label text-uppercase">Linhas iniciais a pular <span class="text-danger">*</span></label>
                <input type="number" class="form-control text-uppercase" id="pulo_linhas" name="pulo_linhas" value="25" min="0" required>
            </div>
            <div class="mb-3">
                <label for="posicao_data" class="form-label text-uppercase">Célula data <span class="text-danger">*</span></label>
                <input type="text" class="form-control text-uppercase" id="posicao_data" name="posicao_data" value="D13" required>
            </div>
        </div>
    </div>
 
    <!-- Mapeamento de Colunas -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-columns-gap me-2"></i>
            <?php echo htmlspecialchars(to_uppercase('Mapeamento de colunas'), ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="mapeamento_codigo" class="form-label text-uppercase">Código <span class="text-danger">*</span></label>
                    <input type="text" class="form-control text-center text-uppercase" id="mapeamento_codigo" name="mapeamento_codigo" value="A" maxlength="2" required>
                </div>
                <div class="col-md-6">
                    <label for="mapeamento_complemento" class="form-label text-uppercase">Complemento <span class="text-danger">*</span></label>
                    <input type="text" class="form-control text-center text-uppercase" id="mapeamento_complemento" name="mapeamento_complemento" value="D" maxlength="2" required>
                </div>
            </div>
            <div class="row g-3 mt-2">
                <div class="col-md-6">
                    <label for="mapeamento_dependencia" class="form-label text-uppercase">Dependência <span class="text-danger">*</span></label>
                    <input type="text" class="form-control text-center text-uppercase" id="mapeamento_dependencia" name="mapeamento_dependencia" value="P" maxlength="2" required>
                </div>
                <div class="col-md-6">
                    <label for="coluna_localidade" class="form-label text-uppercase">Localidade <span class="text-danger">*</span></label>
                    <input type="text" class="form-control text-center text-uppercase" id="coluna_localidade" name="coluna_localidade" value="K" maxlength="2" required>
                </div>
            </div>
        </div>
    </div>
 
    <button type="submit" class="btn btn-primary w-100 text-uppercase">
        <i class="bi bi-upload me-2"></i>
        <?php echo htmlspecialchars(to_uppercase('Importar Planilha'), ENT_QUOTES, 'UTF-8'); ?>
    </button>
</form>
 
<?php
$contentHtml = ob_get_clean();
$contentFile = __DIR__ . '/../../../temp_importar_planilha_content_' . uniqid() . '.php';
file_put_contents($contentFile, $contentHtml);
include __DIR__ . '/../layouts/app_wrapper.php';
@unlink($contentFile);
?>



