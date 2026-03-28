<?php


$appConfig = require dirname(__DIR__, 3) . '/config/app.php';
$projectRoot = $appConfig['project_root'];
$pageTitle = 'CONFERÊNCIA DA IMPORTAÇÃO';
$backUrl = '/spreadsheets/import';

$importacaoId     = $importacao_id      ?? 0;
$importacao       = $importacao         ?? [];
$resumo           = $resumo             ?? [];
$registros        = $registros          ?? [];
$paginaAtual      = $pagina             ?? 1;
$totalPaginas     = $total_paginas      ?? 1;
$totalRegistros   = $total_registros    ?? 0;
$itensPorPagina   = $itens_por_pagina   ?? 20;
$acoesSalvas      = $acoes_salvas       ?? [];
$comunsDetectadas = $comuns_detectadas  ?? [];
$igrejasSalvas    = $igrejas_salvas     ?? [];
$statusPorComum   = $status_por_comum   ?? [];
$responsavelNome  = trim((string) ($importacao['usuario_responsavel_nome'] ?? ''));
$responsavelEmail = trim((string) ($importacao['usuario_responsavel_email'] ?? ''));

ob_start();
?>

<link href="/assets/css/planilhas/importacao_preview.css" rel="stylesheet">

<form id="form-confirmar" action="/spreadsheets/confirm" method="POST">
    <input type="hidden" name="importacao_id" value="<?= $importacaoId ?>">
    <input type="hidden" name="importar_tudo" id="importar_tudo_flag" value="0">

    <?php if ($responsavelNome !== '' || $responsavelEmail !== ''): ?>
        <div class="mb-4 border border-neutral-200 bg-neutral-50 px-4 py-3 text-sm text-neutral-700" style="border-radius:2px">
            Responsável pela importação:
            <strong><?= htmlspecialchars($responsavelNome !== '' ? $responsavelNome : $responsavelEmail) ?></strong>
            <?php if ($responsavelNome !== '' && $responsavelEmail !== ''): ?>
                <span class="text-gray-500">(<?= htmlspecialchars($responsavelEmail) ?>)</span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Cards de Resumo (fixo no topo) -->
    <div class="sticky top-0 mb-4 z-10">
        <div class="grid grid-cols-5 gap-2">
            <div class="bg-white p-3 text-center border border-neutral-200" style="border-radius:2px">
                <h3 class="text-lg font-bold text-black"><?= number_format($resumo['total'] ?? 0) ?></h3>
                <small class="text-gray-600">TOTAL</small>
            </div>
            <div class="bg-white p-3 text-center border border-neutral-200" style="border-radius:2px">
                <h3 class="text-lg font-bold text-black"><?= number_format($resumo['novos'] ?? 0) ?></h3>
                <small class="text-gray-600">NOVOS</small>
            </div>
            <div class="bg-white p-3 text-center border border-neutral-200" style="border-radius:2px">
                <h3 class="text-lg font-bold text-black"><?= number_format($resumo['atualizar'] ?? 0) ?></h3>
                <small class="text-gray-600">ALTERAÇÕES</small>
            </div>
            <div class="bg-white p-3 text-center border border-neutral-200" style="border-radius:2px">
                <h3 class="text-lg font-bold text-black"><?= number_format($resumo['sem_alteracao'] ?? 0) ?></h3>
                <small class="text-gray-600">IGUAIS</small>
            </div>
            <div class="bg-white p-3 text-center border border-neutral-200" style="border-radius:2px">
                <h3 class="text-lg font-bold text-black"><?= number_format($resumo['exclusoes'] ?? 0) ?></h3>
                <small class="text-gray-600">EXCLUSÕES</small>
            </div>
        </div>
    </div>

    <!-- Igrejas Detectadas -->
    <?php if (!empty($comunsDetectadas)): ?>
        <div class="border border-neutral-200 mb-4" style="border-radius:2px">
            <div class="flex items-center gap-2 bg-gray-50 px-4 py-3 border-b border-gray-200">
                <i class="bi bi-building"></i>
                <strong class="text-sm">Igrejas Detectadas (<?= count($comunsDetectadas) ?>)</strong>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50">
                            <th style="width:80px" class="text-center px-3 py-2 font-semibold">STATUS</th>
                            <th class="px-3 py-2 font-semibold text-left">IGREJA</th>
                            <th style="width: 220px" class="px-3 py-2 font-semibold text-left">AÇÃO</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($comunsDetectadas as $comumInfo):
                            $codigoComum     = $comumInfo['codigo'];
                            $statusComum     = $statusPorComum[$codigoComum] ?? 'iguais';
                            $isIguais        = ($statusComum === 'iguais');
                            $statusLabel     = match ($statusComum) {
                                'novo'      => 'NOVOS',
                                'atualizar' => 'ALTERAÇÕES',
                                default     => 'IGUAIS',
                            };
                            $statusBadge     = match ($statusComum) {
                                'novo'      => 'bg-black text-white',
                                'atualizar' => 'bg-neutral-300 text-black',
                                default     => 'bg-neutral-500 text-white',
                            };
                            $acaoSalvaIgreja = $isIguais ? 'pular' : ($igrejasSalvas[$codigoComum] ?? 'pular');
                        ?>
                            <tr class="border-b border-gray-200 hover:bg-gray-50">
                                <td class="text-center px-3 py-2">
                                    <span class="inline-block px-2 py-1 rounded text-xs font-semibold <?= $statusBadge ?>">
                                        <?= $statusLabel ?>
                                    </span>
                                </td>
                                <td class="px-3 py-2">
                                    <span class="inline-block px-2 py-1 bg-gray-900 text-white rounded text-xs font-mono">
                                        <?= htmlspecialchars($codigoComum) ?>
                                    </span>
                                </td>
                                <td class="px-3 py-2">
                                    <select class="px-2 py-1 text-sm border border-gray-300 rounded select-igreja"
                                        name="igrejas[<?= htmlspecialchars($codigoComum) ?>]"
                                        data-codigo="<?= htmlspecialchars($codigoComum) ?>"
                                        <?= $isIguais ? 'disabled' : '' ?>>
                                        <option value="pular"
                                            <?= (!in_array($acaoSalvaIgreja, ['importar'])) ? 'selected' : '' ?>>
                                            ⊘ Não Importar
                                        </option>
                                        <option value="importar"
                                            <?= $acaoSalvaIgreja === 'importar' ? 'selected' : '' ?>>
                                            ✔ Importar
                                        </option>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Barra de Confirmação -->
    <div class="border border-neutral-200 mt-4" style="border-radius:2px">
        <div class="p-3">
            <div class="flex flex-col gap-2">
                <a href="/spreadsheets/import" class="w-full px-4 py-2 border border-neutral-300 text-neutral-700 font-semibold transition text-center" style="border-radius:2px;text-decoration:none" onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background=''">
                    <i class="bi bi-x-lg me-1"></i>Cancelar
                </a>
                <button type="submit" class="w-full px-4 py-2 bg-black text-white font-semibold transition" style="border-radius:2px" id="btn-confirmar">
                    <i class="bi bi-check-lg me-1"></i>Importar
                </button>
                <button type="button" class="w-full px-4 py-2 border border-black text-black font-semibold transition" style="border-radius:2px;background:#fff" id="btn-importar-tudo"
                    onclick="importarTudo()">
                    <i class="bi bi-check-all me-1"></i>Importar Tudo
                </button>
            </div>
        </div>
    </div>
</form>

<script>
    window._importacaoId = <?= (int)$importacaoId ?>;
</script>
<script src="/assets/js/spreadsheets/import-preview.js"></script>

<?php
$contentHtml = ob_get_clean();
include $projectRoot . '/src/Views/layouts/app.php';
?>
