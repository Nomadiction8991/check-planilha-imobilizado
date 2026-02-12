<?php

/**
 * Partial: header_mobile.php
 * Cabeçalho móvel com seletor de comum
 * Variáveis aceitas (opcionais):
 *  - $pageTitle (string)  -> título da página (renderizado em maiúsculas)
 *  - $userName  (string)  -> nome do usuário logado (pode ficar vazio)
 *  - $menuPath  (string)  -> caminho a ser usado no botão de menu (default: '/menu')
 *  - $comuns (array)      -> lista de comuns disponíveis
 *  - $comumAtualId (int)  -> ID da comum selecionada
 */
$menuPath = $menuPath ?? '/menu';
$pageTitle = $pageTitle ?? 'TÍTULO DA PÁGINA';
$userName = $userName ?? '';
$comuns = $comuns ?? [];
$comumAtualId = $comumAtualId ?? null;
?>

<div class="app-header header-mobile">
    <div class="header-left">
        <a href="<?= $menuPath ?>" class="btn-menu" aria-label="Abrir menu">
            <i class="bi bi-list" aria-hidden="true"></i>
        </a>

        <div class="header-title-section">
            <h1 class="app-title"><?= strtoupper(htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8')) ?></h1>
            <div class="user-name"><?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    </div>

    <div class="header-actions">
        <?php if (!empty($comuns)): ?>
        <select id="comum-selector" class="form-select form-select-sm" aria-label="Selecionar Comum">
            <option value="">Selecione a Comum</option>
            <?php foreach ($comuns as $comum): ?>
                <option value="<?= (int)$comum['id'] ?>" <?= (int)$comum['id'] === (int)$comumAtualId ? 'selected' : '' ?>>
                    <?= htmlspecialchars($comum['codigo'] . ' - ' . $comum['descricao'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($comuns)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selector = document.getElementById('comum-selector');
    if (selector) {
        selector.addEventListener('change', function() {
            const comumId = this.value;
            if (comumId) {
                fetch('/usuarios/selecionar-comum', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ comum_id: comumId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Erro ao selecionar comum: ' + (data.message || 'Erro desconhecido'));
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao selecionar comum');
                });
            }
        });
    }
});
</script>
<?php endif; ?>