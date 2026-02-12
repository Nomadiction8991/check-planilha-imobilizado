<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

if (isset($_GET['public']) && $_GET['public'] == '1') {
    define('PUBLIC_REGISTER', true);
}


if (!defined('PUBLIC_REGISTER')) {
    
    if (!function_exists('isLoggedIn') || !isLoggedIn()) {
        header('Location: ../../../login.php');
        exit;
    }
} else {
    
    if (session_STATUS() === PHP_SESSION_NONE) {
        session_start();
    }
    
    
}

include __DIR__ . '/../../../app/controllers/create/UsuarioCreateController.php';

$pageTitle = defined('PUBLIC_REGISTER') ? 'CADASTRO' : 'NOVO USUÁRIO';
if (defined('PUBLIC_REGISTER')) {
    $backUrl = '../../../login.php';
} else {
    
    $qsArr = [];
    if (!empty($_GET['busca'])) { $qsArr['busca'] = $_GET['busca']; }
    if (isset($_GET['status']) && $_GET['status'] !== '') { $qsArr['status'] = $_GET['status']; }
    if (!empty($_GET['pagina'])) { $qsArr['pagina'] = $_GET['pagina']; }
    $backUrl = './usuarios_listar.php' . ($qsArr ? ('?' . http_build_query($qsArr)) : '');
}

ob_start();
?>

<style>
.signature-preview-canvas {
    pointer-events: none;
}


.signature-preview-canvas {
    pointer-events: none;
}
</style>


