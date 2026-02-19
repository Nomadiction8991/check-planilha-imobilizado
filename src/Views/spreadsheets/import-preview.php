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
                        <th>IGREJA / LOCALIDADE</th>
                        <th style="width: 200px">AÇÃO</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($comunsDetectadas as $comumInfo):
                        $codigoComum = $comumInfo['codigo'];
                        $acaoSalvaIgreja = $igrejasSalvas[$codigoComum] ?? 'pular';
                    ?>
                    <tr>
                        <td>
                            <span class="badge <?= $comumInfo['existe'] ? 'bg-success' : 'bg-warning text-dark' ?>">
                                <?= htmlspecialchars($comumInfo['localidade']) ?>
                                <?php if (!$comumInfo['existe']): ?>
                                    <i class="bi bi-plus-circle-fill ms-1"></i> NOVA
                                <?php endif; ?>
                            </span>
                            <small class="text-muted ms-1 font-monospace"><?= htmlspecialchars($codigoComum) ?></small>
                        </td>
                        <td>
                            <select class="form-select form-select-sm select-igreja"
                                    name="igrejas[<?= htmlspecialchars($codigoComum) ?>]"
                                    data-codigo="<?= htmlspecialchars($codigoComum) ?>">
                                <option value="pular"    <?= $acaoSalvaIgreja !== 'importar' ? 'selected' : '' ?>>⊘ Não Importar</option>
                                <option value="importar" <?= $acaoSalvaIgreja === 'importar' ? 'selected' : '' ?>>✔ Importar</option>
                            </select>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Info da página -->
    <div class="d-flex justify-content-between align-items-center mb-2">
        <small class="text-muted">
            Mostrando <?= number_format(min(($paginaAtual - 1) * $itensPorPagina + 1, $totalRegistros)) ?>
            a <?= number_format(min($paginaAtual * $itensPorPagina, $totalRegistros)) ?>
            de <?= number_format($totalRegistros) ?> registros
        </small>
        <?php if ($totalPaginas > 1): ?>
            <small class="text-muted">Página <?= $paginaAtual ?> de <?= $totalPaginas ?></small>
        <?php endif; ?>
    </div>

    <!-- Tabela de Produtos -->
    <div class="table-container">
        <table class="table table-sm table-bordered tabela-preview mb-0">
            <thead>
                <tr>
                    <th style="width: 80px">STATUS</th>
                    <th style="width: 80px">CÓDIGO</th>
                    <th>DESCRIÇÃO</th>
                    <th>NOVA DESCRIÇÃO</th>
                    <th style="width: 110px">IGREJA</th>
                    <th style="width: 130px">DEPENDÊNCIA</th>
                    <th style="width: 130px">AÇÃO</th>
                </tr>
            </thead>
            <tbody id="tabela-body">
                <?php foreach ($registros as $idx => $reg):
                    $status       = $reg['status']        ?? 'erro';
                    $dadosCsv     = $reg['dados_csv']     ?? [];
                    $linhaCsv     = $reg['linha_csv']     ?? ($idx + 1);
                    $acaoSugerida = $reg['acao_sugerida'] ?? 'pular';

                    if (isset($acoesSalvas[(string)$linhaCsv])) {
                        $acaoSugerida = $acoesSalvas[(string)$linhaCsv];
                    }

                    $novaDesc = trim(($dadosCsv['bem'] ?? '') . ' ' . ($dadosCsv['complemento'] ?? ''));
                    if ($novaDesc === '') {
                        $novaDesc = $dadosCsv['descricao_completa'] ?? '';
                    }

                    $badgeClass = match ($status) {
                        'novo'          => 'badge-novo',
                        'atualizar'     => 'badge-atualizar',
                        'sem_alteracao' => 'badge-sem-alteracao',
                        'excluir'       => 'badge-excluir',
                        'erro'          => 'badge-erro',
                        default         => 'badge-sem-alteracao'
                    };

                    $statusLabel = match ($status) {
                        'novo'          => 'NOVO',
                        'atualizar'     => 'ALTERAR',
                        'sem_alteracao' => 'IGUAL',
                        'excluir'       => 'EXCLUIR',
                        'erro'          => 'ERRO',
                        default         => strtoupper($status)
                    };

                    $rowClass = match ($acaoSugerida) {
                        'pular'   => 'acao-pular',
                        'excluir' => 'acao-excluir',
                        default   => ''
                    };
                ?>
                <tr class="registro-row <?= $rowClass ?>"
                    data-status="<?= $status ?>"
                    data-linha="<?= htmlspecialchars((string)$linhaCsv) ?>"
                    data-comum="<?= htmlspecialchars($dadosCsv['codigo_comum'] ?? '') ?>">

                    <td class="text-center">
                        <span class="badge <?= $badgeClass ?>"><?= $statusLabel ?></span>
                    </td>

                    <td class="fw-bold small"><?= htmlspecialchars($dadosCsv['codigo'] ?? '') ?></td>

                    <td class="small" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                        title="<?= htmlspecialchars($dadosCsv['descricao_completa'] ?? '') ?>">
                        <?= htmlspecialchars($dadosCsv['descricao_completa'] ?? '') ?>
                    </td>

                    <td class="small" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                        title="<?= htmlspecialchars($novaDesc) ?>">
                        <?php if ($status === 'atualizar'): ?>
                            <span class="text-success fw-semibold"><?= htmlspecialchars($novaDesc) ?></span>
                        <?php else: ?>
                            <?= htmlspecialchars($novaDesc) ?>
                        <?php endif; ?>
                    </td>

                    <td class="small">
                        <?php
                            $localRow = $dadosCsv['localidade']   ?? '';
                            $codComum = $dadosCsv['codigo_comum'] ?? '';
                            $lbl = $localRow !== '' ? $localRow : $codComum;
                            if ($lbl !== '') {
                                echo '<span class="badge bg-secondary" style="font-size:0.65rem">';
                                echo htmlspecialchars($lbl);
                                echo '</span>';
                            }
                        ?>
                    </td>

                    <td class="small" style="max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                        title="<?= htmlspecialchars($dadosCsv['dependencia_descricao'] ?? '') ?>">
                        <?= htmlspecialchars($dadosCsv['dependencia_descricao'] ?? '') ?>
                    </td>

                    <td class="text-center">
                        <?php if ($status === 'erro'): ?>
                            <span class="text-danger" style="font-size:0.75rem">
                                <i class="bi bi-x-circle"></i> erro
                            </span>
                            <input type="hidden" name="acao[<?= htmlspecialchars((string)$linhaCsv) ?>]" value="pular">

                        <?php elseif ($status === 'excluir'): ?>
                            <select name="acao[<?= htmlspecialchars((string)$linhaCsv) ?>]"
                                    class="form-select form-select-sm select-acao"
                                    onchange="atualizarEstiloLinha(this)">
                                <option value="excluir" <?= $acaoSugerida === 'excluir' ? 'selected' : '' ?>>&#x2715; Excluir</option>
                                <option value="pular"   <?= $acaoSugerida === 'pular'   ? 'selected' : '' ?>>&#x2298; Manter</option>
                            </select>

                        <?php else: ?>
                            <select name="acao[<?= htmlspecialchars((string)$linhaCsv) ?>]"
                                    class="form-select form-select-sm select-acao"
                                    onchange="atualizarEstiloLinha(this)">
                                <option value="importar" <?= $acaoSugerida === 'importar' ? 'selected' : '' ?>>
                                    <?= $status === 'novo' ? '&#x271A; Importar' : '&#x270e; Atualizar' ?>
                                </option>
                                <option value="pular" <?= $acaoSugerida === 'pular' ? 'selected' : '' ?>>&#x2298; Não Importar</option>
                            </select>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginação -->
    <?php if ($totalPaginas > 1): ?>
        <nav aria-label="Paginação do preview" class="mt-3">
            <ul class="pagination pagination-sm justify-content-center mb-0">
                <?php if ($paginaAtual > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="#"
                            onclick="salvarAcoesAntes(event, '?id=<?= $importacaoId ?>&pagina=<?= $paginaAtual - 1 ?>')">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                <?php endif; ?>

                <?php
                $inicio = max(1, $paginaAtual - 3);
                $fim    = min($totalPaginas, $paginaAtual + 3);
                if ($inicio > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="#"
                            onclick="salvarAcoesAntes(event, '?id=<?= $importacaoId ?>&pagina=1')">1</a>
                    </li>
                    <?php if ($inicio > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $inicio; $i <= $fim; $i++): ?>
                    <li class="page-item <?= $i == $paginaAtual ? 'active' : '' ?>">
                        <a class="page-link" href="#"
                            onclick="salvarAcoesAntes(event, '?id=<?= $importacaoId ?>&pagina=<?= $i ?>')">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>

                <?php if ($fim < $totalPaginas): ?>
                    <?php if ($fim < $totalPaginas - 1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                    <li class="page-item">
                        <a class="page-link" href="#"
                            onclick="salvarAcoesAntes(event, '?id=<?= $importacaoId ?>&pagina=<?= $totalPaginas ?>')">
                            <?= $totalPaginas ?>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($paginaAtual < $totalPaginas): ?>
                    <li class="page-item">
                        <a class="page-link" href="#"
                            onclick="salvarAcoesAntes(event, '?id=<?= $importacaoId ?>&pagina=<?= $paginaAtual + 1 ?>')">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>

    <!-- Barra de Confirmação -->
    <div class="card mt-3">
        <div class="card-body d-flex justify-content-between align-items-center py-2 flex-wrap gap-2">
            <div>
                <small class="text-muted" id="contadores-acoes">Calculando&hellip;</small>
            </div>
            <div class="d-flex gap-2">
                <a href="/spreadsheets/import" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg me-1"></i>Cancelar
                </a>
                <button type="submit" class="btn btn-primary" id="btn-confirmar">
                    <i class="bi bi-check-lg me-1"></i>Importar
                </button>
                <button type="button" class="btn btn-danger" id="btn-importar-tudo"
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