<?php

/**
 * Partial: celular_container.php
 * Uso: envolve o conteúdo dentro de um frame que simula a tela de um celular.
 * Não altera nada por padrão — apenas disponibiliza a partial para inclusão futura.
 *
 * Exemplo de uso:
 *   $content = '<p>conteúdo aqui</p>';
 *   include __DIR__ . '/partials/celular_container.php';
 *
 * A partial usa as classes já presentes nos layouts (`app-container`, `mobile-wrapper`)
 * para garantir consistência com o CSS existente.
 */
?>

<div class="app-container celular-frame">
    <div class="mobile-wrapper celular-shell">
        <!-- Coloque aqui o conteúdo que deve ficar dentro do 'celular' -->
        <main class="app-content celular-screen">
            <?= $content ?? ($conteudo ?? '') ?>
        </main>
    </div>
</div>