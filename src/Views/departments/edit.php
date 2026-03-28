<?php

use App\Helpers\{AlertHelper, StringHelper};

$appConfig = require dirname(__DIR__, 3) . '/config/app.php';
$projectRoot = $appConfig['project_root'];
require_once $projectRoot . '/src/Helpers/BootstrapLoader.php';

$pageTitle = 'EDITAR DEPENDÊNCIA';
$backUrl = '/departments';
$dependencia ??= ['id' => '', 'descricao' => ''];

ob_start();
?>

<?= AlertHelper::fromQuery() ?>

<?php
// Alertas customizados
$alertas = [];
if (!empty($mensagem)) {
    $alertas[] = [
        'tipo' => ($tipo_mensagem === 'success') ? 'success' : 'error',
        'mensagem' => $mensagem,
    ];
}

if (!empty($alertas)):
    $alertasOptions = ['alertas' => $alertas];
    include $projectRoot . '/src/Views/layouts/partials/alerts.php';
endif;
?>

<?php if (isset($dependencia)): ?>
    <?php
    $formCardOptions = [
        'titulo'        => 'EDITAR DEPENDÊNCIA',
        'icone'         => 'bi-pencil-square',
        'action'        => '/departments/' . (int)($dependencia['id'] ?? '') . '/edit',
        'method'        => 'POST',
        'back_url'      => $backUrl,
        'back_label'    => 'Cancelar',
        'submit_label'  => 'ATUALIZAR DEPENDÊNCIA',
        'csrf'          => true,
        'campos'        => [
            [
                'tipo'        => 'textarea',
                'name'        => 'descricao',
                'label'       => 'Descrição',
                'value'       => $dependencia['descricao'] ?? '',
                'placeholder' => 'Digite a descrição',
                'required'    => true,
                'rows'        => 3,
            ],
        ],
    ];
    include $projectRoot . '/src/Views/layouts/partials/form-card.php';
    ?>
<?php endif; ?>

<?php
$contentHtml = ob_get_clean();
include $projectRoot . '/src/Views/layouts/app.php';
?>
