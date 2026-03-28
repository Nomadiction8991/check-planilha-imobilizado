<?php
/**
 * Partial: Modal de confirmação de exclusão reutilizável
 *
 * Variáveis esperadas via $confirmModalOptions:
 * - id (string): ID do elemento modal (ex: 'confirmDeleteModal')
 * - titulo (string): Título do modal
 * - mensagem (string): Mensagem de confirmação
 * - icone (string, opcional): Classe Bootstrap Icons do header
 * - btn_cancelar (string): Texto do botão cancelar
 * - btn_excluir (string): Texto do botão excluir
 * - form_action (string|null): URL de ação do form. Se null, botão acionado por JS
 * - hidden_fields (array, opcional): Campos ocultos para o form
 * - csrf (bool, opcional): Se true, inclui token CSRF
 * - btn_excluir_classe (string, opcional): Classes CSS adicionais para o botão excluir
 * - onclick_excluir (string, opcional): Função JS para o botão (se form_action é null)
 */

$confirmModalOptions ??= [];
$id = $confirmModalOptions['id'] ?? 'confirmModal';
$titulo = $confirmModalOptions['titulo'] ?? 'Confirmar';
$mensagem = $confirmModalOptions['mensagem'] ?? 'Tem certeza que deseja continuar?';
$icone = $confirmModalOptions['icone'] ?? 'bi-exclamation-triangle-fill';
$btn_cancelar = $confirmModalOptions['btn_cancelar'] ?? 'Cancelar';
$btn_excluir = $confirmModalOptions['btn_excluir'] ?? 'Excluir';
$form_action = $confirmModalOptions['form_action'] ?? null;
$hidden_fields = $confirmModalOptions['hidden_fields'] ?? [];
$csrf = $confirmModalOptions['csrf'] ?? true;
$btn_excluir_classe = $confirmModalOptions['btn_excluir_classe'] ?? '';
$onclick_excluir = $confirmModalOptions['onclick_excluir'] ?? '';

$tem_form = !empty($form_action);
?>

<div style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:50;align-items:center;justify-content:center" id="<?= htmlspecialchars($id) ?>">
    <div style="background:#fff;border:1px solid #e5e5e5;max-width:440px;width:90%;border-radius:2px">
        <!-- Header -->
        <div style="padding:14px 20px;border-bottom:1px solid #e5e5e5;display:flex;align-items:center;gap:8px">
            <?php if (!empty($icone)): ?>
                <i class="<?= htmlspecialchars($icone) ?>" style="color:#991b1b;font-size:18px;flex-shrink:0"></i>
            <?php endif; ?>
            <strong style="font-size:14px"><?= htmlspecialchars($titulo) ?></strong>
        </div>

        <!-- Body -->
        <div style="padding:16px 20px;font-size:14px;color:#262626;line-height:1.6">
            <?php if (is_array($mensagem)): ?>
                <!-- Se mensagem é array, renderizar cada item -->
                <?php foreach ($mensagem as $msg): ?>
                    <p><?= htmlspecialchars($msg) ?></p>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- String simples -->
                <p><?= htmlspecialchars($mensagem) ?></p>
            <?php endif; ?>
        </div>

        <!-- Footer com botões -->
        <div style="padding:12px 20px;border-top:1px solid #e5e5e5;background:#fafafa;display:flex;gap:8px;justify-content:flex-end">
            <button type="button"
                class="btn btn-outline-secondary"
                style="padding:7px 14px;font-size:13px"
                onclick="document.getElementById('<?= htmlspecialchars($id) ?>').style.display='none'">
                <?= htmlspecialchars($btn_cancelar) ?>
            </button>

            <?php if ($tem_form): ?>
                <!-- Botão dentro de form POST -->
                <form method="POST" action="<?= htmlspecialchars($form_action) ?>" style="display:inline">
                    <?php foreach ($hidden_fields as $name => $value): ?>
                        <input type="hidden" name="<?= htmlspecialchars($name) ?>" value="<?= htmlspecialchars((string)$value) ?>">
                    <?php endforeach; ?>
                    <?php if ($csrf && function_exists('\App\Core\CsrfService::hiddenField')): ?>
                        <?= \App\Core\CsrfService::hiddenField() ?>
                    <?php endif; ?>
                    <button type="submit"
                        style="padding:7px 14px;font-size:13px<?= !empty($btn_excluir_classe) ? ';' . htmlspecialchars($btn_excluir_classe) : '' ?>"
                        class="btn btn-action-delete confirm-delete-btn">
                        <?= htmlspecialchars($btn_excluir) ?>
                    </button>
                </form>
            <?php else: ?>
                <!-- Botão com onclick JavaScript -->
                <button type="button"
                    class="btn btn-action-delete"
                    style="padding:7px 14px;font-size:13px"
                    <?php if (!empty($onclick_excluir)): ?>
                        onclick="<?= htmlspecialchars($onclick_excluir) ?>"
                    <?php endif; ?>>
                    <?= htmlspecialchars($btn_excluir) ?>
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>
