<?php


$appConfig = require dirname(__DIR__, 3) . '/config/app.php';
$projectRoot = $appConfig['project_root'];
require_once $projectRoot . '/src/Helpers/BootstrapLoader.php';




$pageTitle = 'USUÁRIOS';
$backUrl = base_url('/');

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
$createHref = '/users/create' . ($qs ? ('?' . $qs) : '');
ob_start();
?>

<?php if (isset($_GET['success'])): ?>
    <div class="p-4 mb-4 flex items-start gap-3" style="background:#fafafa;border:1px solid #d4d4d4;color:#171717;border-radius:2px">
        <i class="bi bi-check-circle flex-shrink-0 mt-0.5"></i>
        <p class="flex-1">USUÁRIO CADASTRADO COM SUCESSO!</p>
        <button type="button" class="flex-shrink-0 text-xl leading-none hover:opacity-70" onclick="this.parentElement.remove();">&times;</button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['updated'])): ?>
    <div class="p-4 mb-4 flex items-start gap-3" style="background:#fafafa;border:1px solid #d4d4d4;color:#171717;border-radius:2px">
        <i class="bi bi-check-circle flex-shrink-0 mt-0.5"></i>
        <p class="flex-1">USUÁRIO ATUALIZADO COM SUCESSO!</p>
        <button type="button" class="flex-shrink-0 text-xl leading-none hover:opacity-70" onclick="this.parentElement.remove();">&times;</button>
    </div>
<?php endif; ?>

<?php if (!empty($erro)): ?>
    <div class="p-4 mb-4 flex items-start gap-3" style="background:#fafafa;border:1px solid #000;color:#171717;border-radius:2px">
        <i class="bi bi-info-circle flex-shrink-0 mt-0.5"></i>
        <p><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
<?php endif; ?>

<!-- Filtros de Pesquisa -->
<div class="bg-white border border-slate-200 mb-4" style="border-radius:2px">
    <div class="bg-slate-50 px-6 py-3 border-b border-slate-200 flex items-center gap-2">
        <i class="bi bi-search text-slate-600"></i>
        <span class="font-semibold text-slate-700">PESQUISAR</span>
    </div>
    <div class="p-6 space-y-4">
        <form method="get">
            <input type="hidden" name="pagina" value="1">
            <div>
                <label for="filtroNome" class="block text-sm font-medium text-slate-700 mb-2 flex items-center gap-1">
                    <i class="bi bi-person"></i>BUSCAR POR NOME OU E-MAIL
                </label>
                <input type="text" class="w-full px-4 py-2 border border-slate-300 focus:outline-none focus:border-black" style="border-radius:2px" id="filtroNome" name="busca" value="<?php echo htmlspecialchars($filtroNome ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div>
                <label for="filtroSTATUS" class="block text-sm font-medium text-slate-700 mb-2 flex items-center gap-1">
                    <i class="bi bi-funnel"></i>STATUS
                </label>
                <select class="w-full px-4 py-2 border border-slate-300 focus:outline-none focus:border-black" style="border-radius:2px" id="filtroSTATUS" name="status">
                    <option value="" <?php echo ($filtroStatus === '') ? ' selected' : ''; ?>>TODOS</option>
                    <option value="1" <?php echo ($filtroStatus === '1') ? ' selected' : ''; ?>>ATIVOS</option>
                    <option value="0" <?php echo ($filtroStatus === '0') ? ' selected' : ''; ?>>INATIVOS</option>
                </select>
            </div>
            <button type="submit" id="btnBUSCARUsuarios" class="w-full px-4 py-2 bg-black hover:bg-neutral-800 text-white font-semibold transition flex items-center justify-center gap-2" style="border-radius:2px"><i class="bi bi-search"></i>BUSCAR</button>
        </form>
    </div>
    <div id="usuarioCount" class="px-6 py-3 bg-slate-50 border-t border-slate-200 text-sm text-slate-600">
        <?php echo (int)$total_registros_all; ?> <?php echo htmlspecialchars(to_uppercase('USUÁRIO(S) ENCONTRADO(S)'), ENT_QUOTES, 'UTF-8'); ?>
    </div>
</div>

<div class="bg-white border border-slate-200" style="border-radius:2px">
    <div class="bg-slate-50 px-6 py-3 border-b border-slate-200 flex items-center justify-between">
        <span class="flex items-center gap-2 font-semibold text-slate-700">
            <i class="bi bi-people"></i>LISTA DE USUÁRIOS
        </span>
        <span class="inline-block px-3 py-1 bg-slate-200 text-slate-800 text-sm" style="border-radius:2px"><?php echo count($usuarios); ?> ITENS · PÁG. <?php echo (int)$pagina; ?>/<?php echo (int)($total_paginas ?: 1); ?></span>
    </div>
    <div class="overflow-x-auto">
        <?php if (empty($usuarios)): ?>
            <div class="p-8 text-center text-slate-500">
                <i class="bi bi-inbox text-3xl mb-2 block"></i>
                NENHUM USUÁRIO CADASTRADO
            </div>
        <?php else: ?>
            <table class="w-full border-collapse" id="tabelaUsuarios">
                <thead>
                    <tr class="bg-slate-100 border-b border-slate-300">
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">USUÁRIO</th>
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
                            data-STATUS="<?php echo $usuario['ativo']; ?>"
                            class="border-b border-slate-200 hover:bg-slate-50 transition">
                            <td class="px-6 py-4">
                                <div class="space-y-2">
                                    <div class="font-semibold text-slate-900 break-words"><?php echo htmlspecialchars(to_uppercase($usuario['nome'])); ?></div>
                                    <div class="text-sm text-slate-600 break-words"><?php echo htmlspecialchars(to_uppercase($usuario['email']), ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="flex gap-2 flex-wrap mt-3">
                                        <a href="/users/show?id=<?php echo $usuario['id']; ?><?php echo ($qs ? '&' . $qs : ''); ?>"
                                            class="inline-flex items-center gap-2 px-3 py-2 border border-neutral-300 hover:bg-neutral-50 text-sm transition" style="border-radius:2px;color:#171717" title="VISUALIZAR">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if ($is_self): ?>
                                            <a href="/users/edit?id=<?php echo $usuario['id']; ?><?php echo ($qs ? '&' . $qs : ''); ?>"
                                                class="inline-flex items-center gap-2 px-3 py-2 bg-black hover:bg-neutral-800 text-white text-sm transition" style="border-radius:2px" title="EDITAR MEU PERFIL">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($wa_link): ?>
                                            <a href="<?php echo $wa_link; ?>" target="_blank" rel="noopener"
                                                class="inline-flex items-center gap-2 px-3 py-2 border border-black hover:bg-neutral-100 text-sm transition" style="border-radius:2px;color:#171717" title="WHATSAPP">
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
        <?php endif; ?>
    </div>
</div>

<?php
echo \App\Helpers\PaginationHelper::render(
    (int) $pagina,
    (int) $total_paginas,
    '/users',
    [
        'busca' => $filtroNome ?? '',
        'status' => $filtroStatus ?? '',
    ]
);
?>

<script src="/assets/js/usuarios/list.js"></script>

<?php
$contentHtml = ob_get_clean();
include $projectRoot . '/src/Views/layouts/app.php';
?>
