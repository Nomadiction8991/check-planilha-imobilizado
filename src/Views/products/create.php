<?php


$appConfig = require dirname(__DIR__, 3) . '/config/app.php';
$projectRoot = $appConfig['project_root'];
require_once $projectRoot . '/src/Helpers/BootstrapLoader.php';


$pageTitle = 'CADASTRAR PRODUTO';
$backUrl = '/products/view?comum_id=' . urlencode($comum_id);

ob_start();
?>

<?php if (!empty($erros)): ?>
    <div class="px-4 py-3 mb-4" style="background:#fafafa;border:1px solid #000;color:#171717;border-radius:2px">
        <strong>Erros encontrados:</strong>
        <ul class="mb-0">
            <?php foreach ($erros as $erro): ?>
                <li><?php echo htmlspecialchars($erro); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" id="form-PRODUTO" class="needs-validation" novalidate>
    <div class="bg-white border border-neutral-200 mb-4" style="border-radius:2px">
        <div class="px-5 py-4">
            <div class="mb-3">
                <label for="codigo" class="block text-sm font-medium mb-1" style="color:#262626">CÓDIGO <span style="color:#808080">(opcional)</span></label>
                <input type="text" id="codigo" name="codigo" class="w-full px-3 py-2 border border-neutral-300 focus:outline-none focus:border-black" style="border-radius:2px" value="<?php echo htmlspecialchars($_POST['codigo'] ?? ''); ?>" placeholder="CÓDIGO gerado por outro sistema">
                <div class="text-sm mt-1" style="color:#808080">Campo opcional. CÓDIGO externo que NÃO SERÁ INCLUÍDO na DESCRIÇÃO completa.</div>
            </div>

            <div class="mb-3">
                <label for="multiplicador" class="block text-sm font-medium mb-1" style="color:#262626">Multiplicador</label>
                <input type="number" id="multiplicador" name="multiplicador" class="w-full px-3 py-2 border border-neutral-300 focus:outline-none focus:border-black" style="border-radius:2px" min="1" value="<?php echo htmlspecialchars($_POST['multiplicador'] ?? '1'); ?>" required>
                <div class="text-sm mt-1 hidden" style="color:#525252">Informe o multiplicador.</div>
            </div>

            <div class="mb-3">
                <label for="id_tipo_ben" class="block text-sm font-medium mb-1" style="color:#262626">Tipos de Bens</label>
                <select id="id_tipo_ben" name="id_tipo_ben" class="w-full px-3 py-2 border border-neutral-300 focus:outline-none focus:border-black" style="border-radius:2px" required>
                    <option value="">SELECIONE UM TIPO DE BEM</option>
                    <?php foreach ($tipos_bens as $tipo): ?>
                        <option value="<?php echo $tipo['id']; ?>" data-descricao="<?php echo htmlspecialchars($tipo['descricao']); ?>"
                            <?php echo (isset($_POST['id_tipo_bem']) && $_POST['id_tipo_bem'] == $tipo['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($tipo['codigo'] . ' - ' . $tipo['descricao']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="text-sm mt-1 hidden" style="color:#525252">SELECIONE UM TIPO DE BEM.</div>
            </div>

            <div class="mb-3">
                <label for="tipo_ben" class="block text-sm font-medium mb-1" style="color:#262626">Bem</label>
                <select id="tipo_ben" name="tipo_ben" class="w-full px-3 py-2 border border-neutral-300 focus:outline-none focus:border-black" style="border-radius:2px" required>
                    <option value="">PRIMEIRO SELECIONE UM TIPO DE BEM</option>
                </select>
                <div class="text-sm mt-1 hidden" style="color:#525252">SELECIONE UM BEM.</div>
            </div>

            <div class="mb-3">
                <label for="complemento" class="block text-sm font-medium mb-1" style="color:#262626">COMPLEMENTO</label>
                <textarea id="complemento" name="complemento" class="w-full px-3 py-2 border border-neutral-300 focus:outline-none focus:border-black" style="border-radius:2px" rows="3" placeholder="Digite o complemento do PRODUTO" required><?php echo htmlspecialchars($_POST['complemento'] ?? ''); ?></textarea>
                <div class="text-sm mt-1 hidden" style="color:#525252">Informe o complemento.</div>
            </div>

            <div class="mb-3">
                <label for="id_dependencia" class="block text-sm font-medium mb-1" style="color:#262626">DEPENDÊNCIA</label>
                <select id="id_dependencia" name="id_dependencia" class="w-full px-3 py-2 border border-neutral-300 focus:outline-none focus:border-black" style="border-radius:2px" required>
                    <option value="">Selecione uma DEPENDÊNCIA</option>
                    <?php foreach ($dependencias as $dep): ?>
                        <option value="<?php echo $dep['id']; ?>" <?php echo (isset($_POST['id_dependencia']) && $_POST['id_dependencia'] == $dep['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dep['descricao']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="text-sm mt-1 hidden" style="color:#525252">Selecione a DEPENDÊNCIA.</div>
            </div>

            <!-- removido: STATUS (imprimir 14.1 agora fica no card de CONDIÇÃO 14.1, para ficar igual à tela de edição) -->
        </div>
    </div>

    <!-- CONDIÇÃO 14.1 -->
    <div class="bg-white border border-neutral-200 mb-4" style="border-radius:2px">
        <div class="px-5 py-3 border-b border-neutral-200 bg-neutral-50">
            <i class="bi bi-clipboard-check mr-2"></i>
            CONDIÇÃO 14.1
        </div>
        <div class="px-5 py-4">
            <div class="mb-3">
                <div class="flex items-center gap-2 mb-2">
                    <input class="w-4 h-4 accent-black" type="checkbox" id="imprimir_14_1" name="imprimir_14_1" value="1" <?php echo (isset($_POST['imprimir_14_1']) && $_POST['imprimir_14_1'] == 1) ? 'checked' : ''; ?>>
                    <label class="text-sm" for="imprimir_14_1">IMPRIMIR 14.1</label>
                </div>

                <label class="block text-sm font-medium mb-2" style="color:#262626">CONDIÇÃO</label>
                <?php
                $condicaoPost = trim($_POST['condicao_14_1'] ?? '');
                $opcoes141 = [
                    '1' => 'O bem tem mais de cinco anos de uso e o documento fiscal de aquisição está anexo.',
                    '2' => 'O bem tem mais de cinco anos de uso, porém o documento fiscal de aquisição foi extraviado.',
                    '3' => 'O bem tem até cinco anos de uso e o documento fiscal de aquisição está anexo.',
                ];
                foreach ($opcoes141 as $valor => $texto): ?>
                    <div class="flex items-start gap-2 mb-2">
                        <input class="w-4 h-4 accent-black mt-1" type="radio"
                            name="condicao_14_1"
                            id="condicao_141_<?php echo $valor; ?>"
                            value="<?php echo $valor; ?>"
                            <?php echo ($condicaoPost === (string)$valor || ($condicaoPost === '' && $valor === '2')) ? 'checked' : ''; ?>>
                        <label class="text-sm" for="condicao_141_<?php echo $valor; ?>">
                            <?php echo htmlspecialchars($texto, ENT_QUOTES, 'UTF-8'); ?>
                        </label>
                    </div>
                <?php endforeach; ?>
                <div class="text-sm mt-2" style="color:#808080">CONDIÇÃO ATUAL DO PRODUTO PARA O RELATÓRIO</div>
            </div>

            <script>
                (function() {
                    var imprimir = document.getElementById('imprimir_14_1');
                    var radios = Array.from(document.querySelectorAll('input[name="condicao_14_1"]'));

                    function ensureDefault() {
                        if (!radios.some(function(r) {
                                return r.checked;
                            })) {
                            var def = document.getElementById('condicao_141_2');
                            if (def) def.checked = true;
                        }
                    }

                    function updateRequirement() {
                        var required = imprimir && imprimir.checked;
                        radios.forEach(function(r) {
                            if (required) r.setAttribute('required', 'required');
                            else r.removeAttribute('required');
                        });

                        // garantir padrão sempre que não houver seleção
                        ensureDefault();
                    }

                    // inicializar: garantir opção do meio quando nenhuma estiver selecionada
                    ensureDefault();

                    if (imprimir) imprimir.addEventListener('change', updateRequirement);
                })();
            </script>
        </div>
    </div>

    <!-- NOTA FISCAL -->
    <div class="bg-white border border-neutral-200 mb-4" style="border-radius:2px">
        <div class="px-5 py-3 border-b border-neutral-200 bg-neutral-50">
            <i class="bi bi-receipt mr-2"></i>
            NOTA FISCAL
        </div>
        <div class="px-5 py-4">
            <div class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-xs">
                    <label for="nota_numero" class="block text-sm font-medium mb-1" style="color:#262626">NÚMERO</label>
                    <input type="number" class="w-full px-3 py-2 border border-neutral-300 focus:outline-none focus:border-black" style="border-radius:2px" id="nota_numero" name="nota_numero"
                        value="<?php echo htmlspecialchars($_POST['nota_numero'] ?? ''); ?>"
                        placeholder="Nº da nota">
                </div>
                <div class="flex-1 min-w-xs">
                    <label for="nota_data" class="block text-sm font-medium mb-1" style="color:#262626">DATA</label>
                    <input type="date" class="w-full px-3 py-2 border border-neutral-300 focus:outline-none focus:border-black" style="border-radius:2px" id="nota_data" name="nota_data"
                        value="<?php echo htmlspecialchars($_POST['nota_data'] ?? ''); ?>">
                </div>
            </div>
            <div class="flex flex-wrap gap-4 mt-3">
                <div class="flex-1 min-w-xs">
                    <label for="nota_valor" class="block text-sm font-medium mb-1" style="color:#262626">VALOR (R$)</label>
                    <input type="text" class="w-full px-3 py-2 border border-neutral-300 focus:outline-none focus:border-black" style="border-radius:2px" id="nota_valor" name="nota_valor"
                        value="<?php echo htmlspecialchars($_POST['nota_valor'] ?? ''); ?>"
                        placeholder="0,00">
                </div>
                <div class="flex-1 min-w-xs">
                    <label for="nota_fornecedor" class="block text-sm font-medium mb-1" style="color:#262626">FORNECEDOR</label>
                    <input type="text" class="w-full px-3 py-2 border border-neutral-300 focus:outline-none focus:border-black" style="border-radius:2px" id="nota_fornecedor" name="nota_fornecedor"
                        value="<?php echo htmlspecialchars($_POST['nota_fornecedor'] ?? ''); ?>"
                        placeholder="Nome do fornecedor">
                </div>
            </div>
        </div>
    </div>

    <div class="flex flex-col gap-2">
        <button type="submit" class="px-4 py-2 bg-black text-white font-semibold hover:bg-neutral-800 transition w-full" style="border-radius:2px">
            <i class="bi bi-save mr-2"></i>
            CADASTRAR PRODUTO
        </button>
        <a href="<?php echo htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8'); ?>" class="px-4 py-2 border border-neutral-300 text-center font-medium hover:bg-neutral-50 transition w-full" style="border-radius:2px;color:#171717">
            VOLTAR
        </a>
    </div>
</form>

<script>
    window._postTipoBen = <?php echo json_encode($_POST['tipo_ben'] ?? null); ?>;
    window._postIdTipoBen = <?php echo json_encode($_POST['id_tipo_ben'] ?? null); ?>;
</script>
<script src="/assets/js/products/create.js"></script>

<?php
$contentHtml = ob_get_clean();
include $projectRoot . '/src/Views/layouts/app.php';
?>
