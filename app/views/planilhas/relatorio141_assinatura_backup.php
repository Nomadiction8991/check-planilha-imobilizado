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
            p.id_PRODUTO as id,
            p.descricao_completa,
            p.bem as tipo_ben,
            tb.descricao as tipo_descricao,
            COALESCE(a.STATUS, 'pendente') as STATUS_assinatura,
            a.token,
            a.id as id_assinatura
        FROM PRODUTOS p
        LEFT JOIN tipos_bens tb ON p.tipo_bem_id = tb.id
        LEFT JOIN assinaturas_14_1 a ON a.id_PRODUTO = p.id_PRODUTO
        WHERE p.planilha_id = :id_planilha 
        AND p.imprimir_14_1 = 1
        ORDER BY p.id_PRODUTO ASC";

$stmt = $conexao->prepare($sql);
$stmt->bindValue(':id_planilha', $id_planilha);
$stmt->execute();
$PRODUTOS = $stmt->fetchAll();

$pageTitle = 'Assinar Documentos 14.1';
$backUrl = '../planilhas/relatorio141_view.php?id=' . urlencode($id_planilha);
$headerActions = '
    <div class="dropdown">
        <button class="btn-header-action" type="button" id="menuAssinatura" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-list fs-5"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="menuAssinatura">
            <li>
                <a class="dropdown-item" href="../../../logout.php">
                    <i class="bi bi-box-arrow-right me-2"></i>Sair
                </a>
            </li>
        </ul>
    </div>
';

// Gerar URL de compartilhamento desta pÃƒÂ¡gina (inclui parÃƒÂ¢metros atuais)
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? '';
$uri  = $_SERVER['REQUEST_URI'] ?? ('/app/views/planilhas/relatorio141_assinatura.php?id=' . urlencode($id_planilha));
$url_compartilhar = $scheme . '://' . $host . $uri;

ob_start();
?>

<style>
.PRODUTO-card {
    transition: all 0.2s;
    border: 3px solid #dee2e6;
    border-radius: 0.375rem;
    cursor: pointer;
}

