<?php

use App\Helpers\{AlertHelper, PaginationHelper};

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

<?= AlertHelper::fromQuery() ?>

<?php
$filterCardOptions = [
    'titulo'       => 'PESQUISAR DEPENDÊNCIA',
    'icone'        => 'bi-search',
    'campos'       => [
        [
            'tipo'        => 'text',
            'name'        => 'busca',
            'label'       => 'Descrição',
            'value'       => $busca ?? '',
            'placeholder' => 'Descrição da dependência',
        ],
    ],
    'total_label'  => ((int)($total_registros_all ?? 0)) . ' dependência(s) encontrada(s)',
    'hidden'       => ['pagina' => 1],
];
include $projectRoot . '/src/Views/layouts/partials/filter-card.php';
?>

<?php
// Gerar linhas da tabela
ob_start();
if (!empty($dependencias)):
    foreach ($dependencias as $dependencia):
        ?>
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
        <?php
    endforeach;
endif;
$linhasHtml = ob_get_clean();
?>

<?php
$paginacaoHtml = PaginationHelper::render(
    (int) $pagina,
    (int) $total_paginas,
    '/departments',
    ['busca' => $busca ?? '']
);

$tableOptions = [
    'icone'          => 'bi-link-45deg',
    'titulo'         => 'DEPENDÊNCIAS',
    'total'          => count($dependencias ?? []),
    'pagina'         => $pagina ?? 1,
    'total_paginas'  => $total_paginas ?? 1,
    'colunas'        => ['DESCRIÇÃO', 'AÇÕES'],
    'empty_msg'      => 'Nenhuma dependência cadastrada',
    'linhas_html'    => $linhasHtml,
    'paginacao_html' => $paginacaoHtml,
];
include $projectRoot . '/src/Views/layouts/partials/table-wrapper.php';
?>

<script src="/assets/js/departments/index.js"></script>

<?php
$confirmModalOptions = [
    'id'          => 'confirmModalDependencia',
    'titulo'      => 'Confirmação',
    'mensagem'    => 'Tem certeza que deseja excluir?',
    'btn_cancelar'=> 'Cancelar',
    'btn_excluir' => 'Excluir',
];
include $projectRoot . '/src/Views/layouts/partials/confirm-modal.php';
?>

<?php
$contentHtml = ob_get_clean();
include $projectRoot . '/src/Views/layouts/app.php';
?>
