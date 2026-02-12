<?php

/**
 * Partial: Tabela Vazia
 * 
 * Mensagem centralizada para tabelas sem resultados.
 * 
 * Variáveis esperadas:
 * - $colspan: int - Número de colunas da tabela
 * - $mensagem: string - Mensagem a exibir (opcional)
 * - $icone: string - Classe do ícone Bootstrap (opcional)
 */

use App\Helpers\ViewHelper;

$colspan = $colspan ?? 3;
$mensagem = $mensagem ?? 'NENHUM RESULTADO ENCONTRADO';
$icone = $icone ?? 'bi-inbox';
?>

<tr>
    <td colspan="<?= $colspan ?>" class="text-center py-4 text-muted">
        <i class="bi <?= ViewHelper::e($icone) ?> fs-3 d-block mb-2"></i>
        <?= ViewHelper::upper($mensagem) ?>
    </td>
</tr>