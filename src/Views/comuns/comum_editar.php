<?php
require_once dirname(__DIR__, 2) . '/config/bootstrap.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: ' . base_url('/comuns'));
    exit;
}

$comum = obter_comum_por_id($conexao, $id);
if (!$comum) {
    $_SESSION['mensagem'] = 'Comum NÃO encontrada.';
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: ' . base_url('/comuns'));
    exit;
}

$pageTitle = 'EDITAR COMUM';


$filterParams = $_GET;
unset($filterParams['id'], $filterParams['success'], $filterParams['ajax']);
$filterQueryString = http_build_query($filterParams);
$backUrl = '/comuns' . ($filterQueryString !== '' ? ('?' . $filterQueryString) : '');
$filtersHiddenValue = htmlspecialchars($filterQueryString, ENT_QUOTES, 'UTF-8');
$buscaValue = $_REQUEST['busca'] ?? ($filterParams['busca'] ?? '');
$paginaValue = $_REQUEST['pagina'] ?? ($filterParams['pagina'] ?? '');

$comumDescricao = mb_strtoupper((string) ($comum['descricao'] ?? ''), 'UTF-8');
$comumAdm = mb_strtoupper((string) ($comum['administracao'] ?? ''), 'UTF-8');
$comumCIDADE = mb_strtoupper((string) ($comum['cidade'] ?? ''), 'UTF-8');
$comumSetor = mb_strtoupper((string) ($comum['setor'] ?? ''), 'UTF-8');

$mt_cidades = [
    "MT - Acorizal",
    "MT - Água Boa",
    "MT - Alta Floresta",
    "MT - Alto Araguaia",
    "MT - Alto Boa Vista",
    "MT - Alto Garças",
    "MT - Alto Paraguai",
    "MT - Alto Taquari",
    "MT - Apiacás",
    "MT - Araguaiana",
    "MT - Araguainha",
    "MT - Araputanga",
    "MT - Arenápolis",
    "MT - Aripuanã",
    "MT - Barão de Melgaço",
    "MT - Barra do Bugres",
    "MT - Barra do Garças",
    "MT - Bom Jesus do Araguaia",
    "MT - Brasnorte",
    "MT - Cáceres",
    "MT - Campinápolis",
    "MT - Campo Novo do Parecis",
    "MT - Campo Verde",
    "MT - Campos de Júlio",
    "MT - Canabrava do Norte",
    "MT - Canarana",
    "MT - Carlinda",
    "MT - Castanheira",
    "MT - Chapada dos Guimarães",
    "MT - Cláudia",
    "MT - Cocalinho",
    "MT - Colíder",
    "MT - Colniza",
    "MT - Comodoro",
    "MT - Confresa",
    "MT - Conquista d'Oeste",
    "MT - Cotriguaçu",
    "MT - Cuiabá",
    "MT - Curvelândia",
    "MT - Denise",
    "MT - Diamantino",
    "MT - Dom Aquino",
    "MT - Feliz Natal",
    "MT - Figueirópolis d'Oeste",
    "MT - Gaúcha do Norte",
    "MT - General Carneiro",
    "MT - Glória d'Oeste",
    "MT - Guarantã do Norte",
    "MT - Guiratinga",
    "MT - Indiavaí",
    "MT - Ipiranga do Norte",
    "MT - Itanhangá",
    "MT - Itaúba",
    "MT - Itiquira",
    "MT - Jaciara",
    "MT - Jangada",
    "MT - Jauru",
    "MT - Juara",
    "MT - Juína",
    "MT - Juruena",
    "MT - Juscimeira",
    "MT - Lambari d'Oeste",
    "MT - Lucas do Rio Verde",
    "MT - Luciara",
    "MT - Marcelândia",
    "MT - Matupá",
    "MT - Mirassol d'Oeste",
    "MT - Nobres",
    "MT - Nortelândia",
    "MT - Nossa Senhora do Livramento",
    "MT - Nova Bandeirantes",
    "MT - Nova Brasilândia",
    "MT - Nova Canaã do Norte",
    "MT - Nova Guarita",
    "MT - Nova Lacerda",
    "MT - Nova Marilândia",
    "MT - Nova Maringá",
    "MT - Nova Monte Verde",
    "MT - Nova Mutum",
    "MT - Nova Nazaré",
    "MT - Nova Olímpia",
    "MT - Nova Santa Helena",
    "MT - Nova Ubiratã",
    "MT - Nova Xavantina",
    "MT - Novo Horizonte do Norte",
    "MT - Novo Mundo",
    "MT - Novo Santo Antônio",
    "MT - Novo São Joaquim",
    "MT - Paranaíta",
    "MT - Paranatinga",
    "MT - Pedra Preta",
    "MT - Peixoto de Azevedo",
    "MT - Planalto da Serra",
    "MT - Poconé",
    "MT - Pontal do Araguaia",
    "MT - Ponte Branca",
    "MT - Pontes e Lacerda",
    "MT - Porto Alegre do Norte",
    "MT - Porto dos Gaúchos",
    "MT - Porto Esperidião",
    "MT - Porto Estrela",
    "MT - Poxoréu",
    "MT - Primavera do Leste",
    "MT - Querência",
    "MT - Reserva do Cabaçal",
    "MT - Ribeirão Cascalheira",
    "MT - Ribeirãozinho",
    "MT - Rio Branco",
    "MT - Rondolândia",
    "MT - Rondonópolis",
    "MT - Rosário Oeste",
    "MT - Salto do Céu",
    "MT - Santa Carmem",
    "MT - Santa Cruz do Xingu",
    "MT - Santa Rita do Trivelato",
    "MT - Santa Terezinha",
    "MT - Santo Afonso",
    "MT - Santo Antônio do Leste",
    "MT - Santo Antônio do Leverger",
    "MT - São Félix do Araguaia",
    "MT - São José do Povo",
    "MT - São José do Rio Claro",
    "MT - São José do Xingu",
    "MT - São José dos Quatro Marcos",
    "MT - São Pedro da Cipa",
    "MT - Sapezal",
    "MT - Serra Nova Dourada",
    "MT - Sinop",
    "MT - Sorriso",
    "MT - Tabaporã",
    "MT - Tangará da Serra",
    "MT - Tapurah",
    "MT - Terra Nova do Norte",
    "MT - Tesouro",
    "MT - Torixoréu",
    "MT - União do Sul",
    "MT - Vale de São Domingos",
    "MT - Várzea Grande",
    "MT - Vera",
    "MT - Vila Bela da Santíssima Trindade",
    "MT - Vila Rica"
];

