<?php
/**
 * Partial: Menu Dropdown do Header
 * 
 * Menu suspendido com ações principais do sistema.
 * Variáveis esperadas:
 * - $usuarioId: ID do usuário logado (opcional)
 */

$usuarioId = $usuarioId ?? $_SESSION['usuario_id'] ?? null;
?>

<div class="dropdown">
    <button class="btn-header-action" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-list fs-5"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        <?php if ($usuarioId): ?>
            <li>
                <a class="dropdown-item" href="/usuarios">
                    <i class="bi bi-people me-2"></i>LISTAGEM DE USUÁRIOS
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="/dependencias">
                    <i class="bi bi-diagram-3 me-2"></i>LISTAGEM DE DEPENDÊNCIAS
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="/usuarios/editar?id=<?= $usuarioId ?>">
                    <i class="bi bi-pencil-square me-2"></i>EDITAR MEU USUÁRIO
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="/planilhas/importar">
                    <i class="bi bi-upload me-2"></i>IMPORTAR PLANILHA
                </a>
            </li>
            <li><hr class="dropdown-divider"></li>
        <?php endif; ?>
        <li>
            <a class="dropdown-item" href="/logout">
                <i class="bi bi-box-arrow-right me-2"></i>SAIR
            </a>
        </li>
    </ul>
</div>
