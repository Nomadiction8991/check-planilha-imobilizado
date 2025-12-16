<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
 // AUTENTICAÇÃO

// VISUALIZAR / EDITAR LÓGICA:
// - Qualquer usuário acessa sua própria página em modo edição
// - Administrador pode visualizar outros usuários em modo somente leitura
// - Doador/CÔNJUGE NÃO tem listagem, então só verá seu próprio usuário
$idParam = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$loggedId = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 0;
$isSelf = ($idParam && $loggedId && $idParam === $loggedId);
if (!$idParam) {
    header('Location: ../../../index.php');
    exit;
}
// Se NÃO for self e NÃO for admin, bloquear
if (!$isSelf && !isAdmin()) {
    header('Location: ../../../index.php');
    exit;
}

include __DIR__ . '/../../../app/controllers/update/UsuarioUpdateController.php';

$pageTitle = $isSelf ? 'EDITAR USUÁRIO' : 'VISUALIZAR USUÁRIO';
// Voltar: self e admin vão para listagem; outros vão para index
$backUrl = (isAdmin() || $isSelf) ? './usuarios_listar.php' : '../../../index.php';

ob_start();
?>

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

<style>
.signature-preview-canvas {
    pointer-events: none;
}
</style>

<?php if (isset($usuario)): ?>
<form method="POST" id="formUsuario">

    
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
                           placeholder="DIGITE OS DÍGITOS DO RG" required <?php echo !empty($usuario['rg_igual_cpf']) ? 'disabled' : ''; ?>>
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
                <label class="form-check-label" for="casado">SOU CASADO(A)</label>
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
                    <input type="text" class="form-control" id="rg_conjuge" name="rg_conjuge" value="<?php echo htmlspecialchars($usuario['rg_conjuge'] ?? ''); ?>" placeholder="Digite os dígitos do RG">
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
                        $estados = ['AC'=>'Acre','AL'=>'Alagoas','AP'=>'AmapÃ¡','AM'=>'Amazonas','BA'=>'Bahia','CE'=>'CearÃ¡','DF'=>'Distrito Federal','ES'=>'EspÃ­rito Santo','GO'=>'GoiÃ¡s','MA'=>'MaranhÃ£o','MT'=>'Mato Grosso','MS'=>'Mato Grosso do Sul','MG'=>'Minas Gerais','PA'=>'ParÃ¡','PB'=>'ParaÃ­ba','PR'=>'ParanÃ¡','PE'=>'Pernambuco','PI'=>'PiauÃ­','RJ'=>'Rio de Janeiro','RN'=>'Rio Grande do Norte','RS'=>'Rio Grande do Sul','RO'=>'RondÃ´nia','RR'=>'Roraima','SC'=>'Santa Catarina','SP'=>'SÃ£o Paulo','SE'=>'Sergipe','TO'=>'Tocantins'];
                        foreach($estados as $sigla => $nome):
                            $selected = ($usuario['endereco_estado'] ?? '') === $sigla ? 'selected' : '';
                        ?>
                        <option value="<?php echo $sigla; ?>" <?php echo $selected; ?>><?php echo $nome; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <?php if ($isSelf): ?>
    <div class="d-grid gap-2">
        <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-check-lg me-1"></i>
            ATUALIZAR
        </button>
    </div>
    <?php else: ?>
    <div class="alert alert-secondary mt-2 mb-0 small">
        <i class="bi bi-eye me-1"></i> Modo de visualizaÃ§Ã£o (somente leitura)
    </div>
    <?php endif; ?>
</form>

<!-- Assinaturas removidas do formulário (modal e preview removidos) -->

