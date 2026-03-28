<?php


$appConfig = require dirname(__DIR__, 3) . '/config/app.php';
$projectRoot = $appConfig['project_root'];
require_once $projectRoot . '/src/Helpers/BootstrapLoader.php';


// Preferir sempre o contexto fornecido pelo controller
$id_planilha = $id_planilha ?? $comum_id ?? null;
if (!$id_planilha) {
    // sem comum selecionada, redireciona à listagem de igrejas (melhor UX que voltar pra home)
    header('Location: ' . base_url('/churches'));
    exit;
}

$comum_id = $comum_id ?? $id_planilha;
$mensagem = $mensagem ?? '';
$planilha = $planilha ?? null;

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
                throw new Exception('Comum não encontrada.');
            }
        } catch (Exception $ex) {
            $mensagem = to_uppercase('Erro ao carregar comum: ' . $ex->getMessage());
        }
    } else {
        $mensagem = to_uppercase('Erro ao carregar comum: ' . $e->getMessage());
    }
} catch (Exception $e) {
    $mensagem = to_uppercase('Erro ao carregar comum: ' . $e->getMessage());
}

if (!$planilha) {
    $planilha = ['id' => (int)$id_planilha, 'comum' => 'Comum indisponível'];
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
    if ($mensagem === '') {
        $mensagem = to_uppercase('Erro ao carregar dependências: ' . $e->getMessage());
    }
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

$backUrl = '/products/view?comum_id=' . urlencode((string) ($comum_id ?? $id_planilha));
$headerActions = '
    <div class="dropdown">
        <button class="btn-header-action" type="button" id="menuEtiquetas">
            <i class="bi bi-list"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="menuEtiquetas">
            <li>
                <a class="dropdown-item" href="/products/view?comum_id=' . urlencode((string) ($comum_id ?? $id_planilha)) . '">
                    <i class="bi bi-eye me-2"></i>VISUALIZAR COMUM
                </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <form method="POST" action="/logout" style="margin:0">
                    ' . \App\Core\CsrfService::hiddenField() . '
                    <button type="submit" class="dropdown-item" style="background:none;border:none;width:100%;text-align:left">
                        <i class="bi bi-box-arrow-right me-2"></i>SAIR
                    </button>
                </form>
            </li>
        </ul>
    </div>
';

ob_start();
?>

<?php if (!empty($mensagem)): ?>
    <div class="px-4 py-3 mb-4" style="background:#fafafa;border:1px solid #000;color:#171717;border-radius:2px"><?php echo htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<div class="border border-gray-200 mb-4" style="border-radius:2px">
    <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
        <i class="bi bi-tag me-2"></i>
        <?php echo htmlspecialchars(to_uppercase('Códigos para impressão de etiquetas'), ENT_QUOTES, 'UTF-8'); ?>
        <?php if (!empty($_GET['debug'])): ?>
            <div class="text-sm mt-2 text-gray-600">DEBUG: COMUM_ID =
                <?php echo htmlspecialchars($comum_id, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
    </div>
    <div class="p-4">
        <p class="text-gray-600 text-sm mb-4">
            <?php echo htmlspecialchars(to_uppercase('Lista com os códigos dos produtos marcados como "Para Imprimir" e dos produtos novos cadastrados com código preenchido.'), ENT_QUOTES, 'UTF-8'); ?>
        </p>

        <?php if (!empty($dependencias)): ?>
            <div class="mb-4">
                <label for="filtroDependencia"
                    class="block text-sm font-semibold mb-2"><?php echo htmlspecialchars(to_uppercase('Filtrar por dependência'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="flex gap-0 items-center">
                    <select class="flex-1 px-3 py-2 border border-gray-300 focus:outline-none focus:border-black" id="filtroDependencia" style="border-radius:2px 0 0 2px">
                        <option value="">
                            <?php echo htmlspecialchars(to_uppercase('Todas as dependências'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php foreach ($dependencias as $dep): ?>
                            <option value="<?php echo (int)$dep['id']; ?>"
                                <?php echo ((string)$dependencia_selecionada === (string)$dep['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(to_uppercase($dep['dependencia']), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="px-4 py-2 bg-black text-white font-semibold hover:bg-neutral-800 transition" type="button" onclick="filtrarPorDependencia()" style="border-radius:0 2px 2px 0">
                        <i class="bi bi-funnel me-1"></i><?php echo htmlspecialchars(to_uppercase('Filtrar'), ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-2 gap-2 text-sm mb-4">
            <div class="border border-gray-200 p-3 text-center" style="border-radius:2px">
                <div class="text-2xl font-bold text-gray-900"><?php echo count($PRODUTOS); ?></div>
                <div class="text-gray-600 text-sm">
                    <?php echo htmlspecialchars(to_uppercase('Produtos'), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <div class="border border-gray-200 p-3 text-center" style="border-radius:2px">
                <div class="text-2xl font-bold text-gray-900"><?php echo count(array_unique($PRODUTOS_sem_espacos ?? [])); ?></div>
                <div class="text-gray-600 text-sm">
                    <?php echo htmlspecialchars(to_uppercase('Códigos únicos'), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
        </div>

        <?php if (!empty($PRODUTOS)): ?>
            <div class="mt-4">
                <label for="codigosField"
                    class="block text-sm font-semibold mb-2"><?php echo htmlspecialchars(to_uppercase('Códigos'), ENT_QUOTES, 'UTF-8'); ?></label>
                <textarea id="codigosField" class="w-full px-3 py-2 border border-gray-300 font-mono text-sm focus:outline-none focus:border-black" rows="6" readonly style="border-radius:2px"
                    onclick="this.select()"><?php echo htmlspecialchars($codigos_concatenados, ENT_QUOTES, 'UTF-8'); ?></textarea>
                <button class="w-full mt-2 px-3 py-2 bg-black text-white font-semibold hover:bg-neutral-800 transition text-sm" onclick="copiarCodigos()" style="border-radius:2px">
                    <i class="bi bi-clipboard-check me-2"></i>
                    <?php echo htmlspecialchars(to_uppercase('Copiar para área de transferência'), ENT_QUOTES, 'UTF-8'); ?>
                </button>
                <div class="text-gray-600 text-xs mt-2">
                    <?php echo htmlspecialchars(to_uppercase('Clique no campo para selecionar tudo rapidamente.'), ENT_QUOTES, 'UTF-8'); ?>
                </div>
            </div>
        <?php else: ?>
            <div class="border border-black px-4 py-3 mt-4 text-center" style="border-radius:2px;background:#fafafa">
                <strong class="block" style="color:#171717"><?php echo htmlspecialchars(to_uppercase('Nenhum produto disponível para etiquetas.'), ENT_QUOTES, 'UTF-8'); ?></strong>
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
                    <div class="text-sm mt-1" style="color:#525252">
                        <?php echo htmlspecialchars(to_uppercase('Não há produtos marcados para etiqueta na dependência "' . $dep_nome . '".'), ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php else: ?>
                    <div class="text-sm mt-1" style="color:#525252">
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