.PRODUTO-card:hover {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.PRODUTO-card.STATUS-pendente {
    border-color: #ffc107;
}

.PRODUTO-card.STATUS-assinado {
    border-color: #28a745;
}

.PRODUTO-card.selected {
    background-color: #e7f3ff;
    border-color: #007bff !important;
    box-shadow: 0 0 0 2px #007bff;
}

.checkbox-PRODUTO {
    width: 1.25rem;
    height: 1.25rem;
    cursor: pointer;
}

.selection-toolbar {
    position: sticky;
    top: 60px;
    z-index: 100;
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 1rem;
    border-radius: 0.375rem;
    margin-bottom: 1rem;
    display: none;
}

.selection-toolbar.active {
    display: block;
}
 .legenda-STATUS {
     display: flex;
     gap: 1.5rem;
     flex-wrap: wrap;
     align-items: center;
 }
 
 .legenda-item {
     display: flex;
     align-items: center;
     gap: 0.5rem;
     font-size: 0.875rem;
 }
 
 .legenda-cor {
     width: 30px;
     height: 20px;
     border-radius: 3px;
     border: 3px solid;
 }
 
 .legenda-cor.pendente {
     border-color: #ffc107;
 }
 
 .legenda-cor.assinado {
     border-color: #28a745;
 }
</style>

<!-- Card com Link de Compartilhamento da PÃƒÂ¡gina -->
<div class="card mb-3">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-share me-2"></i>
        Link para Compartilhamento desta PÃƒÂ¡gina
    </div>
    <div class="card-body">
        <p class="mb-3">
            <i class="bi bi-info-circle me-1"></i>
            Envie este link para a pessoa que vai assinar os documentos desta planilha.
        </p>
        <div class="input-group">
            <input type="text" class="form-control" id="linkCompartilharSelecao" value="<?php echo htmlspecialchars($url_compartilhar); ?>" readonly>
            <button class="btn btn-primary" type="button" onclick="copiarLinkSelecao()">
                <i class="bi bi-clipboard me-1"></i>
                Copiar
            </button>
        </div>
        <small class="text-muted d-block mt-2">
            <i class="bi bi-shield-check me-1"></i>
            O link abre esta pÃƒÂ¡gina para seleÃƒÂ§ÃƒÂ£o e assinatura.
        </small>
    </div>
    
</div>

<div class="card mb-3">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-pen me-2"></i>
        Selecione os PRODUTOS para Assinar
    </div>
    <div class="card-body">
        <p class="mb-3">
            <i class="bi bi-info-circle me-1"></i>
            Clique no PRODUTO para selecionÃƒÂ¡-lo. VocÃƒÂª pode selecionar vÃƒÂ¡rios PRODUTOS para assinar todos de uma vez.
        </p>
        <!-- Legenda de STATUS -->
        <div class="legenda-STATUS">
            <div class="legenda-item">
                <div class="legenda-cor pendente"></div>
                <span>Pendente</span>
            </div>
            <div class="legenda-item">
                <div class="legenda-cor assinado"></div>
                <span>Assinado</span>
            </div>
        </div>
    </div>
</div>

<!-- Barra de ferramentas flutuante -->
<div class="selection-toolbar" id="selectionToolbar">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <strong><span id="countSelected">0</span> PRODUTO(s) selecionado(s)</strong>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-success" onclick="assinarSelecionados()">
                <i class="bi bi-pen-fill me-1"></i>
                Assinar Selecionados
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="limparSelecao()">
                <i class="bi bi-x me-1"></i>
                CANCELAR
            </button>
        </div>
    </div>
</div>

<?php if (count($PRODUTOS) > 0): ?>
    <div class="row g-3">
        <?php foreach ($PRODUTOS as $PRODUTO): ?>
            <div class="col-12">
                <div class="card PRODUTO-card STATUS-<?php echo $PRODUTO['STATUS_assinatura']; ?>" 
                     data-PRODUTO-id="<?php echo $PRODUTO['id']; ?>"
                     onclick="togglePRODUTO(<?php echo $PRODUTO['id']; ?>)">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="d-flex align-items-start gap-3 flex-grow-1">
                                <div class="form-check">
                                    <input class="form-check-input checkbox-PRODUTO" 
                                           type="checkbox" 
                                           id="PRODUTO_<?php echo $PRODUTO['id']; ?>"
                                           value="<?php echo $PRODUTO['id']; ?>"
                                           onclick="event.stopPropagation();">
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="card-title mb-2">
                                        <i class="bi bi-box-seam me-1"></i>
                                        PRODUTO #<?php echo $PRODUTO['id']; ?>
                                    </h6>
                                    <p class="card-text small mb-2">
                                        <strong>Tipo:</strong> 
                                        <?php echo htmlspecialchars($PRODUTO['tipo_descricao'] ?? 'N/A'); ?>
                                    </p>
                                    <p class="card-text small text-muted mb-0" style="max-height: 3em; overflow: hidden;">
                                        <?php echo htmlspecialchars(substr($PRODUTO['descricao_completa'], 0, 150)); ?>
                                        <?php if (strlen($PRODUTO['descricao_completa']) > 150): ?>...<?php endif; ?>
                                    </p>
                                </div>
                            </div>
                            <div class="ms-3">
                                <!-- EspaÃƒÂ§o reservado para conteÃƒÂºdo adicional se necessÃƒÂ¡rio -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="alert alert-warning">
        <i class="bi bi-info-circle me-2"></i> Nenhum PRODUTO encontrado para assinatura.
    </div>
<?php endif; ?>

<script>
let PRODUTOSSelecionados = new Set();

function togglePRODUTO(id) {
    const checkbox = document.getElementById('PRODUTO_' + id);
    const card = document.querySelector(`[data-PRODUTO-id="${id}"]`);
    
    if (checkbox.checked) {
        checkbox.checked = false;
        PRODUTOSSelecionados.delete(id);
        card.classList.remove('selected');
    } else {
        checkbox.checked = true;
        PRODUTOSSelecionados.add(id);
        card.classList.add('selected');
    }
    
    atualizarToolbar();
}

function atualizarToolbar() {
    const toolbar = document.getElementById('selectionToolbar');
    const counter = document.getElementById('countSelected');
    const count = PRODUTOSSelecionados.size;
    
    counter.textContent = count;
    
    if (count > 0) {
        toolbar.classList.add('active');
    } else {
        toolbar.classList.remove('active');
    }
}

function selecionarTodos() {
    const checkboxes = document.querySelectorAll('.checkbox-PRODUTO');
    checkboxes.forEach(cb => {
        const id = parseInt(cb.value);
        cb.checked = true;
        PRODUTOSSelecionados.add(id);
        const card = document.querySelector(`[data-PRODUTO-id="${id}"]`);
        if (card) card.classList.add('selected');
    });
    atualizarToolbar();
}

function limparSelecao() {
    const checkboxes = document.querySelectorAll('.checkbox-PRODUTO');
    checkboxes.forEach(cb => {
        cb.checked = false;
        const card = document.querySelector(`[data-PRODUTO-id="${cb.value}"]`);
        if (card) card.classList.remove('selected');
    });
    PRODUTOSSelecionados.clear();
    atualizarToolbar();
}

function assinarSelecionados() {
    if (PRODUTOSSelecionados.size === 0) {
        alert('Selecione pelo menos um PRODUTO para assinar.');
        return;
    }
    
    const ids = Array.from(PRODUTOSSelecionados).join(',');
    window.location.href = './relatorio141_assinatura_form.php?ids=' + ids + '&id_planilha=<?php echo $id_planilha; ?>';
}

// Listener nos checkboxes para sincronizar com a seleÃƒÂ§ÃƒÂ£o
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.checkbox-PRODUTO');
    checkboxes.forEach(cb => {
        cb.addEventListener('change', function(e) {
            const id = parseInt(this.value);
            const card = document.querySelector(`[data-PRODUTO-id="${id}"]`);
            
            if (this.checked) {
                PRODUTOSSelecionados.add(id);
                if (card) card.classList.add('selected');
            } else {
                PRODUTOSSelecionados.delete(id);
                if (card) card.classList.remove('selected');
            }
            
            atualizarToolbar();
        });
    });
});

function copiarLinkSelecao() {
    const input = document.getElementById('linkCompartilharSelecao');
    input.select();
    input.setSelectionRange(0, 99999);
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(input.value).then(() => {
            alert('Link copiado!');
        }).catch(() => {
            document.execCommand('copy');
            alert('Link copiado!');
        });
    } else {
        document.execCommand('copy');
        alert('Link copiado!');
    }
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


