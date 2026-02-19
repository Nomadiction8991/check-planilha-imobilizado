<?php

$appConfig   = require dirname(__DIR__, 3) . '/config/app.php';
$projectRoot = $appConfig['project_root'];
$pageTitle   = 'ERROS DE IMPORTAÇÃO';
$backUrl     = '/spreadsheets/import';

$importacaoId   = $importacao_id   ?? 0;
$importacao     = $importacao      ?? [];
$erros          = $erros           ?? [];
$paginaAtual    = $pagina          ?? 1;
$totalPaginas   = $total_paginas   ?? 1;
$totalRegistros = $total_registros ?? 0;

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h5 class="mb-0">
            <i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>
            Erros da Importação
            <?php if (!empty($importacao['arquivo_nome'])): ?>
                <small class="text-muted fw-normal">— <?= htmlspecialchars($importacao['arquivo_nome']) ?></small>
            <?php endif; ?>
        </h5>
        <small class="text-muted">
            <?= number_format($totalRegistros) ?> erro(s) registrado(s)
            <?php if (!empty($importacao['iniciada_em'])): ?>
                &middot; importado em <?= htmlspecialchars($importacao['iniciada_em']) ?>
            <?php endif; ?>
        </small>
    </div>
    <div class="d-flex gap-2">
        <a href="/spreadsheets/import" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Nova Importação
        </a>
        <?php if ($totalRegistros > 0): ?>
            <a href="/spreadsheets/import-errors/download?importacao_id=<?= $importacaoId ?>"
                class="btn btn-outline-success btn-sm">
                <i class="bi bi-download me-1"></i>Baixar CSV
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (empty($erros)): ?>
    <div class="alert alert-success">
        <i class="bi bi-check-circle-fill me-2"></i>
        Nenhum erro registrado para esta importação.
    </div>
<?php else: ?>

    <div class="table-responsive">
        <table class="table table-sm table-bordered table-striped align-middle">
            <thead class="table-dark">
                <tr>
                    <th style="width:80px">Linha</th>
                    <th style="width:90px">Código</th>
                    <th style="width:120px">Localidade</th>
                    <th>Descrição CSV</th>
                    <th style="width:140px">Bem / Complemento</th>
                    <th style="width:120px">Dependência</th>
                    <th>Mensagem de Erro</th>
                    <th style="width:90px" class="text-center">Resolvido</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($erros as $erro): ?>
                    <tr id="erro-row-<?= (int)$erro['id'] ?>"
                        class="<?= $erro['resolvido'] ? 'table-success' : '' ?>">
                        <td class="text-muted font-monospace small">
                            <?= htmlspecialchars((string)($erro['linha_csv'] ?? '')) ?>
                        </td>
                        <td class="font-monospace small fw-bold">
                            <?= htmlspecialchars($erro['codigo'] ?? '') ?>
                        </td>
                        <td class="small">
                            <?= htmlspecialchars($erro['localidade'] ?? '') ?>
                            <?php if (!empty($erro['codigo_comum'])): ?>
                                <br><span class="text-muted"><?= htmlspecialchars($erro['codigo_comum']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="small">
                            <?= htmlspecialchars($erro['descricao_csv'] ?? '') ?>
                        </td>
                        <td class="small">
                            <?php
                            $bem  = trim($erro['bem'] ?? '');
                            $comp = trim($erro['complemento'] ?? '');
                            echo htmlspecialchars($bem);
                            if ($bem && $comp) echo ' ';
                            echo htmlspecialchars($comp);
                            ?>
                        </td>
                        <td class="small">
                            <?= htmlspecialchars($erro['dependencia'] ?? '') ?>
                        </td>
                        <td class="small text-danger">
                            <?= htmlspecialchars($erro['mensagem_erro'] ?? '') ?>
                        </td>
                        <td class="text-center">
                            <div class="form-check form-switch d-flex justify-content-center m-0">
                                <input class="form-check-input chk-resolvido"
                                    type="checkbox"
                                    data-id="<?= (int)$erro['id'] ?>"
                                    <?= $erro['resolvido'] ? 'checked' : '' ?>>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginação -->
    <?php if ($totalPaginas > 1): ?>
        <nav>
            <ul class="pagination pagination-sm justify-content-center flex-wrap">
                <?php if ($paginaAtual > 1): ?>
                    <li class="page-item">
                        <a class="page-link"
                            href="?importacao_id=<?= $importacaoId ?>&pagina=<?= $paginaAtual - 1 ?>">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                <?php endif; ?>

                <?php
                $inicio = max(1, $paginaAtual - 3);
                $fim    = min($totalPaginas, $paginaAtual + 3);
                for ($p = $inicio; $p <= $fim; $p++):
                ?>
                    <li class="page-item <?= $p === $paginaAtual ? 'active' : '' ?>">
                        <a class="page-link"
                            href="?importacao_id=<?= $importacaoId ?>&pagina=<?= $p ?>">
                            <?= $p ?>
                        </a>
                    </li>
                <?php endfor; ?>

                <?php if ($paginaAtual < $totalPaginas): ?>
                    <li class="page-item">
                        <a class="page-link"
                            href="?importacao_id=<?= $importacaoId ?>&pagina=<?= $paginaAtual + 1 ?>">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>

<?php endif; ?>

<script>
    document.querySelectorAll('.chk-resolvido').forEach(chk => {
        chk.addEventListener('change', async function() {
            const erroId = parseInt(this.dataset.id, 10);
            const resolvido = this.checked;
            const row = document.getElementById('erro-row-' + erroId);

            try {
                const resp = await fetch('/spreadsheets/import-errors/resolver', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        erro_id: erroId,
                        resolvido: resolvido
                    })
                });
                const data = await resp.json();
                if (data.sucesso) {
                    row?.classList.toggle('table-success', resolvido);
                } else {
                    this.checked = !resolvido; // reverte
                    console.error('Erro ao atualizar status:', data.erro);
                }
            } catch (e) {
                this.checked = !resolvido; // reverte
                console.error('Falha de conexão:', e);
            }
        });
    });
</script>

<?php
$contentHtml = ob_get_clean();
include $projectRoot . '/src/Views/layouts/app.php';
?>