<?php if (!empty($mensagem)): ?>
    <div class="alert alert-<?php echo $tipo_mensagem === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
        <?php echo to_uppercase(\voku\helper\UTF8::fix_utf8(htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8'))); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>


<!-- JQUERY e INPUTMASK -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.8/dist/inputmask.min.js"></script>
<!-- SIGNATUREPAD -->
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>



<form method="POST" id="formUsuario">
    <?php ?>
    <?php if (!defined('PUBLIC_REGISTER')): ?>
        <input type="hidden" name="busca" value="<?php echo htmlspecialchars($_GET['busca'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="status" value="<?php echo htmlspecialchars($_GET['status'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="pagina" value="<?php echo htmlspecialchars($_GET['pagina'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <!-- CAMPO OCULTO: tipo de usuário (apenas para registro público) -->
    <?php if (defined('PUBLIC_REGISTER')): ?>
        <input type="hidden" name="tipo" value="DOADOR/CÔNJUGE">
    <?php endif; ?>

    
    <!-- CARD 1: DADOS BÁSICOS -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-person-plus me-2"></i>
            DADOS BÁSICOS
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="nome" class="form-label">NOME COMPLETO <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="nome" name="nome" 
                       value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>" required>
            </div>

            <div class="row g-3">
                <div class="col-12">
                    <label for="cpf" class="form-label">CPF <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="cpf" name="cpf" 
                           value="<?php echo htmlspecialchars($_POST['cpf'] ?? ''); ?>" 
                           placeholder="000.000.000-00" required>
                </div>
                <div class="col-12">
                    <label for="rg" class="form-label">RG <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="rg" name="rg" 
                           value="<?php echo htmlspecialchars($_POST['rg'] ?? ''); ?>" 
                           placeholder="<?php echo htmlspecialchars(to_uppercase('Digite os dígitos do RG'), ENT_QUOTES, 'UTF-8'); ?>" required> 
                    <div class="form-check mt-1">
                        <input class="form-check-input" type="checkbox" id="rg_igual_cpf" name="rg_igual_cpf" value="1">
                        <label class="form-check-label" for="rg_igual_cpf">RG igual ao CPF</label>
                    </div>

                </div>
                <div class="col-12">
                    <label for="telefone" class="form-label">TELEFONE <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="telefone" name="telefone" 
                           value="<?php echo htmlspecialchars($_POST['telefone'] ?? ''); ?>" 
                           placeholder="(00) 00000-0000" required>
                </div>
            </div>

            <div class="mb-3 mt-3">
                <label for="email" class="form-label">EMAIL <span class="text-danger">*</span></label>
                <input type="email" class="form-control text-uppercase" id="email" name="email" 
                       value="<?php echo htmlspecialchars(to_uppercase($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>

            <div class="row g-3">
                <div class="col-12">
                    <label for="senha" class="form-label">SENHA <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="senha" name="senha" 
                           minlength="6" required>
                    <small class="text-muted">MÍNIMO DE 6 CARACTERES</small>
                </div>

                <div class="col-12">
                    <label for="confirmar_senha" class="form-label">CONFIRMAR SENHA <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" 
                           minlength="6" required>
                </div>
            </div>




        </div>
    </div>

    <!-- CARD 2: ESTADO CIVIL -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-person-hearts me-2"></i>
            ESTADO CIVIL
        </div>
        <div class="card-body">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="casado" name="casado" value="1">
                <label class="form-check-label" for="casado"><?php echo htmlspecialchars(to_uppercase('Sou casado(a)'), ENT_QUOTES, 'UTF-8'); ?></label>
            </div>
        </div>
    </div>

    <!-- CARD 4: DADOS DO CÔNJUGE (condicional) -->
    <div id="cardConjuge" class="card mb-3" style="display:none;">
        <div class="card-header">
            <i class="bi bi-people-fill me-2"></i>
            DADOS DO CÔNJUGE
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="nome_conjuge" class="form-label">NOME COMPLETO DO CÔNJUGE</label>
                <input type="text" class="form-control" id="nome_conjuge" name="nome_conjuge" value="<?php echo htmlspecialchars($_POST['nome_conjuge'] ?? ''); ?>">
            </div>
            <div class="row g-3">
                <div class="col-12">
                    <label for="cpf_conjuge" class="form-label">CPF DO CÔNJUGE</label>
                    <input type="text" class="form-control" id="cpf_conjuge" name="cpf_conjuge" value="<?php echo htmlspecialchars($_POST['cpf_conjuge'] ?? ''); ?>" placeholder="000.000.000-00">
                </div>
                <div class="col-12">
                    <label for="rg_conjuge" class="form-label">RG DO CÔNJUGE</label>
                    <input type="text" class="form-control" id="rg_conjuge" name="rg_conjuge" value="<?php echo htmlspecialchars($_POST['rg_conjuge'] ?? ''); ?>" placeholder="<?php echo htmlspecialchars(to_uppercase('Digite os dígitos do RG'), ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="form-check mt-1">
                        <input class="form-check-input" type="checkbox" id="rg_conjuge_igual_cpf" name="rg_conjuge_igual_cpf" value="1">
                        <label class="form-check-label" for="rg_conjuge_igual_cpf">RG DO CÔNJUGE IGUAL AO CPF DO CÔNJUGE</label>
                    </div>
                </div>
                <div class="col-12">
                    <label for="telefone_conjuge" class="form-label">TELEFONE DO CÔNJUGE</label>
                    <input type="text" class="form-control" id="telefone_conjuge" name="telefone_conjuge" value="<?php echo htmlspecialchars($_POST['telefone_conjuge'] ?? ''); ?>" placeholder="(00) 00000-0000">
                </div>
            </div>
        </div>
    </div>

    <!-- Card 4: ENDEREÇO -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-geo-alt me-2"></i>
            Endereço
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12">
                    <label for="cep" class="form-label">CEP</label>
                    <input type="text" class="form-control" id="cep" name="endereco_cep" 
                           value="<?php echo htmlspecialchars($_POST['endereco_cep'] ?? ''); ?>" 
                           placeholder="00000-000">
                    <small class="text-muted">PREENCHA PARA BUSCAR AUTOMATICAMENTE</small>
                </div>
                <div class="col-12">
                    <label for="logradouro" class="form-label">LOGRADOURO</label>
                    <input type="text" class="form-control" id="logradouro" name="endereco_logradouro" 
                           value="<?php echo htmlspecialchars($_POST['endereco_logradouro'] ?? ''); ?>">
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-12">
                    <label for="numero" class="form-label">NÚMERO</label>
                    <input type="text" class="form-control" id="numero" name="endereco_numero" 
                           value="<?php echo htmlspecialchars($_POST['endereco_numero'] ?? ''); ?>">
                </div>
                <div class="col-12">
                    <label for="complemento" class="form-label">COMPLEMENTO</label>
                    <input type="text" class="form-control" id="complemento" name="endereco_complemento" 
                           value="<?php echo htmlspecialchars($_POST['endereco_complemento'] ?? ''); ?>" 
                           placeholder="Apto, bloco, etc">
                </div>
                <div class="col-12">
                    <label for="bairro" class="form-label">BAIRRO</label>
                    <input type="text" class="form-control" id="bairro" name="endereco_bairro" 
                           value="<?php echo htmlspecialchars($_POST['endereco_bairro'] ?? ''); ?>">
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-12">
                    <label for="cidade" class="form-label">CIDADE</label>
                    <input type="text" class="form-control" id="cidade" name="endereco_cidade" 
                           value="<?php echo htmlspecialchars($_POST['endereco_cidade'] ?? ''); ?>">
                </div>
                <div class="col-12">
                    <label for="estado" class="form-label">ESTADO</label>
                    <select class="form-select" id="estado" name="endereco_estado">
                        <option value="">Selecione</option>
                        <?php
                        $estados = [
                            'AC' => 'Acre',
                            'AL' => 'Alagoas',
                            'AP' => 'Amapá',
                            'AM' => 'Amazonas',
                            'BA' => 'Bahia',
                            'CE' => 'Ceará',
                            'DF' => 'Distrito Federal',
                            'ES' => 'Espírito Santo',
                            'GO' => 'Goiás',
                            'MA' => 'Maranhão',
                            'MT' => 'Mato Grosso',
                            'MS' => 'Mato Grosso do Sul',
                            'MG' => 'Minas Gerais',
                            'PA' => 'Pará',
                            'PB' => 'Paraíba',
                            'PR' => 'Paraná',
                            'PE' => 'Pernambuco',
                            'PI' => 'Piauí',
                            'RJ' => 'Rio de Janeiro',
                            'RN' => 'Rio Grande do Norte',
                            'RS' => 'Rio Grande do Sul',
                            'RO' => 'Rondônia',
                            'RR' => 'Roraima',
                            'SC' => 'Santa Catarina',
                            'SP' => 'São Paulo',
                            'SE' => 'Sergipe',
                            'TO' => 'Tocantins'
                        ];
                        foreach($estados as $sigla => $nome) {
                            $selected = ($_POST['endereco_estado'] ?? '') === $sigla ? 'selected' : ''; 
                        ?>
                            <option value="<?php echo $sigla; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars(to_uppercase($nome), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="d-grid gap-2">
        <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-check-lg me-1"></i>
            CADASTRAR USUÁRIO
        </button> 
    </div>
</form>

<!-- Assinaturas removidas: campos e validações relacionados a assinaturas foram removidos em razão da alteração do esquema de banco de dados. -->

<script>
// ========== MÁSCARAS COM INPUTMASK ==========
$(document).ready(function() {
    // Máscara CPF: 000.000.000-00
    Inputmask('999.999.999-99').mask('#cpf');
    Inputmask('999.999.999-99').mask('#cpf_conjuge');
    
    // Máscara TELEFONE: (00) 00000-0000 ou (00) 0000-0000
    Inputmask(['(99) 99999-9999', '(99) 9999-9999']).mask('#telefone');
    Inputmask(['(99) 99999-9999', '(99) 9999-9999']).mask('#telefone_conjuge');
    
    // Máscara CEP: 00000-000
    Inputmask('99999-999').mask('#cep');

    // Máscara RG: dígitos com traço antes do último (1 a 8 dígitos + '-' + 1 dígito ou X)
    // Removemos máscara de RG para usar formatação dinâmica

    // Toggle RG igual ao CPF
    // ======= RG Dinâmico =======
    function formatRgDigits(raw) {
        const digits = raw.replace(/\D/g,'');
        if (digits.length <= 1) return digits; // 1 dígito sem hífen
        return digits.slice(0,-1) + '-' + digits.slice(-1);
    }
    const rgInput = document.getElementById('rg');
    rgInput.addEventListener('input', function(){
        if (document.getElementById('rg_igual_cpf').checked) return; // quando igual CPF NÃO formata manual
        const pos = this.selectionStart;
        const raw = this.value;
        const formatted = formatRgDigits(raw);
        this.value = formatted;
    });
    function aplicarRgIgualCpf(aplicar) {
        if (aplicar) {
            // Aplica máscara de CPF ao RG e copia valor mascarado
            Inputmask('999.999.999-99').mask('#rg');
            const cpfMasked = document.getElementById('cpf').value;
            rgInput.value = cpfMasked;
            rgInput.setAttribute('disabled','disabled');
        } else {
            // Remove máscara e limpa para voltar à formatação dinâmica
            rgInput.removeAttribute('disabled');
            Inputmask.remove('#rg');
            rgInput.value = '';
        }
    }
    document.getElementById('rg_igual_cpf').addEventListener('change', function(){ aplicarRgIgualCpf(this.checked); });
    document.getElementById('cpf').addEventListener('input', function(){ if (document.getElementById('rg_igual_cpf').checked) aplicarRgIgualCpf(true); });

    // ======= RG Cônjuge Dinâmico =======
    const rgConjInput = document.getElementById('rg_conjuge');
    rgConjInput.addEventListener('input', function(){
        if (document.getElementById('rg_conjuge_igual_cpf').checked) return;
        const formatted = formatRgDigits(this.value);
        this.value = formatted;
    });
    function aplicarRgConjugeIgualCpf(aplicar) {
        if (aplicar) {
            Inputmask('999.999.999-99').mask('#rg_conjuge');
            const cpfMasked = document.getElementById('cpf_conjuge').value;
            rgConjInput.value = cpfMasked;
            rgConjInput.setAttribute('disabled','disabled');
        } else {
            rgConjInput.removeAttribute('disabled');
            Inputmask.remove('#rg_conjuge');
            rgConjInput.value = '';
        }
    }
    document.getElementById('rg_conjuge_igual_cpf').addEventListener('change', function(){ aplicarRgConjugeIgualCpf(this.checked); });
    document.getElementById('cpf_conjuge').addEventListener('input', function(){ if (document.getElementById('rg_conjuge_igual_cpf').checked) aplicarRgConjugeIgualCpf(true); });

    // Toggle cônjuge (mais robusto)
    (function(){
        const casadoCb = document.getElementById('casado');
        const card = document.getElementById('cardConjuge');
        if (!casadoCb || !card) return;

        function setRequiredOnConjuge(aplicar) {
            const ids = ['nome_conjuge','cpf_conjuge','telefone_conjuge'];
            ids.forEach(id => {
                const el = document.getElementById(id);
                if (!el) return;
                if (aplicar) {
                    el.setAttribute('required','required');
                } else {
                    el.removeAttribute('required');
                }
            });

            // add/remove asterisks on labels
            const labels = {
                nome_conjuge: 'NOME COMPLETO DO CÔNJUGE',
                cpf_conjuge: 'CPF DO CÔNJUGE',
                telefone_conjuge: 'TELEFONE DO CÔNJUGE'
            };
            for (const id in labels) {
                const label = document.querySelector('label[for="' + id + '"]');
                if (!label) continue;
                if (aplicar) {
                    if (!label.querySelector('.required-asterisk')) {
                        const span = document.createElement('span');
                        span.className = 'text-danger required-asterisk ms-1';
                        span.textContent = '*';
                        label.appendChild(span);
                    }
                } else {
                    const star = label.querySelector('.required-asterisk');
                    if (star) star.remove();
                }
            }
        }

        const setVisibility = () => {
            card.style.display = casadoCb.checked ? '' : 'none';
            setRequiredOnConjuge(casadoCb.checked);
        };
        casadoCb.addEventListener('change', setVisibility);
        // inicializa baseado no estado atual
        setVisibility();
    })();
});

// ========== VIACEP: BUSCA AUTOMÁTICA DE ENDEREÇO ==========
document.getElementById('cep').addEventListener('blur', function() {
    const cep = this.value.replace(/\D/g, '');
    
    if (cep.length !== 8) return;
    
    // LIMPAR campos antes de buscar
    document.getElementById('logradouro').value = 'Buscando...';
    document.getElementById('bairro').value = '';
    document.getElementById('cidade').value = '';
    document.getElementById('estado').value = '';
    
    fetch(`https://viacep.com.br/ws/${cep}/json/`)
        .then(response => response.json())
        .then(data => {
            if (data.erro) {
                showFlash('danger', 'CEP NÃO encontrado!');
                document.getElementById('logradouro').value = '';
                return;
            }
            
            document.getElementById('logradouro').value = data.logradouro || '';
            document.getElementById('bairro').value = data.bairro || '';
            document.getElementById('cidade').value = data.localidade || '';
            document.getElementById('estado').value = data.uf || '';
            
            // Focar no número após preencher
            document.getElementById('numero').focus();
        })
        .catch(error => {
            console.error('Erro ao buscar CEP:', error);
            showFlash('danger', 'Erro ao buscar CEP. Tente novamente.');
            document.getElementById('logradouro').value = '';
        });
});

// ========== HELPER: showFlash (local copy) ==========
function showFlash(type, message) {
    const el = document.createElement('div');
    el.className = 'alert alert-' + type + ' alert-dismissible fade show';
    el.setAttribute('role', 'alert');
    const icon = (type === 'success') ? 'check-circle' : 'exclamation-triangle';
    el.innerHTML = '<i class="bi bi-' + icon + ' me-2"></i><span></span><button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    el.querySelector('span').textContent = message;
    const container = document.querySelector('.app-content .container-fluid') || document.querySelector('.app-content') || document.body;
    container.insertBefore(el, container.firstChild);
}

// ========== VALIDAÇÃO E ENVIO DO FORMULÁRIO ==========
document.getElementById('formUsuario').addEventListener('submit', function(e) {
    const senha = document.getElementById('senha').value;
    const confirmar = document.getElementById('confirmar_senha').value;
    
    // Validar senhas
    if (senha !== confirmar) {
        e.preventDefault();
        showFlash('danger', 'AS SENHAS NÃO CONFEREM!');
        return false;
    }
    
    // Campos endereço obrigatórios
    const enderecoObrigatorios = ['cep','logradouro','numero','bairro','cidade','estado'];
    for (let id of enderecoObrigatorios) {
        const el = document.getElementById(id);
        if (!el.value.trim()) { e.preventDefault(); showFlash('danger', 'TODOS OS CAMPOS DE ENDEREÇO SÃO OBRIGATÓRIOS.'); return false; }
    }

    if (document.getElementById('casado').checked) {
        const obrigatoriosConjuge = ['nome_conjuge','cpf_conjuge','telefone_conjuge'];
        for (let id of obrigatoriosConjuge) {
            const el = document.getElementById(id);
            if (!el.value.trim()) { e.preventDefault(); showFlash('danger', 'PREENCHA TODOS OS DADOS OBRIGATÓRIOS DO CÔNJUGE.'); return false; }
        }
    }
});
</script>

<?php
$contentHtml = ob_get_clean();
$tempFile = __DIR__ . '/../../../temp_create_usuario_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app_wrapper.php';
unlink($tempFile);
?>


