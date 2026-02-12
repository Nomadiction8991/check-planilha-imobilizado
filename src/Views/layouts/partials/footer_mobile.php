<?php

/**
 * Partial: footer_mobile.php
 * Rodapé móvel com três botões: Voltar (usa goBack()), Início, Sair (logout).
 * Variáveis aceitas (opcionais):
 *  - $homePath   (string) -> rota do botão Início (padrão: '/comuns')
 *  - $logoutPath (string) -> rota para efetuar logout (padrão: '/logout')
 *
 * Observações:
 * - A função JavaScript `goBack()` já existe nos layouts principais.
 * - Não altera rotas — apenas fornece a marcação para inclusão nos layouts.
 */
$homePath = $homePath ?? '/comuns';
$logoutPath = $logoutPath ?? '/logout';
?>

<footer class="app-footer footer-mobile">
    <div class="footer-left">
        <button class="btn-footer-action" onclick="goBack()" title="Voltar" aria-label="Voltar">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <span class="footer-label">Voltar</span>
        </button>
    </div>

    <div class="footer-center">
        <a href="<?= htmlspecialchars($homePath, ENT_QUOTES, 'UTF-8') ?>" class="btn-footer-action" title="Início" aria-label="Início">
            <i class="bi bi-house-door" aria-hidden="true"></i>
            <span class="footer-label">Início</span>
        </a>
    </div>

    <div class="footer-right">
        <a href="<?= htmlspecialchars($logoutPath, ENT_QUOTES, 'UTF-8') ?>" class="btn-footer-action btn-logout" title="Sair" aria-label="Sair">
            <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
            <span class="footer-label">Sair</span>
        </a>
    </div>
</footer>