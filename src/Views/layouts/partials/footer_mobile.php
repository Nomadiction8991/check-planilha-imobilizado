<?php


/**
 * Partial: footer_mobile.php
 * Rodapé móvel com três botões: Voltar (usa goBack()), Início, Sair (logout).
 * Variáveis aceitas (opcionais):
 *  - $homePath   (string) -> rota do botão Início (padrão: '/churches')
 *  - $logoutPath (string) -> rota para efetuar logout (padrão: '/logout')
 *
 * Observações:
 * - A função JavaScript `goBack()` já existe nos layouts principais.
 * - Não altera rotas — apenas fornece a marcação para inclusão nos layouts.
 */
$homePath = $homePath ?? '/products/view';
$logoutPath = $logoutPath ?? '/logout';
?>

<footer class="app-footer footer-mobile w-full bg-black text-white border-t border-neutral-700 px-4 py-3 flex items-center justify-between gap-2">
    <div class="footer-left flex-1">
        <button class="btn-footer-action w-full inline-flex flex-col items-center gap-1 px-3 py-2 hover:bg-neutral-900 rounded transition" onclick="goBack()" title="Voltar">
            <i class="bi bi-arrow-left text-lg"></i>
            <span class="footer-label text-xs">Voltar</span>
        </button>
    </div>

    <div class="footer-center flex-1">
        <a href="<?= htmlspecialchars($homePath, ENT_QUOTES, 'UTF-8') ?>" class="btn-footer-action w-full inline-flex flex-col items-center gap-1 px-3 py-2 hover:bg-neutral-900 rounded transition" title="Início">
            <i class="bi bi-house-door text-lg"></i>
            <span class="footer-label text-xs">Início</span>
        </a>
    </div>

    <div class="footer-right flex-1">
        <form method="POST" action="<?= htmlspecialchars($logoutPath, ENT_QUOTES, 'UTF-8') ?>" class="w-full">
            <?= \App\Core\CsrfService::hiddenField() ?>
            <button type="submit" class="btn-footer-action btn-logout w-full inline-flex flex-col items-center gap-1 px-3 py-2 hover:bg-red-900 rounded transition" title="Sair">
                <i class="bi bi-box-arrow-right text-lg"></i>
                <span class="footer-label text-xs">Sair</span>
            </button>
        </form>
    </div>
</footer>
