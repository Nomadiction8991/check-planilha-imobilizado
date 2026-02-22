<?php


/**
 * View: Editar Comum
 * Formulário moderno para edição de comuns usando arquitetura MVC
 */
?>

<div class="container-fluid page-editar-comum">
    <div class="card">
                <div class="card-header">
                    <i class="bi bi-pencil-square"></i>
                    <?= htmlspecialchars(mb_strtoupper('Editar Comum', 'UTF-8')) ?>
                </div>
                <div class="card-body">
                    <?php if (!empty($_SESSION['mensagem'])): ?>
                        <div class="alert alert-<?= htmlspecialchars($_SESSION['tipo_mensagem'] ?? 'info') ?> alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($_SESSION['mensagem']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php
                        unset($_SESSION['mensagem'], $_SESSION['tipo_mensagem']);
                        ?>
                    <?php endif; ?>

                    <form method="POST" action="/churches/edit" id="formEditarComum">
                        <input type="hidden" name="id" value="<?= (int)$comum['id'] ?>">
                        <input type="hidden" name="busca" value="<?= htmlspecialchars($busca ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="pagina" value="<?= (int)($pagina ?? 1) ?>">

                        <div class="row g-3">
                            <!-- Código -->
                            <div class="col-md-4">
                                <label for="codigo" class="form-label">
                                    <i class="bi bi-tag me-1"></i>
                                    Código <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="text"
                                    class="form-control text-uppercase"
                                    id="codigo"
                                    name="codigo"
                                    value="<?= htmlspecialchars($comum['codigo'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                    required
                                    maxlength="50">
                            </div>

                            <!-- Descrição -->
                            <div class="col-md-8">
                                <label for="descricao" class="form-label">
                                    <i class="bi bi-card-text me-1"></i>
                                    Descrição <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="text"
                                    class="form-control text-uppercase"
                                    id="descricao"
                                    name="descricao"
                                    value="<?= htmlspecialchars($comum['descricao'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                    required
                                    maxlength="255">
                            </div>

                            <!-- CNPJ -->
                            <div class="col-md-6">
                                <label for="cnpj" class="form-label">
                                    <i class="bi bi-file-earmark-text me-1"></i>
                                    CNPJ
                                </label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="cnpj"
                                    name="cnpj"
                                    value="<?= htmlspecialchars($comum['cnpj'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                    maxlength="18"
                                    placeholder="00.000.000/0000-00">
                            </div>

                            <!-- Administração -->
                            <div class="col-md-6">
                                <label for="administracao" class="form-label">
                                    <i class="bi bi-building me-1"></i>
                                    Administração
                                </label>
                                <input
                                    type="text"
                                    class="form-control text-uppercase"
                                    id="administracao"
                                    name="administracao"
                                    value="<?= htmlspecialchars($comum['administracao'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                    maxlength="100">
                            </div>

                            <!-- Cidade -->
                            <div class="col-md-6">
                                <label for="cidade" class="form-label">
                                    <i class="bi bi-geo-alt me-1"></i>
                                    Cidade
                                </label>
                                <input
                                    type="text"
                                    class="form-control text-uppercase"
                                    id="cidade"
                                    name="cidade"
                                    value="<?= htmlspecialchars($comum['cidade'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                    maxlength="100">
                            </div>

                            <!-- (Campos Estado, Endereço e Telefone removidos conforme solicitado) -->
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i>
                                Salvar Alterações
                            </button>
                            <a href="<?= $backUrl ?? '/churches' ?>" class="btn btn-secondary">
                                <i class="bi bi-x-lg me-1"></i>
                                Cancelar
                            </a>
                        </div>
                    </form>
                </div>
    </div>
</div>

<script src="/assets/js/comuns/edit.js"></script>