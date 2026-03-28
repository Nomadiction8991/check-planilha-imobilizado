<?php


$appConfig = require dirname(__DIR__, 3) . '/config/app.php';
$projectRoot = $appConfig['project_root'];
require_once $projectRoot . '/src/Helpers/BootstrapLoader.php';


$pageTitle = \App\Helpers\StringHelper::toUppercase('editar produto');
$pagina = $pagina ?? 1;
$filtro_nome = $filtro_nome ?? '';
$filtro_dependencia = $filtro_dependencia ?? '';
$filtro_codigo = $filtro_codigo ?? '';
$filtro_STATUS = $filtro_STATUS ?? '';
$id_produto = $id_produto ?? 0;
$comum_id = $comum_id ?? 0;
$dependencias = $dependencias ?? [];
$novo_tipo_bem_id = $novo_tipo_bem_id ?? null;
$novo_bem = $novo_bem ?? '';
$novo_complemento = $novo_complemento ?? '';
$nova_dependencia_id = $nova_dependencia_id ?? null;
$mensagem = $mensagem ?? '';
$tipo_mensagem = $tipo_mensagem ?? '';
$produto = $produto ?? [];
$backUrl = getReturnUrl($comum_id, $pagina, $filtro_nome, $filtro_dependencia, $filtro_codigo, $filtro_STATUS);

ob_start();
?>





