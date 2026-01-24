<?php
require_once __DIR__ . '/app/bootstrap.php';

$pageTitle = 'COMUNS';
$backUrl = null;

$headerActions = '
    <div class="dropdown">
        <button class="btn-header-action" type="button" id="menuPrincipal" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-list fs-5"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="menuPrincipal">';

// Mostrar "Listagem de Usuários" para qualquer usuário autenticado
if (isLoggedIn()) {
    $headerActions .= '
            <li>
                <a class="dropdown-item" href="app/views/usuarios/usuarios_listar.php">
                    <i class="bi bi-people me-2"></i>LISTAGEM DE USUÁRIOS
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="app/views/dependencias/dependencias_listar.php">
                    <i class="bi bi-diagram-3 me-2"></i>LISTAGEM DE DEPENDÊNCIAS
                </a>
            </li>';
}

// Doador/Conjugue: adicionar opção "Editar Meu Usuário"
if (isDoador() && isset($_SESSION['usuario_id'])) {
    $headerActions .= '
            <li>
                <a class="dropdown-item" href="app/views/usuarios/usuario_editar.php?id=' . (int)$_SESSION['usuario_id'] . '">
                    <i class="bi bi-pencil-square me-2"></i>EDITAR MEU USUÁRIO
                </a>
            </li>';
}

// Mostrar "Importar Planilha" para qualquer usuário autenticado
if (isLoggedIn()) {
    $headerActions .= '
            <li>
                <a class="dropdown-item" href="app/views/planilhas/planilha_importar.php">
                    <i class="bi bi-upload me-2"></i>IMPORTAR PLANILHA
                </a>
            </li>';
}

$headerActions .= '
            <li><hr class="dropdown-divider"></li>
            <li>
                <a class="dropdown-item" href="logout.php">
                    <i class="bi bi-box-arrow-right me-2"></i>SAIR
                </a>
            </li>
        </ul>
    </div>
';

$customCss = '
.table-hover tbody tr { cursor: pointer; }
.input-group .btn-clear { border-top-left-radius: 0; border-bottom-left-radius: 0; }
.table.table-center thead th, .table.table-center tbody td { text-align: center; vertical-align: middle; }
';

$busca = trim($_GET['busca'] ?? '');
$buscaDisplay = mb_strtoupper($busca, 'UTF-8');

// Paginação: 10 por página
$pagina = isset($_GET['pagina']) ? max(1, (int) $_GET['pagina']) : 1;
$limite = 10;
$offset = ($pagina - 1) * $limite;

// Build query string for preserving filters when linking to edit/create
$qsArr = [];
if ($busca !== '') {
    $qsArr['busca'] = $busca;
}
if (!empty($pagina) && $pagina > 1) {
    $qsArr['pagina'] = $pagina;
}
$qs = http_build_query($qsArr);

