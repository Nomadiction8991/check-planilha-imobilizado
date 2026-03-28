<?php
/**
 * Partial: Card de formulário reutilizável
 *
 * Variáveis esperadas via $formCardOptions:
 * - titulo (string): Título do card
 * - icone (string): Classe Bootstrap Icons
 * - campos (array): Array de campos do formulário
 * - action (string): URL de ação do form
 * - method (string): GET ou POST (default: POST)
 * - back_url (string): URL do botão cancelar
 * - back_label (string, opcional): Label customizado do botão cancelar
 * - submit_label (string, opcional): Label customizado do botão submit
 * - csrf (bool, opcional): Incluir token CSRF (default: true)
 *
 * Cada campo em 'campos':
 * [
 *   'tipo'       => 'text|textarea|select|email|number|date|...',
 *   'name'       => 'nome_do_campo',
 *   'label'      => 'Rótulo',
 *   'value'      => $valor_atual,
 *   'placeholder' => '...',
 *   'required'   => true|false,
 *   'maxlength'  => 255,
 *   'options'    => [...] (só para select),
 *   'rows'       => 5, (só para textarea)
 * ]
 */

$formCardOptions ??= [];
$titulo = $formCardOptions['titulo'] ?? 'FORMULÁRIO';
$icone = $formCardOptions['icone'] ?? 'bi-pencil';
$campos = $formCardOptions['campos'] ?? [];
$action = $formCardOptions['action'] ?? '';
$method = $formCardOptions['method'] ?? 'POST';
$back_url = $formCardOptions['back_url'] ?? '/';
$back_label = $formCardOptions['back_label'] ?? 'Cancelar';
$submit_label = $formCardOptions['submit_label'] ?? 'Salvar';
$csrf = $formCardOptions['csrf'] ?? true;
?>

<div class="bg-white border border-neutral-200 mb-4" style="border-radius:2px">
    <div class="px-5 py-3 border-b border-neutral-200 bg-neutral-50 flex items-center gap-2">
        <i class="<?= htmlspecialchars($icone) ?> text-neutral-500" style="font-size:13px"></i>
        <span style="font-size:12px;font-weight:600;letter-spacing:0.06em;color:#525252"><?= htmlspecialchars($titulo) ?></span>
    </div>
    <div class="p-5">
        <form method="<?= htmlspecialchars(strtoupper($method)) ?>" action="<?= htmlspecialchars($action) ?>" style="display:flex;flex-direction:column;gap:12px">
            <?php if ($csrf && function_exists('\App\Core\CsrfService::hiddenField')): ?>
                <?= \App\Core\CsrfService::hiddenField() ?>
            <?php endif; ?>

            <?php foreach ($campos as $campo): ?>
                <?php
                $tipo = $campo['tipo'] ?? 'text';
                $name = $campo['name'] ?? '';
                $label = $campo['label'] ?? '';
                $value = $campo['value'] ?? '';
                $placeholder = $campo['placeholder'] ?? '';
                $required = $campo['required'] ?? false;
                $maxlength = $campo['maxlength'] ?? null;
                $options = $campo['options'] ?? [];
                $rows = $campo['rows'] ?? 5;
                $classe_input = 'w-full px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black';
                ?>

                <div>
                    <?php if (!empty($label)): ?>
                        <label for="<?= htmlspecialchars($name) ?>" style="display:block;font-size:12px;font-weight:500;color:#262626;margin-bottom:4px">
                            <?= htmlspecialchars($label) ?>
                            <?php if ($required): ?>
                                <span style="color:#991b1b">*</span>
                            <?php endif; ?>
                        </label>
                    <?php endif; ?>

                    <?php if ($tipo === 'textarea'): ?>
                        <textarea
                            id="<?= htmlspecialchars($name) ?>"
                            name="<?= htmlspecialchars($name) ?>"
                            class="<?= $classe_input ?>"
                            style="border-radius:2px"
                            <?= $required ? 'required' : '' ?>
                            rows="<?= (int)$rows ?>"
                            placeholder="<?= htmlspecialchars($placeholder) ?>"><?= htmlspecialchars((string)$value) ?></textarea>

                    <?php elseif ($tipo === 'select'): ?>
                        <select
                            id="<?= htmlspecialchars($name) ?>"
                            name="<?= htmlspecialchars($name) ?>"
                            class="<?= $classe_input ?>"
                            style="border-radius:2px"
                            <?= $required ? 'required' : '' ?>>
                            <?php foreach ($options as $optValue => $optLabel): ?>
                                <option value="<?= htmlspecialchars((string)$optValue) ?>"
                                    <?= ((string)$value === (string)$optValue) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string)$optLabel) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                    <?php else: ?>
                        <!-- text, email, number, date, etc -->
                        <input
                            type="<?= htmlspecialchars($tipo) ?>"
                            id="<?= htmlspecialchars($name) ?>"
                            name="<?= htmlspecialchars($name) ?>"
                            class="<?= $classe_input ?>"
                            style="border-radius:2px"
                            value="<?= htmlspecialchars((string)$value) ?>"
                            <?= $required ? 'required' : '' ?>
                            <?= $maxlength ? 'maxlength="' . (int)$maxlength . '"' : '' ?>
                            placeholder="<?= htmlspecialchars($placeholder) ?>">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <!-- Botões de ação -->
            <div style="display:flex;flex-direction:column;gap:8px;padding-top:16px;border-top:1px solid #e5e5e5">
                <button type="submit"
                    class="w-full px-4 py-2 bg-black text-white text-sm font-medium hover:bg-neutral-900 transition flex items-center justify-center gap-2"
                    style="border-radius:2px">
                    <i class="bi bi-check-lg"></i><?= htmlspecialchars($submit_label) ?>
                </button>
                <a href="<?= htmlspecialchars($back_url, ENT_QUOTES, 'UTF-8') ?>"
                    style="display:flex;align-items:center;justify-content:center;gap:8px;padding:8px 16px;border:1px solid #d4d4d4;color:#525252;font-size:13px;text-decoration:none;border-radius:2px;transition:background 120ms"
                    onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background=''">
                    <i class="bi bi-x-lg"></i><?= htmlspecialchars($back_label) ?>
                </a>
            </div>
        </form>
    </div>
</div>
