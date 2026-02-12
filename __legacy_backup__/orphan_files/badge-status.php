<?php

/**
 * Partial: Badge de Status
 * 
 * Variáveis esperadas:
 * - $ativo: bool - Status ativo/inativo
 * - $labelAtivo: string - Texto para status ativo (opcional)
 * - $labelInativo: string - Texto para status inativo (opcional)
 */

$ativo = $ativo ?? true;
$labelAtivo = $labelAtivo ?? 'ATIVO';
$labelInativo = $labelInativo ?? 'INATIVO';

$classe = $ativo ? 'bg-success' : 'bg-secondary';
$texto = $ativo ? $labelAtivo : $labelInativo;
?>

<span class="badge <?= $classe ?>"><?= $texto ?></span>