<script>
    window._tiposBensOpcoes = <?php echo json_encode(array_reduce($tipos_bens, function ($carry, $item) {
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
    window._novoBem = <?php echo json_encode(!empty($novo_bem) ? mb_strtoupper($novo_bem, 'UTF-8') : ''); ?>;
</script>
<script src="/assets/js/products/edit.js"></script>

<?php if (!empty($mensagem)): ?>
    <?php
    $msgStyle = 'background:#fafafa;border:1px solid #d4d4d4;color:#171717';
    $msgIcon = $tipo_mensagem === 'success' ? 'bi-check-circle' : 'bi-info-circle';
    ?>
    <div style="<?= $msgStyle ?>;border-radius:2px;padding:10px 14px;margin-bottom:16px;display:flex;align-items:flex-start;gap:10px;font-size:13px" role="alert">
        <i class="bi <?= $msgIcon ?>" style="margin-top:2px;flex-shrink:0"></i>
        <span style="flex:1"><?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?></span>
        <button type="button" style="background:none;border:none;cursor:pointer;font-size:16px;line-height:1;color:inherit;padding:0" onclick="this.parentElement.remove()">&times;</button>
    </div>
<?php endif; ?>

<form method="POST">
    <input type="hidden" name="pagina" value="<?php echo $pagina; ?>">
    <input type="hidden" name="nome" value="<?php echo htmlspecialchars($filtro_nome); ?>">
    <input type="hidden" name="dependencia" value="<?php echo htmlspecialchars($filtro_dependencia); ?>">
    <input type="hidden" name="filtro_codigo" value="<?php echo htmlspecialchars($filtro_codigo); ?>">
    <input type="hidden" name="STATUS" value="<?php echo htmlspecialchars($filtro_STATUS); ?>">

    <!-- DADOS DO PRODUTO -->
    <div class="bg-white border border-neutral-200 mb-4">
        <div class="px-6 py-4 border-b border-neutral-200 bg-neutral-50">
            <i class="bi bi-box-seam me-2"></i>
            <?php echo \App\Helpers\StringHelper::toUppercase('Dados do Produto'); ?>
        </div>
        <div class="px-6 py-4">
            <!-- TIPO DE BEM -->
            <div class="mb-3">
                <label for="novo_tipo_bem_id" class="block text-sm font-medium text-neutral-700 mb-1">
                    <i class="bi bi-tag me-1"></i>
                    TIPO DE BEM
                </label>
                <select class="w-full px-3 py-2 border border-neutral-300 focus:outline-none focus:border-black" style="border-radius:2px" id="novo_tipo_bem_id" name="novo_tipo_bem_id">
                    <?php foreach ($tipos_bens as $tb): ?>
                        <option value="<?php echo $tb['id']; ?>"
                            <?php
                            // Selecionar valor editado se existir, caso contrário selecionar o valor padrão do produto
                            $selectedTipo = false;
                            if (!is_null($novo_tipo_bem_id) && $novo_tipo_bem_id !== '') {
                                $selectedTipo = ($novo_tipo_bem_id == $tb['id']);
                            } else {
                                $selectedTipo = (isset($produto['tipo_bem_id']) && $produto['tipo_bem_id'] == $tb['id']);
                            }
                            echo $selectedTipo ? 'selected' : '';
                            ?>>
                            <?php echo htmlspecialchars($tb['codigo'] . ' - ' . $tb['descricao']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="text-sm text-neutral-500 mt-1"><?php echo htmlspecialchars(\App\Helpers\StringHelper::toUppercase('Selecione o tipo de bem para desbloquear o campo "BEM"'), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>

            <!-- BEM -->
            <div class="mb-3" id="div_bem">
                <label for="novo_bem" class="block text-sm font-medium text-neutral-700 mb-1">
                    <i class="bi bi-box me-1"></i>
                    BEM
                </label>
                <select class="w-full px-3 py-2 border border-neutral-300 focus:outline-none focus:border-black" style="border-radius:2px" id="novo_bem" name="novo_bem" disabled>
                    <option value="">-- Escolha o TIPO DE BEM acima --</option>
                </select>
                <div class="text-sm text-neutral-500 mt-1"><?php echo htmlspecialchars(\App\Helpers\StringHelper::toUppercase('Fica bloqueado até selecionar o TIPO DE BEM'), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>

            <!-- COMPLEMENTO -->
            <div class="mb-3">
                <label for="novo_complemento" class="block text-sm font-medium text-neutral-700 mb-1">
                    <i class="bi bi-card-text me-1"></i>
                    COMPLEMENTO
                </label>
                <textarea class="w-full px-3 py-2 border border-neutral-300 focus:outline-none focus:border-black" style="border-radius:2px" id="novo_complemento" name="novo_complemento"
                    rows="3" placeholder="<?php echo htmlspecialchars(\App\Helpers\StringHelper::toUppercase('Característica + Marca + Medidas'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($novo_complemento ?? ''); ?></textarea>
                <div class="text-sm text-neutral-500 mt-1"><?php echo htmlspecialchars(\App\Helpers\StringHelper::toUppercase('Deixe em branco para NÃO alterar. Ex: COR PRETA + MARCA XYZ + 1,80M X 0,80M'), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>

            <!-- DEPENDÊNCIA -->
            <div class="mb-3">
                <label for="nova_dependencia_id" class="block text-sm font-medium text-neutral-700 mb-1">
                    <i class="bi bi-building me-1"></i>
                    DEPENDÊNCIA
                </label>
                <select class="w-full px-3 py-2 border border-neutral-300 focus:outline-none focus:border-black" style="border-radius:2px" id="nova_dependencia_id" name="nova_dependencia_id">
                    <?php foreach ($dependencias as $dep): ?>
                        <option value="<?php echo $dep['id']; ?>"
                            <?php
                            // Selecionar dependência editada se existir, caso contrário selecionar o valor padrão do produto
                            $selectedDep = false;
                            if (!is_null($nova_dependencia_id) && $nova_dependencia_id !== '') {
                                $selectedDep = ($nova_dependencia_id == $dep['id']);
                            } else {
                                $selectedDep = (isset($produto['dependencia_id']) && $produto['dependencia_id'] == $dep['id']);
                            }
                            echo $selectedDep ? 'selected' : '';
                            ?>>
                            <?php echo htmlspecialchars($dep['descricao']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <!-- RELATÓRIO 14.1 -->
    <div class="bg-white border border-neutral-200 mb-4">
        <div class="px-6 py-4 border-b border-neutral-200 bg-neutral-50">
            <i class="bi bi-file-earmark-text me-2"></i>
            <?php echo \App\Helpers\StringHelper::toUppercase('Relatório 14.1'); ?>
        </div>
        <div class="px-6 py-4">
            <div class="mb-3">
                <div class="flex items-center gap-2">
                    <input class="w-4 h-4 accent-black" type="checkbox" id="imprimir_14_1" name="imprimir_14_1" value="1"
                        <?php echo ((int)($produto['imprimir_14_1'] ?? 0) === 1) ? 'checked' : ''; ?>>
                    <label class="text-sm" for="imprimir_14_1">
                        <?php echo \App\Helpers\StringHelper::toUppercase('Imprimir no Relatório 14.1'); ?>
                    </label>
                </div>
                <div class="text-sm text-neutral-500 mt-1"><?php echo htmlspecialchars(\App\Helpers\StringHelper::toUppercase('Marque para incluir este produto no relatório 14.1'), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>

            <div class="mb-3">
                <label class="block text-sm font-medium text-neutral-700 mb-2">
                    <i class="bi bi-clipboard-check me-1"></i>
                    <?php echo \App\Helpers\StringHelper::toUppercase('Situação do Bem (Formulário 14.1)'); ?>
                </label>
                <?php
                $condicaoAtual = trim($produto['condicao_14_1'] ?? '');
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
                            <?php echo (
                                $condicaoAtual === (string)$valor ||
                                ($condicaoAtual === '' && $valor === '2')
                            ) ? 'checked' : ''; ?>>
                        <label class="text-sm" for="condicao_141_<?php echo $valor; ?>">
                            <?php echo htmlspecialchars($texto, ENT_QUOTES, 'UTF-8'); ?>
                        </label>
                    </div>
                <?php endforeach; ?>
                <div class="text-sm text-neutral-500 mt-2"><?php echo htmlspecialchars(\App\Helpers\StringHelper::toUppercase('Situação conforme declaração de doação de bem móvel'), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>

            <script>
                (function() {
                    var imprimir = document.getElementById('imprimir_14_1');
                    if (!imprimir) return;
                    var radios = Array.from(document.querySelectorAll('input[name="condicao_14_1"]'));

                    function updateRequirement() {
                        var required = imprimir.checked;
                        radios.forEach(function(r) {
                            if (required) r.setAttribute('required', 'required');
                            else r.removeAttribute('required');
                        });

                        // garantir opção do meio quando nenhuma estiver selecionada (sempre)
                        if (!radios.some(function(r) {
                                return r.checked;
                            })) {
                            var def = document.getElementById('condicao_141_2');
                            if (def) def.checked = true;
                        }
                    }

                    imprimir.addEventListener('change', updateRequirement);
                    // inicializar estado do requerimento e padrão
                    updateRequirement();
                })();
            </script>
        </div>
    </div>

    <!-- NOTA FISCAL -->
    <div class="bg-white border border-neutral-200 mb-4">
        <div class="px-6 py-4 border-b border-neutral-200 bg-neutral-50">
            <i class="bi bi-receipt me-2"></i>
            <?php echo \App\Helpers\StringHelper::toUppercase('Nota Fiscal'); ?>
        </div>
        <div class="px-6 py-4">
            <div class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-xs">
                    <label for="nota_numero" class="block text-sm font-medium text-neutral-700 mb-1">
                        <?php echo \App\Helpers\StringHelper::toUppercase('Número'); ?>
                    </label>
                    <input type="number" class="w-full px-3 py-2 border border-neutral-300 focus:outline-none focus:border-black" style="border-radius:2px" id="nota_numero" name="nota_numero"
                        value="<?php echo htmlspecialchars((string)($produto['nota_numero'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                        placeholder="Nº da nota">
                </div>
                <div class="flex-1 min-w-xs">
                    <label for="nota_data" class="block text-sm font-medium text-neutral-700 mb-1">
                        <?php echo \App\Helpers\StringHelper::toUppercase('Data'); ?>
                    </label>
                    <input type="date" class="w-full px-3 py-2 border border-neutral-300 focus:outline-none focus:border-black" style="border-radius:2px" id="nota_data" name="nota_data"
                        value="<?php echo htmlspecialchars($produto['nota_data'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>
            <div class="flex flex-wrap gap-4 mt-3">
                <div class="flex-1 min-w-xs">
                    <label for="nota_valor" class="block text-sm font-medium text-neutral-700 mb-1">
                        <?php echo \App\Helpers\StringHelper::toUppercase('Valor (R$)'); ?>
                    </label>
                    <input type="text" class="w-full px-3 py-2 border border-neutral-300 focus:outline-none focus:border-black" style="border-radius:2px" id="nota_valor" name="nota_valor"
                        value="<?php echo htmlspecialchars($produto['nota_valor'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        placeholder="0,00">
                </div>
                <div class="flex-1 min-w-xs">
                    <label for="nota_fornecedor" class="block text-sm font-medium text-neutral-700 mb-1">
                        <?php echo \App\Helpers\StringHelper::toUppercase('Fornecedor'); ?>
                    </label>
                    <input type="text" class="w-full px-3 py-2 border border-neutral-300 focus:outline-none focus:border-black" style="border-radius:2px" id="nota_fornecedor" name="nota_fornecedor"
                        value="<?php echo htmlspecialchars($produto['nota_fornecedor'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        placeholder="Nome do fornecedor">
                </div>
            </div>
        </div>
    </div>

    <button type="submit" class="px-4 py-2 bg-black text-white font-semibold hover:bg-neutral-800 transition w-full" style="border-radius:2px">
        <i class="bi bi-check-lg me-2"></i>
        <?php echo \App\Helpers\StringHelper::toUppercase('Salvar alterações'); ?>
    </button>
</form>

<div class="mt-3">
    <form method="POST" action="/products/clear-edits">
        <input type="hidden" name="id_PRODUTO" value="<?php echo (int) $id_produto; ?>">
        <input type="hidden" name="pagina" value="<?php echo (int) $pagina; ?>">
        <input type="hidden" name="nome" value="<?php echo htmlspecialchars($filtro_nome, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="dependencia" value="<?php echo htmlspecialchars($filtro_dependencia, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="filtro_codigo" value="<?php echo htmlspecialchars($filtro_codigo, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="status" value="<?php echo htmlspecialchars($filtro_STATUS, ENT_QUOTES, 'UTF-8'); ?>">
        <button type="submit"
            class="px-4 py-2 border border-black text-center hover:bg-neutral-100 transition w-full inline-block"
            style="border-radius:2px;color:#171717">
            <i class="bi bi-trash3 me-2"></i>
            <?php echo htmlspecialchars(\App\Helpers\StringHelper::toUppercase('Limpar Edições'), ENT_QUOTES, 'UTF-8'); ?>
        </button>
    </form>
    <div class="text-sm text-neutral-500 mt-1"><?php echo htmlspecialchars(\App\Helpers\StringHelper::toUppercase('Remove todos os campos editados e desmarca para impressão.'), ENT_QUOTES, 'UTF-8'); ?></div>
</div>

<?php
$contentHtml = ob_get_clean();
include $projectRoot . '/src/Views/layouts/app.php';
?>
