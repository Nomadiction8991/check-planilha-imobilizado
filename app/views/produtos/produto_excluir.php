<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
 // AUTENTICAÇÃO
include __DIR__ . '/../../../app/controllers/delete/PRODUTODeleteController.php';

$pageTitle = 'Excluir PRODUTO';
$backUrl = './PRODUTOS_listar.php?id=' . urlencode($id_planilha) . '&' . gerarParametrosFiltro();

ob_start();
?>

<div class="alert alert-warning">
  <strong>AtenÃ§Ã£o:</strong> Tem certeza que deseja excluir este PRODUTO? Esta aÃ§Ã£o NÃO pode ser desfeita.
  <?php if (!empty($erros)): ?>
    <ul class="mb-0 mt-2">
      <?php foreach ($erros as $erro): ?>
        <li><?php echo htmlspecialchars($erro); ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
  
</div>

<form method="POST" id="form-PRODUTO">
  <div class="card mb-3">
    <div class="card-body">
      <?php if (!empty($PRODUTO['codigo'])): ?>
      <div class="mb-3">
        <label class="form-label">CÓDIGO</label>
        <input type="text" class="form-control" value="<?php echo htmlspecialchars($PRODUTO['codigo']); ?>" disabled>
      </div>
      <?php endif; ?>

      <div class="mb-3">
        <label class="form-label">Tipos de Bens</label>
        <input type="text" class="form-control" value="<?php echo htmlspecialchars(($PRODUTO['tipo_codigo'] ?? '') . ' - ' . ($PRODUTO['tipo_descricao'] ?? '')); ?>" disabled>
      </div>

      <div class="mb-3">
        <label class="form-label">Bem</label>
        <input type="text" class="form-control" value="<?php echo htmlspecialchars($PRODUTO['tipo_ben'] ?? ''); ?>" disabled>
      </div>

      <div class="mb-3">
        <label class="form-label">COMPLEMENTO</label>
        <textarea class="form-control" rows="3" disabled><?php echo htmlspecialchars($PRODUTO['complemento'] ?? ''); ?></textarea>
      </div>

      <div class="mb-3">
        <label class="form-label">DEPENDÊNCIA</label>
        <input type="text" class="form-control" value="<?php echo htmlspecialchars($PRODUTO['dependencia_descricao'] ?? $PRODUTO['dependencia'] ?? ''); ?>" disabled>
      </div>

      <div class="mb-2">
        <label class="form-label">STATUS</label>
        <div class="d-flex gap-2">
          <span class="badge bg-<?php echo (isset($PRODUTO['condicao_141']) && ($PRODUTO['condicao_141'] == 1 || $PRODUTO['condicao_141'] == 3)) ? 'warning text-dark' : 'secondary'; ?>">Nota</span>
          <span class="badge bg-<?php echo ($PRODUTO['imprimir_14_1'] == 1) ? 'primary' : 'secondary'; ?>">14.1</span>
        </div>
      </div>
    </div>
  </div>

  <button type="submit" class="btn btn-danger w-100">
    <i class="bi bi-trash me-2"></i>
    Confirmar ExclusÃ£o
  </button>
</form>

<?php
$contentHtml = ob_get_clean();
$tempFile = __DIR__ . '/../../../temp_delete_PRODUTO_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app_wrapper.php';
unlink($tempFile);
?>


