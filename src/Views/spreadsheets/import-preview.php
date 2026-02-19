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

ob_start();
?>

<link href="/assets/css/planilhas/importacao_preview.css" rel="stylesheet">

<form id="form-confirmar" action="/spreadsheets/confirm" method="POST">
    <input type="hidden" name="importacao_id" value="<?= $importacaoId ?>">
    <input type="hidden" name="importar_tudo" id="importar_tudo_flag" value="0">

    <!-- Cards de Resumo (fixo no topo) -->
    <div class="resumo-sticky mb-3">
        <div class="row g-2">
            <div class="col">
                <div class="resumo-card">
                    <h3 class="text-primary"><?= number_format($resumo['total'] ?? 0) ?></h3>
                    <small>TOTAL</small>
                </div>
            </div>
            <div class="col">
                <div class="resumo-card">
                    <h3 class="text-success"><?= number_format($resumo['novos'] ?? 0) ?></h3>
                    <small>NOVOS</small>
                </div>
            </div>
            <div class="col">
                <div class="resumo-card">
                    <h3 class="text-warning"><?= number_format($resumo['atualizar'] ?? 0) ?></h3>
                    <small>ALTERAÇÕES</small>
                </div>
            </div>
            <div class="col">
                <div class="resumo-card">
                    <h3 class="text-secondary"><?= number_format($resumo['sem_alteracao'] ?? 0) ?></h3>
                    <small>IGUAIS</small>
                </div>
            </div>
            <div class="col">
                <div class="resumo-card">
                    <h3 class="text-danger"><?= number_format($resumo['exclusoes'] ?? 0) ?></h3>
                    <small>EXCLUSÕES</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Igrejas Detectadas -->
    <?php if (!empty($comunsDetectadas)): ?>
        <div class="card mb-3">
            <div class="card-header py-2 d-flex align-items-center gap-2">
                <i class="bi bi-building"></i>
                <strong class="small text-uppercase">Igrejas Detectadas (<?= count($comunsDetectadas) ?>)</strong>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-bordered mb-0 tabela-igrejas">
                    <thead>
                        <tr>
                            <th style="width:80px" class="text-center">STATUS</th>
                            <th>IGREJA</th>
                            <th style="width: 220px">AÇÃO</th>
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
                                'novo'      => 'bg-success',
                                'atualizar' => 'bg-warning text-dark',
                                default     => 'bg-secondary',
                            };
                            $acaoSalvaIgreja = $isIguais ? 'pular' : ($igrejasSalvas[$codigoComum] ?? 'pular');
                        ?>
                            <tr>
                                <td class="text-center">
                                    <span class="badge <?= $statusBadge ?>">
                                        <?= $statusLabel ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-dark font-monospace" style="font-size:.8rem">
                                        <?= htmlspecialchars($codigoComum) ?>
                                    </span>
                                </td>
                                <td>
                                    <select class="form-select form-select-sm select-igreja"
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
    <div class="card mt-3">
        <div class="card-body py-2">
            <div class="d-flex flex-column gap-2">
                <a href="/spreadsheets/import" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-x-lg me-1"></i>Cancelar
                </a>
                <button type="submit" class="btn btn-primary w-100" id="btn-confirmar">
                    <i class="bi bi-check-lg me-1"></i>Importar
                </button>
                <button type="button" class="btn btn-danger w-100" id="btn-importar-tudo"
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