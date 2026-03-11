<?php

$appConfig = require dirname(__DIR__, 3) . '/config/app.php';
$projectRoot = $appConfig['project_root'];
require_once $projectRoot . '/src/Helpers/BootstrapLoader.php';

// Dados fornecidos pelo ProdutoController::signView()
$id_planilha = $id_planilha ?? 0;
$usuario_id  = $usuario_id  ?? (isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 0);
$PRODUTOS    = $PRODUTOS    ?? [];

$pageTitle      = 'Assinar PRODUTOS';
$backUrl        = '/products/view?id=' . $id_planilha . '&comum_id=' . $id_planilha;
$customCssPath  = '/assets/css/produtos/produtos_assinar.css';

ob_start();
?>

<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    <strong>Instruções:</strong> Selecione os PRODUTOS que deseja assinar.
    Você está assinando como <strong>Administrador/Acessor</strong>.
</div>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-boxes me-2"></i>
            PRODUTOS Disponíveis
        </span>
        <div>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="selecionarTodos()">
                <i class="bi bi-check-all"></i> Todos
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="desmarcarTodos()">
                <i class="bi bi-x-lg"></i> Nenhum
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($PRODUTOS)): ?>
            <p class="text-muted text-center mb-0">Nenhum PRODUTO disponível nesta comum.</p>
        <?php else: ?>
            <div id="PRODUTOSContainer">
                <?php foreach ($PRODUTOS as $PRODUTO): ?>
                    <?php
                    $assinado_por_mim = ($PRODUTO['minha_assinatura'] == $usuario_id);
                    ?>
                    <div class="card PRODUTO-card mb-2 <?php echo $assinado_por_mim ? 'assinado' : ''; ?>" data-PRODUTO-id="<?php echo $PRODUTO['id_produto']; ?>">
                        <div class="card-body py-2">
                            <div class="d-flex align-items-center">
                                <div class="form-check me-3">
                                    <input class="form-check-input PRODUTO-checkbox"
                                        type="checkbox"
                                        value="<?php echo $PRODUTO['id_produto']; ?>"
                                        id="PRODUTO_<?php echo $PRODUTO['id_produto']; ?>">
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold">
                                        <?php echo \App\Helpers\ViewHelper::e(\App\Helpers\ViewHelper::formatarCodigoCurto($PRODUTO['codigo'] ?? 'S/N')); ?>
                                        <?php if ($assinado_por_mim): ?>
                                            <span class="badge bg-success ms-2">
                                                <i class="bi bi-check-circle"></i> Assinado por você
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($PRODUTO['imprimir_14_1']): ?>
                                            <span class="badge bg-info ms-2">
                                                <i class="bi bi-file-earmark-pdf"></i> No relatório 14.1
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="small text-muted">
                                        <?php echo htmlspecialchars($PRODUTO['tipo_descricao'] ?? ''); ?>
                                        <?php if ($PRODUTO['complemento']): ?>
                                            - <?php echo htmlspecialchars($PRODUTO['complemento']); ?>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($PRODUTO['dependencia_descricao']): ?>
                                        <div class="small text-muted">
                                            <i class="bi bi-building"></i>
                                            <?php echo htmlspecialchars($PRODUTO['dependencia_descricao']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($PRODUTOS)): ?>
    <div class="card">
        <div class="card-body">
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-success flex-grow-1" onclick="assinarPRODUTOS()">
                    <i class="bi bi-pen me-1"></i>
                    Assinar Selecionados
                </button>
                <button type="button" class="btn btn-danger flex-grow-1" onclick="desassinarPRODUTOS()">
                    <i class="bi bi-x-circle me-1"></i>
                    Remover Assinatura
                </button>
            </div>
            <small class="text-muted d-block mt-2">
                <i class="bi bi-info-circle"></i>
                Selecione os PRODUTOS acima e clique em "Assinar" ou "Remover Assinatura"
            </small>
        </div>
    </div>
<?php endif; ?>

<script src="/assets/js/produtos/sign.js"></script>

<?php
$contentHtml = ob_get_clean();
include $projectRoot . '/src/Views/layouts/app.php';
?>
