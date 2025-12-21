<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
// AUTENTICAÇÃO
require_once __DIR__ . '/../../../app/controllers/update/ProdutoUpdateController.php';

$pageTitle = to_uppercase('editar produto');
$backUrl = getReturnUrl($comum_id, $pagina, $filtro_nome, $filtro_dependencia, $filtro_codigo, $filtro_STATUS);

ob_start();
?>

<style>
    .text-uppercase-input {
        text-transform: uppercase;
    }
</style>

<script>
    // Mapeamento de tipos de bens e suas opÃ§Ãµes de bem
    const tiposBensOpcoes = <?php echo json_encode(array_reduce($tipos_bens, function ($carry, $item) {
                                // Separar opÃ§Ãµes por / se houver
                                $opcoes = [];
                                if (!empty($item['descricao'])) {
                                    $partes = explode('/', $item['descricao']);
                                    $opcoes = array_map('trim', $partes);
                                }
                                $carry[$item['id']] = [
                                    'codigo' => $item['codigo'],
                                    'descricao' => $item['descricao'],
                                    'opcoes' => $opcoes
                                ];
                                return $carry;
                            }, [])); ?>;

    document.addEventListener('DOMContentLoaded', function() {
        const selectTipoBEM = document.getElementById('novo_tipo_bem_id');
        const selectBEM = document.getElementById('novo_bem');

        // FunÃ§Ã£o para atualizar opÃ§Ãµes de BEM baseado no TIPO DE BEM selecionado
        function atualizarOpcoesBEM() {
            const tipoBEMId = selectTipoBEM.value;

            if (!tipoBEMId) {
                // Desabilitar e limpar
                selectBEM.disabled = true;
                selectBEM.innerHTML = '<option value="">-- ESCOLHA O TIPO DE BEM ACIMA --</option>';
                return;
            }

            const opcoes = tiposBensOpcoes[tipoBEMId]?.opcoes || [];

            if (opcoes.length > 1) {
                // Tem múltiplas opções separadas por /
                selectBEM.disabled = false;
                selectBEM.innerHTML = '<option value="">-- SELECIONE --</option>';
                opcoes.forEach(opcao => {
                    const opt = document.createElement('option');
                    opt.value = opcao.toUpperCase();
                    opt.textContent = opcao.toUpperCase();
                    selectBEM.appendChild(opt);
                });
            } else if (opcoes.length === 1) {
                // Apenas uma opÃ§Ã£o, preencher automaticamente
                selectBEM.disabled = false;
                selectBEM.innerHTML = '';
                const opt = document.createElement('option');
                opt.value = opcoes[0].toUpperCase();
                opt.textContent = opcoes[0].toUpperCase();
                opt.selected = true;
                selectBEM.appendChild(opt);
            } else {
                // Sem opções, campo livre
                selectBEM.disabled = true;
                selectBEM.innerHTML = '<option value="">-- NÃO APLICÁVEL --</option>';
            }
        }

        // Listener para mudanÃ§a de TIPO DE BEM
        selectTipoBEM.addEventListener('change', atualizarOpcoesBEM);

        // Inicializar estado
        atualizarOpcoesBEM();

        // Converter inputs para uppercase automaticamente
        document.querySelectorAll('.text-uppercase-input').forEach(input => {
            input.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });
        });

        // PrÃ©-preencher BEM usando o valor jÃ¡ processado pelo controller (editado ou original)
        const bemPrefill = '<?php echo !empty($novo_bem) ? addslashes(mb_strtoupper($novo_bem, 'UTF-8')) : ''; ?>';
        if (bemPrefill) {
            if (selectTipoBEM.value) {
                atualizarOpcoesBEM();
                for (const opt of selectBEM.options) {
                    if (opt.value === bemPrefill) {
                        opt.selected = true;
                        break;
                    }
                }
            } else {
                selectBEM.innerHTML = '<option value="' + bemPrefill + '" selected>' + bemPrefill + '</option>';
                selectBEM.disabled = true;
            }
        }
    });
</script>

