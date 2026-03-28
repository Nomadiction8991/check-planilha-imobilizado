<?php


use App\Helpers\{AlertHelper, FormHelper, ViewHelper};

$publicRegister = $publicRegister ?? false;
$errors = $errors ?? [];
$old = $old ?? [];
?>

<!-- Alertas de Erro -->
<?php if (!empty($errors)): ?>
    <?= AlertHelper::error(implode('<br>', array_map('ViewHelper::upper', $errors))) ?>
<?php endif; ?>

<form method="POST" id="formUsuario">
    <!-- novo usuário inicia ativo por padrão -->
    <input type="hidden" name="ativo" value="1">
    <!-- Tipo de usuário para registro público -->
    <?php if ($publicRegister): ?>
        <input type="hidden" name="tipo" value="DOADOR/CÔNJUGE">
    <?php endif; ?>

    <!-- CARD 1: DADOS BÁSICOS -->
    <div class="bg-white border border-neutral-200 mb-4" style="border-radius:2px">
        <div class="bg-neutral-50 px-6 py-3 border-b border-neutral-200 flex items-center gap-2">
            <i class="bi bi-person-plus text-neutral-600"></i>
            <span class="font-semibold text-neutral-700">DADOS BÁSICOS</span>
        </div>
        <div class="p-6 space-y-4">
            <?= FormHelper::text('nome', 'NOME COMPLETO', $old['nome'] ?? '', [
                'required' => true,
                'placeholder' => 'DIGITE O NOME COMPLETO'
            ]) ?>

            <div class="row">
                <div class="col-12">
                    <?= FormHelper::text('cpf', 'CPF', $old['cpf'] ?? '', [
                        'required' => true,
                        'id' => 'cpf',
                        'placeholder' => '000.000.000-00'
                    ]) ?>
                </div>

                <div class="col-12">
                    <?= FormHelper::text('rg', 'RG', $old['rg'] ?? '', [
                        'required' => true,
                        'id' => 'rg'
                    ]) ?>
                    <?= FormHelper::checkbox('rg_igual_cpf', 'RG IGUAL AO CPF', false, [
                        'id' => 'rg_igual_cpf'
                    ]) ?>
                </div>

                <div class="col-12">
                    <?= FormHelper::text('telefone', 'TELEFONE', $old['telefone'] ?? '', [
                        'required' => true,
                        'id' => 'telefone',
                        'placeholder' => '(00) 00000-0000'
                    ]) ?>
                </div>
            </div>

            <?= FormHelper::email('email', 'EMAIL', $old['email'] ?? '', [
                'required' => true
            ]) ?>

            <div class="row">
                <div class="col-12">
                    <?= FormHelper::password('senha', 'SENHA', [
                        'required' => true,
                        'id' => 'senha',
                        'help' => 'Mínimo de 6 caracteres'
                    ]) ?>
                </div>

                <div class="col-12">
                    <?= FormHelper::password('confirmar_senha', 'CONFIRMAR SENHA', [
                        'required' => true,
                        'id' => 'confirmar_senha'
                    ]) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- CARD 2: ESTADO CIVIL -->
    <div class="bg-white border border-neutral-200 mb-4" style="border-radius:2px">
        <div class="bg-neutral-50 px-6 py-3 border-b border-neutral-200 flex items-center gap-2">
            <i class="bi bi-person-hearts text-neutral-600"></i>
            <span class="font-semibold text-neutral-700">ESTADO CIVIL</span>
        </div>
        <div class="p-6">
            <?= FormHelper::checkbox('casado', 'SOU CASADO(A)', ($old['casado'] ?? false), [
                'id' => 'casado'
            ]) ?>
        </div>
    </div>

    <!-- CARD 3: DADOS DO CÔNJUGE -->
    <div id="cardConjuge" class="bg-white border border-neutral-200 mb-4 hidden" style="border-radius:2px">
        <div class="bg-neutral-50 px-6 py-3 border-b border-neutral-200 flex items-center gap-2">
            <i class="bi bi-people-fill text-neutral-600"></i>
            <span class="font-semibold text-neutral-700">DADOS DO CÔNJUGE</span>
        </div>
        <div class="p-6 space-y-4">
            <?= FormHelper::text('nome_conjuge', 'NOME COMPLETO DO CÔNJUGE', $old['nome_conjuge'] ?? '', [
                'id' => 'nome_conjuge'
            ]) ?>

            <div class="row">
                <div class="col-12">
                    <?= FormHelper::text('cpf_conjuge', 'CPF DO CÔNJUGE', $old['cpf_conjuge'] ?? '', [
                        'id' => 'cpf_conjuge',
                        'placeholder' => '000.000.000-00'
                    ]) ?>
                </div>

                <div class="col-12">
                    <?= FormHelper::text('rg_conjuge', 'RG DO CÔNJUGE', $old['rg_conjuge'] ?? '', [
                        'id' => 'rg_conjuge'
                    ]) ?>
                    <?= FormHelper::checkbox('rg_conjuge_igual_cpf', 'RG DO CÔNJUGE IGUAL AO CPF DO CÔNJUGE', false, [
                        'id' => 'rg_conjuge_igual_cpf'
                    ]) ?>
                </div>

                <div class="col-12">
                    <?= FormHelper::text('telefone_conjuge', 'TELEFONE DO CÔNJUGE', $old['telefone_conjuge'] ?? '', [
                        'id' => 'telefone_conjuge',
                        'placeholder' => '(00) 00000-0000'
                    ]) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- CARD 4: ENDEREÇO -->
    <div class="bg-white border border-neutral-200 mb-4" style="border-radius:2px">
        <div class="bg-neutral-50 px-6 py-3 border-b border-neutral-200 flex items-center gap-2">
            <i class="bi bi-geo-alt text-neutral-600"></i>
            <span class="font-semibold text-neutral-700">ENDEREÇO</span>
        </div>
        <div class="p-6 space-y-4">
            <?= FormHelper::text('endereco_cep', 'CEP', $old['endereco_cep'] ?? '', [
                'id' => 'cep',
                'placeholder' => '00000-000',
                'help' => 'Preencha para buscar automaticamente'
            ]) ?>

            <?= FormHelper::text('endereco_logradouro', 'LOGRADOURO', $old['endereco_logradouro'] ?? '', [
                'id' => 'logradouro'
            ]) ?>

            <div class="row">
                <div class="col-6">
                    <?= FormHelper::text('endereco_numero', 'NÚMERO', $old['endereco_numero'] ?? '', [
                        'id' => 'numero'
                    ]) ?>
                </div>

                <div class="col-6">
                    <?= FormHelper::text('endereco_complemento', 'COMPLEMENTO', $old['endereco_complemento'] ?? '', [
                        'id' => 'complemento',
                        'placeholder' => 'Apto, bloco, etc'
                    ]) ?>
                </div>
            </div>

            <?= FormHelper::text('endereco_bairro', 'BAIRRO', $old['endereco_bairro'] ?? '', [
                'id' => 'bairro'
            ]) ?>

            <div class="row">
                <div class="col-8">
                    <?= FormHelper::text('endereco_cidade', 'CIDADE', $old['endereco_cidade'] ?? '', [
                        'id' => 'cidade'
                    ]) ?>
                </div>

                <div class="col-4">
                    <?php
                    $estados = [
                        '' => 'Selecione',
                        'AC' => 'AC',
                        'AL' => 'AL',
                        'AP' => 'AP',
                        'AM' => 'AM',
                        'BA' => 'BA',
                        'CE' => 'CE',
                        'DF' => 'DF',
                        'ES' => 'ES',
                        'GO' => 'GO',
                        'MA' => 'MA',
                        'MT' => 'MT',
                        'MS' => 'MS',
                        'MG' => 'MG',
                        'PA' => 'PA',
                        'PB' => 'PB',
                        'PR' => 'PR',
                        'PE' => 'PE',
                        'PI' => 'PI',
                        'RJ' => 'RJ',
                        'RN' => 'RN',
                        'RS' => 'RS',
                        'RO' => 'RO',
                        'RR' => 'RR',
                        'SC' => 'SC',
                        'SP' => 'SP',
                        'SE' => 'SE',
                        'TO' => 'TO'
                    ];
                    ?>
                    <?= FormHelper::select('endereco_estado', 'UF', $estados, $old['endereco_estado'] ?? '', [
                        'id' => 'estado'
                    ]) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Botões -->
    <div class="flex flex-col gap-2 mt-6">
        <button type="submit" class="w-full px-4 py-3 bg-black hover:bg-neutral-900 text-white font-semibold transition" style="border-radius:2px">
            <i class="bi bi-check-lg me-2"></i>CADASTRAR USUÁRIO
        </button>
        <a href="<?= $publicRegister ? '/login' : '/users' ?>" class="w-full px-4 py-3 border border-neutral-300 hover:bg-neutral-50 font-semibold transition text-center" style="border-radius:2px;color:#171717">
            <i class="bi bi-x-lg me-2"></i>CANCELAR
        </a>
    </div>
</form>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.8/dist/inputmask.min.js"></script>
<script src="/assets/js/users/create.js"></script>
