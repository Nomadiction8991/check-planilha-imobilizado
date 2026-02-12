<?php
require_once dirname(__DIR__, 2) . '/Helpers/BootstrapLoader.php';


$id_planilha = $_GET['id'] ?? null;
$contexto = $_GET['contexto'] ?? 'auto';
$origem = $_GET['origem'] ?? null;
$modo_publico = !empty($_SESSION['public_acesso']);

if ($contexto === 'auto') {
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    if (strpos($referer, 'relatorio141') !== false) {
        $contexto = 'relatorio';
    } elseif (
        strpos($referer, 'planilha_visualizar.php') !== false ||
        strpos($referer, 'configuracao_importacao_editar.php') !== false ||
        strpos($referer, 'produto_copiar_etiquetas.php') !== false ||
        strpos($referer, 'relatorio_imprimir_alteracao.php') !== false ||
        strpos($referer, 'produtos_listar.php') !== false
    ) {
        $contexto = 'planilha';
    } else {
        $contexto = 'principal';
    }
}

if ($origem) {
    $backUrl = $origem;
} elseif (($contexto === 'planilha' || $contexto === 'relatorio') && $id_planilha) {
    if ($modo_publico) {
        $backUrl = base_url('assinatura_publica.php');
    } else {
        $backUrl = base_url('/planilhas/visualizar?id=' . urlencode($id_planilha));
    }
} else {
    $backUrl = $modo_publico ? base_url('assinatura_publica.php') : base_url('/');
}

$pageTitle = "Menu";
$headerActions = '';

ob_start();
?>

