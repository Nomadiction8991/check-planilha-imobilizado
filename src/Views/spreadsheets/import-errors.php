<?php

$appConfig   = require dirname(__DIR__, 3) . '/config/app.php';
$projectRoot = $appConfig['project_root'];

// ── Variáveis vindas do controller ─────────────────────────────────────────
$modo            = $modo            ?? 'geral';       // 'comum' | 'importacao' | 'geral'
$comumId         = $comum_id        ?? 0;
$importacaoId    = $importacao_id   ?? 0;
$importacao      = $importacao      ?? [];
$comum           = $comum           ?? [];
$erros           = $erros           ?? [];
$paginaAtual     = $pagina          ?? 1;
$totalPaginas    = $total_paginas   ?? 1;
$totalRegistros  = $total_registros ?? 0;
$resumo          = $resumo          ?? ['pendentes' => 0, 'resolvidos' => 0];

// ── Título e back URL ────────────────────────────────────────────────────────
if ($modo === 'comum') {
    $nomeComum  = htmlspecialchars($comum['descricao'] ?? 'Comum #' . $comumId);
    $pageTitle  = 'ERROS DE IMPORTAÇÃO — ' . strtoupper($nomeComum);
    $backUrl    = '/products/view?comum_id=' . $comumId;
    $backLabel  = 'Voltar para ' . $nomeComum;
    $downloadUrl = '/spreadsheets/import-errors/download?comum_id=' . $comumId;
    $queryStr    = 'comum_id=' . $comumId;
} elseif ($modo === 'importacao') {
    $nomeArquivo = htmlspecialchars($importacao['arquivo_nome'] ?? 'Importação #' . $importacaoId);
    $pageTitle   = 'ERROS DE IMPORTAÇÃO — ' . strtoupper($nomeArquivo);
    $backUrl     = '/spreadsheets/import';
    $backLabel   = 'Nova Importação';
    $downloadUrl = '/spreadsheets/import-errors/download?importacao_id=' . $importacaoId;
    $queryStr    = 'importacao_id=' . $importacaoId;
} else {
    $pageTitle   = 'ERROS DE IMPORTAÇÃO';
    $backUrl     = '/spreadsheets/import';
    $backLabel   = 'Nova Importação';
    $downloadUrl = null; // geral sem download (precisa de contexto)
    $queryStr    = '';
}

ob_start();
?>

