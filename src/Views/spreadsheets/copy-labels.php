<?php


$appConfig = require dirname(__DIR__, 3) . '/config/app.php';
$projectRoot = $appConfig['project_root'];
require_once $projectRoot . '/src/Helpers/BootstrapLoader.php';


$id_planilha = $_GET['id'] ?? null;
if (!$id_planilha) {
    header('Location: ' . base_url('/'));
    exit;
}

$comum_id = $id_planilha;


try {
    $sql_planilha = "SELECT id, descricao as comum, cnpj, administracao, cidade FROM comums WHERE id = :id";
    $stmt_planilha = $conexao->prepare($sql_planilha);
    $stmt_planilha->bindValue(':id', $id_planilha);
    $stmt_planilha->execute();
    $planilha = $stmt_planilha->fetch();
    if (!$planilha) throw new Exception('Planilha no encontrada.');
} catch (PDOException $e) {
    if ($e->getCode() === '42S02' || stripos($e->getMessage(), '1146') !== false || stripos($e->getMessage(), "doesn't exist") !== false) {

        try {
            $stmt = $conexao->prepare('SELECT id, descricao as comum FROM comums WHERE id = :id');
            $stmt->bindValue(':id', $id_planilha, PDO::PARAM_INT);
            $stmt->execute();
            $comum = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($comum) {
                $planilha = ['id' => (int)$comum['id'], 'comum' => $comum['comum'], 'comum_id' => (int)$comum['id'], 'ativo' => 1];
                $using_comum_fallback = true;
            } else {
                die('Comum no encontrada.');
            }
        } catch (Exception $ex) {
            die('Erro ao carregar planilha/comum: ' . $ex->getMessage());
        }
    } else {
        die('Erro ao carregar planilha: ' . $e->getMessage());
    }
} catch (Exception $e) {
    die('Erro ao carregar planilha: ' . $e->getMessage());
}


try {
    $sql_dependencias = "
        SELECT DISTINCT 
            COALESCE(p.editado_dependencia_id, p.dependencia_id) as id,
            COALESCE(d_edit.descricao, d_orig.descricao) as dependencia 
        FROM produtos p
        LEFT JOIN dependencias d_orig ON p.dependencia_id = d_orig.id
        LEFT JOIN dependencias d_edit ON p.editado_dependencia_id = d_edit.id
        WHERE p.comum_id = :comum_id 
          AND COALESCE(p.imprimir_etiqueta, 0) = 1
          AND COALESCE(d_edit.descricao, d_orig.descricao) IS NOT NULL
        ORDER BY dependencia
    ";
    $stmt_dependencias = $conexao->prepare($sql_dependencias);
    $stmt_dependencias->bindValue(':comum_id', $id_planilha);
    $stmt_dependencias->execute();
    $dependencias = $stmt_dependencias->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $dependencias = [];
}

$dependencia_selecionada = $_GET['dependencia'] ?? '';


try {
    $sql_PRODUTOS = "SELECT p.codigo, COALESCE(d_edit.descricao, d_orig.descricao, '') as dependencia
                     FROM produtos p 
                     LEFT JOIN dependencias d_orig ON p.dependencia_id = d_orig.id
                     LEFT JOIN dependencias d_edit ON p.editado_dependencia_id = d_edit.id
                     WHERE p.comum_id = :comum_id AND COALESCE(p.imprimir_etiqueta, 0) = 1";
    if (!empty($dependencia_selecionada)) {
        $sql_PRODUTOS .= " AND COALESCE(p.editado_dependencia_id, p.dependencia_id) = :dependencia_id";
    }
    $sql_PRODUTOS .= " ORDER BY p.codigo";
    $stmt_PRODUTOS = $conexao->prepare($sql_PRODUTOS);
    $stmt_PRODUTOS->bindValue(':comum_id', $id_planilha);
    if (!empty($dependencia_selecionada)) {
        $stmt_PRODUTOS->bindValue(':dependencia_id', (int)$dependencia_selecionada, PDO::PARAM_INT);
    }
    $stmt_PRODUTOS->execute();
    $PRODUTOS = $stmt_PRODUTOS->fetchAll(PDO::FETCH_ASSOC);



















    $PRODUTOS_novos = [];


    $PRODUTOS = array_merge($PRODUTOS, $PRODUTOS_novos);

    $codigos = array_column($PRODUTOS, 'codigo');
    $PRODUTOS_sem_espacos = array_map(fn($c) => str_replace(' ', '', $c), $codigos);
    $codigos_concatenados = implode(',', $PRODUTOS_sem_espacos);
} catch (Exception $e) {
    $codigos_concatenados = '';
    $PRODUTOS = [];

    if ($e instanceof PDOException && ($e->getCode() === '42S02' || stripos($e->getMessage(), '1146') !== false || stripos($e->getMessage(), "doesn't exist") !== false)) {
        $mensagem = to_uppercase("Erro ao carregar produtos (comum_id: " . $comum_id . "): tabela 'produtos' no encontrada no banco de dados. Verifique a instalao ou migraes e contate o administrador.");
    } else {

        $mensagem = to_uppercase('Erro ao carregar produtos (comum_id: ' . $comum_id . '): ' . $e->getMessage());
    }
}

