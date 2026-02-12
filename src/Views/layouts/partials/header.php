<!-- Header -->
<header class="app-header">
    <div class="header-left">
        <button class="btn-menu" type="button" data-bs-toggle="offcanvas" data-bs-target="#menuLateral" aria-controls="menuLateral">
            <i class="bi bi-list fs-5"></i>
        </button>
        <div class="header-title-section">
            <h1 class="app-title"><?php echo htmlspecialchars(to_uppercase($pageTitle ?? 'Anvy'), ENT_QUOTES, 'UTF-8'); ?></h1>
            <?php if (isset($_SESSION['usuario_nome'])): ?>
                <small class="user-name">
                    <i class="bi bi-person-circle me-1"></i>
                    <?php echo htmlspecialchars(to_uppercase($_SESSION['usuario_nome']), ENT_QUOTES, 'UTF-8'); ?>
                </small>
            <?php endif; ?>
        </div>
    </div>
    <div class="header-actions">
        <?php if (isset($headerActions) && $headerActions !== ''): ?>
            <?php echo $headerActions; ?>
        <?php endif; ?>
    </div>
</header>