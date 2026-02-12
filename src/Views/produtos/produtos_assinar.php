<?php
require_once dirname(__DIR__, 2) . '/Helpers/BootstrapLoader.php';


$id_planilha = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id_planilha) {
    header('Location: ' . base_url('/'));
    exit;
}

$usuario_id = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 0;


$coluna_assinatura = 'administrador_acessor_id';


$sql = "SELECT 
            p.id_produto,
            p.codigo,
            p.descricao_completa,
            p.complemento,
            p.imprimir_14_1,
            p.{$coluna_assinatura} as minha_assinatura,
            tb.descricao as tipo_descricao,
            d.descricao as dependencia_descricao
        FROM produtos p
        LEFT JOIN tipos_bens tb ON p.tipo_bem_id = tb.id
        LEFT JOIN dependencias d ON p.dependencia_id = d.id
        WHERE p.comum_id = :id_comum AND p.ativo = 1
        ORDER BY p.id_produto ASC";
$stmt = $conexao->prepare($sql);
$stmt->bindValue(':id_comum', $id_planilha);
$stmt->execute();
$PRODUTOS = $stmt->fetchAll();

$pageTitle = 'Assinar PRODUTOS';
$backUrl = '/planilhas/visualizar?id=' . $id_planilha . '&comum_id=' . $id_planilha;

ob_start();
?>

<style>
    .PRODUTO-card {
        border-left: 4px solid #dee2e6;
        transition: all 0.3s;
    }

    .PRODUTO-card.assinado {
        border-left-color: #198754;
        background-color: #f8fff8;
    }

    .PRODUTO-card.selecionado {
        border-left-color: #0d6efd;
        background-color: #f0f7ff;
    }

    .PRODUTO-card:hover {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }


    .PRODUTO-card {
        border-left: 4px solid #dee2e6;
        transition: all 0.3s;
    }

    .PRODUTO-card.assinado {
        border-left-color: #198754;
        background-color: #f8fff8;
    }

    .PRODUTO-card.selecionado {
        border-left-color: #0d6efd;
        background-color: #f0f7ff;
    }

    .PRODUTO-card:hover {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
</style>




<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    <strong>Instruções:</strong> Selecione os PRODUTOS que deseja assinar.
    Você está assinando como <strong>Administrador/Acessor</strong>.
</div>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-boxes me-2"></i>
            PRODUTOS Disponíveis
        </span>
        <div>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="selecionarTodos()">
                <i class="bi bi-check-all"></i> Todos
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="desmarcarTodos()">
                <i class="bi bi-x-lg"></i> Nenhum
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($PRODUTOS)): ?>
            <p class="text-muted text-center mb-0">Nenhum PRODUTO disponvel nesta comum.</p>
        <?php else: ?>
            <div id="PRODUTOSContainer">
                <?php foreach ($PRODUTOS as $PRODUTO): ?>
                    <?php
                    $assinado_por_mim = ($PRODUTO['minha_assinatura'] == $usuario_id);
                    $pode_desassinar = $assinado_por_mim;
                    ?>
                    <div class="card PRODUTO-card mb-2 <?php echo $assinado_por_mim ? 'assinado' : ''; ?>" data-PRODUTO-id="<?php echo $PRODUTO['id_produto']; ?>">
                        <div class="card-body py-2">
                            <div class="d-flex align-items-center">
                                <div class="form-check me-3">
                                    <input class="form-check-input PRODUTO-checkbox"
                                        type="checkbox"
                                        value="<?php echo $PRODUTO['id_produto']; ?>"
                                        id="PRODUTO_<?php echo $PRODUTO['id_produto']; ?>">
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold">
                                        <?php echo htmlspecialchars($PRODUTO['codigo'] ?? 'S/N'); ?>
                                        <?php if ($assinado_por_mim): ?>
                                            <span class="badge bg-success ms-2">
                                                <i class="bi bi-check-circle"></i> Assinado por vocª
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($PRODUTO['imprimir_14_1']): ?>
                                            <span class="badge bg-info ms-2">
                                                <i class="bi bi-file-earmark-pdf"></i> No relat³rio 14.1
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="small text-muted">
                                        <?php echo htmlspecialchars($PRODUTO['tipo_descricao'] ?? ''); ?>
                                        <?php if ($PRODUTO['complemento']): ?>
                                            - <?php echo htmlspecialchars($PRODUTO['complemento']); ?>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($PRODUTO['dependencia_descricao']): ?>
                                        <div class="small text-muted">
                                            <i class="bi bi-building"></i>
                                            <?php echo htmlspecialchars($PRODUTO['dependencia_descricao']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($PRODUTOS)): ?>
    <div class="card">
        <div class="card-body">
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-success flex-grow-1" onclick="assinarPRODUTOS()">
                    <i class="bi bi-pen me-1"></i>
                    Assinar Selecionados
                </button>
                <button type="button" class="btn btn-danger flex-grow-1" onclick="desassinarPRODUTOS()">
                    <i class="bi bi-x-circle me-1"></i>
                    Remover Assinatura
                </button>
            </div>
            <small class="text-muted d-block mt-2">
                <i class="bi bi-info-circle"></i>
                Selecione os PRODUTOS acima e clique em "Assinar" ou "Remover Assinatura"
            </small>
        </div>
    </div>
<?php endif; ?>

<script>
    function selecionarTodos() {
        document.querySelectorAll('.PRODUTO-checkbox').forEach(cb => {
            cb.checked = true;
            cb.closest('.PRODUTO-card').classList.add('selecionado');
        });
    }

    function desmarcarTodos() {
        document.querySelectorAll('.PRODUTO-checkbox').forEach(cb => {
            cb.checked = false;
            cb.closest('.PRODUTO-card').classList.remove('selecionado');
        });
    }

    // ATUALIZAR visual ao selecionar
    document.querySelectorAll('.PRODUTO-checkbox').forEach(cb => {
        cb.addEventListener('change', function() {
            if (this.checked) {
                this.closest('.PRODUTO-card').classList.add('selecionado');
            } else {
                this.closest('.PRODUTO-card').classList.remove('selecionado');
            }
        });
    });

    function assinarPRODUTOS() {
        const selecionados = Array.from(document.querySelectorAll('.PRODUTO-checkbox:checked'))
            .map(cb => cb.value);

        if (selecionados.length === 0) {
            alert('Selecione pelo menos um PRODUTO para assinar');
            return;
        }

        if (!confirm(`Deseja assinar ${selecionados.length} PRODUTO(s)?`)) {
            return;
        }

        executarAcao('assinar', selecionados);
    }

    function desassinarPRODUTOS() {
        const selecionados = Array.from(document.querySelectorAll('.PRODUTO-checkbox:checked'))
            .map(cb => cb.value);

        if (selecionados.length === 0) {
            alert('Selecione pelo menos um PRODUTO para remover a assinatura');
            return;
        }

        if (!confirm(`Deseja remover sua assinatura de ${selecionados.length} PRODUTO(s)?`)) {
            return;
        }

        executarAcao('desassinar', selecionados);
    }

    function executarAcao(acao, PRODUTOS) {
        const formData = new FormData();
        formData.append('acao', acao);
        PRODUTOS.forEach(id => formData.append('PRODUTOS[]', id));

        fetch('/produtos/assinar', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Erro: ' + data.message);
                }
            })
            .catch(error => {
                alert('Erro ao processar solicita§£o');
                console.error(error);
            });
    }
</script>

<?php
$contentHtml = ob_get_clean();
$tempFile = __DIR__ . '/../../../temp_assinar_PRODUTOS_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app_wrapper.php';
unlink($tempFile);
?>