$pageTitle = 'Copiar Etiquetas';

$backUrl = '/products/view?id=' . urlencode($id_planilha) . '&comum_id=' . urlencode($id_planilha);
$headerActions = '
    <div class="dropdown">
        <button class="btn-header-action" type="button" id="menuEtiquetas" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-list fs-5"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="menuEtiquetas">
            <li>
                <a class="dropdown-item" href="/products/view?id=' . $id_planilha . '&comum_id=' . $id_planilha . '">
                    <i class="bi bi-eye me-2"></i>VISUALIZAR COMUM
                </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <a class="dropdown-item" href="/logout">
                    <i class="bi bi-box-arrow-right me-2"></i>SAIR
                </a>
            </li>
        </ul>
    </div>
';

ob_start();
?>

<?php if (!empty($mensagem)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-header">
        <i class="bi bi-tag me-2"></i>
        <?php echo htmlspecialchars(to_uppercase('Cdigos para impresso de etiquetas'), ENT_QUOTES, 'UTF-8'); ?>
        <?php if (!empty($_GET['debug'])): ?>
            <div class="small text-muted mt-1">DEBUG: COMUM_ID =
                <?php echo htmlspecialchars($comum_id, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-3">
            <?php echo htmlspecialchars(to_uppercase('Lista com os códigos dos produtos marcados como "Para Imprimir" e dos produtos novos cadastrados com código preenchido.'), ENT_QUOTES, 'UTF-8'); ?>
        </p>

        <?php if (!empty($dependencias)): ?>
            <div class="mb-3">
                <label for="filtroDependencia"
                    class="form-label"><?php echo htmlspecialchars(to_uppercase('Filtrar por dependncia'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="input-group">
                    <select class="form-select" id="filtroDependencia">
                        <option value="">
                            <?php echo htmlspecialchars(to_uppercase('Todas as dependncias'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php foreach ($dependencias as $dep): ?>
                            <option value="<?php echo (int)$dep['id']; ?>"
                                <?php echo ((string)$dependencia_selecionada === (string)$dep['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(to_uppercase($dep['dependencia']), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-primary" type="button" onclick="filtrarPorDependencia()">
                        <i class="bi bi-funnel me-1"></i><?php echo htmlspecialchars(to_uppercase('Filtrar'), ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <div class="row g-2 small">
            <div class="col-6">
                <div class="card shadow-sm-custom">
                    <div class="card-body text-center">
                        <div class="h4 mb-0"><?php echo count($PRODUTOS); ?></div>
                        <div class="text-muted">
                            <?php echo htmlspecialchars(to_uppercase('Produtos'), ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card shadow-sm-custom">
                    <div class="card-body text-center">
                        <div class="h4 mb-0"><?php echo count(array_unique($PRODUTOS_sem_espacos ?? [])); ?></div>
                        <div class="text-muted">
                            <?php echo htmlspecialchars(to_uppercase('Cdigos nicos'), ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($PRODUTOS)): ?>
            <div class="mt-3 position-relative">
                <label for="codigosField"
                    class="form-label"><?php echo htmlspecialchars(to_uppercase('Cdigos'), ENT_QUOTES, 'UTF-8'); ?></label>
                <textarea id="codigosField" class="form-control" rows="6" readonly
                    onclick="this.select()"><?php echo htmlspecialchars($codigos_concatenados, ENT_QUOTES, 'UTF-8'); ?></textarea>
                <button class="btn btn-primary btn-sm mt-2 w-100" onclick="copiarCodigos()">
                    <i class="bi bi-clipboard-check me-2"></i>
                    <?php echo htmlspecialchars(to_uppercase('Copiar para rea de transferncia'), ENT_QUOTES, 'UTF-8'); ?>
                </button>
                <div class="form-text">
                    <?php echo htmlspecialchars(to_uppercase('Clique no campo para selecionar tudo rapidamente.'), ENT_QUOTES, 'UTF-8'); ?>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-warning mt-3 text-center">
                <strong><?php echo htmlspecialchars(to_uppercase('Nenhum produto disponvel para etiquetas.'), ENT_QUOTES, 'UTF-8'); ?></strong>
                <?php if (!empty($dependencia_selecionada)): ?>
                    <?php

                    $dep_nome = '';
                    foreach ($dependencias as $d) {
                        if ($d['id'] == $dependencia_selecionada) {
                            $dep_nome = $d['dependencia'];
                            break;
                        }
                    }
                    ?>
                    <div class="small">
                        <?php echo htmlspecialchars(to_uppercase('No h produtos marcados para etiqueta na dependncia "' . $dep_nome . '".'), ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php else: ?>
                    <div class="small">
                        <?php echo htmlspecialchars(to_uppercase('Marque produtos com o ícone de etiqueta ou cadastre produtos com código preenchido.'), ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="/assets/js/spreadsheets/copy-labels.js"></script>

<?php
$contentHtml = ob_get_clean();
$headerActions = '';
include $projectRoot . '/src/Views/layouts/app.php';
?>