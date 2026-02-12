<?php
$pageTitle = 'EDITAR TIPO DE BEM';
$backUrl = '/tipos-bens';
?>

<div class="container-fluid py-3">
    <?php if (isset($_GET['erro'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_GET['erro'], ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <i class="bi bi-pencil-square me-2"></i>
            EDITAR TIPO DE BEM #<?= htmlspecialchars($tipo['id'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <div class="card-body">
            <form method="POST" action="/tipos-bens/<?= htmlspecialchars($tipo['id'], ENT_QUOTES, 'UTF-8') ?>/editar">
                <input type="hidden" name="id" value="<?= htmlspecialchars($tipo['id'], ENT_QUOTES, 'UTF-8') ?>">
                
                <div class="mb-3">
                    <label for="codigo" class="form-label">Código *</label>
                    <input type="number" class="form-control" id="codigo" name="codigo" required 
                           value="<?= htmlspecialchars($tipo['codigo'], ENT_QUOTES, 'UTF-8') ?>"
                           min="1">
                </div>

                <div class="mb-3">
                    <label for="descricao" class="form-label">Descrição *</label>
                    <input type="text" class="form-control" id="descricao" name="descricao" required
                           value="<?= htmlspecialchars($tipo['descricao'], ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>SALVAR ALTERAÇÕES
                    </button>
                    <a href="/tipos-bens" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-2"></i>CANCELAR
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
