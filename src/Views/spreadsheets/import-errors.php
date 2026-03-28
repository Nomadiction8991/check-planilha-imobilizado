<?php
\App\Core\CsrfService::getToken();

$appConfig   = require dirname(__DIR__, 3) . '/config/app.php';
$projectRoot = $appConfig['project_root'];
$csrfToken   = \App\Core\CsrfService::getToken();

// ── Variáveis vindas do controller ─────────────────────────────────────────
$modo            = $modo            ?? 'geral';       // 'comum' | 'importacao' | 'geral'
$comumId         = $comum_id        ?? 0;
$importacaoId    = $importacao_id   ?? 0;
$importacao      = $importacao      ?? [];
$comum           = $comum           ?? [];
$errosRaw        = $erros ?? [];
/** @var array<int, array<string, mixed>> $erros */
$erros           = is_array($errosRaw) ? $errosRaw : [];
$paginaAtual     = $pagina          ?? 1;
$totalPaginas    = $total_paginas   ?? 1;
$totalRegistros  = $total_registros ?? 0;
$resumo          = $resumo          ?? ['pendentes' => 0, 'resolvidos' => 0];
$responsavelNome = trim((string) ($importacao['usuario_responsavel_nome'] ?? ''));
$responsavelEmail = trim((string) ($importacao['usuario_responsavel_email'] ?? ''));

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

    .msg-erro-text {
        color: #b02a37;
        font-size: 0.82rem;
        line-height: 1.35;
    }

    .table-erros th {
        white-space: nowrap;
        font-size: 0.8rem;
    }

    .table-erros td {
        font-size: 0.83rem;
        vertical-align: middle;
    }

    .codigo-cell {
        font-family: monospace;
        font-weight: 700;
        font-size: 0.9rem;
    }

    .contador-resumo {
        font-size: 0.9rem;
    }
</style>

<!-- ── Cabeçalho ─────────────────────────────────────────────────────────── -->
<div class="flex justify-between items-start flex-wrap gap-3 mb-4">

    <div>
        <h5 class="mb-1">
            <i class="bi bi-exclamation-octagon-fill text-black me-2"></i>
            Erros de Importação
            <?php if ($modo === 'comum'): ?>
                <small class="text-gray-600 font-normal">— <?= $nomeComum ?></small>
            <?php elseif ($modo === 'importacao' && !empty($importacao['arquivo_nome'])): ?>
                <small class="text-gray-600 font-normal">— <?= $nomeArquivo ?></small>
            <?php endif; ?>
        </h5>
        <?php if ($modo === 'importacao' && ($responsavelNome !== '' || $responsavelEmail !== '')): ?>
            <div class="text-sm text-gray-600">
                Responsável:
                <strong><?= htmlspecialchars($responsavelNome !== '' ? $responsavelNome : $responsavelEmail) ?></strong>
                <?php if ($responsavelNome !== '' && $responsavelEmail !== ''): ?>
                    <span class="text-gray-500">(<?= htmlspecialchars($responsavelEmail) ?>)</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Botão de download CSV -->
    <?php if ($downloadUrl && $resumo['pendentes'] > 0): ?>
        <div class="flex gap-2 flex-wrap">
            <a href="<?= $downloadUrl ?>" class="inline-flex items-center px-3 py-2 bg-black text-white font-semibold rounded transition text-sm">
                <i class="bi bi-download me-1"></i>Baixar CSV para reimportar
                <span class="ml-2 px-2 py-1 bg-white text-black rounded text-xs font-bold"><?= $resumo['pendentes'] ?>
                    it<?= $resumo['pendentes'] !== 1 ? 'ens' : 'em' ?></span>
            </a>
        </div>
    <?php endif; ?>

</div>

<!-- ── Alerta: ainda há pendentes ────────────────────────────────────────── -->
<?php if ($resumo['pendentes'] > 0): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
        <div>
            <strong><?= $resumo['pendentes'] ?> item<?= $resumo['pendentes'] !== 1 ? 'ns' : '' ?>
                ainda com erro.</strong>
            <div class="text-sm mt-1">
                Corrija o CSV, baixe-o com o botão acima e reimporte pela tela de
                <a href="/spreadsheets/import" class="underline font-semibold" style="color:inherit">Importar Planilha</a>.
                Esta mensagem desaparecerá automaticamente quando todos forem marcados como resolvidos.
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-success">
        <i class="bi bi-check-circle-fill flex-shrink-0 mt-1"></i>
        <div>
            <strong>Tudo certo!</strong>&nbsp;Todos os erros desta importação foram resolvidos.
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($_GET['aviso'])): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle-fill flex-shrink-0 mt-1"></i>
        <div class="text-sm">
            <?= htmlspecialchars((string) $_GET['aviso'], ENT_QUOTES, 'UTF-8') ?>
        </div>
    </div>
<?php endif; ?>

<!-- ── Como usar ─────────────────────────────────────────────────────────── -->
<?php if ($resumo['pendentes'] > 0 && $downloadUrl): ?>
    <div class="border border-neutral-200 bg-neutral-50 mb-4" style="border-radius:2px">
        <div class="p-3">
            <p class="mb-2 font-semibold text-gray-700">
                <i class="bi bi-info-circle me-1"></i>Como corrigir:
            </p>
            <ol class="mb-0 text-sm text-gray-700 list-decimal" style="padding-left: 1.2rem; line-height: 1.8;">
                <li>Clique em <strong>Baixar CSV para reimportar</strong> — ele contém apenas os itens com erro.</li>
                <li>Abra o arquivo, verifique a coluna <em>Nome</em> (col D) e a <em>Dependência</em> (col P).</li>
                <li>Corrija os dados incorretos e salve o arquivo.</li>
                <li>Vá em <a href="/spreadsheets/import" class="font-semibold underline hover:text-gray-900">Importar Planilha</a> e envie o CSV corrigido.</li>
                <li>Depois da reimportação, marque os erros abaixo como <strong>Resolvido</strong>.</li>
            </ol>
        </div>
    </div>
