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
    <!-- Tipo de usuário para registro público -->
    <?php if ($publicRegister): ?>
        <input type="hidden" name="tipo" value="DOADOR/CÔNJUGE">
    <?php endif; ?>

    <!-- CARD 1: DADOS BÁSICOS -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-person-plus me-2"></i>DADOS BÁSICOS
        </div>
        <div class="card-body">
            <?= FormHelper::text('nome', 'NOME COMPLETO', $old['nome'] ?? '', [
                'required' => true,
                'placeholder' => 'DIGITE O NOME COMPLETO'
            ]) ?>

            <div class="row g-3">
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

            <div class="row g-3">
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
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-person-hearts me-2"></i>ESTADO CIVIL
        </div>
        <div class="card-body">
            <?= FormHelper::checkbox('casado', 'SOU CASADO(A)', ($old['casado'] ?? false), [
                'id' => 'casado'
            ]) ?>
        </div>
    </div>

    <!-- CARD 3: DADOS DO CÔNJUGE -->
    <div id="cardConjuge" class="card mb-3" style="display:none;">
        <div class="card-header">
            <i class="bi bi-people-fill me-2"></i>DADOS DO CÔNJUGE
        </div>
        <div class="card-body">
            <?= FormHelper::text('nome_conjuge', 'NOME COMPLETO DO CÔNJUGE', $old['nome_conjuge'] ?? '', [
                'id' => 'nome_conjuge'
            ]) ?>

            <div class="row g-3">
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
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-geo-alt me-2"></i>ENDEREÇO
        </div>
        <div class="card-body">
            <?= FormHelper::text('endereco_cep', 'CEP', $old['endereco_cep'] ?? '', [
                'id' => 'cep',
                'placeholder' => '00000-000',
                'help' => 'Preencha para buscar automaticamente'
            ]) ?>

            <?= FormHelper::text('endereco_logradouro', 'LOGRADOURO', $old['endereco_logradouro'] ?? '', [
                'id' => 'logradouro'
            ]) ?>

            <div class="row g-3">
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

            <div class="row g-3">
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
    <?= FormHelper::buttons(
        'CADASTRAR USUÁRIO',
        $publicRegister ? '/login' : '/usuarios'
    ) ?>
</form>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.8/dist/inputmask.min.js"></script>

<script>
    $(document).ready(function() {
        // Máscaras
        Inputmask('999.999.999-99').mask('#cpf, #cpf_conjuge');
        Inputmask('99.999.999-9').mask('#rg, #rg_conjuge');
        Inputmask('(99) 99999-9999').mask('#telefone, #telefone_conjuge');
        Inputmask('99999-999').mask('#cep');

        // Mostrar/ocultar dados do cônjuge
        $('#casado').on('change', function() {
            $('#cardConjuge').toggle(this.checked);

            // Tornar campos obrigatórios se casado
            if (this.checked) {
                $('#nome_conjuge, #cpf_conjuge, #rg_conjuge, #telefone_conjuge')
                    .attr('required', true);
            } else {
                $('#nome_conjuge, #cpf_conjuge, #rg_conjuge, #telefone_conjuge')
                    .removeAttr('required');
            }
        });

        // RG igual ao CPF
        $('#rg_igual_cpf').on('change', function() {
            if (this.checked) {
                const cpf = $('#cpf').val().replace(/\D/g, '');
                $('#rg').val(cpf).prop('readonly', true);
            } else {
                $('#rg').val('').prop('readonly', false);
            }
        });

        $('#rg_conjuge_igual_cpf').on('change', function() {
            if (this.checked) {
                const cpf = $('#cpf_conjuge').val().replace(/\D/g, '');
                $('#rg_conjuge').val(cpf).prop('readonly', true);
            } else {
                $('#rg_conjuge').val('').prop('readonly', false);
            }
        });

        // Buscar CEP
        $('#cep').on('blur', function() {
            const cep = $(this).val().replace(/\D/g, '');

            if (cep.length === 8) {
                $.getJSON(`https://viacep.com.br/ws/${cep}/json/`, function(data) {
                    if (!data.erro) {
                        $('#logradouro').val(data.logradouro.toUpperCase());
                        $('#bairro').val(data.bairro.toUpperCase());
                        $('#cidade').val(data.localidade.toUpperCase());
                        $('#estado').val(data.uf);
                        $('#numero').focus();
                    } else {
                        alert('CEP NÃO ENCONTRADO!');
                    }
                }).fail(function() {
                    alert('ERRO AO BUSCAR CEP!');
                });
            }
        });

        // Validar senha
        $('#formUsuario').on('submit', function(e) {
            const senha = $('#senha').val();
            const confirmar = $('#confirmar_senha').val();

            if (senha !== confirmar) {
                e.preventDefault();
                alert('AS SENHAS NÃO COINCIDEM!');
                return false;
            }

            if (senha.length < 6) {
                e.preventDefault();
                alert('A SENHA DEVE TER NO MÍNIMO 6 CARACTERES!');
                return false;
            }
        });
    });
</script>