$mt_cidades = array_map(
    fn($cidade) => mb_strtoupper($cidade, 'UTF-8'),
    $mt_cidades
);

ob_start();
?>

<?php if (!empty($_SESSION['mensagem'])): ?>
    <div class="alert alert-<?php echo $_SESSION['tipo_mensagem'] ?? 'info'; ?> alert-dismissible fade show">
        <?php echo htmlspecialchars($_SESSION['mensagem']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['mensagem'], $_SESSION['tipo_mensagem']); ?>
<?php endif; ?>

<div class="container py-4">
    <div class="card">
        <div class="card-header">
            <i class="bi bi-pencil-square me-2"></i><?php echo htmlspecialchars(to_uppercase('Editar Comum'), ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <div class="card-body">
            <form method="POST" action="/comuns/editar" novalidate>
                <input type="hidden" name="id" value="<?php echo (int) $comum['id']; ?>">
                <input type="hidden" name="filters" value="<?php echo $filtersHiddenValue; ?>">
                <?php ?>
                <input type="hidden" name="busca" value="<?php echo htmlspecialchars($buscaValue, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="pagina" value="<?php echo htmlspecialchars($paginaValue, ENT_QUOTES, 'UTF-8'); ?>">

                <div class="mb-3">
                    <label class="form-label"><?php echo htmlspecialchars(to_uppercase('CÓDIGO'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <input type="text" class="form-control text-uppercase w-100" value="<?php echo htmlspecialchars($comum['codigo']); ?>" disabled>
                </div>

                <div class="row g-3">
                    <div class="col-12">
                        <label for="descricao" class="form-label"><?php echo htmlspecialchars(to_uppercase('Descrição'), ENT_QUOTES, 'UTF-8'); ?> <span class="text-danger">*</span></label>
                        <input type="text" id="descricao" name="descricao" class="form-control text-uppercase w-100" required
                            value="<?php echo htmlspecialchars($comumDescricao); ?>">
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-12">
                        <label for="cnpj" class="form-label"><?php echo htmlspecialchars(to_uppercase('CNPJ'), ENT_QUOTES, 'UTF-8'); ?> <span class="text-danger">*</span></label>
                        <input type="text" id="cnpj" name="cnpj" class="form-control text-uppercase w-100" required
                            value="<?php echo htmlspecialchars($comum['cnpj']); ?>" placeholder="00.000.000/0000-00">
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-12">
                        <label for="administracao" class="form-label"><?php echo htmlspecialchars(to_uppercase('Administração'), ENT_QUOTES, 'UTF-8'); ?> <span class="text-danger">*</span></label>
                        <select id="administracao" name="administracao" class="form-select text-uppercase w-100" required>
                            <option value=""><?php echo htmlspecialchars(to_uppercase('Selecione'), ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php foreach ($mt_cidades as $op): ?>
                                <option value="<?php echo htmlspecialchars($op); ?>" <?php echo $comumAdm === $op ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(to_uppercase($op), ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-12">
                        <label for="cidade" class="form-label"><?php echo htmlspecialchars(to_uppercase('CIDADE'), ENT_QUOTES, 'UTF-8'); ?> <span class="text-danger">*</span></label>
                        <select id="cidade" name="cidade" class="form-select text-uppercase w-100" required>
                            <option value=""><?php echo htmlspecialchars(to_uppercase('Selecione'), ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php foreach ($mt_cidades as $op): ?>
                                <option value="<?php echo htmlspecialchars($op); ?>" <?php echo $comumCIDADE === $op ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(to_uppercase($op), ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-12">
                        <label for="setor" class="form-label"><?php echo htmlspecialchars(to_uppercase('Setor (opcional)'), ENT_QUOTES, 'UTF-8'); ?></label>
                        <input type="text" id="setor" name="setor" class="form-control text-uppercase w-100"
                            value="<?php echo htmlspecialchars($comumSetor); ?>">
                    </div>
                </div>

                <div class="mt-4 d-grid gap-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-save me-2"></i><?php echo htmlspecialchars(to_uppercase('Salvar'), ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.8/dist/inputmask.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const cnpjInput = document.getElementById('cnpj');
        if (cnpjInput && window.Inputmask) {
            Inputmask({
                mask: "99.999.999/9999-99"
            }).mask(cnpjInput);
        }
    });
</script>

<?php if ($filterQueryString !== ''): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const backBtn = document.querySelector('.btn-back');
            if (!backBtn) return;
            const base = backBtn.getAttribute('href').split('?')[0];
            backBtn.setAttribute('href', base + '?' + <?php echo json_encode($filterQueryString); ?>);
        });
    </script>
<?php endif; ?>

<?php
$contentHtml = ob_get_clean();
$contentFile = __DIR__ . '/../../../temp_editar_comum_' . uniqid() . '.php';
file_put_contents($contentFile, $contentHtml);
include __DIR__ . '/../layouts/app_wrapper.php';
@unlink($contentFile);
?>