<script>
// ========== MÁSCARAS COM INPUTMASK (PROTEGIDAS) ==========
(function(){
    function initEditUserForm() {
    try {
        // Se a biblioteca de Inputmask não estiver carregada, ignorar as máscaras
        if (typeof Inputmask !== 'undefined') {
            Inputmask('999.999.999-99').mask('#cpf');
            Inputmask('999.999.999-99').mask('#cpf_conjuge');
            Inputmask(['(99) 99999-9999', '(99) 9999-9999']).mask('#telefone');
            Inputmask(['(99) 99999-9999', '(99) 9999-9999']).mask('#telefone_conjuge');
            Inputmask('99999-999').mask('#cep');
        }

        // RG dinâmico (aplica apenas se os elementos existirem)
        function formatRgDigits(raw) {
            const digits = raw.replace(/\D/g,'');
            if (digits.length <= 1) return digits;
            return digits.slice(0,-1) + '-' + digits.slice(-1);
        }

        const rgInput = document.getElementById('rg');
        if (rgInput) {
            const rgIgualEl = document.getElementById('rg_igual_cpf');
            const cpfEl = document.getElementById('cpf');
            function aplicarRgIgualCpf(aplicar) {
                if (aplicar) {
                    if (typeof Inputmask !== 'undefined') Inputmask('999.999.999-99').mask('#rg');
                    rgInput.value = cpfEl ? cpfEl.value : rgInput.value;
                    rgInput.setAttribute('disabled','disabled');
                } else {
                    rgInput.removeAttribute('disabled');
                    if (typeof Inputmask !== 'undefined') Inputmask.remove('#rg');
                    rgInput.value = formatRgDigits('<?php echo preg_replace('/\D/','', $usuario['rg'] ?? ''); ?>');
                }
            }
            if (rgIgualEl) rgInput.addEventListener('input', function(){ if (!rgIgualEl.checked) this.value = formatRgDigits(this.value); });
            if (rgIgualEl) rgIgualEl.addEventListener('change', function(){ aplicarRgIgualCpf(this.checked); });
            if (cpfEl) cpfEl.addEventListener('input', function(){ if (rgIgualEl && rgIgualEl.checked) aplicarRgIgualCpf(true); });
            aplicarRgIgualCpf(rgIgualEl ? rgIgualEl.checked : false);
        }

        const rgConjInput = document.getElementById('rg_conjuge');
        if (rgConjInput) {
            const rgConjIgualEl = document.getElementById('rg_conjuge_igual_cpf');
            const cpfConjEl = document.getElementById('cpf_conjuge');
            function aplicarRgConjugeIgualCpf(aplicar) {
                if (aplicar) {
                    if (typeof Inputmask !== 'undefined') Inputmask('999.999.999-99').mask('#rg_conjuge');
                    rgConjInput.value = cpfConjEl ? cpfConjEl.value : rgConjInput.value;
                    rgConjInput.setAttribute('disabled','disabled');
                } else {
                    rgConjInput.removeAttribute('disabled');
                    if (typeof Inputmask !== 'undefined') Inputmask.remove('#rg_conjuge');
                    rgConjInput.value = formatRgDigits('<?php echo preg_replace('/\D/','', $usuario['rg_conjuge'] ?? ''); ?>');
                }
            }
            if (rgConjIgualEl) rgConjInput.addEventListener('input', function(){ if (!rgConjIgualEl.checked) this.value = formatRgDigits(this.value); });
            if (rgConjIgualEl) rgConjIgualEl.addEventListener('change', function(){ aplicarRgConjugeIgualCpf(this.checked); });
            if (cpfConjEl) cpfConjEl.addEventListener('input', function(){ if (rgConjIgualEl && rgConjIgualEl.checked) aplicarRgConjugeIgualCpf(true); });
            aplicarRgConjugeIgualCpf(rgConjIgualEl ? rgConjIgualEl.checked : false);
        }

        // Toggle do bloco do cônjuge (IIFE garante execução isolada)
        (function(){
            const casadoCb = document.getElementById('casado');
            const card = document.getElementById('cardConjuge');
            if (!casadoCb || !card) return;
            const setVisibility = () => {
                card.style.display = casadoCb.checked ? '' : 'none';
                try { console.debug('usuario_editar: casado = ' + (casadoCb.checked ? 'true' : 'false')); } catch(e){}
            };
            casadoCb.addEventListener('change', setVisibility);
            setVisibility();
        })();

    } catch (e) {
        console.error('Erro ao inicializar máscaras/inputs em usuario_editar:', e);
    }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEditUserForm);
    } else {
        initEditUserForm();
    }
})();