// Count filtered and global totals
$total_count = contar_comuns($conexao, $busca);
$total_count_all = contar_comuns($conexao, '');
$total_paginas = $total_count > 0 ? (int) ceil($total_count / $limite) : 1;
// Tentar carregar a página com captura de erro e logging temporário
try {
    $comums = buscar_comuns_paginated($conexao, $busca, $limite, $offset);
} catch (Throwable $e) {
    // garantir diretório de logs
    @is_dir(__DIR__ . '/storage/logs') || @mkdir(__DIR__ . '/storage/logs', 0755, true);
    @file_put_contents(__DIR__ . '/storage/logs/comuns_page_debug.log', date('c') . " PAGE ERROR " . json_encode(['busca' => $busca, 'message' => $e->getMessage(), 'trace' => substr($e->getTraceAsString(), 0, 1200)]) . PHP_EOL, FILE_APPEND);
    $comums = [];
}
// AJAX handler: retorna as linhas da tabela e a contagem em JSON
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    try {
        // recompute pagina/limit/offset from ajax params
        $pagina_ajax = isset($_GET['pagina']) ? max(1, (int) $_GET['pagina']) : 1;
        $limite_ajax = isset($_GET['limite']) ? max(1, (int) $_GET['limite']) : $limite;
        $offset_ajax = ($pagina_ajax - 1) * $limite_ajax;

        $comums_page = buscar_comuns_paginated($conexao, $busca, $limite_ajax, $offset_ajax);

        $rowsHtml = '';
        if (empty($comums_page)) {
            $rowsHtml = '<tr><td colspan="3" class="text-center py-4 text-muted"><i class="bi bi-inbox fs-3 d-block mb-2"></i>NENHUM COMUM ENCONTRADO</td></tr>';
        } else {
            foreach ($comums_page as $comum) {
                $cadastro_ok = trim((string) $comum['descricao']) !== ''
                    && trim((string) $comum['cnpj']) !== ''
                    && trim((string) $comum['administracao']) !== ''
                    && trim((string) $comum['cidade']) !== '';

                $rowsHtml .= '<tr>';
                $rowsHtml .= '<td class="fw-semibold text-uppercase">' . htmlspecialchars($comum['codigo']) . '</td>';
                $rowsHtml .= '<td class="text-uppercase">' . htmlspecialchars($comum['descricao']) . '</td>';
                $rowsHtml .= '<td>';
                $rowsHtml .= '<div class="btn-group btn-group-sm" role="group">';
                // preserve current busca/page when building edit links for AJAX rows
                $editHref = 'app/views/comuns/comum_editar.php?id=' . (int) $comum['id'] . ($busca !== '' || $pagina_ajax > 1 ? ('&' . http_build_query(['busca' => $busca, 'pagina' => $pagina_ajax])) : '');
                $viewHref = 'app/views/planilhas/planilha_visualizar.php?comum_id=' . (int) $comum['id'];
                $rowsHtml .= '<a class="btn btn-outline-primary" href="' . $editHref . '" title="Editar"><i class="bi bi-pencil"></i></a>';
                $rowsHtml .= '<a class="btn btn-outline-secondary btn-view-planilha" href="' . $viewHref . '" data-cadastro-ok="' . ($cadastro_ok ? '1' : '0') . '" data-edit-url="' . $editHref . '" title="Visualizar planilha"><i class="bi bi-eye"></i></a>';
                $rowsHtml .= '</div>';
                $rowsHtml .= '</td>';
                $rowsHtml .= '</tr>';
            }
        }

        $total_count_ajax = contar_comuns($conexao, $busca);
        $total_count_all_ajax = contar_comuns($conexao, '');
        $total_pages_ajax = $total_count_ajax > 0 ? (int) ceil($total_count_ajax / $limite_ajax) : 1;

        $payload = [
            'rows' => $rowsHtml,
            'count' => $total_count_ajax,
            'count_all' => $total_count_all_ajax,
            'rows_count' => count($comums_page),
            'page' => $pagina_ajax,
            'total_pages' => $total_pages_ajax
        ];

        // garantir diretório de logs
        @is_dir(__DIR__ . '/storage/logs') || @mkdir(__DIR__ . '/storage/logs', 0755, true);
        @file_put_contents(__DIR__ . '/storage/logs/comuns_ajax_debug.log', date('c') . " AJAX OK " . json_encode(['busca' => $busca, 'page' => $pagina_ajax, 'rows_count' => count($comums_page)]) . PHP_EOL, FILE_APPEND);

        echo json_encode($payload);
        exit;
    } catch (Throwable $e) {
        @is_dir(__DIR__ . '/storage/logs') || @mkdir(__DIR__ . '/storage/logs', 0755, true);
        $err = ['error' => true, 'message' => $e->getMessage(), 'trace' => substr($e->getTraceAsString(), 0, 1200)];
        @file_put_contents(__DIR__ . '/storage/logs/comuns_ajax_debug.log', date('c') . " AJAX ERROR " . json_encode($err) . PHP_EOL, FILE_APPEND);
        http_response_code(500);
        echo json_encode($err);
        exit;
    }
}

function formatar_codigo_comum($codigo)
{
    $codigo = preg_replace("/\\D/", '', (string) $codigo);
    if ($codigo === '') {
        return 'BR --';
    }

    $codigo = str_pad($codigo, 6, '0', STR_PAD_LEFT);
    $prefixo = substr($codigo, 0, 2);
    $sufixo = substr($codigo, 2);

    return 'BR ' . $prefixo . '-' . $sufixo;
}

ob_start();
?>

