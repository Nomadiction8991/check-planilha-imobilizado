<?php

/**
 * View: Cadastrar Tipo de Bem
 */

$customCssPath = '/assets/css/asset-types/create.css';
?>

<div class="container-fluid page-editar-asset-type">
    <div class="card">
        <div class="card-header">
            <i class="bi bi-plus-circle me-2"></i>
            NOVO TIPO DE BEM
        </div>
        <div class="card-body">

            <?php if (isset($_GET['erro'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= htmlspecialchars($_GET['erro'], ENT_QUOTES, 'UTF-8') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="/asset-types/create">
                <div class="row g-3">
                    <!-- Descrição -->
                    <div class="col-12">
                        <label for="descricao" class="form-label">
                            <i class="bi bi-card-text me-1"></i>
                            Descrição <span class="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            class="form-control text-uppercase"
                            id="descricao"
                            name="descricao"
                            required
                            maxlength="255"
                            placeholder="EX: IMÓVEIS">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>
                        Salvar
                    </button>
                    <a href="<?= $backUrl ?? '/asset-types' ?>" class="btn btn-secondary">
                        <i class="bi bi-x-lg me-1"></i>
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="/assets/js/asset-types/create.js"></script>
