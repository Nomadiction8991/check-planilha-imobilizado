<?php
/**
 * Partial: Campo de Busca Reutilizável
 * 
 * Variáveis esperadas:
 * - $busca: Valor atual da busca
 * - $placeholder: Placeholder do campo (opcional)
 * - $action: URL de ação do formulário (opcional, padrão: atual)
 * - $showClearButton: Mostrar botão limpar (opcional, padrão: true)
 */

use App\Helpers\ViewHelper;

$busca = $busca ?? '';
$placeholder = $placeholder ?? 'DIGITE PARA BUSCAR';
$action = $action ?? '';
$showClearButton = $showClearButton ?? true;
?>

<form method="GET" <?= $action ? 'action="' . ViewHelper::e($action) . '"' : '' ?>>
    <div class="input-group">
        <input 
            type="text" 
            name="busca" 
            class="form-control text-uppercase" 
            value="<?= ViewHelper::e($busca) ?>"
            placeholder="<?= ViewHelper::e($placeholder) ?>"
        >
        <?php if ($showClearButton && $busca): ?>
            <button type="button" class="btn btn-outline-secondary" onclick="this.previousElementSibling.value=''; this.form.submit();">
                <i class="bi bi-x-lg"></i>
            </button>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-search"></i>
        </button>
    </div>
</form>
