<?php


use App\Helpers\{AlertHelper, PaginationHelper, ViewHelper};

$usuarios = $usuarios ?? [];
$total = $total ?? 0;
$pagina = $pagina ?? 1;
$totalPaginas = $totalPaginas ?? 1;
$busca = $busca ?? '';
$limite = $limite ?? 10;
?>

<!-- Alertas de Feedback -->
<?= AlertHelper::fromQuery() ?>

<!-- Card de Filtros -->
<div class="card mb-3">
    <div class="card-header">
        <i class="bi bi-funnel me-2"></i>PESQUISAR
    </div>
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-12">
                <label for="busca" class="form-label">NOME</label>
                <div class="input-group">
                    <input
                        type="text"
                        name="busca"
                        id="busca"
                        class="form-control text-uppercase"
                        value="<?= ViewHelper::e($busca) ?>"
                        placeholder="DIGITE O NOME DO USUÁRIO">
                    <?php if ($busca): ?>
                        <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('busca').value=''; this.form.submit();" title="LIMPAR">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-2"></i>FILTRAR
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Informações de Resultado -->
<?= PaginationHelper::info($total, $pagina, $limite) ?>

<!-- Tabela de Usuários -->
<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>NOME</th>
                <th class="text-center">AÇÕES</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($usuarios)): ?>
                <tr>
                    <td colspan="2" class="text-center py-4 text-muted">
                        <i class="bi bi-people fs-3 d-block mb-2"></i>
                        NENHUM USUÁRIO ENCONTRADO
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($usuarios as $usuario): ?>
                    <?php
                    $editUrl = ViewHelper::urlComQuery('/users/edit', ['id' => $usuario['id']]);
                    ?>
                    <tr>
                        <td class="text-uppercase fw-semibold">
                            <?= ViewHelper::e($usuario['nome'] ?? '') ?>
                        </td>
                        <td class="text-center">
                            <a
                                class="btn btn-sm btn-outline-primary"
                                href="<?= ViewHelper::e($editUrl) ?>"
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

<!-- Paginação -->
<?= PaginationHelper::render($pagina, $totalPaginas, '/users', ['busca' => $busca]) ?>