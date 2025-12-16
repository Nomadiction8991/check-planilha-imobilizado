<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/bootstrap.php';


if (!isAdmin()) {
    header('Location: ../../../index.php');
    exit;
}

// Incluir lÃ³gica de leitura (preparada em CRUD)
try {
    include __DIR__ . '/../../../app/controllers/read/DependenciaListController.php';
} catch (Throwable $e) {
    $dependencias = [];
    $total_registros = 0;
    $total_paginas = 0;
    $pagina = 1;
    error_log('Erro na view dependencias: ' . $e->getMessage());
}

$pageTitle = 'Dependencias';
$backUrl = '../../../index.php';
$headerActions = '<a href="./dependencia_criar.php" class="btn-header-action" title="Nova Dependencia"><i class="bi bi-plus-lg"></i></a>';


if (!function_exists('dep_corrigir_encoding')) {
    function dep_corrigir_encoding($texto) {
        if ($texto === null) return '';
        $texto = trim((string)$texto);
        if ($texto === '') return '';
        $enc = mb_detect_encoding($texto, ['UTF-8','ISO-8859-1','Windows-1252','ASCII'], true);
        if ($enc && $enc !== 'UTF-8') {
            $texto = mb_convert_encoding($texto, 'UTF-8', $enc);
        }
        if (preg_match('/Ãƒ|Ã‚|ï¿½/', $texto)) {
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
                <label for="busca_dep" class="form-label"><i class="bi bi-list me-1"></i> CÓDIGO OU DESCRIÇÃO</label>
                <input id="busca_dep" name="busca" type="text" class="form-control" value="<?php echo htmlspecialchars($busca ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="mb-3">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-2"></i>BUSCAR</button>
            </div>
        </form>
    </div>
    <div class="card-footer text-muted small">
        <?php echo (int)$total_registros; ?> <?php echo htmlspecialchars(to_uppercase('dependência(s) encontrada(s)'), ENT_QUOTES, 'UTF-8'); ?>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-list me-2"></i>
            <?php echo htmlspecialchars(to_uppercase('Lista de Dependências'), ENT_QUOTES, 'UTF-8'); ?>
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
                                        <a href="./dependencia_editar.php?id=<?php echo $dependencia['id']; ?>"
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
function deletarDependencia(id) {
    if (confirm('Tem certeza que deseja excluir esta dependencia?')) {
        fetch('../../../app/controllers/delete/DependenciaDeleteController.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=' + id
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Erro: ' + data.message);
            }
        })
        .catch(error => {
            alert('Erro na requisiÃ§Ã£o: ' + error);
        });
    }
}
</script>

<?php
$contentHtml = ob_get_clean();
$tempFile = sys_get_temp_dir() . '/temp_read_dependencia_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app_wrapper.php';
unlink($tempFile);
?>


