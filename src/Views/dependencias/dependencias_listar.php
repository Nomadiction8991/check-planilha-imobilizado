<?php

declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/Helpers/BootstrapLoader.php';


try {
} catch (Throwable $e) {
    $dependencias = [];
    $total_registros = 0;
    $total_paginas = 0;
    $pagina = 1;
    error_log('Erro na view dependencias: ' . $e->getMessage());
}

$pageTitle = 'Dependencias';
$backUrl = base_url('/');

$qsArr = [];
if (!empty($busca)) {
    $qsArr['busca'] = $busca;
}
if (!empty($pagina) && $pagina > 1) {
    $qsArr['pagina'] = $pagina;
}
$qs = http_build_query($qsArr);
$createHref = '/dependencias/criar' . ($qs ? ('?' . $qs) : '');
$headerActions = '<a href="' . $createHref . '" class="btn-header-action" title="Nova Dependencia"><i class="bi bi-plus-lg"></i></a>';


if (!function_exists('dep_corrigir_encoding')) {
    function dep_corrigir_encoding($texto)
    {
        if ($texto === null) return '';
        $texto = trim((string)$texto);
        if ($texto === '') return '';
        $enc = mb_detect_encoding($texto, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'], true);
        if ($enc && $enc !== 'UTF-8') {
            $texto = mb_convert_encoding($texto, 'UTF-8', $enc);
        }
        if (preg_match('/Áƒ|Á‚|ï¿½/', $texto)) {
            $t1 = @utf8_decode($texto);
            if ($t1 !== false && mb_detect_encoding($t1, 'UTF-8', true)) {
                $texto = $t1;
            } else {
                $t2 = @utf8_encode($texto);
                if ($t2 !== false && mb_detect_encoding($t2, 'UTF-8', true)) {
                    $texto = $t2;
                }
            }
        }
        return $texto;
    }
}

ob_start();
?>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        Operacao realizada com sucesso!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-header">
        <i class="bi bi-search me-2"></i> PESQUISAR DEPENDÊNCIA
    </div>
    <div class="card-body">
        <form method="get">
            <input type="hidden" name="pagina" value="1">
            <div class="mb-3">
                <label for="busca_dep" class="form-label"><i class="bi bi-list me-1"></i> <?php echo htmlspecialchars(to_uppercase('Descrição'), ENT_QUOTES, 'UTF-8'); ?></label>
                <input id="busca_dep" name="busca" type="text" class="form-control" placeholder="<?php echo htmlspecialchars(to_uppercase('Digite parte da descrição'), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($busca ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="mb-3">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-2"></i>BUSCAR</button>
            </div>
        </form>
    </div>
    <div class="card-footer text-muted small">
        <?php echo (int)$total_registros_all; ?> <?php echo htmlspecialchars(to_uppercase('dependência(s) encontrada(s)'), ENT_QUOTES, 'UTF-8'); ?>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-list me-2"></i>
            <?php echo htmlspecialchars(to_uppercase('Dependências'), ENT_QUOTES, 'UTF-8'); ?>
        </span>
        <span class="badge bg-white text-dark"><?php echo count($dependencias); ?> ITENS (PÁG. <?php echo $pagina; ?>/<?php echo $total_paginas ?: 1; ?>)</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($dependencias)): ?>
            <div class="p-4 text-center text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                Nenhuma dependencia cadastrada
            </div>
        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th><?php echo htmlspecialchars(to_uppercase('Descrição'), ENT_QUOTES, 'UTF-8'); ?></th>
                            <th><?php echo htmlspecialchars(to_uppercase('Ações'), ENT_QUOTES, 'UTF-8'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dependencias as $dependencia): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(to_uppercase(dep_corrigir_encoding($dependencia['descricao'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="/dependencias/editar?id=<?php echo $dependencia['id']; ?><?php echo ($qs ? '&' . $qs : ''); ?>"
                                            class="btn btn-sm btn-outline-primary" title="EDITAR">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                            onclick="deletarDependencia(<?php echo $dependencia['id']; ?>)"
                                            title="Excluir">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Paginacao -->
<?php if ($total_paginas > 1): ?>
    <nav aria-label="Paginacao" class="mt-3">
        <ul class="pagination justify-content-center">
            <?php if ($pagina > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?pagina=<?php echo $pagina - 1; ?>">Anterior</a>
                </li>
            <?php endif; ?>

            <?php for ($i = max(1, $pagina - 2); $i <= min($total_paginas, $pagina + 2); $i++): ?>
                <li class="page-item <?php echo $i === $pagina ? 'active' : ''; ?>">
                    <a class="page-link" href="?pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>

            <?php if ($pagina < $total_paginas): ?>
                <li class="page-item">
                    <a class="page-link" href="?pagina=<?php echo $pagina + 1; ?>">Proximo</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
<?php endif; ?>

<script>
    function showFlash(type, message) {
        const el = document.createElement('div');
        el.className = 'alert alert-' + type + ' alert-dismissible fade show';
        el.setAttribute('role', 'alert');
        const icon = (type === 'success') ? 'check-circle' : 'exclamation-triangle';
        el.innerHTML = '<i class="bi bi-' + icon + ' me-2"></i><span></span><button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        el.querySelector('span').textContent = message;
        // Insert at top of main content area if available
        const container = document.querySelector('.app-content .container-fluid') || document.querySelector('.app-content') || document.body;
        container.insertBefore(el, container.firstChild);
    }

    function deletarDependencia(id) {
        // Open confirm modal and attach id to confirm button
        const modal = document.getElementById('confirmModalDependencia');
        if (!modal) {
            // fallback to prompt if modal not available
            if (!confirm('Tem certeza que deseja excluir esta dependência?')) return;
            performDelete(id);
            return;
        }
        const confirmBtn = modal.querySelector('.confirm-delete');
        modal.querySelector('.modal-body span').textContent = 'Deseja realmente excluir esta dependência?';
        confirmBtn.setAttribute('data-delete-id', id);
        // show modal using Bootstrap API if available
        if (typeof bootstrap !== 'undefined') {
            const bs = new bootstrap.Modal(modal);
            bs.show();
        } else {
            modal.style.display = 'block';
            modal.classList.add('show');
        }
    }

    function performDelete(id) {
        fetch('/dependencias/deletar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=' + id
            })
            .then(response => response.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        showFlash('success', data.message);
                        // Give user a moment to see the flash before reloading
                        setTimeout(function() {
                            location.reload();
                        }, 900);
                    } else {
                        showFlash('danger', data.message);
                    }
                } catch (e) {
                    console.error('Invalid JSON response:', text);
                    showFlash('danger', 'Erro na requisição: resposta inválida do servidor. Verifique o console para detalhes.');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                showFlash('danger', 'Erro na requisição: ' + String(error));
            });
    }

    // Bind modal confirm button
    (function() {
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('confirmModalDependencia');
            if (!modal) return;
            modal.querySelector('.confirm-delete').addEventListener('click', function(e) {
                const id = this.getAttribute('data-delete-id');
                // hide modal
                if (typeof bootstrap !== 'undefined') {
                    bootstrap.Modal.getInstance(modal)?.hide();
                } else {
                    modal.classList.remove('show');
                    modal.style.display = 'none';
                }
                if (id) performDelete(id);
            });
        });
    })();
</script>

<!-- Confirm modal for deleting dependencia -->
<div class="modal fade" id="confirmModalDependencia" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body"><span></span></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger confirm-delete">Excluir</button>
            </div>
        </div>
    </div>
</div>

<?php
$contentHtml = ob_get_clean();
$tempFile = sys_get_temp_dir() . '/temp_read_dependencia_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app_wrapper.php';
unlink($tempFile);
?>