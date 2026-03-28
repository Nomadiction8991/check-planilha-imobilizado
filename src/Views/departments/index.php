<?php

$appConfig = require dirname(__DIR__, 3) . '/config/app.php';
$projectRoot = $appConfig['project_root'];
require_once $projectRoot . '/src/Helpers/BootstrapLoader.php';


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
$createHref = '/departments/create' . ($qs ? ('?' . $qs) : '');
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
    <div style="background:#f0fdf4;border:1px solid #86efac;color:#166534;border-radius:2px;padding:10px 14px;margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;font-size:13px" role="alert">
        <span>Operação realizada com sucesso!</span>
        <button type="button" style="background:none;border:none;cursor:pointer;color:inherit;font-size:16px;line-height:1" onclick="this.parentElement.remove()"><i class="bi bi-x"></i></button>
    </div>
<?php endif; ?>

<div class="bg-white border border-neutral-200 mb-4" style="border-radius:2px">
    <div class="px-5 py-3 border-b border-neutral-200 bg-neutral-50 flex items-center gap-2">
        <i class="bi bi-search text-neutral-500" style="font-size:13px"></i>
        <span style="font-size:12px;font-weight:600;letter-spacing:0.06em;color:#525252">PESQUISAR DEPENDÊNCIA</span>
    </div>
    <div class="p-5">
        <form method="get" class="flex gap-2">
            <input type="hidden" name="pagina" value="1">
            <input id="busca_dep" name="busca" type="text"
                class="flex-1 px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black"
                style="border-radius:2px"
                placeholder="Descrição da dependência"
                value="<?php echo htmlspecialchars($busca ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            <button type="submit"
                class="px-4 py-2 bg-black text-white text-sm font-medium hover:bg-neutral-900 transition flex items-center gap-2"
                style="border-radius:2px">
                <i class="bi bi-search"></i>Buscar
            </button>
        </form>
    </div>
    <div class="px-5 py-2 border-t border-neutral-100 bg-neutral-50" style="font-size:12px;color:#808080">
        <?php echo (int)$total_registros_all; ?> dependência(s) encontrada(s)
    </div>
</div>

<div class="bg-white border border-neutral-200" style="border-radius:2px">
    <div class="px-5 py-3 border-b border-neutral-200 bg-neutral-50 flex items-center justify-between">
        <span style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:600;letter-spacing:0.06em;color:#525252">
            <i class="bi bi-link-45deg" style="font-size:13px"></i>DEPENDÊNCIAS
        </span>
        <span style="font-size:11px;color:#808080;background:#fff;border:1px solid #e5e5e5;padding:2px 8px;border-radius:2px">
            <?php echo count($dependencias); ?> itens · pág. <?php echo $pagina; ?>/<?php echo $total_paginas ?: 1; ?>
        </span>
    </div>
    <div class="overflow-x-auto">
        <?php if (empty($dependencias)): ?>
            <div class="py-10 text-center" style="color:#a3a3a3;font-size:13px">
                <i class="bi bi-inbox block mb-2" style="font-size:24px"></i>
                Nenhuma dependência cadastrada
            </div>
        <?php else: ?>
            <table class="w-full border-collapse" style="font-size:13px">
                <thead>
                    <tr style="background:#fafafa;border-bottom:2px solid #000">
                        <th class="px-5 py-3 text-left" style="font-size:11px;font-weight:700;letter-spacing:0.08em;color:#000">DESCRIÇÃO</th>
                        <th class="px-5 py-3 text-left" style="font-size:11px;font-weight:700;letter-spacing:0.08em;color:#000;width:100px">AÇÕES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dependencias as $dependencia): ?>
                        <tr style="border-bottom:1px solid #e5e5e5" onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''">
                            <td class="px-5 py-3" style="color:#000;text-transform:uppercase"><?php echo htmlspecialchars(dep_corrigir_encoding($dependencia['descricao'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="px-5 py-3">
                                <div style="display:flex;gap:6px">
                                    <a href="/departments/edit?id=<?php echo $dependencia['id']; ?><?php echo $qs ? '&' . $qs : ''; ?>"
                                        style="display:inline-flex;align-items:center;padding:5px 10px;border:1px solid #000;color:#000;font-size:12px;text-decoration:none;border-radius:2px;transition:background 120ms"
                                        onmouseover="this.style.background='#000';this.style.color='#fff'"
                                        onmouseout="this.style.background='';this.style.color='#000'"
                                        title="Editar">
                                        <i class="bi bi-pencil" style="font-size:11px"></i>
                                    </a>
                                    <button type="button"
                                        style="display:inline-flex;align-items:center;padding:5px 10px;border:1px solid #991b1b;color:#991b1b;font-size:12px;background:#fff;cursor:pointer;border-radius:2px;transition:background 120ms"
                                        onmouseover="this.style.background='#991b1b';this.style.color='#fff'"
                                        onmouseout="this.style.background='#fff';this.style.color='#991b1b'"
                                        onclick="deletarDependencia(<?php echo $dependencia['id']; ?>)"
                                        title="Excluir">
                                        <i class="bi bi-trash" style="font-size:11px"></i>
                                    </button>
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
    '/departments',
    ['busca' => $busca ?? '']
);
?>

<script src="/assets/js/dependencias/index.js"></script>

<!-- Modal de confirmação de exclusão -->
<div style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:50;align-items:center;justify-content:center" id="confirmModalDependencia">
    <div style="background:#fff;border:1px solid #e5e5e5;max-width:440px;width:90%;border-radius:2px">
        <div style="padding:14px 20px;border-bottom:1px solid #e5e5e5">
            <strong style="font-size:14px">Confirmação</strong>
        </div>
        <div style="padding:16px 20px;font-size:14px;color:#262626"><span></span></div>
        <div style="padding:12px 20px;border-top:1px solid #e5e5e5;background:#fafafa;display:flex;gap:8px;justify-content:flex-end">
            <button type="button"
                style="padding:7px 14px;border:1px solid #d4d4d4;background:#fff;font-size:13px;cursor:pointer;border-radius:2px"
                onclick="document.getElementById('confirmModalDependencia').style.display='none'">
                Cancelar
            </button>
            <button type="button"
                style="padding:7px 14px;background:#991b1b;color:#fff;border:1px solid #991b1b;font-size:13px;cursor:pointer;border-radius:2px"
                class="confirm-delete">
                Excluir
            </button>
        </div>
    </div>
</div>

<?php
$contentHtml = ob_get_clean();
include $projectRoot . '/src/Views/layouts/app.php';
?>