<style>
    .erro-row-pendente td {
        background-color: rgba(220, 53, 69, 0.04) !important;
    }
    .erro-row-resolvido td {
        background-color: rgba(25, 135, 84, 0.06) !important;
        opacity: 0.75;
    }
    .badge-pendente   { background-color: #dc3545; }
    .badge-resolvido  { background-color: #198754; }
    .msg-erro-text    { color: #b02a37; font-size: 0.82rem; line-height: 1.35; }
    .table-erros th   { white-space: nowrap; font-size: 0.8rem; }
    .table-erros td   { font-size: 0.83rem; vertical-align: middle; }
    .codigo-cell      { font-family: monospace; font-weight: 700; font-size: 0.9rem; }
    .contador-resumo  { font-size: 0.9rem; }
</style>

<!-- ── Cabeçalho ─────────────────────────────────────────────────────────── -->
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">

    <div>
        <h5 class="mb-1">
            <i class="bi bi-exclamation-octagon-fill text-danger me-2"></i>
            Erros de Importação
            <?php if ($modo === 'comum'): ?>
                <small class="text-muted fw-normal">— <?= $nomeComum ?></small>
            <?php elseif ($modo === 'importacao' && !empty($importacao['arquivo_nome'])): ?>
                <small class="text-muted fw-normal">— <?= $nomeArquivo ?></small>
            <?php endif; ?>
        </h5>

        <!-- Resumo: pendentes e resolvidos -->
        <div class="d-flex gap-3 contador-resumo mt-1">
            <span>
                <span class="badge badge-pendente rounded-pill me-1"><?= $resumo['pendentes'] ?></span>
                pendente<?= $resumo['pendentes'] !== 1 ? 's' : '' ?>
            </span>
            <span>
                <span class="badge badge-resolvido rounded-pill me-1"><?= $resumo['resolvidos'] ?></span>
                resolvido<?= $resumo['resolvidos'] !== 1 ? 's' : '' ?>
            </span>
            <?php if ($modo === 'importacao' && !empty($importacao['iniciada_em'])): ?>
                <span class="text-muted">
                    <i class="bi bi-clock me-1"></i>
                    <?= htmlspecialchars($importacao['iniciada_em']) ?>
                </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Botões de ação -->
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= $backUrl ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i><?= $backLabel ?>
        </a>
        <?php if ($downloadUrl && $resumo['pendentes'] > 0): ?>
            <a href="<?= $downloadUrl ?>" class="btn btn-success btn-sm">
                <i class="bi bi-download me-1"></i>Baixar CSV para reimportar
                <span class="badge bg-white text-success ms-1"><?= $resumo['pendentes'] ?>
                    it<?= $resumo['pendentes'] !== 1 ? 'ens' : 'em' ?></span>
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- ── Alerta: ainda há pendentes ────────────────────────────────────────── -->
<?php if ($resumo['pendentes'] > 0): ?>
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-3"
         role="alert" style="border-left: 4px solid #842029; border-radius: 8px;">
        <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
        <div>
            <strong><?= $resumo['pendentes'] ?> item<?= $resumo['pendentes'] !== 1 ? 'ns' : '' ?>
            ainda com erro.</strong>
            Corrija o CSV, baixe-o com o botão acima e reimporte pela tela de
            <a href="/spreadsheets/import" class="alert-link">Importar Planilha</a>.
            Esta mensagem desaparecerá automaticamente quando todos forem marcados como resolvidos.
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3" role="alert">
        <i class="bi bi-check-circle-fill flex-shrink-0"></i>
        <strong>Tudo certo!</strong>&nbsp;Todos os erros desta importação foram resolvidos.
    </div>
<?php endif; ?>

<!-- ── Como usar ─────────────────────────────────────────────────────────── -->
<?php if ($resumo['pendentes'] > 0 && $downloadUrl): ?>
    <div class="card mb-3 border-0 bg-light">
        <div class="card-body py-2 px-3">
            <p class="mb-1 fw-semibold small text-secondary">
                <i class="bi bi-info-circle me-1"></i>Como corrigir:
            </p>
            <ol class="mb-0 small text-secondary" style="padding-left: 1.2rem; line-height: 1.8;">
                <li>Clique em <strong>Baixar CSV para reimportar</strong> — ele contém apenas os itens com erro.</li>
                <li>Abra o arquivo, verifique a coluna <em>Nome</em> (col D) e a <em>Dependência</em> (col P).</li>
                <li>Corrija os dados incorretos e salve o arquivo.</li>
                <li>Vá em <a href="/spreadsheets/import" class="fw-semibold">Importar Planilha</a> e envie o CSV corrigido.</li>
                <li>Depois da reimportação, marque os erros abaixo como <strong>Resolvido</strong>.</li>
            </ol>
        </div>
    </div>
<?php endif; ?>

<!-- ── Tabela de erros ───────────────────────────────────────────────────── -->
<?php if (empty($erros)): ?>
    <div class="alert alert-success">
        <i class="bi bi-check2-all me-2"></i>
        Nenhum registro de erro encontrado.
    </div>
<?php else: ?>

    <div id="contador-pendentes-info"
         class="text-muted small mb-2"
         data-pendentes="<?= $resumo['pendentes'] ?>">
        Exibindo <?= count($erros) ?> de <?= number_format($totalRegistros) ?> registro(s)
        <?php if ($totalPaginas > 1): ?>
            — página <?= $paginaAtual ?>/<?= $totalPaginas ?>
        <?php endif; ?>
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-bordered table-hover align-middle table-erros">
            <thead class="table-dark">
                <tr>
                    <th style="width:90px">Código</th>
                    <th>Descrição CSV</th>
                    <th style="width:160px">Bem / Complemento</th>
                    <th style="width:140px">Dependência</th>
                    <th style="width:150px">Motivo do Erro</th>
                    <th style="width:80px">Data</th>
                    <th style="width:100px" class="text-center">Resolvido</th>
                </tr>
            </thead>
            <tbody id="tabela-erros-body">
                <?php foreach ($erros as $erro): ?>
                    <?php
                    $eResolvido = (bool) ($erro['resolvido'] ?? false);
                    $rowClass   = $eResolvido ? 'erro-row-resolvido' : 'erro-row-pendente';
                    $bem  = trim($erro['bem'] ?? '');
                    $comp = trim($erro['complemento'] ?? '');
                    $dataFormatada = !empty($erro['created_at'])
                        ? date('d/m/y H:i', strtotime($erro['created_at']))
                        : '—';
                    ?>
                    <tr id="erro-row-<?= (int)$erro['id'] ?>" class="<?= $rowClass ?>">

                        <!-- Código -->
                        <td class="codigo-cell">
                            <?= htmlspecialchars($erro['codigo'] ?? '—') ?>
                            <?php if (!empty($erro['linha_csv'])): ?>
                                <br><span class="text-muted" style="font-size:0.72rem; font-weight:400">
                                    linha <?= htmlspecialchars((string)$erro['linha_csv']) ?>
                                </span>
                            <?php endif; ?>
                        </td>

                        <!-- Descrição CSV (nome original) -->
                        <td>
                            <span class="fw-semibold">
                                <?= htmlspecialchars($erro['descricao_csv'] ?? '') ?>
                            </span>
                            <?php if (!empty($erro['localidade'])): ?>
                                <br>
                                <span class="text-muted" style="font-size:0.75rem;">
                                    <i class="bi bi-building me-1"></i>
                                    <?= htmlspecialchars($erro['localidade']) ?>
                                    <?php if (!empty($erro['codigo_comum'])): ?>
                                        (<?= htmlspecialchars($erro['codigo_comum']) ?>)
                                    <?php endif; ?>
                                </span>
                            <?php endif; ?>
                        </td>

                        <!-- Bem / Complemento -->
                        <td>
                            <?php if ($bem || $comp): ?>
                                <span class="fw-semibold"><?= htmlspecialchars($bem) ?></span>
                                <?php if ($comp): ?>
                                    <br><span class="text-muted"><?= htmlspecialchars($comp) ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>

                        <!-- Dependência -->
                        <td><?= htmlspecialchars($erro['dependencia'] ?? '—') ?></td>

                        <!-- Motivo -->
                        <td class="msg-erro-text">
                            <?= htmlspecialchars($erro['mensagem_erro'] ?? '') ?>
                        </td>

                        <!-- Data -->
                        <td class="text-muted"><?= $dataFormatada ?></td>

                        <!-- Toggle Resolvido -->
                        <td class="text-center">
                            <div class="form-check form-switch d-flex justify-content-center align-items-center m-0 gap-2">
                                <input class="form-check-input chk-resolvido"
                                       type="checkbox"
                                       data-id="<?= (int)$erro['id'] ?>"
                                       <?= $eResolvido ? 'checked' : '' ?>
                                       title="<?= $eResolvido ? 'Clique para marcar como pendente' : 'Clique para marcar como resolvido' ?>">
                                <span class="badge rounded-pill chk-badge"
                                      style="font-size:0.68rem;"
                                      id="badge-<?= (int)$erro['id'] ?>">
                                    <?= $eResolvido ? '<span class="badge badge-resolvido">OK</span>' : '<span class="badge badge-pendente">PEND</span>' ?>
                                </span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- ── Paginação ──────────────────────────────────────────────────────── -->
    <?php if ($totalPaginas > 1): ?>
        <nav aria-label="Paginação de erros">
            <ul class="pagination pagination-sm justify-content-center flex-wrap">
                <?php if ($paginaAtual > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?= $queryStr ?>&pagina=<?= $paginaAtual - 1 ?>">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                <?php endif; ?>

                <?php
                $ini = max(1, $paginaAtual - 3);
                $fim = min($totalPaginas, $paginaAtual + 3);
                for ($p = $ini; $p <= $fim; $p++):
                ?>
                    <li class="page-item <?= $p === $paginaAtual ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= $queryStr ?>&pagina=<?= $p ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($paginaAtual < $totalPaginas): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?= $queryStr ?>&pagina=<?= $paginaAtual + 1 ?>">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>

<?php endif; ?>

<!-- ── JavaScript: toggle resolvido via AJAX ─────────────────────────────── -->
<script>
(function () {
    const body      = document.getElementById('tabela-erros-body');
    if (!body) return;

    const toastFn   = (msg, ok) => {
        // Fallback simples — usa o toast do Bootstrap se disponível
        const el = document.createElement('div');
        el.className = `alert alert-${ok ? 'success' : 'danger'} position-fixed`
            + ' bottom-0 end-0 m-3 py-2 px-3 shadow';
        el.style.cssText = 'z-index:9999;min-width:220px;font-size:.85rem;border-radius:8px';
        el.textContent = msg;
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 2800);
    };

    body.addEventListener('change', async function (e) {
        const chk = e.target;
        if (!chk.classList.contains('chk-resolvido')) return;

        const erroId    = parseInt(chk.dataset.id, 10);
        const resolvido = chk.checked;
        const row       = document.getElementById('erro-row-' + erroId);
        const badge     = document.getElementById('badge-' + erroId);

        // Feedback imediato
        chk.disabled = true;
        if (row) {
            row.className = resolvido ? 'erro-row-resolvido' : 'erro-row-pendente';
        }
        if (badge) {
            badge.innerHTML = resolvido
                ? '<span class="badge badge-resolvido">OK</span>'
                : '<span class="badge badge-pendente">PEND</span>';
        }

        try {
            const resp = await fetch('/spreadsheets/import-errors/resolver', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({erro_id: erroId, resolvido: resolvido})
            });
            const data = await resp.json();

            if (!data.sucesso) {
                throw new Error(data.erro || 'Erro desconhecido');
            }

            // Atualiza o contador de pendentes na UI
            const info = document.getElementById('contador-pendentes-info');
            if (info && typeof data.pendentes === 'number') {
                info.dataset.pendentes = data.pendentes;
            }

            toastFn(
                resolvido ? 'Marcado como resolvido ✓' : 'Reaberto como pendente',
                data.sucesso
            );

        } catch (ex) {
            // Reverte em caso de falha
            chk.checked = !resolvido;
            if (row) row.className = !resolvido ? 'erro-row-resolvido' : 'erro-row-pendente';
            if (badge) {
                badge.innerHTML = !resolvido
                    ? '<span class="badge badge-resolvido">OK</span>'
                    : '<span class="badge badge-pendente">PEND</span>';
            }
            toastFn('Falha ao salvar: ' + ex.message, false);
        } finally {
            chk.disabled = false;
        }
    });
}());
</script>

<?php
$contentHtml = ob_get_clean();
include $projectRoot . '/src/Views/layouts/app.php';
?>
