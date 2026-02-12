<?php
$pageTitle = 'TIPOS DE BENS';
$backUrl = '/menu';
?>

<div class="container-fluid py-3">
    <?php if (isset($_GET['sucesso'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_GET['sucesso'], ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['erro'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_GET['erro'], ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-box-seam me-2"></i>TIPOS DE BENS</span>
            <a href="/tipos-bens/criar" class="btn btn-light btn-sm" style="color: #6f42c1; border-color: #e0e0e0;">
                <i class="bi bi-plus-circle me-1"></i>NOVO
            </a>
        </div>
        <div class="card-body">
            <!-- Busca -->
            <div class="mb-3">
                <form method="GET" action="/tipos-bens">
                    <div class="input-group">
                        <input type="text" class="form-control" name="busca"
                            placeholder="Buscar por código ou descrição..."
                            value="<?= htmlspecialchars($busca ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                        <?php if (!empty($busca)): ?>
                            <a href="/tipos-bens" class="btn btn-outline-secondary">
                                <i class="bi bi-x"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Tabela -->
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>CÓDIGO</th>
                            <th>DESCRIÇÃO</th>
                            <th class="text-center">AÇÕES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tipos)): ?>
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                    NENHUM TIPO DE BEM ENCONTRADO
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tipos as $tipo): ?>
                                <tr>
                                    <td class="fw-semibold"><?= htmlspecialchars($tipo['codigo'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($tipo['descricao'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <a href="/tipos-bens/<?= htmlspecialchars($tipo['id'], ENT_QUOTES, 'UTF-8') ?>/editar"
                                                class="btn btn-outline-primary" title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <?php if ($totalPaginas > 1): ?>
                <nav>
                    <ul class="pagination pagination-sm justify-content-center">
                        <?php if ($pagina > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?busca=<?= urlencode($busca ?? '') ?>&pagina=<?= $pagina - 1 ?>">
                                    Anterior
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                            <li class="page-item <?= $i === $pagina ? 'active' : '' ?>">
                                <a class="page-link" href="?busca=<?= urlencode($busca ?? '') ?>&pagina=<?= $i ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($pagina < $totalPaginas): ?>
                            <li class="page-item">
                                <a class="page-link" href="?busca=<?= urlencode($busca ?? '') ?>&pagina=<?= $pagina + 1 ?>">
                                    Próxima
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>