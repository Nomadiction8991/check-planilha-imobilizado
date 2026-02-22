<?php


use App\Helpers\{AlertHelper, PaginationHelper, ViewHelper};


$comuns = $comuns ?? [];
$total = $total ?? 0;
$pagina = $pagina ?? 1;
$totalPaginas = $totalPaginas ?? 1;
$busca = $busca ?? '';
$limite = $limite ?? 10;
?>

<!-- Alertas de Feedback -->
<?= AlertHelper::fromQuery() ?>

<!-- Card de Pesquisa -->
<div class="card mb-3">
    <div class="card-header">
        <i class="bi bi-search me-2"></i>PESQUISAR COMUM
    </div>
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-12">
                <label for="busca" class="form-label">CÓDIGO OU DESCRIÇÃO</label>
                <div class="input-group">
                    <input
                        type="text"
                        name="busca"
                        id="busca"
                        class="form-control text-uppercase"
                        value="<?= ViewHelper::e($busca) ?>"
                        placeholder="DIGITE CÓDIGO OU DESCRIÇÃO">
                    <?php if ($busca): ?>
                        <a href="?" class="btn btn-outline-secondary" title="LIMPAR BUSCA">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-2"></i>BUSCAR
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Informações de Resultado -->
<?= PaginationHelper::info($total, $pagina, $limite) ?>

<!-- Tabela de Comuns -->
<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th width="25%">CÓDIGO</th>
                <th width="50%">DESCRIÇÃO</th>
                <th width="25%" class="text-center">AÇÕES</th>
            </tr>
        </thead>
        <tbody id="tabela-comuns">
            <?php if (empty($comuns)): ?>
                <tr>
                    <td colspan="3" class="text-center py-4 text-muted">
                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                        NENHUM COMUM ENCONTRADO
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($comuns as $comum): ?>
                    <?php

                    $cadastroCompleto = !empty(trim($comum['descricao'] ?? '')) &&
                        !empty(trim($comum['cnpj'] ?? '')) &&
                        !empty(trim($comum['administracao'] ?? '')) &&
                        !empty(trim($comum['cidade'] ?? ''));


                    $codigo = preg_replace("/\D/", '', (string)($comum['codigo'] ?? ''));
                    if ($codigo === '') {
                        $codigoFormatado = 'BR --';
                    } else {
                        $codigo = str_pad($codigo, 6, '0', STR_PAD_LEFT);
                        $codigoFormatado = 'BR ' . substr($codigo, 0, 2) . '-' . substr($codigo, 2);
                    }


                    $editUrl = ViewHelper::urlComQuery('/churches/edit', ['id' => $comum['id']]);
                    $viewUrl = ViewHelper::urlComQuery('/products/view', ['comum_id' => $comum['id']]);
                    ?>
                    <tr>
                        <td class="fw-semibold text-uppercase">
                            <?= ViewHelper::e($codigoFormatado) ?>
                        </td>
                        <td class="text-uppercase">
                            <?= ViewHelper::e($comum['descricao'] ?? '') ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm d-flex justify-content-center" role="group">
                                <a
                                    class="btn btn-outline-primary"
                                    href="<?= ViewHelper::e($editUrl) ?>"
                                    title="EDITAR COMUM">
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
<?= PaginationHelper::render($pagina, $totalPaginas, '/churches', ['busca' => $busca]) ?>

<!-- Modal: Cadastro Incompleto -->
<div class="modal fade" id="modalCadastroIncompleto" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                    CADASTRO INCOMPLETO
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>O CADASTRO DESTE COMUM ESTÁ INCOMPLETO.</p>
                <p class="mb-0">DESEJA COMPLETAR AS INFORMAÇÕES AGORA?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">CANCELAR</button>
                <a href="#" id="btnCompletarCadastro" class="btn btn-primary">COMPLETAR CADASTRO</a>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script src="/assets/js/comuns/index.js"></script>