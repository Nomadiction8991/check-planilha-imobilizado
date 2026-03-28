<?php

use App\Helpers\AlertHelper;

$appConfig = require dirname(__DIR__, 3) . '/config/app.php';
$projectRoot = $appConfig['project_root'];
require_once $projectRoot . '/src/Helpers/BootstrapLoader.php';

$pageTitle = 'Nova DEPENDÊNCIA';
$backUrl = '/departments';

ob_start();
?>

<?= AlertHelper::fromQuery() ?>

<?php
// Alertas customizados
$alertas = [];
if (!empty($mensagem)) {
    $alertas[] = [
        'tipo' => 'info',
        'mensagem' => $mensagem,
    ];
}

if (!empty($alertas)):
    $alertasOptions = ['alertas' => $alertas];
    include $projectRoot . '/src/Views/layouts/partials/alerts.php';
endif;
?>

<?php
$formCardOptions = [
    'titulo'        => 'CADASTRAR NOVA DEPENDÊNCIA',
    'icone'         => 'bi-plus-circle',
    'action'        => '',
    'method'        => 'POST',
    'back_url'      => $backUrl,
    'back_label'    => 'Cancelar',
    'submit_label'  => 'Cadastrar Dependência',
    'csrf'          => true,
    'campos'        => [
        [
            'tipo'        => 'textarea',
            'name'        => 'descricao',
            'label'       => 'Descrição',
            'value'       => $_POST['descricao'] ?? '',
            'placeholder' => 'Digite a descrição',
            'required'    => true,
            'rows'        => 3,
        ],
    ],
];
include $projectRoot . '/src/Views/layouts/partials/form-card.php';
?>

<?php
$contentHtml = ob_get_clean();
include $projectRoot . '/src/Views/layouts/app.php';
?>
