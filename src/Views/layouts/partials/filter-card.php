<?php
/**
 * Partial: Bloco de filtros/pesquisa reutilizável
 *
 * Variáveis esperadas via $filterCardOptions:
 * - titulo (string): Título do card
 * - icone (string): Classe Bootstrap Icons (ex: 'bi-search')
 * - campos (array): Array de campos do formulário
 * - total_label (string): Texto de rodapé com contagem
 * - form_action (string, opcional): URL de ação (default: vazio = GET atual)
 * - hidden (array, opcional): Campos ocultos adicionais
 *
 * Cada elemento em 'campos':
 * [
 *   'tipo' => 'text|select|textarea',
 *   'name' => 'nome_do_campo',
 *   'label' => 'Rótulo',
 *   'value' => $valor_atual,
 *   'placeholder' => '...',
 *   'options' => [...] (só para select),
 * ]
 */

$filterCardOptions ??= [];
$titulo = $filterCardOptions['titulo'] ?? 'PESQUISAR';
$icone = $filterCardOptions['icone'] ?? 'bi-search';
$campos = $filterCardOptions['campos'] ?? [];
$total_label = $filterCardOptions['total_label'] ?? '';
$form_action = $filterCardOptions['form_action'] ?? '';
$hidden = $filterCardOptions['hidden'] ?? [];
?>

<div class="bg-white border border-neutral-200 mb-4" style="border-radius:2px">
    <div class="px-5 py-3 border-b border-neutral-200 bg-neutral-50 flex items-center gap-2">
        <i class="<?= htmlspecialchars($icone) ?> text-neutral-500" style="font-size:13px"></i>
        <span style="font-size:12px;font-weight:600;letter-spacing:0.06em;color:#525252"><?= htmlspecialchars($titulo) ?></span>
    </div>
    <div class="p-5">
        <form method="GET" action="<?= htmlspecialchars($form_action) ?>">
            <!-- Campos ocultos -->
            <?php foreach ($hidden as $name => $value): ?>
                <input type="hidden" name="<?= htmlspecialchars($name) ?>" value="<?= htmlspecialchars((string)$value) ?>">
            <?php endforeach; ?>

            <!-- Campos visíveis -->
            <?php foreach ($campos as $campo): ?>
                <?php
                $tipo = $campo['tipo'] ?? 'text';
                $name = $campo['name'] ?? '';
                $label = $campo['label'] ?? '';
                $value = $campo['value'] ?? '';
                $placeholder = $campo['placeholder'] ?? '';
                $options = $campo['options'] ?? [];
                $classe = $campo['classe'] ?? 'w-full px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black';
                ?>

                <div class="mb-3">
                    <?php if (!empty($label)): ?>
                        <label for="<?= htmlspecialchars($name) ?>" style="display:block;font-size:12px;font-weight:500;color:#262626;margin-bottom:4px">
                            <?= htmlspecialchars($label) ?>
                        </label>
                    <?php endif; ?>

                    <?php if ($tipo === 'select'): ?>
                        <select id="<?= htmlspecialchars($name) ?>" name="<?= htmlspecialchars($name) ?>"
                            class="<?= $classe ?>"
                            style="border-radius:2px">
                            <?php foreach ($options as $optValue => $optLabel): ?>
                                <option value="<?= htmlspecialchars((string)$optValue) ?>"
                                    <?= ((string)$value === (string)$optValue) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string)$optLabel) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php elseif ($tipo === 'textarea'): ?>
                        <textarea id="<?= htmlspecialchars($name) ?>" name="<?= htmlspecialchars($name) ?>"
                            class="<?= $classe ?>"
                            style="border-radius:2px"
                            placeholder="<?= htmlspecialchars($placeholder) ?>"></textarea>
                    <?php else: ?>
                        <!-- text, number, email, etc -->
                        <input type="<?= htmlspecialchars($tipo) ?>" id="<?= htmlspecialchars($name) ?>" name="<?= htmlspecialchars($name) ?>"
                            class="<?= $classe ?>"
                            style="border-radius:2px"
                            value="<?= htmlspecialchars((string)$value) ?>"
                            placeholder="<?= htmlspecialchars($placeholder) ?>">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <!-- Botão submit -->
            <button type="submit"
                class="w-full px-4 py-2 bg-black text-white text-sm font-medium hover:bg-neutral-900 transition flex items-center justify-center gap-2"
                style="border-radius:2px">
                <i class="bi bi-search"></i>Filtrar
            </button>
        </form>
    </div>

    <!-- Rodapé com contagem -->
    <?php if (!empty($total_label)): ?>
        <div class="px-5 py-2 border-t border-neutral-100 bg-neutral-50" style="font-size:12px;color:#808080">
            <?= htmlspecialchars($total_label) ?>
        </div>
    <?php endif; ?>
</div>
