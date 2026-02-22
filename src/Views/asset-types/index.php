<?php

use App\Helpers\{AlertHelper, PaginationHelper, ViewHelper};

$tipos       = $tipos       ?? [];
$total       = $total       ?? 0;
$pagina      = $pagina      ?? 1;
$totalPaginas = $totalPaginas ?? 1;
$busca       = $busca       ?? '';
$limite      = $limite      ?? 20;
?>

<!-- Alertas -->
<?= AlertHelper::fromQuery() ?>

<!-- Card de Pesquisa -->
<div class="card mb-3">
    <div class="card-header">
        <i class="bi bi-search me-2"></i>PESQUISAR TIPO DE BEM
    </div>
    <div class="card-body">
        <form method="GET" action="/asset-types" class="row g-2">
            <div class="col-12">
                <label for="busca" class="form-label">DESCRIÇÃO</label>
                <div class="input-group">
                    <input
                        type="text"
                        name="busca"
                        id="busca"
                        class="form-control text-uppercase"
                        value="<?= htmlspecialchars($busca, ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="DIGITE A DESCRIÇÃO">
                    <?php if ($busca): ?>
                        <a href="/asset-types" class="btn btn-outline-secondary" title="LIMPAR">
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

<!-- Card de Listagem -->
<div class="card">
    <div class="card-header">
        <i class="bi bi-box-seam me-2"></i>TIPOS DE BENS
    </div>
    <div class="card-body p-0">

        <?php if (isset($erro)): ?>
            <div class="alert alert-danger m-3"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>DESCRIÇÃO</th>
                        <th width="80" class="text-center"><?php echo htmlspecialchars(to_uppercase('Ações'), ENT_QUOTES, 'UTF-8'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tipos)): ?>
                        <tr>
                            <td colspan="2" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                NENHUM TIPO DE BEM ENCONTRADO
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tipos as $tipo): ?>
                            <tr>
                                <td class="text-uppercase">
                                    <?= htmlspecialchars($tipo['descricao'], ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td class="text-center">
                                    <a
                                        href="/asset-types/<?= (int)$tipo['id'] ?>/edit"
                                        class="btn btn-sm btn-outline-primary"
                                        title="EDITAR">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<!-- Botão Novo -->
<div class="mt-3">
    <a href="/asset-types/create" class="btn btn-primary w-100">
        <i class="bi bi-plus-circle me-2"></i>NOVO TIPO DE BEM
    </a>
</div>

<!-- Paginação -->
<?= PaginationHelper::render($pagina, $totalPaginas, '/asset-types', ['busca' => $busca]) ?>