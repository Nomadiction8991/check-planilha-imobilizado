<?php

declare(strict_types=1);

$appConfig = require dirname(__DIR__, 3) . '/config/app.php';
$projectRoot = $appConfig['project_root'];
require_once $projectRoot . '/src/Helpers/BootstrapLoader.php';



$idParam = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$idParam) {
    header('Location: /users');
    exit;
}


$pageTitle = 'EDITAR USUÁRIO';

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

$backUrl = '/users' . ($qsArr ? ('?' . http_build_query($qsArr)) : '');

ob_start();
?>

<link href="/assets/css/usuarios/usuario_editar.css" rel="stylesheet">


<?php if (!empty($mensagem)): ?>
    <div class="alert alert-<?php echo $tipo_mensagem === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
        <?php echo $mensagem; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- JQUERY E INPUTMASK -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.8/dist/inputmask.min.js"></script>
<!-- SIGNATUREPAD -->
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>



<?php if (isset($usuario)): ?>
    <form method="POST" id="formUsuario">
        <?php
        ?>
        <input type="hidden" name="busca" value="<?php echo htmlspecialchars($_GET['busca'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="status" value="<?php echo htmlspecialchars($_GET['status'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="pagina" value="<?php echo htmlspecialchars($_GET['pagina'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">


        <!-- Card 1: DADOS BÁSICOS -->
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-person-plus me-2"></i>
                DADOS BÁSICOS
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="nome" class="form-label">NOME COMPLETO <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nome" name="nome"
                        value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>
                </div>

                <div class="row g-3">
                    <div class="col-12">
                        <label for="cpf" class="form-label">CPF <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="cpf" name="cpf"
                            value="<?php echo htmlspecialchars($usuario['cpf'] ?? ''); ?>"
                            placeholder="000.000.000-00" required>
                    </div>
                    <div class="col-12">
                        <label for="rg" class="form-label">RG <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="rg" name="rg"
                            value="<?php echo htmlspecialchars($usuario['rg'] ?? ''); ?>"
                            placeholder="<?php echo htmlspecialchars(to_uppercase('Digite os dígitos do RG'), ENT_QUOTES, 'UTF-8'); ?>" required <?php echo !empty($usuario['rg_igual_cpf']) ? 'disabled' : ''; ?>>
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="checkbox" id="rg_igual_cpf" name="rg_igual_cpf" value="1" <?php echo !empty($usuario['rg_igual_cpf']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="rg_igual_cpf">RG IGUAL AO CPF</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label for="telefone" class="form-label">TELEFONE <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="telefone" name="telefone"
                            value="<?php echo htmlspecialchars($usuario['telefone'] ?? ''); ?>"
                            placeholder="(00) 00000-0000" required>
                    </div>
                </div>

                <div class="mb-3 mt-3">
                    <label for="email" class="form-label">EMAIL <span class="text-danger">*</span></label>
                    <input type="email" class="form-control text-uppercase" id="email" name="email"
                        value="<?php echo htmlspecialchars(to_uppercase($usuario['email']), ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>

                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    DEIXE OS CAMPOS DE SENHA EM BRANCO PARA MANTER A SENHA ATUAL
                </div>

                <div class="row g-3">
                    <div class="col-12">
                        <label for="senha" class="form-label">NOVA SENHA</label>
                        <input type="password" class="form-control" id="senha" name="senha" minlength="6">
                        <small class="text-muted">MÍNIMO DE 6 CARACTERES</small>
                    </div>

                    <div class="col-12">
                        <label for="confirmar_senha" class="form-label">CONFIRMAR NOVA SENHA</label>
                        <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" minlength="6">
                    </div>
                </div>


            </div>
        </div>

        <!-- Card 2: ESTADO CIVIL -->
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-person-hearts me-2"></i>
                ESTADO CIVIL
            </div>
            <div class="card-body">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="casado" name="casado" value="1" <?php echo !empty($usuario['casado']) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="casado"><?php echo htmlspecialchars(to_uppercase('Sou casado(a)'), ENT_QUOTES, 'UTF-8'); ?></label>
                </div>
            </div>
        </div>

        <!-- Card 4: DADOS DO CÔNJUGE (condicional) -->
        <div id="cardConjuge" class="card mb-3" style="display: <?php echo !empty($usuario['casado']) ? '' : 'none'; ?>;">
            <div class="card-header">
                <i class="bi bi-people-fill me-2"></i>
                DADOS DO CÔNJUGE
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="nome_conjuge" class="form-label">NOME COMPLETO DO CÔNJUGE</label>
                    <input type="text" class="form-control" id="nome_conjuge" name="nome_conjuge" value="<?php echo htmlspecialchars($usuario['nome_conjuge'] ?? ''); ?>">
                </div>
                <div class="row g-3">
                    <div class="col-12">
                        <label for="cpf_conjuge" class="form-label">CPF DO CÔNJUGE</label>
                        <input type="text" class="form-control" id="cpf_conjuge" name="cpf_conjuge" value="<?php echo htmlspecialchars($usuario['cpf_conjuge'] ?? ''); ?>" placeholder="000.000.000-00">
                    </div>
                    <div class="col-12">
                        <label for="rg_conjuge" class="form-label">RG DO CÔNJUGE</label>
                        <input type="text" class="form-control" id="rg_conjuge" name="rg_conjuge" value="<?php echo htmlspecialchars($usuario['rg_conjuge'] ?? ''); ?>" placeholder="<?php echo htmlspecialchars(to_uppercase('Digite os dígitos do RG'), ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="checkbox" id="rg_conjuge_igual_cpf" name="rg_conjuge_igual_cpf" value="1" <?php echo !empty($usuario['rg_conjuge_igual_cpf']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="rg_conjuge_igual_cpf">RG DO CÔNJUGE IGUAL AO CPF DO CÔNJUGE</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label for="telefone_conjuge" class="form-label">TELEFONE DO CÔNJUGE</label>
                        <input type="text" class="form-control" id="telefone_conjuge" name="telefone_conjuge" value="<?php echo htmlspecialchars($usuario['telefone_conjuge'] ?? ''); ?>" placeholder="(00) 00000-0000">
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 4: ENDEREÇO -->
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-geo-alt me-2"></i>
                ENDEREÇO
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label for="cep" class="form-label">CEP</label>
                        <input type="text" class="form-control" id="cep" name="endereco_cep"
                            value="<?php echo htmlspecialchars($usuario['endereco_cep'] ?? ''); ?>"
                            placeholder="00000-000">
                        <small class="text-muted">PREENCHA PARA BUSCAR AUTOMATICAMENTE</small>
                    </div>
                    <div class="col-12">
                        <label for="logradouro" class="form-label">LOGRADOURO</label>
                        <input type="text" class="form-control" id="logradouro" name="endereco_logradouro"
                            value="<?php echo htmlspecialchars($usuario['endereco_logradouro'] ?? ''); ?>">
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-12">
                        <label for="numero" class="form-label">NÚMERO</label>
                        <input type="text" class="form-control" id="numero" name="endereco_numero"
                            value="<?php echo htmlspecialchars($usuario['endereco_numero'] ?? ''); ?>">
                    </div>
                    <div class="col-12">
                        <label for="complemento" class="form-label">COMPLEMENTO</label>
                        <input type="text" class="form-control" id="complemento" name="endereco_complemento"
                            value="<?php echo htmlspecialchars($usuario['endereco_complemento'] ?? ''); ?>"
                            placeholder="Apto, bloco, etc">
                    </div>
                    <div class="col-12">
                        <label for="bairro" class="form-label">BAIRRO</label>
                        <input type="text" class="form-control" id="bairro" name="endereco_bairro"
                            value="<?php echo htmlspecialchars($usuario['endereco_bairro'] ?? ''); ?>">
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-12">
                        <label for="cidade" class="form-label">CIDADE</label>
                        <input type="text" class="form-control" id="cidade" name="endereco_cidade"
                            value="<?php echo htmlspecialchars($usuario['endereco_cidade'] ?? ''); ?>">
                    </div>
                    <div class="col-12">
                        <label for="estado" class="form-label">ESTADO</label>
                        <select class="form-select" id="estado" name="endereco_estado">
                            <option value="">Selecione</option>
                            <?php
                            $estados = ['AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas', 'BA' => 'Bahia', 'CE' => 'Ceará', 'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo', 'GO' => 'Goiás', 'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul', 'MG' => 'Minas Gerais', 'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná', 'PE' => 'Pernambuco', 'PI' => 'Piauí', 'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte', 'RS' => 'Rio Grande do Sul', 'RO' => 'RondÁ´nia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina', 'SP' => 'São Paulo', 'SE' => 'Sergipe', 'TO' => 'Tocantins'];
                            foreach ($estados as $sigla => $nome):
                                $selected = ($usuario['endereco_estado'] ?? '') === $sigla ? 'selected' : '';
                            ?>
                                <option value="<?php echo $sigla; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars(to_uppercase($nome), ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-check-lg me-1"></i>
                ATUALIZAR
            </button>
        </div>
    </form>

    <!-- Assinaturas removidas do formulário (modal e preview removidos) -->

    <!-- Variáveis PHP necessárias para o JS externo -->
    <script>
        window._editUserRgDigits = '<?php echo preg_replace('/\D/', '', $usuario['rg'] ?? ''); ?>';
        window._editUserRgConjugeDigits = '<?php echo preg_replace('/\D/', '', $usuario['rg_conjuge'] ?? ''); ?>';
    </script>
    <script src="/assets/js/usuarios/edit.js"></script>
<?php endif; ?>

<?php
$contentHtml = ob_get_clean();
include $projectRoot . '/src/Views/layouts/app.php';
?>