<?php if (!empty($mensagem)): ?>
    <div class="alert alert-<?php echo $tipo_mensagem === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
        <?php echo $mensagem; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="POST">
    <input type="hidden" name="pagina" value="<?php echo $pagina; ?>">
    <input type="hidden" name="nome" value="<?php echo htmlspecialchars($filtro_nome); ?>">
    <input type="hidden" name="dependencia" value="<?php echo htmlspecialchars($filtro_dependencia); ?>">
    <input type="hidden" name="filtro_codigo" value="<?php echo htmlspecialchars($filtro_codigo); ?>">
    <input type="hidden" name="STATUS" value="<?php echo htmlspecialchars($filtro_STATUS); ?>">

    <div class="card mb-3">
        <div class="card-body">
            <!-- TIPO DE BEM -->
            <div class="mb-3">
                <label for="novo_tipo_bem_id" class="form-label">
                    <i class="bi bi-tag me-1"></i>
                    TIPO DE BEM
                </label>
                <select class="form-select" id="novo_tipo_bem_id" name="novo_tipo_bem_id">
                    <option value=""><?php echo htmlspecialchars(to_uppercase('-- Não alterar --'), ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php foreach ($tipos_bens as $tb): ?>
                        <option value="<?php echo $tb['id']; ?>"
                            <?php echo (isset($novo_tipo_bem_id) && $novo_tipo_bem_id == $tb['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($tb['codigo'] . ' - ' . $tb['descricao']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text"><?php echo htmlspecialchars(to_uppercase('Selecione o tipo de bem para desbloquear o campo "BEM"'), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>

            <!-- BEM (sempre visÃ­vel, desabilitado atÃ© escolher tipo) -->
            <div class="mb-3" id="div_bem">
                <label for="novo_bem" class="form-label">
                    <i class="bi bi-box me-1"></i>
                    BEM
                </label>
                <select class="form-select text-uppercase-input" id="novo_bem" name="novo_bem" disabled>
                    <option value="">-- Escolha o TIPO DE BEM acima --</option>
                </select>
                <div class="form-text"><?php echo htmlspecialchars(to_uppercase('Fica bloqueado até selecionar o TIPO DE BEM'), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>

            <!-- COMPLEMENTO -->
            <div class="mb-3">
                <label for="novo_complemento" class="form-label">
                    <i class="bi bi-card-text me-1"></i>
                    COMPLEMENTO
                </label>
                <textarea class="form-control text-uppercase-input" id="novo_complemento" name="novo_complemento"
                    rows="3" placeholder="<?php echo htmlspecialchars(to_uppercase('Característica + Marca + Medidas'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($novo_complemento ?? ''); ?></textarea>
                <div class="form-text"><?php echo htmlspecialchars(to_uppercase('Deixe em branco para NÃO alterar. Ex: COR PRETA + MARCA XYZ + 1,80M X 0,80M'), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>

            <!-- DEPENDÃŠNCIA -->
            <div class="mb-3">
                <label for="nova_dependencia_id" class="form-label">
                    <i class="bi bi-building me-1"></i>
                    DEPENDÊNCIA
                </label>
                <select class="form-select" id="nova_dependencia_id" name="nova_dependencia_id">
                    <option value=""><?php echo htmlspecialchars(to_uppercase('-- Não alterar --'), ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php foreach ($dependencias as $dep): ?>
                        <option value="<?php echo $dep['id']; ?>"
                            <?php echo (isset($nova_dependencia_id) && $nova_dependencia_id == $dep['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dep['descricao']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary w-100">
        <i class="bi bi-check-lg me-2"></i>
        <?php echo to_uppercase('Salvar alterações'); ?>
    </button>
</form>

<div class="mt-3">
    <a href="./produtos_limpar_edicoes.php?id=<?php echo $comum_id; ?>&comum_id=<?php echo $comum_id; ?>&id_PRODUTO=<?php echo $id_PRODUTO; ?>&pagina=<?php echo $pagina; ?>&nome=<?php echo urlencode($filtro_nome); ?>&dependencia=<?php echo urlencode($filtro_dependencia); ?>&filtro_codigo=<?php echo urlencode($filtro_codigo); ?>&STATUS=<?php echo urlencode($filtro_STATUS); ?>"
        class="btn btn-outline-danger w-100"
        onclick="return confirm('<?php echo htmlspecialchars(to_uppercase('Tem certeza que deseja limpar as EDIÇÕES deste PRODUTO?'), ENT_QUOTES, 'UTF-8'); ?>');">
        <i class="bi bi-trash3 me-2"></i>
        <?php echo htmlspecialchars(to_uppercase('Limpar Edições'), ENT_QUOTES, 'UTF-8'); ?>
    </a>
    <div class="form-text mt-1"><?php echo htmlspecialchars(to_uppercase('Remove todos os campos editados e desmarca para impressão.'), ENT_QUOTES, 'UTF-8'); ?></div>
</div>

<?php
$contentHtml = ob_get_clean();
$tempFile = __DIR__ . '/../../../temp_editar_PRODUTO_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app_wrapper.php';
unlink($tempFile);
?>