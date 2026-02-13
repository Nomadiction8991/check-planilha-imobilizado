<?php

/**
 * View: Editar Comum
 * Formulário moderno para edição de comuns usando arquitetura MVC
 */
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-pencil-square me-2"></i>
                        <?= htmlspecialchars(mb_strtoupper('Editar Comum', 'UTF-8')) ?>
                    </h5>
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

                    <form method="POST" action="/comuns/editar" id="formEditarComum">
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

                            <!-- Estado -->
                            <div class="col-md-6">
                                <label for="estado" class="form-label">
                                    <i class="bi bi-map me-1"></i>
                                    Estado
                                </label>
                                <select class="form-select" id="estado" name="estado">
                                    <option value="">Selecione...</option>
                                    <?php
                                    $estados = [
                                        'AC',
                                        'AL',
                                        'AP',
                                        'AM',
                                        'BA',
                                        'CE',
                                        'DF',
                                        'ES',
                                        'GO',
                                        'MA',
                                        'MT',
                                        'MS',
                                        'MG',
                                        'PA',
                                        'PB',
                                        'PR',
                                        'PE',
                                        'PI',
                                        'RJ',
                                        'RN',
                                        'RS',
                                        'RO',
                                        'RR',
                                        'SC',
                                        'SP',
                                        'SE',
                                        'TO'
                                    ];
                                    $estadoAtual = $comum['estado'] ?? '';
                                    foreach ($estados as $uf):
                                    ?>
                                        <option value="<?= $uf ?>" <?= $estadoAtual === $uf ? 'selected' : '' ?>>
                                            <?= $uf ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Endereço -->
                            <div class="col-12">
                                <label for="endereco" class="form-label">
                                    <i class="bi bi-house me-1"></i>
                                    Endereço
                                </label>
                                <input
                                    type="text"
                                    class="form-control text-uppercase"
                                    id="endereco"
                                    name="endereco"
                                    value="<?= htmlspecialchars($comum['endereco'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                    maxlength="255">
                            </div>

                            <!-- Telefone -->
                            <div class="col-md-6">
                                <label for="telefone" class="form-label">
                                    <i class="bi bi-telephone me-1"></i>
                                    Telefone
                                </label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="telefone"
                                    name="telefone"
                                    value="<?= htmlspecialchars($comum['telefone'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                    maxlength="20"
                                    placeholder="(00) 00000-0000">
                            </div>
                        </div>

                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i>
                                Salvar Alterações
                            </button>
                            <a href="<?= $backUrl ?? '/comuns' ?>" class="btn btn-secondary">
                                <i class="bi bi-x-lg me-1"></i>
                                Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<link href="/assets/css/comuns/edit.css" rel="stylesheet">

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-uppercase para campos específicos
        const upperFields = document.querySelectorAll('.text-uppercase');
        upperFields.forEach(field => {
            field.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });
        });

        // Máscara de CNPJ
        const cnpjField = document.getElementById('cnpj');
        if (cnpjField) {
            cnpjField.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length <= 14) {
                    value = value.replace(/^(\d{2})(\d)/, '$1.$2');
                    value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
                    value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
                    value = value.replace(/(\d{4})(\d)/, '$1-$2');
                    e.target.value = value;
                }
            });
        }

        // Máscara de telefone
        const telefoneField = document.getElementById('telefone');
        if (telefoneField) {
            telefoneField.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length <= 11) {
                    if (value.length <= 10) {
                        value = value.replace(/^(\d{2})(\d)/, '($1) $2');
                        value = value.replace(/(\d{4})(\d)/, '$1-$2');
                    } else {
                        value = value.replace(/^(\d{2})(\d)/, '($1) $2');
                        value = value.replace(/(\d{5})(\d)/, '$1-$2');
                    }
                    e.target.value = value;
                }
            });
        }

        // Validação do formulário
        const form = document.getElementById('formEditarComum');
        if (form) {
            form.addEventListener('submit', function(e) {
                const codigo = document.getElementById('codigo').value.trim();
                const descricao = document.getElementById('descricao').value.trim();

                if (!codigo || !descricao) {
                    e.preventDefault();
                    alert('Código e Descrição são obrigatórios!');
                    return false;
                }
            });
        }
    });
</script>