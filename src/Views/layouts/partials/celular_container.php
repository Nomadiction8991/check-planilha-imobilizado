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

<div class="app-container celular-frame w-full flex items-center justify-center min-h-screen bg-slate-900 p-4">
    <div class="mobile-wrapper celular-shell w-full max-w-sm bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col">
        <!-- Coloque aqui o conteúdo que deve ficar dentro do 'celular' -->
        <main class="app-content celular-screen flex-1 overflow-y-auto">
            <?= $content ?? ($conteudo ?? '') ?>
        </main>
    </div>
</div>