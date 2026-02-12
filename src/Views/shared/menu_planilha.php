<?php
require_once dirname(__DIR__, 2) . '/config/bootstrap.php';

$id_planilha = $_GET['id'] ?? null;

if (!$id_planilha) {
    header('Location: ' . base_url('/'));
    exit;
}


$pageTitle = "Menu";
$backUrl = '/planilhas/visualizar?id=' . $id_planilha;


ob_start();
?>

<style>
    .menu-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    .menu-card {
        transition: transform 0.2s, box-shadow 0.2s;
        cursor: pointer;
    }

    .menu-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .menu-card.disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .menu-card.disabled:hover {
        transform: none;
    }


    .menu-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    .menu-card {
        transition: transform 0.2s, box-shadow 0.2s;
        cursor: pointer;
    }

    .menu-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .menu-card.disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .menu-card.disabled:hover {
        transform: none;
    }
</style>




<div class="menu-grid">
    <a href="/produtos?id=<?php echo $id_planilha; ?>" class="text-decoration-none">
        <div class="card menu-card">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-plus-circle-fill me-2" style="color: #28a745;"></i>
                    <?php echo htmlspecialchars(to_uppercase('Cadastrar Produto'), ENT_QUOTES, 'UTF-8'); ?>
                </h5>
                <p class="card-text small text-muted">Adicionar novo produto manualmente</p>
            </div>
        </div>
    </a>

    <a href="/planilhas/importar" class="text-decoration-none">
        <div class="card menu-card">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-file-earmark-arrow-up-fill me-2" style="color: #28a745;"></i>
                    <?php echo htmlspecialchars(to_uppercase('Importar Nova Planilha'), ENT_QUOTES, 'UTF-8'); ?>
                </h5>
                <p class="card-text small text-muted">Importar uma nova planilha CSV</p>
            </div>
        </div>
    </a>

    <a href="/relatorios/visualizar?id=<?php echo $id_planilha; ?>&form=14.1" class="text-decoration-none">
        <div class="card menu-card">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-file-earmark-text me-2" style="color: #667eea;"></i>
                    <?php echo htmlspecialchars(to_uppercase('Formulário 14.1'), ENT_QUOTES, 'UTF-8'); ?>
                </h5>
                <p class="card-text small text-muted">Declaração de Doação de Bem Móvel</p>
            </div>
        </div>
    </a>

    <a href="/relatorios/visualizar?id=<?php echo $id_planilha; ?>&form=14.2" class="text-decoration-none">
        <div class="card menu-card">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-file-earmark-arrow-down me-2" style="color: #28a745;"></i>
                    <?php echo htmlspecialchars(to_uppercase('Formulário 14.2'), ENT_QUOTES, 'UTF-8'); ?>
                </h5>
                <p class="card-text small text-muted">Ocorrência de Entrada de Bens</p>
            </div>
        </div>
    </a>

    <a href="/relatorios/visualizar?id=<?php echo $id_planilha; ?>&form=14.3" class="text-decoration-none">
        <div class="card menu-card">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-file-earmark-arrow-up me-2" style="color: #dc3545;"></i>
                    <?php echo htmlspecialchars(to_uppercase('Formulário 14.3'), ENT_QUOTES, 'UTF-8'); ?>
                </h5>
                <p class="card-text small text-muted">Declaração de Saída de Bens</p>
            </div>
        </div>
    </a>

    <a href="/relatorios/visualizar?id=<?php echo $id_planilha; ?>&form=14.4" class="text-decoration-none">
        <div class="card menu-card">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-box-arrow-right me-2" style="color: #ffc107;"></i>
                    <?php echo htmlspecialchars(to_uppercase('Formulário 14.4'), ENT_QUOTES, 'UTF-8'); ?>
                </h5>
                <p class="card-text small text-muted">Declaração de Retirada de Bem</p>
            </div>
        </div>
    </a>

    <a href="/relatorios/visualizar?id=<?php echo $id_planilha; ?>&form=14.5" class="text-decoration-none">
        <div class="card menu-card">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-clipboard-check me-2" style="color: #17a2b8;"></i>
                    <?php echo htmlspecialchars(to_uppercase('Formulário 14.5'), ENT_QUOTES, 'UTF-8'); ?>
                </h5>
                <p class="card-text small text-muted">Ata de Inventário de Bens</p>
            </div>
        </div>
    </a>

    <a href="/relatorios/visualizar?id=<?php echo $id_planilha; ?>&form=14.6" class="text-decoration-none">
        <div class="card menu-card">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-pencil-square me-2" style="color: #6f42c1;"></i>
                    <?php echo htmlspecialchars(to_uppercase('Formulário 14.6'), ENT_QUOTES, 'UTF-8'); ?>
                </h5>
                <p class="card-text small text-muted">Alteração de Cadastro de Bem</p>
            </div>
        </div>
    </a>

    <a href="/relatorios/visualizar?id=<?php echo $id_planilha; ?>&form=14.7" class="text-decoration-none">
        <div class="card menu-card">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-arrow-left-right me-2" style="color: #e83e8c;"></i>
                    <?php echo htmlspecialchars(to_uppercase('Formulário 14.7'), ENT_QUOTES, 'UTF-8'); ?>
                </h5>
                <p class="card-text small text-muted">Movimentação Interna de Bem</p>
            </div>
        </div>
    </a>

    <a href="/relatorios/visualizar?id=<?php echo $id_planilha; ?>&form=14.8" class="text-decoration-none">
        <div class="card menu-card">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-calendar3 me-2" style="color: #fd7e14;"></i>
                    <?php echo htmlspecialchars(to_uppercase('Formulário 14.8'), ENT_QUOTES, 'UTF-8'); ?>
                </h5>
                <p class="card-text small text-muted">Movimento Mensal de Bem</p>
            </div>
        </div>
    </a>

    <a href="/produtos/etiqueta?id=<?php echo $id_planilha; ?>" class="text-decoration-none">
        <div class="card menu-card">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-tags-fill me-2" style="color: #ff9800;"></i>
                    <?php echo htmlspecialchars(to_uppercase('Copiar Etiquetas'), ENT_QUOTES, 'UTF-8'); ?>
                </h5>
                <p class="card-text small text-muted">Copiar etiquetas selecionadas</p>
            </div>
        </div>
    </a>

    <a href="/relatorios/visualizar?id=<?php echo $id_planilha; ?>&form=alteracao" class="text-decoration-none">
        <div class="card menu-card">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-file-earmark-diff-fill me-2" style="color: #9c27b0;"></i>
                    <?php echo htmlspecialchars(to_uppercase('Imprimir Alterações'), ENT_QUOTES, 'UTF-8'); ?>
                </h5>
                <p class="card-text small text-muted">Relatório de alteraçÁµes realizadas</p>
            </div>
        </div>
    </a>

    <div class="card menu-card disabled">
        <div class="card-body">
            <h5 class="card-title">
                <i class="bi bi-gear-fill me-2" style="color: #6c757d;"></i>
                <?php echo htmlspecialchars(to_uppercase('Em Desenvolvimento'), ENT_QUOTES, 'UTF-8'); ?>
            </h5>
            <p class="card-text small text-muted">Funcionalidade em breve</p>
        </div>
    </div>
</div>

<?php

$contentHtml = ob_get_clean();


$tempFile = __DIR__ . '/../../../temp_menu_content_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;


include __DIR__ . '/../layouts/app_wrapper.php';


unlink($tempFile);
?>