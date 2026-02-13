<?php
require_once dirname(__DIR__, 2) . '/Helpers/BootstrapLoader.php';



$idParam = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$idParam) {
    header('Location: /usuarios');
    exit;
}

$stmt = $conexao->prepare('SELECT * FROM usuarios WHERE id = :id');
$stmt->bindValue(':id', $idParam, PDO::PARAM_INT);
$stmt->execute();
$usuario = $stmt->fetch();

if (!$usuario) {
    header('Location: /usuarios');
    exit;
}

$pageTitle = 'VISUALIZAR Usuário';

$qsArr = [];
if (!empty($_GET['busca'])) {
    $qsArr['busca'] = $_GET['busca'];
}
if (isset($_GET['status']) && $_GET['status'] !== '') {
    $qsArr['status'] = $_GET['status'];
}
if (!empty($_GET['pagina'])) {
    $qsArr['pagina'] = $_GET['pagina'];
}
$backUrl = '/usuarios' . ($qsArr ? ('?' . http_build_query($qsArr)) : '');


function format_usuario_valor($valor)
{
    if ($valor === null || $valor === '') {
        return '-';
    }

    return mb_strtoupper(htmlspecialchars($valor, ENT_QUOTES, 'UTF-8'), 'UTF-8');
}

ob_start();
?>

<link href="/assets/css/usuarios/usuario_ver.css" rel="stylesheet">


<!-- JQUERY e INPUTMASK -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.8/dist/inputmask.min.js"></script>



<div class="card mb-3 shadow-sm">
    <div class="card-header card-header-contrast border-bottom-0">
        <h5 class="mb-0"><i class="bi bi-person-plus me-2"></i>DADOS BÁSICOS</h5>
    </div>
    <div class="card-body border-top">
        <div class="row g-3">
            <div class="col-12">
                <div class="info-label">NOME COMPLETO</div>
                <div class="info-value"><?php echo format_usuario_valor($usuario['nome']); ?></div>
            </div>
            <div class="col-12">
                <div class="info-label">CPF</div>
                <div class="info-value"><?php echo format_usuario_valor($usuario['cpf'] ?? ''); ?></div>
            </div>
            <div class="col-12">
                <div class="info-label">RG</div>
                <div class="info-value"><?php echo format_usuario_valor($usuario['rg'] ?? ''); ?></div>
            </div>
            <div class="col-12">
                <div class="info-label">TELEFONE</div>
                <div class="info-value"><?php echo format_usuario_valor($usuario['telefone'] ?? ''); ?></div>
            </div>
            <div class="col-12">
                <div class="info-label">EMAIL</div>
                <div class="info-value"><?php echo format_usuario_valor($usuario['email']); ?></div>
            </div>
        </div>
    </div>
</div>

<!-- ESTADO CIVIL: removido do layout de visualização conforme solicitado. Mantido apenas o cartão de Dados do CÔNJUGE. -->

<?php if (!empty($usuario['casado'])): ?>
    <div class="card mb-3 shadow-sm">
        <div class="card-header card-header-contrast border-bottom-0">
            <h5 class="mb-0"><i class="bi bi-people-fill me-2"></i>DADOS DO CÔNJUGE</h5>
        </div>
        <div class="card-body border-top">
            <div class="row g-3">
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

<div class="card mb-3 shadow-sm">
    <div class="card-header card-header-contrast border-bottom-0">
        <h5 class="mb-0"><i class="bi bi-geo-alt me-2"></i>ENDEREÇO</h5>
    </div>
    <div class="card-body border-top">
        <div class="row g-3">
            <div class="col-12">
                <div class="info-label">CEP</div>
                <div class="info-value"><?php echo format_usuario_valor($usuario['endereco_cep'] ?? ''); ?></div>
            </div>
            <div class="col-12">
                <div class="info-label">LOGRADOURO</div>
                <div class="info-value"><?php echo format_usuario_valor($usuario['endereco_logradouro'] ?? ''); ?></div>
            </div>
            <div class="col-12">
                <div class="info-label">NÚMERO</div>
                <div class="info-value"><?php echo format_usuario_valor($usuario['endereco_numero'] ?? ''); ?></div>
            </div>
            <div class="col-12">
                <div class="info-label">COMPLEMENTO</div>
                <div class="info-value"><?php echo format_usuario_valor($usuario['endereco_complemento'] ?? ''); ?></div>
            </div>
            <div class="col-12">
                <div class="info-label">BAIRRO</div>
                <div class="info-value"><?php echo format_usuario_valor($usuario['endereco_bairro'] ?? ''); ?></div>
            </div>
            <div class="col-12">
                <div class="info-label">CIDADE</div>
                <div class="info-value"><?php echo format_usuario_valor($usuario['endereco_cidade'] ?? ''); ?></div>
            </div>
            <div class="col-12">
                <div class="info-label">ESTADO</div>
                <div class="info-value"><?php echo format_usuario_valor($usuario['endereco_estado'] ?? ''); ?></div>
            </div>
        </div>
    </div>
</div>

<?php
$contentHtml = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
?>