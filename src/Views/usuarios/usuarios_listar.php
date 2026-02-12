<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';



include __DIR__ . '/../../../app/controllers/read/UsuarioListController.php';

$pageTitle = 'USUÁRIOS';
$backUrl = '../../../index.php';

$qsArr = [];
if (!empty($filtroNome)) {
    $qsArr['busca'] = $filtroNome;
}
if ($filtroStatus !== '') {
    $qsArr['status'] = $filtroStatus;
}
if (!empty($pagina) && $pagina > 1) {
    $qsArr['pagina'] = $pagina;
}
$qs = http_build_query($qsArr);
$createHref = './usuario_criar.php' . ($qs ? ('?' . $qs) : '');
$headerActions = '<a href="' . $createHref . '" class="btn-header-action" title="NOVO USUÁRIO"><i class="bi bi-plus-lg"></i></a>';


ob_start();
?>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        USUÁRIO CADASTRADO COM SUCESSO!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['updated'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        USUÁRIO ATUALIZADO COM SUCESSO!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($erro)): ?>
    <div class="alert alert-danger">
        <?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?>
    </div>
<?php endif; ?>

<!-- Filtros de Pesquisa -->
<div class="card mb-3">
    <div class="card-header">
        <i class="bi bi-search me-2"></i>PESQUISAR
    </div>
    <div class="card-body">
        <form method="get" aria-label="Formulário de busca">
            <input type="hidden" name="pagina" value="1">
            <div class="mb-3">
                <label for="filtroNome" class="form-label">
                    <i class="bi bi-person me-1"></i>
                    BUSCAR POR NOME OU E-MAIL
                </label>
                <input type="text" class="form-control" id="filtroNome" name="busca" value="<?php echo htmlspecialchars($filtroNome ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="mb-2">
                <label for="filtroSTATUS" class="form-label">
                    <i class="bi bi-funnel me-1"></i>
                    STATUS
                </label>
                <select class="form-select" id="filtroSTATUS" name="status">
                    <option value="" <?php echo ($filtroStatus === '') ? ' selected' : ''; ?>>TODOS</option>
                    <option value="1" <?php echo ($filtroStatus === '1') ? ' selected' : ''; ?>>ATIVOS</option>
                    <option value="0" <?php echo ($filtroStatus === '0') ? ' selected' : ''; ?>>INATIVOS</option>
                </select>
            </div>
            <div class="mb-3">
                <button type="submit" id="btnBUSCARUsuarios" class="btn btn-primary w-100 mt-2"><i class="bi bi-search me-2"></i>BUSCAR</button>
            </div>
        </form>
    </div>
    <div id="usuarioCount" class="card-footer text-muted small">
        <?php echo (int)$total_registros_all; ?> <?php echo htmlspecialchars(to_uppercase('USUÁRIO(S) ENCONTRADO(S)'), ENT_QUOTES, 'UTF-8'); ?>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-people me-2"></i>
            LISTA DE USUÁRIOS
        </span>
        <span class="badge bg-white text-dark"><?php echo count($usuarios); ?> ITENS</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($usuarios)): ?>
            <div class="p-4 text-center text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                NENHUM USUÁRIO CADASTRADO
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="tabelaUsuarios">
                    <thead>
                        <tr>
                            <th>USUÁRIO</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                            <?php
                            $telefone_limpo = preg_replace('/\D/', '', $usuario['telefone'] ?? '');
                            $wa_link = ($telefone_limpo && (strlen($telefone_limpo) === 10 || strlen($telefone_limpo) === 11))
                                ? ('https://wa.me/55' . $telefone_limpo)
                                : null;
                            $loggedId = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 0;
                            $is_self = $loggedId === (int)$usuario['id'];
                            ?>
                            <tr data-nome="<?php echo strtolower(htmlspecialchars($usuario['nome'])); ?>"
                                data-email="<?php echo strtolower(htmlspecialchars($usuario['email'])); ?>"
                                data-STATUS="<?php echo $usuario['ativo']; ?>">
                                <td>
                                    <div class="d-flex flex-column">
                                        <div class="fw-semibold text-wrap"><?php echo htmlspecialchars(to_uppercase($usuario['nome'])); ?></div>
                                        <div class="small text-muted text-wrap"><?php echo htmlspecialchars(to_uppercase($usuario['email']), ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div class="mt-2 d-flex gap-1 flex-wrap justify-content-end">
                                            <a href="./usuario_ver.php?id=<?php echo $usuario['id']; ?><?php echo ($qs ? '&' . $qs : ''); ?>"
                                                class="btn btn-sm btn-outline-secondary" title="VISUALIZAR">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <?php if ($is_self): ?>
                                                <a href="./usuario_editar.php?id=<?php echo $usuario['id']; ?><?php echo ($qs ? '&' . $qs : ''); ?>"
                                                    class="btn btn-sm btn-outline-primary" title="EDITAR MEU PERFIL">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($wa_link): ?>
                                                <a href="<?php echo $wa_link; ?>" target="_blank" rel="noopener"
                                                    class="btn btn-sm btn-outline-success" title="WHATSAPP">
                                                    <i class="bi bi-whatsapp"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
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

<?php if ($total_paginas > 1): ?>
    <nav class="mt-3" aria-label="PAGINAÇÁO USUÁRIOS">
        <ul class="pagination pagination-sm justify-content-center mb-0">
            <?php if ($pagina > 1): ?>
                <li class="page-item"><a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])); ?>">&laquo;</a></li>
            <?php endif; ?>
            <?php $ini = max(1, $pagina - 2);
            $fim = min($total_paginas, $pagina + 2);
            for ($i = $ini; $i <= $fim; $i++): ?>
                <li class="page-item <?php echo $i == $pagina ? 'active' : ''; ?>">
                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $i])); ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
            <?php if ($pagina < $total_paginas): ?>
                <li class="page-item"><a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])); ?>">&raquo;</a></li>
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
        const container = document.querySelector('.app-content .container-fluid') || document.querySelector('.app-content') || document.body;
        container.insertBefore(el, container.firstChild);
    }
    document.addEventListener('DOMContentLoaded', function() {
        window.excluirUsuario = function(id, nome) {
            if (!confirm('TEM CERTEZA QUE DESEJA EXCLUIR O USUÁRIO "' + nome + '"?')) {
                return;
            }

            fetch('../../../app/controllers/delete/UsuarioDeleteController.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'id=' + id
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showFlash('success', data.message);
                        setTimeout(function() {
                            location.reload();
                        }, 900);
                    } else {
                        showFlash('danger', data.message);
                    }
                })
                .catch(error => {
                    showFlash('danger', 'ERRO AO EXCLUIR USUÁRIO');
                    console.error(error);
                });
        }
    });
</script>

<?php
$contentHtml = ob_get_clean();
$tempFile = __DIR__ . '/../../../temp_read_usuario_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app_wrapper.php';
unlink($tempFile);
?>