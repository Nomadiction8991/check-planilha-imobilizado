<?php
/**
 * View: Editar Tipo de Bem
 */

use App\Helpers\AlertHelper;

$appConfig = require dirname(__DIR__, 3) . '/config/app.php';
$projectRoot = $appConfig['project_root'];
require_once $projectRoot . '/src/Helpers/BootstrapLoader.php';

$tipo ??= ['id' => '', 'descricao' => ''];
$pageTitle = 'Editar Tipo de Bem';
$backUrl = $_GET['backUrl'] ?? '/asset-types';

ob_start();
?>

<?= AlertHelper::fromQuery() ?>

<?php
// Alertas de sessão (se houver)
$alertas = [];
if (!empty($_SESSION['mensagem'])) {
    $isSuccess = ($_SESSION['tipo_mensagem'] ?? '') === 'success';
    $alertas[] = [
        'tipo' => $isSuccess ? 'success' : 'error',
        'mensagem' => $_SESSION['mensagem'],
        'fechar' => true,
    ];
    unset($_SESSION['mensagem'], $_SESSION['tipo_mensagem']);
}

if (!empty($alertas)):
    $alertasOptions = ['alertas' => $alertas];
    include $projectRoot . '/src/Views/layouts/partials/alerts.php';
endif;
?>

<?php
$formCardOptions = [
    'titulo'        => 'EDITAR TIPO DE BEM',
    'icone'         => 'bi-pencil-square',
    'action'        => '/asset-types/' . (int)($tipo['id'] ?? '') . '/edit',
    'method'        => 'POST',
    'back_url'      => $backUrl,
    'back_label'    => 'Cancelar',
    'submit_label'  => 'Salvar Alterações',
    'csrf'          => true,
    'campos'        => [
        [
            'tipo'        => 'text',
            'name'        => 'descricao',
            'label'       => 'Descrição',
            'value'       => $tipo['descricao'] ?? '',
            'placeholder' => 'EX: IMÓVEIS',
            'required'    => true,
            'maxlength'   => 255,
        ],
    ],
];
include $projectRoot . '/src/Views/layouts/partials/form-card.php';

// Campo oculto de ID
?>
<input type="hidden" name="id" value="<?= (int)($tipo['id'] ?? '') ?>" style="display:none">

<?php
$contentHtml = ob_get_clean();
include $projectRoot . '/src/Views/layouts/app.php';
?>
