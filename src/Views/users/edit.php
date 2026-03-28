<?php


$appConfig = require dirname(__DIR__, 3) . '/config/app.php';
$projectRoot = $appConfig['project_root'];
require_once $projectRoot . '/src/Helpers/BootstrapLoader.php';

$pageTitle = 'EDITAR USUÁRIO';

$filtros_retorno = $filtros_retorno ?? [];
$qsArr = [];
if (!empty($filtros_retorno['busca'])) {
    $qsArr['busca'] = $filtros_retorno['busca'];
}
if (isset($filtros_retorno['status']) && $filtros_retorno['status'] !== '') {
    $qsArr['status'] = $filtros_retorno['status'];
}
if (!empty($filtros_retorno['pagina'])) {
    $qsArr['pagina'] = $filtros_retorno['pagina'];
}

$backUrl = '/users' . ($qsArr ? ('?' . http_build_query($qsArr)) : '');

ob_start();
?>

<link href="/assets/css/usuarios/usuario_editar.css" rel="stylesheet">


<?php if (!empty($mensagem)): ?>
    <div class="p-4 mb-4 flex justify-between items-center" style="background:#fafafa;border:1px solid #d4d4d4;border-radius:2px;color:#171717">
        <span><?php echo $mensagem; ?></span>
        <button type="button" style="color:#171717" onclick="this.parentElement.style.display='none';"><i class="bi bi-x"></i></button>
    </div>
<?php endif; ?>

<!-- JQUERY E INPUTMASK -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.8/dist/inputmask.min.js"></script>
<!-- SIGNATUREPAD -->
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>



