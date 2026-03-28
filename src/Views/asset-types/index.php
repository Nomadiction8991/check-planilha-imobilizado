<?php


use App\Helpers\{AlertHelper, PaginationHelper, ViewHelper};

$tipos        = $tipos        ?? [];
$total        = $total        ?? 0;
$pagina       = $pagina       ?? 1;
$totalPaginas = $totalPaginas ?? 1;
$busca        = $busca        ?? '';
$limite       = $limite       ?? 20;
?>

<?= AlertHelper::fromQuery() ?>

<div class="bg-white border border-neutral-200 mb-4" style="border-radius:2px">
    <div class="px-5 py-3 border-b border-neutral-200 bg-neutral-50 flex items-center gap-2">
        <i class="bi bi-search text-neutral-500" style="font-size:13px"></i>
        <span style="font-size:12px;font-weight:600;letter-spacing:0.06em;color:#525252">PESQUISAR TIPO DE BEM</span>
    </div>
    <div class="p-5">
        <form method="GET" action="/asset-types" class="flex gap-2">
            <input
                type="text"
                name="busca"
                id="busca"
                class="flex-1 px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black"
                style="border-radius:2px"
                value="<?= htmlspecialchars($busca, ENT_QUOTES, 'UTF-8') ?>"
                placeholder="Descrição">
            <?php if ($busca): ?>
                <a href="/asset-types"
                    style="display:inline-flex;align-items:center;padding:8px 12px;border:1px solid #d4d4d4;color:#525252;font-size:13px;text-decoration:none;border-radius:2px">
                    <i class="bi bi-x-lg"></i>
                </a>
            <?php endif; ?>
            <button type="submit"
                class="px-4 py-2 bg-black text-white text-sm font-medium hover:bg-neutral-900 transition flex items-center gap-2"
                style="border-radius:2px">
                <i class="bi bi-search"></i>Buscar
            </button>
        </form>
    </div>
</div>

<?= PaginationHelper::info($total, $pagina, $limite) ?>

<div class="bg-white border border-neutral-200 mb-4" style="border-radius:2px">
    <div class="px-5 py-3 border-b border-neutral-200 bg-neutral-50 flex items-center gap-2">
        <i class="bi bi-box-seam text-neutral-500" style="font-size:13px"></i>
        <span style="font-size:12px;font-weight:600;letter-spacing:0.06em;color:#525252">TIPOS DE BENS</span>
    </div>
    <div class="overflow-x-auto">
        <?php if (isset($erro)): ?>
            <div class="p-5" style="background:#fef2f2;border-bottom:1px solid #fecaca;color:#991b1b;font-size:13px">
                <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>
        <table class="w-full border-collapse" style="font-size:13px">
            <thead>
                <tr style="background:#fafafa;border-bottom:2px solid #000">
                    <th class="px-5 py-3 text-left" style="font-size:11px;font-weight:700;letter-spacing:0.08em;color:#000">DESCRIÇÃO</th>
                    <th class="px-5 py-3 text-center" style="font-size:11px;font-weight:700;letter-spacing:0.08em;color:#000;width:80px">AÇÕES</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tipos)): ?>
                    <tr>
                        <td colspan="2" class="text-center py-10" style="color:#a3a3a3;font-size:13px">
                            <i class="bi bi-inbox block mb-2" style="font-size:24px"></i>
                            Nenhum tipo de bem encontrado
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tipos as $tipo): ?>
                        <tr style="border-bottom:1px solid #e5e5e5" onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''">
                            <td class="px-5 py-3" style="color:#000;text-transform:uppercase">
                                <?= htmlspecialchars($tipo['descricao'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <a href="/asset-types/<?= (int)$tipo['id'] ?>/edit"
                                    style="display:inline-flex;align-items:center;padding:5px 10px;border:1px solid #000;color:#000;font-size:12px;text-decoration:none;border-radius:2px;transition:background 120ms"
                                    onmouseover="this.style.background='#000';this.style.color='#fff'"
                                    onmouseout="this.style.background='';this.style.color='#000'"
                                    title="Editar">
                                    <i class="bi bi-pencil" style="font-size:11px"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<a href="/asset-types/create"
    class="flex items-center justify-center gap-2 w-full px-4 py-2 bg-black text-white text-sm font-medium hover:bg-neutral-900 transition mb-4"
    style="border-radius:2px;text-decoration:none">
    <i class="bi bi-plus-circle"></i>Novo Tipo de Bem
</a>

<?= PaginationHelper::render($pagina, $totalPaginas, '/asset-types', ['busca' => $busca]) ?>
