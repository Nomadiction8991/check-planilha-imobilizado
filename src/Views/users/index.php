<?php


use App\Helpers\{AlertHelper, PaginationHelper, ViewHelper};

$usuarios = $usuarios ?? [];
$total = $total ?? 0;
$pagina = $pagina ?? 1;
$totalPaginas = $totalPaginas ?? 1;
$busca = $busca ?? '';
$limite = $limite ?? 10;
?>

<?= AlertHelper::fromQuery() ?>

<div class="bg-white border border-neutral-200 mb-4" style="border-radius:2px">
    <div class="px-5 py-3 border-b border-neutral-200 bg-neutral-50 flex items-center gap-2">
        <i class="bi bi-funnel text-neutral-500" style="font-size:13px"></i>
        <span style="font-size:12px;font-weight:600;letter-spacing:0.06em;color:#525252">PESQUISAR</span>
    </div>
    <div class="p-5">
        <form method="GET" class="flex gap-2">
            <input
                type="text"
                name="busca"
                id="busca"
                class="flex-1 px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black"
                style="border-radius:2px"
                value="<?= ViewHelper::e($busca) ?>"
                placeholder="Nome do usuário">
            <?php if ($busca): ?>
                <button type="button"
                    class="px-3 py-2 border border-neutral-300 text-neutral-600 hover:border-black hover:text-black transition text-sm"
                    style="border-radius:2px;background:#fff"
                    onclick="document.getElementById('busca').value=''; this.form.submit()">
                    <i class="bi bi-x-lg"></i>
                </button>
            <?php endif; ?>
            <button type="submit"
                class="px-4 py-2 bg-black text-white text-sm font-medium hover:bg-neutral-900 transition flex items-center gap-2"
                style="border-radius:2px">
                <i class="bi bi-search"></i>Filtrar
            </button>
        </form>
    </div>
</div>

<?= PaginationHelper::info($total, $pagina, $limite) ?>

<div class="bg-white border border-neutral-200" style="border-radius:2px">
    <table class="w-full border-collapse" style="font-size:13px">
        <thead>
            <tr style="background:#fafafa;border-bottom:2px solid #000">
                <th class="px-5 py-3 text-left" style="font-size:11px;font-weight:700;letter-spacing:0.08em;color:#000">NOME</th>
                <th class="px-5 py-3 text-center" style="font-size:11px;font-weight:700;letter-spacing:0.08em;color:#000;width:80px">AÇÕES</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($usuarios)): ?>
                <tr>
                    <td colspan="2" class="text-center py-10" style="color:#a3a3a3;font-size:13px">
                        <i class="bi bi-people block mb-2" style="font-size:24px"></i>
                        Nenhum usuário encontrado
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($usuarios as $usuario): ?>
                    <?php $editUrl = ViewHelper::urlComQuery('/users/edit', ['id' => $usuario['id']]); ?>
                    <tr style="border-bottom:1px solid #e5e5e5" onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''">
                        <td class="px-5 py-3" style="font-weight:500;color:#000">
                            <?= ViewHelper::e($usuario['nome'] ?? '') ?>
                        </td>
                        <td class="px-5 py-3 text-center">
                            <a href="<?= ViewHelper::e($editUrl) ?>"
                                style="display:inline-flex;align-items:center;gap:4px;padding:5px 10px;border:1px solid #000;color:#000;font-size:12px;text-decoration:none;border-radius:2px;transition:background 120ms"
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

<?= PaginationHelper::render($pagina, $totalPaginas, '/users', ['busca' => $busca]) ?>
