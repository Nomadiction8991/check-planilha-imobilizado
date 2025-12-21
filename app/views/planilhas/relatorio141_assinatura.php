<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
// AutenticaÃƒÂ§ÃƒÂ£o

$id_planilha = $_GET['id'] ?? null;

if (!$id_planilha) {
    header('Location: ../../../index.php');
    exit;
}

// BUSCAR PRODUTOS que podem ser assinados (imprimir_14_1 = 1)
$sql = "SELECT 
            p.id_PRODUTO,
            p.descricao_completa,
            p.tipo_bem_id,
            p.condicao_14_1,
            p.doador_conjugue_id,
            tb.descricao as tipo_descricao,
            u.nome as doador_nome
        FROM PRODUTOS p
        LEFT JOIN tipos_bens tb ON p.tipo_bem_id = tb.id
        LEFT JOIN usuarios u ON p.doador_conjugue_id = u.id
        WHERE p.comum_id = :id_comum 
        AND p.imprimir_14_1 = 1
        ORDER BY p.id_PRODUTO ASC";

$stmt = $conexao->prepare($sql);
$stmt->bindValue(':id_comum', $id_planilha);
$stmt->execute();
$PRODUTOS = $stmt->fetchAll();

// Calcular estatÃƒÂ­sticas
$total_PRODUTOS = count($PRODUTOS);
$PRODUTOS_assinados = 0;
$doacoes_por_pessoa = [];

foreach ($PRODUTOS as $PRODUTO) {
    // Verificar se estÃƒÂ¡ assinado: doador_conjugue_id diferente de NULL e diferente de 0
    if (!is_null($PRODUTO['doador_conjugue_id']) && $PRODUTO['doador_conjugue_id'] > 0) {
        $PRODUTOS_assinados++;
        $nome_doador = $PRODUTO['doador_nome'] ?? 'Sem nome';
        if (!isset($doacoes_por_pessoa[$nome_doador])) {
            $doacoes_por_pessoa[$nome_doador] = 0;
        }
        $doacoes_por_pessoa[$nome_doador]++;
    }
}

// Ordenar por quantidade de doaÃƒÂ§ÃƒÂµes
arsort($doacoes_por_pessoa);

$pageTitle = 'Assinar Documentos 14.1';
$backUrl = 'relatorio141_view.php?id=' . urlencode($id_planilha) . '&comum_id=' . urlencode($id_planilha);
$headerActions = '';

ob_start();
?>

<style>
    .PRODUTO-card {
        transition: all 0.2s;
        border: 1px solid #dee2e6;
        border-left: 4px solid #dee2e6;
        border-radius: 0.375rem;
        cursor: pointer;
    }

    .PRODUTO-card:hover {
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .PRODUTO-card.assinado {
        border-left-color: #28a745;
    }

    .PRODUTO-card.pendente {
        border-left-color: #ffc107;
    }

    .PRODUTO-card.selected {
        background-color: #e7f3ff;
        border-color: #007bff !important;
        border-left-color: #007bff !important;
    }

    .doador-tag {
        display: inline-block;
        background: #28a745;
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 1rem;
        font-size: 0.875rem;
        font-weight: 500;
        margin-bottom: 0.5rem;
    }

    .stats-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 0.5rem;
        padding: 1.5rem;
    }

    .stats-number {
        font-size: 2.5rem;
        font-weight: bold;
        line-height: 1;
    }

    .doacoes-list {
        max-height: 200px;
        overflow-y: auto;
    }

    .doacao-item {
        display: flex;
        justify-content: space-between;
        padding: 0.5rem 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    }

    .doacao-item:last-child {
        border-bottom: none;
    }
</style>

