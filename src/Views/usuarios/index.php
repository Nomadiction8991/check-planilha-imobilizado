<?php

/**
 * View: Listagem de Usuários
 * 
 * Variáveis esperadas:
 * - $usuarios: Array de usuários paginados
 * - $total: Total de registros
 * - $pagina: Página atual
 * - $totalPaginas: Total de páginas
 * - $busca: Termo de busca
 * - $status: Filtro de status
 * - $limite: Itens por página
 */

use App\Helpers\{AlertHelper, PaginationHelper, ViewHelper};

$usuarios = $usuarios ?? [];
$total = $total ?? 0;
$pagina = $pagina ?? 1;
$totalPaginas = $totalPaginas ?? 1;
$busca = $busca ?? '';
$status = $status ?? '';
$limite = $limite ?? 10;
?>

<!-- Alertas de Feedback -->
<?= AlertHelper::fromQuery() ?>

<!-- Card de Filtros -->
<div class="card mb-3">
    <div class="card-header">
        <i class="bi bi-funnel me-2"></i>FILTROS DE PESQUISA
    </div>
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-12">
                <label for="busca" class="form-label">NOME OU EMAIL</label>
                <div class="input-group">
                    <input
                        type="text"
                        name="busca"
                        id="busca"
                        class="form-control text-uppercase"
                        value="<?= ViewHelper::e($busca) ?>"
                        placeholder="DIGITE NOME OU EMAIL">
                    <?php if ($busca): ?>
                        <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('busca').value=''; this.form.submit();" title="LIMPAR">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-12">
                <label for="status" class="form-label">STATUS</label>
                <select name="status" id="status" class="form-select">
                    <option value="">TODOS</option>
                    <option value="1" <?= ViewHelper::selected($status, '1') ?>>ATIVOS</option>
                    <option value="0" <?= ViewHelper::selected($status, '0') ?>>INATIVOS</option>
                </select>
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
                <th>EMAIL</th>
                <th>STATUS</th>
                <th class="text-center">AÇÕES</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($usuarios)): ?>
                <tr>
                    <td colspan="4" class="text-center py-4 text-muted">
                        <i class="bi bi-people fs-3 d-block mb-2"></i>
                        NENHUM USUÁRIO ENCONTRADO
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($usuarios as $usuario): ?>
                    <?php
                    $ativo = (bool)($usuario['ativo'] ?? true);
                    $editUrl = ViewHelper::urlComQuery('/usuarios/editar', ['id' => $usuario['id']]);
                    $viewUrl = ViewHelper::urlComQuery('/usuarios/ver', ['id' => $usuario['id']]);
                    ?>
                    <tr class="<?= ViewHelper::classeLinhaStatus($ativo) ?>">
                        <td class="text-uppercase fw-semibold">
                            <?= ViewHelper::e($usuario['nome'] ?? '') ?>
                        </td>
                        <td class="text-uppercase">
                            <?= ViewHelper::e($usuario['email'] ?? '') ?>
                        </td>
                        <td>
                            <?= ViewHelper::badgeStatus($ativo) ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm d-flex justify-content-center" role="group">
                                <a
                                    class="btn btn-outline-info"
                                    href="<?= ViewHelper::e($viewUrl) ?>"
                                    title="VISUALIZAR">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a
                                    class="btn btn-outline-primary"
                                    href="<?= ViewHelper::e($editUrl) ?>"
                                    title="EDITAR">
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
<?= PaginationHelper::render($pagina, $totalPaginas, '/usuarios', ['busca' => $busca, 'status' => $status]) ?>