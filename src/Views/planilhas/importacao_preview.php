<?php
$pageTitle = 'CONFERÊNCIA DA IMPORTAÇÃO';
$backUrl = '/planilhas/importar';

$importacaoId = $importacao_id ?? 0;
$importacao = $importacao ?? [];
$resumo = $resumo ?? [];
$registros = $registros ?? [];
$paginaAtual = $pagina ?? 1;
$totalPaginas = $total_paginas ?? 1;
$totalRegistros = $total_registros ?? 0;
$itensPorPagina = $itens_por_pagina ?? 50;
$filtroStatus = $filtro_status ?? 'todos';
$acoesSalvas = $acoes_salvas ?? [];

ob_start();
?>

<style>
    .badge-novo {
        background-color: #198754 !important;
        color: white !important;
    }

    .badge-atualizar {
        background-color: #fd7e14 !important;
        color: white !important;
    }

    .badge-sem-alteracao {
        background-color: #6c757d !important;
        color: white !important;
    }

    .badge-erro {
        background-color: #dc3545 !important;
        color: white !important;
    }

    .diff-antes {
        background-color: #f8d7da !important;
        text-decoration: line-through;
        padding: 1px 4px;
        border-radius: 3px;
        font-size: 0.8rem;
    }

    .diff-depois {
        background-color: #d1e7dd !important;
        padding: 1px 4px;
        border-radius: 3px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .registro-row {
        transition: background-color 0.2s ease;
    }

    .registro-row:hover {
        background-color: rgba(0, 123, 255, 0.05) !important;
    }

    .registro-row.acao-pular {
        opacity: 0.5;
    }

    .registro-row.acao-excluir {
        background-color: rgba(220, 53, 69, 0.08) !important;
    }

    .resumo-card {
        text-align: center;
        padding: 15px 10px;
        border-radius: 8px;
        border: 1px solid #dee2e6;
    }

    .resumo-card h3 {
        margin: 0;
        font-size: 1.8rem;
        font-weight: 700;
    }

    .resumo-card small {
        text-transform: uppercase;
        font-size: 0.7rem;
        letter-spacing: 1px;
        color: #6c757d;
    }

    .filtro-btn {
        font-size: 0.75rem;
        padding: 4px 12px;
    }

    .filtro-btn.active {
        font-weight: 700;
    }

    .tabela-preview {
        font-size: 0.82rem;
    }

    .tabela-preview th {
        position: sticky;
        top: 0;
        z-index: 10;
        background: #f8f9fa;
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .tabela-preview td {
        vertical-align: middle;
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .select-acao {
        font-size: 0.78rem;
        padding: 2px 6px;
        min-width: 100px;
    }

    .diff-cell {
        white-space: normal !important;
    }

    .table-container {
        max-height: 65vh;
        overflow-y: auto;
        border: 1px solid #dee2e6;
        border-radius: 4px;
    }

    .acao-massa-bar {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        padding: 10px 15px;
    }
</style>

<form id="form-confirmar" action="/planilhas/confirmar" method="POST">
    <input type="hidden" name="importacao_id" value="<?= $importacaoId ?>">

    <!-- Cards de Resumo -->
    <div class="row g-2 mb-3">
        <div class="col-3">
            <div class="resumo-card">
                <h3 class="text-primary"><?= number_format($resumo['total'] ?? 0) ?></h3>
                <small>TOTAL</small>
            </div>
        </div>
        <div class="col-3">
            <div class="resumo-card">
                <h3 class="text-success"><?= number_format($resumo['novos'] ?? 0) ?></h3>
                <small>NOVOS</small>
            </div>
        </div>
        <div class="col-3">
            <div class="resumo-card">
                <h3 class="text-warning"><?= number_format($resumo['atualizar'] ?? 0) ?></h3>
                <small>ALTERAR</small>
            </div>
        </div>
        <div class="col-3">
            <div class="resumo-card">
                <h3 class="text-secondary"><?= number_format($resumo['sem_alteracao'] ?? 0) ?></h3>
                <small>IGUAL</small>
            </div>
        </div>
    </div>

    <?php if (($resumo['erros'] ?? 0) > 0): ?>
        <div class="alert alert-danger py-2 small mb-3">
            <i class="bi bi-exclamation-triangle me-1"></i>
            <?= $resumo['erros'] ?> linha(s) com erro de leitura serão ignoradas.
        </div>
    <?php endif; ?>

    <!-- Barra de Filtros e Ações em Massa -->
    <div class="acao-massa-bar mb-3">
        <div class="row align-items-center">
            <div class="col-auto">
                <span class="fw-bold small text-uppercase">Filtrar:</span>
            </div>
            <div class="col-auto">
                <div class="btn-group" role="group">
                    <?php
                    $filtros = [
                        'todos' => 'TODOS',
                        'novo' => 'NOVOS',
                        'atualizar' => 'ALTERAR',
                        'sem_alteracao' => 'IGUAL',
                    ];
                    $filtroClasses = [
                        'todos' => 'btn-outline-secondary',
                        'novo' => 'btn-outline-success',
                        'atualizar' => 'btn-outline-warning',
                        'sem_alteracao' => 'btn-outline-secondary',
                    ];
                    foreach ($filtros as $key => $label):
                        $isActive = ($filtroStatus === $key) ? ' active' : '';
                        $btnClass = $filtroClasses[$key] ?? 'btn-outline-secondary';
                        $href = '?id=' . $importacaoId . '&filtro=' . $key . '&pagina=1';
                    ?>
                        <a href="<?= $href ?>" class="btn <?= $btnClass ?> filtro-btn<?= $isActive ?>"
                            onclick="salvarAcoesAntes(event, this.href)">
                            <?= $label ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-12 col-md-auto ms-md-auto mt-2 mt-md-0">
                <span class="fw-bold small text-uppercase me-2 d-block d-md-inline mb-1 mb-md-0">Ação em massa:</span>
                <div class="btn-group me-2" role="group">
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="acaoEmMassa('importar')" title="Aplica apenas aos registros desta página">
                        <i class="bi bi-check"></i> PÁGINA
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="acaoEmMassa('pular')" title="Pular registros desta página">
                        <i class="bi bi-dash-circle"></i> PULAR PÁG.
                    </button>
                </div>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-sm btn-success" onclick="acaoMassaTodos('importar')" title="Importar TODOS os <?= number_format($totalRegistros) ?> registros">
                        <i class="bi bi-check-all"></i> IMPORTAR TODOS (<?= number_format($totalRegistros) ?>)
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="acaoMassaTodos('pular')" title="Pular TODOS os registros">
                        <i class="bi bi-dash-circle"></i> PULAR TODOS
                    </button>
                </div>
            </div>
        </div>
    </div>

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

    <!-- Tabela de Registros -->
    <div class="table-container">
        <table class="table table-sm table-bordered tabela-preview mb-0">
            <thead>
                <tr>
                    <th style="width: 40px">#</th>
                    <th style="width: 80px">STATUS</th>
                    <th style="width: 80px">CÓDIGO</th>
                    <th>DESCRIÇÃO</th>
                    <th>BEM</th>
                    <th>COMPLEMENTO</th>
                    <th>DEPENDÊNCIA</th>
                    <th style="width: 110px">AÇÃO</th>
                </tr>
            </thead>
            <tbody id="tabela-body">
                <?php foreach ($registros as $idx => $reg): ?>
                    <?php
                    $status = $reg['status'] ?? 'erro';
                    $dadosCsv = $reg['dados_csv'] ?? [];
                    $dadosDb = $reg['dados_db'] ?? [];
                    $diferencas = $reg['diferencas'] ?? [];
                    $linhaCsv = $reg['linha_csv'] ?? ($idx + 1);
                    $acaoSugerida = $reg['acao_sugerida'] ?? 'pular';

                    // Se o usuário já salvou uma ação para esta linha, usar ela
                    if (isset($acoesSalvas[(string)$linhaCsv])) {
                        $acaoSugerida = $acoesSalvas[(string)$linhaCsv];
                    }

                    $badgeClass = match ($status) {
                        'novo' => 'badge-novo',
                        'atualizar' => 'badge-atualizar',
                        'sem_alteracao' => 'badge-sem-alteracao',
                        'erro' => 'badge-erro',
                        default => 'badge-sem-alteracao'
                    };

                    $statusLabel = match ($status) {
                        'novo' => 'NOVO',
                        'atualizar' => 'ALTERAR',
                        'sem_alteracao' => 'IGUAL',
                        'erro' => 'ERRO',
                        default => $status
                    };
                    ?>
                    <tr class="registro-row <?= $acaoSugerida === 'pular' ? 'acao-pular' : '' ?>"
                        data-status="<?= $status ?>"
                        data-linha="<?= $linhaCsv ?>">
                        <td class="text-center"><?= $linhaCsv ?></td>
                        <td class="text-center">
                            <span class="badge <?= $badgeClass ?>"><?= $statusLabel ?></span>
                        </td>
                        <td class="fw-bold"><?= htmlspecialchars($dadosCsv['codigo'] ?? '') ?></td>

                        <!-- Descrição com diff -->
                        <td class="diff-cell">
                            <?php if (isset($diferencas['descricao_completa'])): ?>
                                <span class="diff-antes"><?= htmlspecialchars($diferencas['descricao_completa']['antes']) ?></span>
                                <br>
                                <span class="diff-depois"><?= htmlspecialchars($diferencas['descricao_completa']['depois']) ?></span>
                            <?php else: ?>
                                <?= htmlspecialchars($dadosCsv['descricao_completa'] ?? '') ?>
                            <?php endif; ?>
                        </td>

                        <!-- Bem com diff -->
                        <td class="diff-cell">
                            <?php if (isset($diferencas['bem'])): ?>
                                <span class="diff-antes"><?= htmlspecialchars($diferencas['bem']['antes']) ?></span>
                                <br>
                                <span class="diff-depois"><?= htmlspecialchars($diferencas['bem']['depois']) ?></span>
                            <?php else: ?>
                                <?= htmlspecialchars($dadosCsv['bem'] ?? '') ?>
                            <?php endif; ?>
                        </td>

                        <!-- Complemento com diff -->
                        <td class="diff-cell">
                            <?php if (isset($diferencas['complemento'])): ?>
                                <span class="diff-antes"><?= htmlspecialchars($diferencas['complemento']['antes']) ?></span>
                                <br>
                                <span class="diff-depois"><?= htmlspecialchars($diferencas['complemento']['depois']) ?></span>
                            <?php else: ?>
                                <?= htmlspecialchars($dadosCsv['complemento'] ?? '') ?>
                            <?php endif; ?>
                        </td>

                        <!-- Dependência com diff -->
                        <td class="diff-cell">
                            <?php if (isset($diferencas['dependencia'])): ?>
                                <span class="diff-antes"><?= htmlspecialchars($diferencas['dependencia']['antes']) ?></span>
                                <br>
                                <span class="diff-depois"><?= htmlspecialchars($diferencas['dependencia']['depois']) ?></span>
                            <?php else: ?>
                                <?= htmlspecialchars($dadosCsv['dependencia_descricao'] ?? '') ?>
                            <?php endif; ?>
                        </td>

                        <!-- Ação -->
                        <td class="text-center">
                            <?php if ($status === 'erro'): ?>
                                <span class="text-danger small"><i class="bi bi-x-circle"></i> <?= htmlspecialchars($reg['erro'] ?? 'Erro') ?></span>
                                <input type="hidden" name="acao[<?= $linhaCsv ?>]" value="pular">
                            <?php else: ?>
                                <select name="acao[<?= $linhaCsv ?>]"
                                    class="form-select form-select-sm select-acao"
                                    onchange="atualizarEstiloLinha(this)">
                                    <option value="importar" <?= $acaoSugerida === 'importar' ? 'selected' : '' ?>>
                                        <?= $status === 'novo' ? '✚ Importar' : '✎ Atualizar' ?>
                                    </option>
                                    <option value="pular" <?= $acaoSugerida === 'pular' ? 'selected' : '' ?>>
                                        ⊘ Pular
                                    </option>
                                    <?php if ($status !== 'novo'): ?>
                                        <option value="excluir">
                                            ✕ Excluir
                                        </option>
                                    <?php endif; ?>
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
                            onclick="salvarAcoesAntes(event, '?id=<?= $importacaoId ?>&filtro=<?= $filtroStatus ?>&pagina=<?= $paginaAtual - 1 ?>')">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                <?php endif; ?>

                <?php
                $inicio = max(1, $paginaAtual - 3);
                $fim = min($totalPaginas, $paginaAtual + 3);
                if ($inicio > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="#"
                            onclick="salvarAcoesAntes(event, '?id=<?= $importacaoId ?>&filtro=<?= $filtroStatus ?>&pagina=1')">1</a>
                    </li>
                    <?php if ($inicio > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $inicio; $i <= $fim; $i++): ?>
                    <li class="page-item <?= $i == $paginaAtual ? 'active' : '' ?>">
                        <a class="page-link" href="#"
                            onclick="salvarAcoesAntes(event, '?id=<?= $importacaoId ?>&filtro=<?= $filtroStatus ?>&pagina=<?= $i ?>')">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>

                <?php if ($fim < $totalPaginas): ?>
                    <?php if ($fim < $totalPaginas - 1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                    <li class="page-item">
                        <a class="page-link" href="#"
                            onclick="salvarAcoesAntes(event, '?id=<?= $importacaoId ?>&filtro=<?= $filtroStatus ?>&pagina=<?= $totalPaginas ?>')">
                            <?= $totalPaginas ?>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($paginaAtual < $totalPaginas): ?>
                    <li class="page-item">
                        <a class="page-link" href="#"
                            onclick="salvarAcoesAntes(event, '?id=<?= $importacaoId ?>&filtro=<?= $filtroStatus ?>&pagina=<?= $paginaAtual + 1 ?>')">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>

    <!-- Barra de Confirmação Fixa -->
    <div class="card mt-3">
        <div class="card-body d-flex justify-content-between align-items-center py-2">
            <div>
                <span class="text-muted small" id="contadores-acoes">
                    Calculando...
                </span>
            </div>
            <div>
                <a href="/planilhas/importar" class="btn btn-outline-secondary me-2">
                    <i class="bi bi-x-lg me-1"></i>CANCELAR
                </a>
                <button type="submit" class="btn btn-primary" id="btn-confirmar"
                    onclick="return salvarAntesDeConfirmar()">
                    <i class="bi bi-check-lg me-1"></i>CONFIRMAR IMPORTAÇÃO
                </button>
            </div>
        </div>
    </div>
</form>

<script>
    (() => {
        'use strict';

        const IMPORTACAO_ID = <?= (int)$importacaoId ?>;

        // ─── Coletar ações da página atual ───
        function coletarAcoesPagina() {
            const acoes = {};
            document.querySelectorAll('.select-acao').forEach(select => {
                const row = select.closest('tr');
                const linha = row?.dataset?.linha;
                if (linha) acoes[linha] = select.value;
            });
            // Incluir hidden inputs (erros)
            document.querySelectorAll('input[type="hidden"][name^="acao["]').forEach(input => {
                const match = input.name.match(/acao\[(\d+)\]/);
                if (match) acoes[match[1]] = input.value;
            });
            return acoes;
        }

        // ─── Salvar ações via AJAX ───
        async function salvarAcoes() {
            const acoes = coletarAcoesPagina();
            if (Object.keys(acoes).length === 0) return true;

            try {
                const resp = await fetch('/planilhas/preview/salvar-acoes', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        importacao_id: IMPORTACAO_ID,
                        acoes: acoes
                    })
                });
                const data = await resp.json();
                return data.sucesso === true;
            } catch (e) {
                console.error('Erro ao salvar ações:', e);
                return false;
            }
        }

        // ─── Salvar ações antes de navegar (paginação/filtros) ───
        window.salvarAcoesAntes = async function(e, url) {
            e.preventDefault();
            await salvarAcoes();
            window.location.href = url;
        };

        // ─── Salvar antes de confirmar ───
        window.salvarAntesDeConfirmar = function() {
            // As ações da página atual são enviadas pelo form normalmente
            // As ações anteriores já estão na sessão
            return true;
        };

        // ─── Atualizar estilo da linha conforme ação selecionada ───
        window.atualizarEstiloLinha = function(select) {
            const row = select.closest('tr');
            row.classList.remove('acao-pular', 'acao-excluir');

            if (select.value === 'pular') {
                row.classList.add('acao-pular');
            } else if (select.value === 'excluir') {
                row.classList.add('acao-excluir');
            }

            atualizarContadores();
        };

        // ─── Ação em massa — PÁGINA ATUAL ───
        window.acaoEmMassa = function(acao) {
            document.querySelectorAll('.registro-row').forEach(row => {
                const select = row.querySelector('.select-acao');
                if (!select) return;

                const opcao = select.querySelector(`option[value="${acao}"]`);
                if (opcao) {
                    select.value = acao;
                    atualizarEstiloLinha(select);
                }
            });
        };

        // ─── Ação em massa — TODOS OS REGISTROS (todas as páginas) ───
        window.acaoMassaTodos = async function(acao) {
            const label = acao === 'importar' ? 'IMPORTAR' : 'PULAR';
            if (!confirm(`Aplicar "${label}" a TODOS os registros de todas as páginas?`)) return;

            // Primeiro salva ações da página atual
            await salvarAcoes();

            try {
                const resp = await fetch('/planilhas/preview/acao-massa', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        importacao_id: IMPORTACAO_ID,
                        acao: acao
                    })
                });
                const data = await resp.json();
                if (data.sucesso) {
                    // Atualiza os selects da página atual para refletir
                    acaoEmMassa(acao);
                    alert(`${label} aplicado a ${data.total_aplicadas.toLocaleString()} registros.`);
                } else {
                    alert('Erro: ' + (data.erro || 'Falha ao aplicar ação'));
                }
            } catch (e) {
                console.error('Erro ação em massa:', e);
                alert('Erro de conexão ao aplicar ação.');
            }
        };

        // ─── Contadores de ações (só da página atual) ───
        function atualizarContadores() {
            let importar = 0,
                pular = 0,
                excluir = 0;

            document.querySelectorAll('.select-acao').forEach(select => {
                switch (select.value) {
                    case 'importar':
                        importar++;
                        break;
                    case 'pular':
                        pular++;
                        break;
                    case 'excluir':
                        excluir++;
                        break;
                }
            });

            document.querySelectorAll('input[type="hidden"][name^="acao"]').forEach(() => {
                pular++;
            });

            document.getElementById('contadores-acoes').innerHTML =
                `<strong class="text-success">${importar}</strong> importar · ` +
                `<strong class="text-secondary">${pular}</strong> pular · ` +
                `<strong class="text-danger">${excluir}</strong> excluir` +
                ` <span class="text-muted">(esta página)</span>`;
        }

        // ─── Confirmação antes de submeter ───
        document.getElementById('form-confirmar').addEventListener('submit', function(e) {
            let excluir = 0;
            document.querySelectorAll('.select-acao').forEach(select => {
                if (select.value === 'excluir') excluir++;
            });

            if (excluir > 0) {
                if (!confirm(`Atenção: ${excluir} produto(s) serão DESATIVADOS. Confirmar?`)) {
                    e.preventDefault();
                    return;
                }
            }
        });

        // Inicializa contadores e estilos
        atualizarContadores();
        document.querySelectorAll('.select-acao').forEach(select => {
            atualizarEstiloLinha(select);
        });
    })();
</script>

<?php
$contentHtml = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
?>