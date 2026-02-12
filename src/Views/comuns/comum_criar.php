<?php
$pageTitle = 'CADASTRAR IGREJA';
$backUrl = '/comuns';
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
            <i class="bi bi-building me-2"></i>
            NOVA IGREJA
        </div>
        <div class="card-body">
            <form method="POST" action="/comuns/criar">
                <div class="mb-3">
                    <label for="codigo" class="form-label">Código *</label>
                    <input type="number" class="form-control" id="codigo" name="codigo" required 
                           placeholder="Ex: 1001" min="1">
                    <small class="text-muted">Código numérico único da igreja</small>
                </div>

                <div class="mb-3">
                    <label for="descricao" class="form-label">Descrição *</label>
                    <input type="text" class="form-control" id="descricao" name="descricao" required
                           placeholder="Ex: Igreja Central">
                </div>

                <div class="mb-3">
                    <label for="cnpj" class="form-label">CNPJ</label>
                    <input type="text" class="form-control" id="cnpj" name="cnpj"
                           placeholder="00.000.000/0000-00">
                </div>

                <div class="mb-3">
                    <label for="administracao" class="form-label">Administração</label>
                    <input type="text" class="form-control" id="administracao" name="administracao"
                           placeholder="Nome da administração">
                </div>

                <div class="mb-3">
                    <label for="cidade" class="form-label">Cidade</label>
                    <input type="text" class="form-control" id="cidade" name="cidade"
                           placeholder="Nome da cidade">
                </div>

                <div class="mb-3">
                    <label for="setor" class="form-label">Setor</label>
                    <input type="number" class="form-control" id="setor" name="setor"
                           placeholder="Número do setor">
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>SALVAR IGREJA
                    </button>
                    <a href="/comuns" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-2"></i>CANCELAR
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