<div class="card">
    <div class="card-header">
        <i class="bi bi-list me-2"></i><?php echo htmlspecialchars(to_uppercase('Menu de Opções'), ENT_QUOTES, 'UTF-8'); ?>
    </div>
    <div class="list-group list-group-flush">
        <?php if ($contexto === 'principal'): ?>
            <div class="list-group-item bg-light text-muted small fw-semibold">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> PLANILHAS
            </div>
            <a href="/planilhas/importar" class="list-group-item list-group-item-action">
                <i class="bi bi-upload me-2"></i><?php echo htmlspecialchars(to_uppercase('Importar Planilha'), ENT_QUOTES, 'UTF-8'); ?>
            </a>
            <div class="list-group-item bg-light text-muted small fw-semibold">
                <i class="bi bi-people me-1"></i> <?php echo htmlspecialchars(to_uppercase('Administração'), ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <a href="/usuarios" class="list-group-item list-group-item-action">
                <i class="bi bi-people me-2"></i><?php echo htmlspecialchars(to_uppercase('Listagem de Usuários'), ENT_QUOTES, 'UTF-8'); ?>
            </a>
            <div class="list-group-item bg-light text-muted small fw-semibold">
                <i class="bi bi-gear me-1"></i> <?php echo htmlspecialchars(to_uppercase('Sistema'), ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <a href="/logout" class="list-group-item list-group-item-action text-danger">
                <i class="bi bi-box-arrow-right me-2"></i><?php echo htmlspecialchars(to_uppercase('Sair'), ENT_QUOTES, 'UTF-8'); ?>
            </a>
        <?php endif; ?>

        <?php if ($contexto === 'planilha' && $id_planilha): ?>
            <?php if (!$modo_publico): ?>
                <div class="list-group-item bg-light text-muted small fw-semibold">
                    <i class="bi bi-box-seam me-1"></i> <?php echo htmlspecialchars(to_uppercase('Produtos'), ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <a href="/produtos?id=<?php echo $id_planilha; ?>" class="list-group-item list-group-item-action">
                    <i class="bi bi-list-ul me-2"></i><?php echo htmlspecialchars(to_uppercase('Listagem de Produtos'), ENT_QUOTES, 'UTF-8'); ?>
                </a>
            <?php endif; ?>
            <div class="list-group-item bg-light text-muted small fw-semibold">
                <i class="bi bi-file-earmark-text me-1"></i> <?php echo htmlspecialchars(to_uppercase('Relatórios'), ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <a href="/relatorios/14-1?id=<?php echo $id_planilha; ?>" class="list-group-item list-group-item-action">
                <i class="bi bi-file-earmark-pdf me-2"></i><?php echo htmlspecialchars(to_uppercase('Relatório 14.1'), ENT_QUOTES, 'UTF-8'); ?>
            </a>
            <a href="/relatorios/visualizar?id=<?php echo $id_planilha; ?>&form=alteracao" class="list-group-item list-group-item-action">
                <i class="bi bi-file-earmark-diff me-2"></i><?php echo htmlspecialchars(to_uppercase('Relatório de Alterações'), ENT_QUOTES, 'UTF-8'); ?>
            </a>
            <?php if (!$modo_publico): ?>
                <div class="list-group-item bg-light text-muted small fw-semibold">
                    <i class="bi bi-three-dots me-1"></i> <?php echo htmlspecialchars(to_uppercase('Outros'), ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <a href="/produtos/etiqueta?id=<?php echo $id_planilha; ?>" class="list-group-item list-group-item-action">
                    <i class="bi bi-tags me-2"></i><?php echo htmlspecialchars(to_uppercase('Copiar Etiquetas'), ENT_QUOTES, 'UTF-8'); ?>
                </a>
                <a href="/planilhas/configuracao?id=<?php echo $id_planilha; ?>" class="list-group-item list-group-item-action">
                    <i class="bi bi-pencil me-2"></i><?php echo htmlspecialchars(to_uppercase('Editar Planilha'), ENT_QUOTES, 'UTF-8'); ?>
                </a>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($contexto === 'relatorio' && $id_planilha): ?>
            <div class="list-group-item bg-light text-muted small fw-semibold">
                <i class="bi bi-compass me-1"></i> <?php echo htmlspecialchars(to_uppercase('Navegação'), ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <a href="/planilhas/visualizar?id=<?php echo $id_planilha; ?>" class="list-group-item list-group-item-action">
                <i class="bi bi-eye me-2"></i><?php echo htmlspecialchars(to_uppercase('Ver Planilha'), ENT_QUOTES, 'UTF-8'); ?>
            </a>
            <a href="/planilhas/configuracao?id=<?php echo $id_planilha; ?>" class="list-group-item list-group-item-action">
                <i class="bi bi-pencil me-2"></i>Editar Planilha
            </a>
            <?php if (!$modo_publico): ?>
                <div class="list-group-item bg-light text-muted small fw-semibold">
                    <i class="bi bi-box-seam me-1"></i> PRODUTOS
                </div>
                <a href="/produtos?id=<?php echo $id_planilha; ?>" class="list-group-item list-group-item-action">
                    <i class="bi bi-plus-circle me-2"></i><?php echo htmlspecialchars(to_uppercase('Cadastrar Produto'), ENT_QUOTES, 'UTF-8'); ?>
                </a>
            <?php endif; ?>
            <div class="list-group-item bg-light text-muted small fw-semibold">
                <i class="bi bi-printer me-1"></i> <?php echo htmlspecialchars(to_uppercase('Impressões'), ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <?php if (!$modo_publico): ?>
                <a href="/produtos/etiqueta?id=<?php echo $id_planilha; ?>" class="list-group-item list-group-item-action">
                    <i class="bi bi-tags me-2"></i><?php echo htmlspecialchars(to_uppercase('Copiar Etiquetas'), ENT_QUOTES, 'UTF-8'); ?>
                </a>
            <?php endif; ?>
            <a href="/relatorios/visualizar?id=<?php echo $id_planilha; ?>&form=alteracao" class="list-group-item list-group-item-action">
                <i class="bi bi-file-earmark-diff me-2"></i><?php echo htmlspecialchars(to_uppercase('Imprimir Alterações'), ENT_QUOTES, 'UTF-8'); ?>
            </a>
        <?php endif; ?>

        <?php if ($modo_publico): ?>
            <div class="list-group-item bg-light text-muted small fw-semibold">
                <i class="bi bi-gear me-1"></i> SISTEMA
            </div>
            <a href="/logout" class="list-group-item list-group-item-action text-danger">
                <i class="bi bi-box-arrow-right me-2"></i>Sair
            </a>
        <?php endif; ?>
    </div>
</div>

<?php
$contentHtml = ob_get_clean();
$tempFile = __DIR__ . '/../../../temp_menu_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app_wrapper.php';
unlink($tempFile);
?>