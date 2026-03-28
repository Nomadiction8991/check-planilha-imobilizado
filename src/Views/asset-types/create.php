<?php
/**
 * View: Cadastrar Tipo de Bem
 */

use App\Helpers\AlertHelper;

$appConfig = require dirname(__DIR__, 3) . '/config/app.php';
$projectRoot = $appConfig['project_root'];
require_once $projectRoot . '/src/Helpers/BootstrapLoader.php';

$pageTitle = 'Novo Tipo de Bem';
$backUrl = '/asset-types';

ob_start();
?>

<?= AlertHelper::fromQuery() ?>

<?php
// Alertas de sessão (se houver)
$alertas = [];
if (!empty($_SESSION['mensagem'])) {
    $alertas[] = [
        'tipo' => $_SESSION['tipo_mensagem'] ?? 'info',
        'mensagem' => $_SESSION['mensagem'],
        'fechar' => true,
    ];
    unset($_SESSION['mensagem'], $_SESSION['tipo_mensagem']);
}

// Mostrar alertas customizados
if (!empty($alertas)):
    $alertasOptions = ['alertas' => $alertas];
    include $projectRoot . '/src/Views/layouts/partials/alerts.php';
endif;
?>

<?php
$formCardOptions = [
    'titulo'        => 'NOVO TIPO DE BEM',
    'icone'         => 'bi-plus-circle',
    'action'        => '/asset-types/create',
    'method'        => 'POST',
    'back_url'      => $backUrl,
    'back_label'    => 'Cancelar',
    'submit_label'  => 'Salvar',
    'csrf'          => true,
    'campos'        => [
        [
            'tipo'        => 'text',
            'name'        => 'descricao',
            'label'       => 'Descrição',
            'value'       => $_POST['descricao'] ?? '',
            'placeholder' => 'EX: IMÓVEIS',
            'required'    => true,
            'maxlength'   => 255,
        ],
    ],
];
include $projectRoot . '/src/Views/layouts/partials/form-card.php';
?>

<?php
$contentHtml = ob_get_clean();
include $projectRoot . '/src/Views/layouts/app.php';
?>