<?php if (isset($_SESSION['sucesso'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        <?php echo htmlspecialchars(to_uppercase($_SESSION['sucesso']), ENT_QUOTES, 'UTF-8'); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php unset($_SESSION['sucesso']);
endif; ?>

<?php if (isset($_SESSION['erro'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <?php echo htmlspecialchars($_SESSION['erro']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php unset($_SESSION['erro']);
endif; ?>

<!-- Resumo Informativo -->
<div class="alert alert-info mb-4">
    <h5 class="alert-heading mb-3">
        <i class="bi bi-info-circle-fill me-2"></i>
        InformaÃƒÂ§ÃƒÂµes sobre as Assinaturas
    </h5>

    <div class="mb-3">
        <strong>Total de PRODUTOS nesta planilha:</strong> <?php echo $total_PRODUTOS; ?>
        (<?php echo $PRODUTOS_assinados; ?> assinados, <?php echo $total_PRODUTOS - $PRODUTOS_assinados; ?> pendentes)
    </div>

    <?php if (!empty($doacoes_por_pessoa)): ?>
        <div class="mb-2">
            <strong>PRODUTOS jÃƒÂ¡ assinados por:</strong>
        </div>
        <ul class="mb-0">
            <?php foreach ($doacoes_por_pessoa as $nome => $quantidade): ?>
                <li>
                    <strong><?php echo htmlspecialchars($nome); ?></strong> -
                    <?php echo $quantidade; ?> <?php echo $quantidade == 1 ? 'PRODUTO' : 'PRODUTOS'; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <div class="text-muted">
            <em>Nenhum PRODUTO foi assinado ainda.</em>
        </div>
    <?php endif; ?>
</div>

<div class="card mb-3">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-pen me-2"></i>
        PRODUTOS para Assinar
    </div>
    <div class="card-body">
        <p class="mb-2">
            <i class="bi bi-info-circle me-1"></i>
            <strong>Selecione</strong> um ou mais PRODUTOS e clique em "Assinar Selecionados" para assinar todos de uma vez.
        </p>
        <p class="mb-0 text-muted small">
            Ou clique diretamente em um PRODUTO individual para assinÃƒÂ¡-lo separadamente.
        </p>
    </div>
</div>

<!-- Barra de aÃƒÂ§ÃƒÂµes para PRODUTOS selecionados -->
<div class="alert alert-success mb-3" id="toolbarSelecao" style="display: none;">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <strong><span id="contadorSelecionados">0</span> PRODUTO(s) selecionado(s)</strong>
        </div>
        <div>
            <button type="button" class="btn btn-success btn-sm" id="btnAssinarSelecionados" onclick="assinarSelecionados()">
                <i class="bi bi-check2-all me-1"></i>
                Assinar Selecionados
            </button>
            <button type="button" class="btn btn-danger btn-sm" id="btnDesfazerSelecionados" style="display:none" onclick="desfazerSelecionados()">
                <i class="bi bi-arrow-counterclockwise me-1"></i>
                Desfazer Assinatura
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="limparSelecao()">
                <i class="bi bi-x-lg me-1"></i>
                LIMPAR SeleÃƒÂ§ÃƒÂ£o
            </button>
        </div>
    </div>
</div>

<?php if (count($PRODUTOS) > 0): ?>
    <div class="row g-3">
        <?php foreach ($PRODUTOS as $PRODUTO):
            // Verificar se estÃƒÂ¡ assinado: doador_conjugue_id diferente de NULL e diferente de 0
            $assinado = (!is_null($PRODUTO['doador_conjugue_id']) && $PRODUTO['doador_conjugue_id'] > 0);
            $STATUS_class = $assinado ? 'assinado' : 'pendente';
        ?>
            <div class="col-12">
                <div class="card PRODUTO-card <?php echo $STATUS_class; ?>"
                    data-PRODUTO-id="<?php echo $PRODUTO['id_PRODUTO']; ?>">
                    <div class="card-body">
                        <div class="d-flex gap-3">
                            <!-- Checkbox de seleÃƒÂ§ÃƒÂ£o -->
                            <div class="form-check">
                                <input class="form-check-input"
                                    type="checkbox"
                                    id="check_<?php echo $PRODUTO['id_PRODUTO']; ?>"
                                    value="<?php echo $PRODUTO['id_PRODUTO']; ?>"
                                    data-assinado="<?php echo $assinado ? '1' : '0'; ?>"
                                    onchange="atualizarSelecao()"
                                    style="width: 1.25rem; height: 1.25rem; cursor: pointer;">
                            </div>

                            <!-- ConteÃƒÂºdo do PRODUTO -->
                            <div class="flex-grow-1" onclick="abrirAssinatura(<?php echo $PRODUTO['id_PRODUTO']; ?>)" style="cursor: pointer;">
                                <?php if ($assinado): ?>
                                    <div class="doador-tag">
                                        <i class="bi bi-check-circle-fill me-1"></i>
                                        Doado por: <?php echo htmlspecialchars($PRODUTO['doador_nome']); ?>
                                    </div>
                                <?php endif; ?>

                                <h6 class="card-title mb-2">
                                    <i class="bi bi-box-seam me-1"></i>
                                    <?php echo htmlspecialchars($PRODUTO['tipo_descricao'] ?? 'PRODUTO'); ?>
                                </h6>
                                <p class="card-text small text-muted mb-0">
                                    <?php echo htmlspecialchars(substr($PRODUTO['descricao_completa'], 0, 150)); ?>
                                    <?php if (strlen($PRODUTO['descricao_completa']) > 150): ?>...<?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
            <h5 class="text-muted">Nenhum PRODUTO para assinar</h5>
            <p class="text-muted small mb-0">
                Certifique-se de que existem PRODUTOS marcados para impressÃƒÂ£o no relatÃƒÂ³rio 14.1
            </p>
        </div>
    </div>
<?php endif; ?>

<script>
    let PRODUTOSSelecionados = new Set();

    function atualizarSelecao() {
        PRODUTOSSelecionados.clear();
        document.querySelectorAll('.form-check-input:checked').forEach(checkbox => {
            PRODUTOSSelecionados.add(parseInt(checkbox.value));
        });

        const toolbar = document.getElementById('toolbarSelecao');
        const contador = document.getElementById('contadorSelecionados');
        const btnAssinar = document.getElementById('btnAssinarSelecionados');
        const btnDesfazer = document.getElementById('btnDesfazerSelecionados');

        contador.textContent = PRODUTOSSelecionados.size;

        if (PRODUTOSSelecionados.size > 0) {
            toolbar.style.display = 'block';
            // Se todos selecionados estÃƒÂ£o assinados, mostra apenas Desfazer; caso contrÃƒÂ¡rio, mostra Assinar
            const todosAssinados = Array.from(document.querySelectorAll('.form-check-input:checked'))
                .every(cb => cb.getAttribute('data-assinado') === '1');
            if (todosAssinados) {
                btnDesfazer.style.display = 'inline-block';
                btnAssinar.style.display = 'none';
            } else {
                btnDesfazer.style.display = 'none';
                btnAssinar.style.display = 'inline-block';
            }
        } else {
            toolbar.style.display = 'none';
            btnDesfazer.style.display = 'none';
            btnAssinar.style.display = 'inline-block';
        }
    }

    function limparSelecao() {
        document.querySelectorAll('.form-check-input').forEach(checkbox => {
            checkbox.checked = false;
        });
        atualizarSelecao();
    }

    function assinarSelecionados() {
        if (PRODUTOSSelecionados.size === 0) {
            alert('Selecione pelo menos um PRODUTO para assinar.');
            return;
        }

        const ids = Array.from(PRODUTOSSelecionados).join(',');
        window.location.href = 'relatorio141_assinatura_form.php?ids=' + ids + '&id_planilha=<?php echo $id_planilha; ?>';
    }

    function abrirAssinatura(id) {
        window.location.href = 'relatorio141_assinatura_form.php?id=' + id + '&id_planilha=<?php echo $id_planilha; ?>';
    }

    function desfazerSelecionados() {
        if (PRODUTOSSelecionados.size === 0) return;
        if (!confirm('Deseja desfazer a assinatura dos itens selecionados e limpar os dados de nota?')) return;
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '../../../app/controllers/update/PRODUTODesassinar141Controller.php';
        PRODUTOSSelecionados.forEach(id => {
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'ids_PRODUTOS[]';
            inp.value = id;
            form.appendChild(inp);
        });
        const pid = document.createElement('input');
        pid.type = 'hidden';
        pid.name = 'id_planilha';
        pid.value = '<?php echo htmlspecialchars($id_planilha, ENT_QUOTES); ?>';
        form.appendChild(pid);
        document.body.appendChild(form);
        form.submit();
    }
</script>

<?php
$contentHtml = ob_get_clean();
$tempFile = __DIR__ . '/../../../temp_assinatura_14_1_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app_wrapper.php';
unlink($tempFile);
?>