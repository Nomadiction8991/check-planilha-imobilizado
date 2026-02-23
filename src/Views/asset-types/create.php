<?php

/**
 * View: Cadastrar Tipo de Bem
 */
?>

<div class="container-fluid page-editar-asset-type">
    <div class="card">
        <div class="card-header">
            <i class="bi bi-plus-circle me-2"></i>
            NOVO TIPO DE BEM
        </div>
        <div class="card-body">

            <?php if (!empty($_SESSION['mensagem'])): ?>
                <div class="alert alert-<?= htmlspecialchars($_SESSION['tipo_mensagem'] ?? 'info') ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['mensagem']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['mensagem'], $_SESSION['tipo_mensagem']); ?>
            <?php endif; ?>

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

<style>
    .page-editar-asset-type {
        padding: 12px;
    }

    .page-editar-asset-type>.card {
        width: 95%;
        margin: 0 auto;
    }

    .page-editar-asset-type .form-label {
        font-weight: 600;
        font-size: .9rem;
        color: #444;
        display: block;
        margin-bottom: 4px;
    }

    .page-editar-asset-type .form-label i {
        color: #6c757d;
        font-size: .85rem;
    }

    .page-editar-asset-type .form-actions {
        margin-top: 20px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .page-editar-asset-type .form-actions .btn {
        width: 100%;
    }
</style>