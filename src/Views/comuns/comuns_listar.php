<?php
require_once dirname(__DIR__, 2) . '/Helpers/BootstrapLoader.php';




$pageTitle = 'Gerenciar Comuns';
$backUrl = base_url('/');

$qsArr = [];
if (!empty($_GET['busca'])) {
    $qsArr['busca'] = $_GET['busca'];
}
if (!empty($pagina) && $pagina > 1) {
    $qsArr['pagina'] = $pagina;
}
$qs = http_build_query($qsArr);
$pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$limite = 20;
$offset = ($pagina - 1) * $limite;


try {
    $total_registros = (int) contar_comuns($conexao, '');
    $total_paginas = (int)ceil($total_registros / $limite);
} catch (Exception $e) {
    $total_registros = 0;
    $total_paginas = 1;
    $erro = "Erro ao contar comuns: " . $e->getMessage();
}


try {
    $comuns = buscar_comuns_paginated($conexao, '', $limite, $offset);
} catch (Exception $e) {
    $comuns = [];
    $erro = "Erro ao carregar comuns: " . $e->getMessage();
}


ob_start();
?>

<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
            <span>
                <i class="bi bi-building me-2"></i>
                <?php echo htmlspecialchars(to_uppercase('Lista de Comuns'), ENT_QUOTES, 'UTF-8'); ?>
            </span>
            <span class="badge bg-white text-dark"><?php echo (int)$total_registros; ?> ITENS (PG. <?php echo $pagina; ?>/<?php echo $total_paginas ?: 1; ?>)</span>
        </div>
        <div class="card-body">
            <?php if (!empty($_SESSION['mensagem'])): ?>
                <div class="alert alert-<?php echo $_SESSION['tipo_mensagem'] ?? 'info'; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_SESSION['mensagem']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['mensagem'], $_SESSION['tipo_mensagem']); ?>
            <?php endif; ?>

            <?php if (!empty($erro)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($erro); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($comuns)): ?>
                <div class="alert alert-info" role="alert">
                    <i class="bi bi-info-circle me-2"></i>
                    Nenhuma comum cadastrada no momento.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th width="15%">C³digo</th>
                                <th width="60%">Descri§£o</th>
                                <th width="15%" class="text-center">PRODUTOS</th>
                                <th width="10%" class="text-center">A§µes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($comuns as $comum): ?>
                                <?php
                                $total_PRODUTOS = contar_PRODUTOS_por_comum($conexao, $comum['id']);
                                $badge_class = $total_PRODUTOS > 0 ? 'badge-success' : 'badge-secondary';
                                ?>
                                <tr>
                                    <td>
                                        <code><?php echo htmlspecialchars($comum['codigo']); ?></code>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($comum['descricao']); ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge <?php echo $badge_class; ?>">
                                            <?php echo $total_PRODUTOS; ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="/comuns/editar?id=<?php echo $comum['id']; ?><?php echo ($qs ? '&' . $qs : ''); ?>"
                                                class="btn btn-outline-primary"
                                                title="EDITAR">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="/comuns/configuracao?comum_id=<?php echo $comum['id']; ?>"
                                                class="btn btn-outline-secondary"
                                                title="VISUALIZAR">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 text-muted small d-flex justify-content-between align-items-center">
                    <span>
                        <i class="bi bi-info-circle me-1"></i>
                        <?php echo htmlspecialchars(to_uppercase("Pgina {$pagina} de {$total_paginas} | Exibindo"), ENT_QUOTES, 'UTF-8'); ?> <strong><?php echo count($comuns); ?></strong> <?php echo htmlspecialchars(to_uppercase('de'), ENT_QUOTES, 'UTF-8'); ?> <strong><?php echo $total_registros; ?></strong>
                    </span>
                </div>

                <?php if ($total_paginas > 1): ?>
                    <nav class="mt-2" aria-label="Pagina§£o comuns">
                        <ul class="pagination pagination-sm justify-content-center mb-0">
                            <?php if ($pagina > 1): ?>
                                <li class="page-item"><a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])); ?>">&laquo;</a></li>
                            <?php endif; ?>
                            <?php $ini = max(1, $pagina - 2);
                            $fim = min($total_paginas, $pagina + 2);
                            for ($i = $ini; $i <= $fim; $i++): ?>
                                <li class="page-item <?php echo $i == $pagina ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $i])); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <?php if ($pagina < $total_paginas): ?>
                                <li class="page-item"><a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])); ?>">&raquo;</a></li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php
$conteudo = ob_get_clean();

require_once __DIR__ . '/../layouts/app.php';
?>