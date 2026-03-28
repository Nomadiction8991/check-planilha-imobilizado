<?php
/**
 * Partial: Renderizar alertas de sucesso, erro e aviso
 *
 * Variáveis esperadas:
 * - $alertas (array, opcional): Array de alertas customizados
 *
 * Exemplo:
 * $alertas = [
 *     ['tipo' => 'success', 'mensagem' => 'Operação realizada!'],
 *     ['tipo' => 'error', 'mensagem' => 'Erro ao processar'],
 * ];
 *
 * Se não houver alertas, usa AlertHelper::fromQuery() automaticamente
 */

use App\Helpers\AlertHelper;

$alertas ??= [];

// Se houver alertas customizados, renderizá-los
if (!empty($alertas)):
    foreach ($alertas as $alerta):
        $tipo = $alerta['tipo'] ?? 'info';
        $mensagem = $alerta['mensagem'] ?? '';
        $icone = $alerta['icone'] ?? 'bi-info-circle';
        $fechar = $alerta['fechar'] ?? true;

        $cores = [
            'success' => ['bg' => '#f0fdf4', 'border' => '#86efac', 'color' => '#166534', 'icone' => 'bi-check-circle'],
            'error' => ['bg' => '#fef2f2', 'border' => '#fecaca', 'color' => '#991b1b', 'icone' => 'bi-exclamation-circle'],
            'warning' => ['bg' => '#fef3c7', 'border' => '#fde047', 'color' => '#b45309', 'icone' => 'bi-exclamation-triangle'],
            'info' => ['bg' => '#f0f9ff', 'border' => '#bfdbfe', 'color' => '#1e40af', 'icone' => 'bi-info-circle'],
        ];

        $cor = $cores[$tipo] ?? $cores['info'];
        $icone = $cor['icone'];
        ?>
        <div style="background:<?= $cor['bg'] ?>;border:1px solid <?= $cor['border'] ?>;color:<?= $cor['color'] ?>;border-radius:2px;padding:12px 14px;margin-bottom:16px;display:flex;align-items:flex-start;gap:10px"
            role="alert">
            <i class="<?= $icone ?>" style="flex-shrink:0;margin-top:2px;font-size:16px"></i>
            <p style="flex:1;margin:0;font-size:13px;line-height:1.4"><?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?></p>
            <?php if ($fechar): ?>
                <button type="button"
                    style="background:none;border:none;cursor:pointer;color:inherit;font-size:18px;line-height:1;padding:0;margin-left:8px;flex-shrink:0"
                    aria-label="Fechar alerta"
                    onclick="this.parentElement.remove()">
                    &times;
                </button>
            <?php endif; ?>
        </div>
        <?php
    endforeach;
else:
    // Usar AlertHelper para ler de query string
    echo AlertHelper::fromQuery();
endif;
?>