<?php endif; ?>

<!-- ── Tabela de erros ───────────────────────────────────────────────────── -->
<?php if (empty($erros)): ?>
    <div class="bg-neutral-50 border border-neutral-300 rounded px-4 py-3">
        <i class="bi bi-check2-all me-2 text-black"></i>
        <span class="text-black">Nenhum registro de erro encontrado.</span>
    </div>
<?php else: ?>

    <div id="contador-pendentes-info"
        class="text-gray-600 text-sm mb-3"
        data-pendentes="<?= $resumo['pendentes'] ?>">
        Exibindo <?= count($erros) ?> de <?= number_format($totalRegistros) ?> registro(s)
        <?php if ($totalPaginas > 1): ?>
            — página <?= $paginaAtual ?>/<?= $totalPaginas ?>
        <?php endif; ?>
    </div>

    <div class="overflow-x-auto border border-gray-200" style="border-radius:2px">
        <table class="table-erros w-full text-sm border-collapse">
            <thead class="bg-gray-900 text-white">
                <tr>
                    <th style="width:110px" class="px-3 py-2 text-left font-semibold">Código</th>
                    <th class="px-3 py-2 text-left font-semibold">Descrição CSV</th>
                    <th style="width:220px" class="px-3 py-2 text-left font-semibold">Motivo do Erro</th>
                </tr>
            </thead>
            <tbody id="tabela-erros-body">
                <?php foreach ($erros as $erro): ?>
                    <?php
                    $eResolvido = (bool) ($erro['resolvido'] ?? false);
                    $rowClass   = $eResolvido ? 'erro-row-resolvido' : 'erro-row-pendente';
                    ?>
                    <tr id="erro-row-<?= (int)$erro['id'] ?>" class="<?= $rowClass ?> border-b border-gray-200 hover:bg-gray-50">

                        <!-- Código -->
                        <td class="codigo-cell px-3 py-2">
                            <?= \App\Helpers\ViewHelper::e(\App\Helpers\ViewHelper::formatarCodigoCurto($erro['codigo'] ?? '—')) ?>
                            <?php if (!empty($erro['linha_csv'])): ?>
                                <br><span class="text-gray-600" style="font-size:0.72rem; font-weight:400">
                                    linha <?= htmlspecialchars((string)$erro['linha_csv']) ?>
                                </span>
                            <?php endif; ?>
                        </td>

                        <!-- Descrição CSV (nome original) -->
                        <td class="px-3 py-2">
                            <?= htmlspecialchars($erro['descricao_csv'] ?? '') ?>
                        </td>

                        <!-- Motivo -->
                        <td class="msg-erro-text px-3 py-2">
                            <?= htmlspecialchars($erro['mensagem_erro'] ?? '') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- ── Paginação ──────────────────────────────────────────────────────── -->
    <?php
    $paginationQuery = [];
    if ($modo === 'comum' && $comumId > 0) {
        $paginationQuery['comum_id'] = $comumId;
    }
    if ($modo === 'importacao' && $importacaoId > 0) {
        $paginationQuery['importacao_id'] = $importacaoId;
    }

    echo \App\Helpers\PaginationHelper::render(
        (int) $paginaAtual,
        (int) $totalPaginas,
        '/spreadsheets/import-errors',
        $paginationQuery
    );
    ?>

<?php endif; ?>

<!-- ── JavaScript: toggle resolvido via AJAX ─────────────────────────────── -->
<script>
    (function() {
        const body = document.getElementById('tabela-erros-body');
        if (!body) return;

        const toastFn = (msg, ok) => {
            const el = document.createElement('div');
            el.className = 'position-fixed bottom-0 end-0 m-3 py-2 px-3';
            el.style.cssText = 'z-index:9999;min-width:220px;font-size:.85rem;border-radius:2px;background:#fafafa;border:1px solid ' + (ok ? '#d4d4d4' : '#000') + ';color:#171717';
            el.textContent = msg;
            document.body.appendChild(el);
            setTimeout(() => el.remove(), 2800);
        };

        body.addEventListener('change', async function(e) {
            const chk = e.target;
            if (!chk.classList.contains('chk-resolvido')) return;

            const erroId = parseInt(chk.dataset.id, 10);
            const resolvido = chk.checked;
            const row = document.getElementById('erro-row-' + erroId);
            const badge = document.getElementById('badge-' + erroId);

            // Feedback imediato
            chk.disabled = true;
            if (row) {
                row.className = resolvido ? 'erro-row-resolvido' : 'erro-row-pendente';
            }
            if (badge) {
                badge.innerHTML = resolvido ?
                    '<span class="badge badge-resolvido">OK</span>' :
                    '<span class="badge badge-pendente">PEND</span>';
            }

            try {
                const resp = await fetch('/spreadsheets/import-errors/resolver', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': <?= json_encode($csrfToken, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
                    },
                    body: JSON.stringify({
                        erro_id: erroId,
                        resolvido: resolvido
                    })
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
                    badge.innerHTML = !resolvido ?
                        '<span class="badge badge-resolvido">OK</span>' :
                        '<span class="badge badge-pendente">PEND</span>';
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