<?php if (!empty($_SESSION['mensagem'])): ?>
    <div class="alert alert-<?php echo ($_SESSION['tipo_mensagem'] ?? 'info') === 'success' ? 'success' : (($_SESSION['tipo_mensagem'] ?? 'info') === 'danger' ? 'danger' : 'info'); ?> alert-dismissible fade show"
        role="alert">
        <?php echo htmlspecialchars($_SESSION['mensagem']);
        unset($_SESSION['mensagem'], $_SESSION['tipo_mensagem']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-header">
        <i class="bi bi-search me-2"></i>PESQUISAR COMUM
    </div>
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-12">
                <label for="busca" class="form-label">CÓDIGO OU DESCRIÇÃO</label>
                <div class="input-group">
                    <input type="text" name="busca" id="busca" class="form-control text-uppercase"
                        value="<?php echo htmlspecialchars($buscaDisplay); ?>">
                </div>
            </div>
            <div class="col-12 pt-3">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-2"></i>BUSCAR
                </button>
            </div>
        </form>
    </div>
    <div id="comumCount" class="card-footer text-muted small">
        <?php echo (int)$total_count_all; ?> COMUM(NS) ENCONTRADA(S)
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-building me-2"></i>COMUNS
        </span>
        <span id="comumBadge" class="badge bg-white text-dark"><?php echo count($comums); ?> ITENS</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped table-center mb-0 align-middle">
                <thead>
                    <tr>
                        <th style="width: 40%">CÓDIGO</th>
                        <th>DESCRIÇÃO</th>
                        <th style="width: 140px">AÇÃO</th>
                    </tr>
                </thead>
                <tbody id="comunsTbody">
                    <?php if (empty($comums)): ?>
                        <tr>
                            <td colspan="3" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                NENHUMA COMUM ENCONTRADA
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($comums as $comum): ?>
                            <?php
                            $cadastro_ok = trim((string) $comum['descricao']) !== ''
                                && trim((string) $comum['cnpj']) !== ''
                                && trim((string) $comum['administracao']) !== ''
                                && trim((string) $comum['cidade']) !== '';
                            ?>
                            <tr>
                                <td class="fw-semibold text-uppercase">
                                    <?php echo htmlspecialchars($comum['codigo']); ?>
                                </td>
                                <td class="text-uppercase">
                                    <?php echo htmlspecialchars($comum['descricao']); ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a class="btn btn-outline-primary"
                                            href="app/views/comuns/comum_editar.php?id=<?php echo (int) $comum['id']; ?><?php echo ($qs ? ('&' . $qs) : ''); ?>"
                                            title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a class="btn btn-outline-secondary btn-view-planilha"
                                            href="app/views/planilhas/planilha_visualizar.php?comum_id=<?php echo (int) $comum['id']; ?>"
                                            data-cadastro-ok="<?php echo $cadastro_ok ? '1' : '0'; ?>"
                                            data-edit-url="app/views/comuns/comum_editar.php?id=<?php echo (int) $comum['id']; ?>"
                                            title="Visualizar planilha">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<nav id="comumPagination" class="mt-3" aria-label="PAGINAÇÃO COMUNS">
    <ul class="pagination pagination-sm justify-content-center mb-0">
        <?php if ($pagina > 1): ?>
            <li class="page-item"><a class="page-link" href="#" data-page="<?php echo $pagina - 1; ?>">&laquo;</a></li>
        <?php endif; ?>
        <?php $ini = max(1, $pagina - 2);
        $fim = min($total_paginas, $pagina + 2);
        for ($i = $ini; $i <= $fim; $i++): ?>
            <li class="page-item <?php echo $i == $pagina ? 'active' : ''; ?>">
                <a class="page-link" href="#" data-page="<?php echo $i; ?>"><?php echo $i; ?></a>
            </li>
        <?php endfor; ?>
        <?php if ($pagina < $total_paginas): ?>
            <li class="page-item"><a class="page-link" href="#" data-page="<?php echo $pagina + 1; ?>">&raquo;</a></li>
        <?php endif; ?>
    </ul>
</nav>

<?php
$contentHtml = ob_get_clean();
$contentFile = __DIR__ . '/temp_index_content.php';
file_put_contents($contentFile, $contentHtml);

require_once __DIR__ . '/app/views/layouts/app_wrapper.php';

@unlink($contentFile);
?>

<!-- Modal cadastro incompleto -->
<div class="modal fade" id="cadastroIncompletoModal" tabindex="-1" aria-labelledby="cadastroIncompletoLabel"
    aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cadastroIncompletoLabel">CADASTRO INCOMPLETO</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p>COMPLETE OS DADOS DA COMUM (DESCRIÇÃO, CNPJ, ADMINISTRAÇÃO E CIDADE) PARA VISUALIZAR A PLANILHA.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary w-47" data-bs-dismiss="modal">DEIXAR PARA
                    DEPOIS</button>
                <a href="#" class="btn btn-primary btn-edit-agora w-47">
                    <i class="bi bi-pencil-square me-1"></i>EDITAR AGORA
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var modalEl = document.getElementById('cadastroIncompletoModal');
        var modalInstance = modalEl ? new bootstrap.Modal(modalEl) : null;

        // Delegated handler for view-planilha buttons (works for dynamically loaded rows)
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('.btn-view-planilha');
            if (!btn) return;
            var ok = btn.getAttribute('data-cadastro-ok') === '1';
            if (!ok) {
                e.preventDefault();
                if (modalInstance && modalEl) {
                    var editBtn = modalEl.querySelector('.btn-edit-agora');
                    if (editBtn) {
                        editBtn.setAttribute('href', btn.getAttribute('data-edit-url'));
                    }
                    modalInstance.show();
                }
            }
        });

        // Search input (not used for live search now)
        var input = document.getElementById('busca');
        var timeout = null;

        function doSearch(q, page) {
            page = page || 1;
            var url = window.location.pathname + '?ajax=1&busca=' + encodeURIComponent(q) + '&pagina=' +
                encodeURIComponent(page) + '&limite=' + encodeURIComponent(<?php echo $limite; ?>);
            fetch(url, {
                    credentials: 'same-origin'
                })
                .then(function(res) {
                    return res.json();
                })
                .then(function(data) {
                    var tbody = document.getElementById('comunsTbody');
                    var countEl = document.getElementById('comumCount');
                    var badge = document.getElementById('comumBadge');
                    if (tbody) tbody.innerHTML = data.rows;
                    if (countEl) countEl.textContent = (typeof data.count_all !== 'undefined' ? data.count_all :
                        data.count) + ' COMUM(NS) ENCONTRADA(S)';
                    var rowsCount = (typeof data.rows_count !== 'undefined') ? data.rows_count : (tbody ? tbody
                        .querySelectorAll('tr').length : 0);
                    if (badge) badge.textContent = rowsCount + ' ITENS';
                    // rebuild pagination
                    var pagination = document.getElementById('comumPagination');
                    if (pagination && data.total_pages) {
                        var page = data.page || 1;
                        var total = data.total_pages;
                        var ini = Math.max(1, page - 2);
                        var fim = Math.min(total, page + 2);
                        var html = '<ul class="pagination pagination-sm justify-content-center mb-0">';
                        if (page > 1) html +=
                            '<li class="page-item"><a class="page-link" href="#" data-page="' + (page - 1) +
                            '">&laquo;</a></li>';
                        for (var i = ini; i <= fim; i++) {
                            html += '<li class="page-item ' + (i === page ? 'active' : '') +
                                '"><a class="page-link" href="#" data-page="' + i + '">' + i + '</a></li>';
                        }
                        if (page < total) html +=
                            '<li class="page-item"><a class="page-link" href="#" data-page="' + (page + 1) +
                            '">&raquo;</a></li>';
                        html += '</ul>';
                        pagination.innerHTML = html;
                    }
                })
                .catch(function(err) {
                    console.error('Erro na busca AJAX:', err);
                });
        }

        // (No live search) the form will submit normally when the user clicks Buscar.

        // Pagination click handling (delegated)
        var pagination = document.getElementById('comumPagination');
        if (pagination) {
            pagination.addEventListener('click', function(e) {
                var a = e.target.closest('a[data-page]');
                if (!a) return;
                e.preventDefault();
                var page = parseInt(a.getAttribute('data-page'), 10) || 1;
                currentPage = page;
                doSearch(input ? input.value.trim() : '', currentPage);
            });
        }

        // current page variable
        var currentPage = <?php echo $pagina; ?>;
    });
</script>