<?php if (isset($usuario)): ?>
    <form method="POST" id="formUsuario">
        <input type="hidden" name="id" value="<?php echo (int)($usuario['id'] ?? 0); ?>">
        <input type="hidden" name="busca" value="<?php echo htmlspecialchars($filtros_retorno['busca'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="status" value="<?php echo htmlspecialchars($filtros_retorno['status'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="pagina" value="<?php echo htmlspecialchars($filtros_retorno['pagina'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">


        <!-- Card 1: DADOS BÁSICOS -->
        <div class="bg-white border border-neutral-200 mb-4" style="border-radius:2px">
            <div class="bg-neutral-50 px-6 py-3 border-b border-neutral-200 flex items-center gap-2">
                <i class="bi bi-person-plus text-neutral-600"></i>
                <span class="font-semibold text-neutral-700">DADOS BÁSICOS</span>
            </div>
            <div class="p-6 space-y-4">
                <div class="mb-3">
                    <label for="nome" class="block text-sm font-medium text-neutral-700 mb-1">NOME COMPLETO <span style="color:#525252">*</span></label>
                    <input type="text" class="w-full px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black" id="nome" name="nome"
                        value="<?php echo htmlspecialchars($usuario['nome']); ?>" required style="border-radius:2px">
                </div>

                <div class="row">
                    <div class="col-12">
                        <label for="cpf" class="block text-sm font-medium text-neutral-700 mb-1">CPF <span style="color:#525252">*</span></label>
                        <input type="text" class="w-full px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black" id="cpf" name="cpf"
                            value="<?php echo htmlspecialchars($usuario['cpf'] ?? ''); ?>"
                            placeholder="000.000.000-00" required style="border-radius:2px">
                    </div>
                    <div class="col-12">
                        <label for="rg" class="block text-sm font-medium text-neutral-700 mb-1">RG <span style="color:#525252">*</span></label>
                        <input type="text" class="w-full px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black" id="rg" name="rg"
                            value="<?php echo htmlspecialchars($usuario['rg'] ?? ''); ?>"
                            placeholder="<?php echo htmlspecialchars(\App\Helpers\StringHelper::toUppercase('Digite os dígitos do RG'), ENT_QUOTES, 'UTF-8'); ?>" required <?php echo !empty($usuario['rg_igual_cpf']) ? 'disabled' : ''; ?> style="border-radius:2px">
                        <div class="mt-1">
                            <input type="checkbox" id="rg_igual_cpf" name="rg_igual_cpf" value="1" <?php echo !empty($usuario['rg_igual_cpf']) ? 'checked' : ''; ?>>
                            <label for="rg_igual_cpf" class="text-sm text-neutral-700">RG IGUAL AO CPF</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label for="telefone" class="block text-sm font-medium text-neutral-700 mb-1">TELEFONE <span style="color:#525252">*</span></label>
                        <input type="text" class="w-full px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black" id="telefone" name="telefone"
                            value="<?php echo htmlspecialchars($usuario['telefone'] ?? ''); ?>"
                            placeholder="(00) 00000-0000" required style="border-radius:2px">
                    </div>
                </div>

                <div class="mb-3 mt-3">
                    <label for="email" class="block text-sm font-medium text-neutral-700 mb-1">EMAIL <span style="color:#525252">*</span></label>
                    <input type="email" class="w-full px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black" id="email" name="email"
                        value="<?php echo htmlspecialchars(\App\Helpers\StringHelper::toUppercase($usuario['email']), ENT_QUOTES, 'UTF-8'); ?>" required style="border-radius:2px">
                </div>

                <!-- Nota fixa sobre senha: não utilizar a classe 'alert' para evitar auto-dismiss do layout JS -->
                <div class="p-2" style="background:#fafafa;border:1px solid #d4d4d4;border-radius:2px">
                    <i class="bi bi-info-circle me-2" style="color:#171717"></i>
                    <strong style="color:#171717">Nota:</strong> <span style="color:#171717">DEIXE OS CAMPOS DE SENHA EM BRANCO PARA MANTER A SENHA ATUAL</span>
                </div>

                <div class="row">
                    <div class="col-12">
                        <label for="senha" class="block text-sm font-medium text-neutral-700 mb-1">NOVA SENHA</label>
                        <input type="password" class="w-full px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black" id="senha" name="senha" minlength="6" style="border-radius:2px">
                        <small class="text-neutral-500 text-sm">MÍNIMO DE 6 CARACTERES</small>
                    </div>

                    <div class="col-12">
                        <label for="confirmar_senha" class="block text-sm font-medium text-neutral-700 mb-1">CONFIRMAR NOVA SENHA</label>
                        <input type="password" class="w-full px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black" id="confirmar_senha" name="confirmar_senha" minlength="6" style="border-radius:2px">
                    </div>
                </div>


            </div>
        </div>

        <!-- Card 2: ESTADO CIVIL -->
        <div class="bg-white border border-neutral-200 mb-4" style="border-radius:2px">
            <div class="bg-neutral-50 px-6 py-3 border-b border-neutral-200 flex items-center gap-2">
                <i class="bi bi-person-hearts text-neutral-600"></i>
                <span class="font-semibold text-neutral-700">ESTADO CIVIL</span>
            </div>
            <div class="p-6">
                <div>
                    <input type="checkbox" id="casado" name="casado" value="1" <?php echo !empty($usuario['casado']) ? 'checked' : ''; ?>>
                    <label for="casado" class="text-sm text-neutral-700"><?php echo htmlspecialchars(\App\Helpers\StringHelper::toUppercase('Sou casado(a)'), ENT_QUOTES, 'UTF-8'); ?></label>
                </div>
            </div>
        </div>

        <!-- Card 4: DADOS DO CÔNJUGE (condicional) -->
        <div id="cardConjuge" class="bg-white border border-neutral-200 mb-4 <?php echo empty($usuario['casado']) ? 'hidden' : ''; ?>" style="border-radius:2px">
            <div class="bg-neutral-50 px-6 py-3 border-b border-neutral-200 flex items-center gap-2">
                <i class="bi bi-people-fill text-neutral-600"></i>
                <span class="font-semibold text-neutral-700">DADOS DO CÔNJUGE</span>
            </div>
            <div class="p-6 space-y-4">
                <div class="mb-3">
                    <label for="nome_conjuge" class="block text-sm font-medium text-neutral-700 mb-1">NOME COMPLETO DO CÔNJUGE</label>
                    <input type="text" class="w-full px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black" id="nome_conjuge" name="nome_conjuge" value="<?php echo htmlspecialchars($usuario['nome_conjuge'] ?? ''); ?>" style="border-radius:2px">
                </div>
                <div class="row">
                    <div class="col-12">
                        <label for="cpf_conjuge" class="block text-sm font-medium text-neutral-700 mb-1">CPF DO CÔNJUGE</label>
                        <input type="text" class="w-full px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black" id="cpf_conjuge" name="cpf_conjuge" value="<?php echo htmlspecialchars($usuario['cpf_conjuge'] ?? ''); ?>" placeholder="000.000.000-00" style="border-radius:2px">
                    </div>
                    <div class="col-12">
                        <label for="rg_conjuge" class="block text-sm font-medium text-neutral-700 mb-1">RG DO CÔNJUGE</label>
                        <input type="text" class="w-full px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black" id="rg_conjuge" name="rg_conjuge" value="<?php echo htmlspecialchars($usuario['rg_conjuge'] ?? ''); ?>" placeholder="<?php echo htmlspecialchars(\App\Helpers\StringHelper::toUppercase('Digite os dígitos do RG'), ENT_QUOTES, 'UTF-8'); ?>" style="border-radius:2px">
                        <div class="mt-1">
                            <input type="checkbox" id="rg_conjuge_igual_cpf" name="rg_conjuge_igual_cpf" value="1" <?php echo !empty($usuario['rg_conjuge_igual_cpf']) ? 'checked' : ''; ?>>
                            <label for="rg_conjuge_igual_cpf" class="text-sm text-neutral-700">RG DO CÔNJUGE IGUAL AO CPF DO CÔNJUGE</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label for="telefone_conjuge" class="block text-sm font-medium text-neutral-700 mb-1">TELEFONE DO CÔNJUGE</label>
                        <input type="text" class="w-full px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black" id="telefone_conjuge" name="telefone_conjuge" value="<?php echo htmlspecialchars($usuario['telefone_conjuge'] ?? ''); ?>" placeholder="(00) 00000-0000" style="border-radius:2px">
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 4: ENDEREÇO -->
        <div class="bg-white border border-neutral-200 mb-4" style="border-radius:2px">
            <div class="bg-neutral-50 px-6 py-3 border-b border-neutral-200 flex items-center gap-2">
                <i class="bi bi-geo-alt text-neutral-600"></i>
                <span class="font-semibold text-neutral-700">ENDEREÇO</span>
            </div>
            <div class="p-6 space-y-4">
                <div class="row">
                    <div class="col-12">
                        <label for="cep" class="block text-sm font-medium text-neutral-700 mb-1">CEP</label>
                        <input type="text" class="w-full px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black" id="cep" name="endereco_cep"
                            value="<?php echo htmlspecialchars($usuario['endereco_cep'] ?? ''); ?>"
                            placeholder="00000-000" style="border-radius:2px">
                        <small class="text-neutral-500 text-sm">PREENCHA PARA BUSCAR AUTOMATICAMENTE</small>
                    </div>
                    <div class="col-12">
                        <label for="logradouro" class="block text-sm font-medium text-neutral-700 mb-1">LOGRADOURO</label>
                        <input type="text" class="w-full px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black" id="logradouro" name="endereco_logradouro"
                            value="<?php echo htmlspecialchars($usuario['endereco_logradouro'] ?? ''); ?>" style="border-radius:2px">
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-12">
                        <label for="numero" class="block text-sm font-medium text-neutral-700 mb-1">NÚMERO</label>
                        <input type="text" class="w-full px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black" id="numero" name="endereco_numero"
                            value="<?php echo htmlspecialchars($usuario['endereco_numero'] ?? ''); ?>" style="border-radius:2px">
                    </div>
                    <div class="col-12">
                        <label for="complemento" class="block text-sm font-medium text-neutral-700 mb-1">COMPLEMENTO</label>
                        <input type="text" class="w-full px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black" id="complemento" name="endereco_complemento"
                            value="<?php echo htmlspecialchars($usuario['endereco_complemento'] ?? ''); ?>"
                            placeholder="Apto, bloco, etc" style="border-radius:2px">
                    </div>
                    <div class="col-12">
                        <label for="bairro" class="block text-sm font-medium text-neutral-700 mb-1">BAIRRO</label>
                        <input type="text" class="w-full px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black" id="bairro" name="endereco_bairro"
                            value="<?php echo htmlspecialchars($usuario['endereco_bairro'] ?? ''); ?>" style="border-radius:2px">
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-12">
                        <label for="cidade" class="block text-sm font-medium text-neutral-700 mb-1">CIDADE</label>
                        <input type="text" class="w-full px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black" id="cidade" name="endereco_cidade"
                            value="<?php echo htmlspecialchars($usuario['endereco_cidade'] ?? ''); ?>" style="border-radius:2px">
                    </div>
                    <div class="col-12">
                        <label for="estado" class="block text-sm font-medium text-neutral-700 mb-1">ESTADO</label>
                        <select class="w-full px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black" id="estado" name="endereco_estado" style="border-radius:2px">
                            <option value="">Selecione</option>
                            <?php
                            $estados = ['AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas', 'BA' => 'Bahia', 'CE' => 'Ceará', 'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo', 'GO' => 'Goiás', 'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul', 'MG' => 'Minas Gerais', 'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná', 'PE' => 'Pernambuco', 'PI' => 'Piauí', 'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte', 'RS' => 'Rio Grande do Sul', 'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina', 'SP' => 'São Paulo', 'SE' => 'Sergipe', 'TO' => 'Tocantins'];
                            foreach ($estados as $sigla => $nome):
                                $selected = ($usuario['endereco_estado'] ?? '') === $sigla ? 'selected' : '';
                            ?>
                                <option value="<?php echo $sigla; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars(\App\Helpers\StringHelper::toUppercase($nome), ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-2 mt-6">
            <button type="submit" class="w-full px-4 py-3 bg-black hover:bg-neutral-900 text-white font-semibold transition" style="border-radius:2px">
                <i class="bi bi-check-lg me-1"></i>ATUALIZAR
            </button>
            <a href="<?php echo htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8'); ?>" class="w-full px-4 py-3 border border-neutral-300 hover:bg-neutral-50 font-semibold transition text-center" style="border-radius:2px;color:#171717">
                <i class="bi bi-arrow-left me-1"></i>VOLTAR
            </a>
        </div>
    </form>

    <!-- Assinaturas removidas do formulário (modal e preview removidos) -->

    <!-- Variáveis PHP necessárias para o JS externo -->
    <script>
        window._editUserRgDigits = '<?php echo preg_replace('/\D/', '', $usuario['rg'] ?? ''); ?>';
        window._editUserRgConjugeDigits = '<?php echo preg_replace('/\D/', '', $usuario['rg_conjuge'] ?? ''); ?>';
    </script>
    <script src="/assets/js/users/edit.js"></script>
<?php endif; ?>

<?php
$contentHtml = ob_get_clean();
include $projectRoot . '/src/Views/layouts/app.php';
?>
