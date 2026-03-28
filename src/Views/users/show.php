<?php


$appConfig = require dirname(__DIR__, 3) . '/config/app.php';
$projectRoot = $appConfig['project_root'];
require_once $projectRoot . '/src/Helpers/BootstrapLoader.php';

$usuario = $usuario ?? [];
$filtrosRetorno = $filtros_retorno ?? [];

$pageTitle = 'VISUALIZAR Usuário';

$qsArr = [];
if (!empty($filtrosRetorno['busca'])) {
    $qsArr['busca'] = $filtrosRetorno['busca'];
}
if (isset($filtrosRetorno['status']) && $filtrosRetorno['status'] !== '') {
    $qsArr['status'] = $filtrosRetorno['status'];
}
if (!empty($filtrosRetorno['pagina'])) {
    $qsArr['pagina'] = $filtrosRetorno['pagina'];
}
$backUrl = '/users' . ($qsArr ? ('?' . http_build_query($qsArr)) : '');


function format_usuario_valor($valor)
{
    if ($valor === null || $valor === '') {
        return '-';
    }

    return mb_strtoupper(htmlspecialchars($valor, ENT_QUOTES, 'UTF-8'), 'UTF-8');
}

ob_start();
?>

<!-- JQUERY e INPUTMASK -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.8/dist/inputmask.min.js"></script>



<div class="bg-white border border-neutral-200 mb-4" style="border-radius:2px">
    <div class="bg-neutral-50 px-6 py-3 border-b border-neutral-200">
        <h5 class="mb-0 flex items-center gap-2 font-semibold text-neutral-700"><i class="bi bi-person-plus"></i>DADOS BÁSICOS</h5>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 gap-4">
            <div>
                <div class="text-sm font-medium text-neutral-600">NOME COMPLETO</div>
                <div class="text-lg font-semibold text-neutral-900"><?php echo format_usuario_valor($usuario['nome']); ?></div>
            </div>
            <div>
                <div class="text-sm font-medium text-neutral-600">CPF</div>
                <div class="text-lg font-semibold text-neutral-900"><?php echo format_usuario_valor($usuario['cpf'] ?? ''); ?></div>
            </div>
            <div>
                <div class="text-sm font-medium text-neutral-600">RG</div>
                <div class="text-lg font-semibold text-neutral-900"><?php echo format_usuario_valor($usuario['rg'] ?? ''); ?></div>
            </div>
            <div>
                <div class="text-sm font-medium text-neutral-600">TELEFONE</div>
                <div class="text-lg font-semibold text-neutral-900"><?php echo format_usuario_valor($usuario['telefone'] ?? ''); ?></div>
            </div>
            <div>
                <div class="text-sm font-medium text-neutral-600">EMAIL</div>
                <div class="text-lg font-semibold text-neutral-900"><?php echo format_usuario_valor($usuario['email']); ?></div>
            </div>
        </div>
    </div>
</div>

<!-- ESTADO CIVIL: removido do layout de visualização conforme solicitado. Mantido apenas o cartão de Dados do CÔNJUGE. -->

<?php if (!empty($usuario['casado'])): ?>
    <div class="bg-white border border-neutral-200 mb-4" style="border-radius:2px">
        <div class="bg-neutral-50 px-6 py-3 border-b border-neutral-200">
            <h5 class="mb-0 flex items-center gap-2 font-semibold text-neutral-700"><i class="bi bi-people-fill"></i>DADOS DO CÔNJUGE</h5>
        </div>
        <div class="p-6">
            <div class="row">
                <div class="col-12">
                    <div class="info-label">NOME COMPLETO</div>
                    <div class="info-value"><?php echo format_usuario_valor($usuario['nome_conjuge'] ?? ''); ?></div>
                </div>
                <div class="col-12">
                    <div class="info-label">CPF</div>
                    <div class="info-value"><?php echo format_usuario_valor($usuario['cpf_conjuge'] ?? ''); ?></div>
                </div>
                <div class="col-12">
                    <div class="info-label">RG</div>
                    <div class="info-value"><?php echo format_usuario_valor($usuario['rg_conjuge'] ?? ''); ?></div>
                </div>
                <div class="col-12">
                    <div class="info-label">TELEFONE</div>
                    <div class="info-value"><?php echo format_usuario_valor($usuario['telefone_conjuge'] ?? ''); ?></div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="bg-white border border-neutral-200 mb-4" style="border-radius:2px">
    <div class="bg-neutral-50 px-6 py-3 border-b border-neutral-200">
        <h5 class="mb-0 flex items-center gap-2 font-semibold text-neutral-700"><i class="bi bi-geo-alt"></i>ENDEREÇO</h5>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 gap-4">
            <div>
                <div class="text-sm font-medium text-neutral-600">CEP</div>
                <div class="text-lg font-semibold text-neutral-900"><?php echo format_usuario_valor($usuario['endereco_cep'] ?? ''); ?></div>
            </div>
            <div>
                <div class="text-sm font-medium text-neutral-600">LOGRADOURO</div>
                <div class="text-lg font-semibold text-neutral-900"><?php echo format_usuario_valor($usuario['endereco_logradouro'] ?? ''); ?></div>
            </div>
            <div>
                <div class="text-sm font-medium text-neutral-600">NÚMERO</div>
                <div class="text-lg font-semibold text-neutral-900"><?php echo format_usuario_valor($usuario['endereco_numero'] ?? ''); ?></div>
            </div>
            <div>
                <div class="text-sm font-medium text-neutral-600">COMPLEMENTO</div>
                <div class="text-lg font-semibold text-neutral-900"><?php echo format_usuario_valor($usuario['endereco_complemento'] ?? ''); ?></div>
            </div>
            <div>
                <div class="text-sm font-medium text-neutral-600">BAIRRO</div>
                <div class="text-lg font-semibold text-neutral-900"><?php echo format_usuario_valor($usuario['endereco_bairro'] ?? ''); ?></div>
            </div>
            <div>
                <div class="text-sm font-medium text-neutral-600">CIDADE</div>
                <div class="text-lg font-semibold text-neutral-900"><?php echo format_usuario_valor($usuario['endereco_cidade'] ?? ''); ?></div>
            </div>
            <div>
                <div class="text-sm font-medium text-neutral-600">ESTADO</div>
                <div class="text-lg font-semibold text-neutral-900"><?php echo format_usuario_valor($usuario['endereco_estado'] ?? ''); ?></div>
            </div>
        </div>
    </div>
</div>

<?php
$contentHtml = ob_get_clean();
include $projectRoot . '/src/Views/layouts/app.php';
?>