// ========== VIACEP: BUSCA AUTOMÃTICA DE ENDEREÃ‡O ==========
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
                alert('CEP NÃO encontrado!');
                document.getElementById('logradouro').value = '';
                return;
            }
            
            document.getElementById('logradouro').value = data.logradouro || '';
            document.getElementById('bairro').value = data.bairro || '';
            document.getElementById('cidade').value = data.localidade || '';
            document.getElementById('estado').value = data.uf || '';
            
            // Focar no nÃºmero apÃ³s preencher
            document.getElementById('numero').focus();
        })
        .catch(error => {
            console.error('Erro ao buscar CEP:', error);
            alert('Erro ao buscar CEP. Tente novamente.');
            document.getElementById('logradouro').value = '';
        });
});

// ========== ASSINATURA DIGITAL (PADRÃƒO MODAL) ==========
// VariÃ¡veis globais
// Assinatura JS removida: funcionalidades e manipulações de DOM para captura/preview de assinaturas foram removidas.
// Mantivemos apenas um comportamento de 'readonly' para visualização quando não é o próprio usuário.

// Funções de assinatura removidas. Mantemos stubs para evitar erros caso chamadas permaneçam em código legado.
window.limparModalAssinatura = function(){ /* assinatura removida */ };
window.salvarModalAssinatura = function(){ /* assinatura removida */ };
window.fecharModalAssinatura = async function(){ /* assinatura removida */ };

// Se NÃO é o próprio usuário, desabilitar todos campos
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!$isSelf): ?>
    Array.from(document.querySelectorAll('#formUsuario input, #formUsuario select, #formUsuario button.btn-primary')).forEach(el => {
        if (el.tagName === 'BUTTON') {
            el.disabled = true;
        } else {
            el.setAttribute('disabled','disabled');
        }
    });
    // Evitar submissão
    const form = document.getElementById('formUsuario');
    form.addEventListener('submit', e => { e.preventDefault(); });
    <?php endif; ?>
});

// ========== VALIDAÃ‡ÃƒO E ENVIO DO FORMULÃRIO ==========
<?php if ($isSelf): ?>
document.getElementById('formUsuario').addEventListener('submit', function(e) {
    const senha = document.getElementById('senha').value;
    const confirmar = document.getElementById('confirmar_senha').value;
    
    // ValidaÃ§Ã£o senhas (somente se preenchidas)
    if (senha || confirmar) {
        if (senha !== confirmar) {
            e.preventDefault();
            alert('As senhas NÃO conferem!');
            return false;
        }
    }
    // EndereÃ§o obrigatÃ³rio
    const enderecoObrigatorios = ['cep','logradouro','numero','bairro','cidade','estado'];
    for (let id of enderecoObrigatorios) { const el = document.getElementById(id); if (!el.value.trim()) { e.preventDefault(); alert('Todos os campos de endereÃ§o sÃ£o obrigatÃ³rios.'); return false; } }

    // Assinaturas foram removidas do formulário; não são mais obrigatórias

    // ValidaÃ§Ã£o de cÃ´njuge se casado
    if (document.getElementById('casado').checked) {
        const obrigatoriosConjuge = ['nome_conjuge','cpf_conjuge','telefone_conjuge'];
        for (let id of obrigatoriosConjuge) {
            const el = document.getElementById(id);
            if (el && !el.value.trim()) {
                e.preventDefault();
                alert('Preencha todos os dados obrigatÃ³rios do cÃ´njuge.');
                return false;
            }
        }
        // Assinatura do cônjuge removida do formulário
    }
    // Assinaturas jÃ¡ estÃ£o salvas nos campos hidden
});
<?php endif; ?>
</script>
<?php endif; ?>

<?php
$contentHtml = ob_get_clean();
$tempFile = __DIR__ . '/../../../temp_editar_usuario_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app_wrapper.php';
unlink($tempFile);
?>


