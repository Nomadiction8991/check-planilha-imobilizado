(() => {
    'use strict';

    const config = window.previewImportConfig || {};

    if (!config.saveUrl) {
        return;
    }

    function collectChurchActions() {
        const churches = {};
        document.querySelectorAll('.js-preview-church-action').forEach((select) => {
            const code = select.dataset.codigo;
            if (code) {
                churches[code] = select.value;
            }
        });
        return churches;
    }

    function collectDependencyActions() {
        const dependencies = {};
        document.querySelectorAll('.js-preview-dependency-action').forEach((select) => {
            const key = select.dataset.depKey;
            if (key) {
                dependencies[key] = select.value;
            }
        });
        return dependencies;
    }

    async function postJson(url, payload) {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': config.csrfToken || '',
            },
            body: JSON.stringify(payload),
        });

        const data = await response.json();

        if (!response.ok || data.sucesso !== true) {
            throw new Error(data.erro || 'Não foi possível concluir a ação.');
        }

        return data;
    }

    async function saveActions() {
        return postJson(config.saveUrl, {
            acoes: {},
            igrejas: collectChurchActions(),
            dependencias: collectDependencyActions(),
        });
    }

    function toggleDependencyRow(churchKey, action, churchRow) {
        const depRows = document.querySelectorAll(`.dependency-row[data-for-church="${churchKey}"]`);
        if (depRows.length > 0) {
            if (action === 'personalizado') {
                depRows.forEach(row => {
                    row.classList.remove('is-hidden');
                    // Pequeno delay para animação de fade-in
                    requestAnimationFrame(() => {
                        row.style.opacity = '1';
                        row.style.transform = 'translateY(0)';
                    });
                });
                
                // Scroll suave se a tabela for grande
                if (depRows.length > 3) {
                    churchRow.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            } else {
                depRows.forEach(row => {
                    row.classList.add('is-hidden');
                    row.style.opacity = '0';
                    row.style.transform = 'translateY(-10px)';
                });
            }
        }
    }

    document.querySelectorAll('.js-preview-church-action').forEach((select) => {
        select.addEventListener('change', async () => {
            const churchKey = select.dataset.codigo;
            const value = select.value;

            const churchRow = select.closest('tr');
            toggleDependencyRow(churchKey, value, churchRow);

            select.disabled = true;
            try {
                await saveActions();
            } finally {
                select.disabled = false;
            }
        });
    });

    document.querySelectorAll('.js-preview-dependency-action').forEach((select) => {
        select.addEventListener('change', async () => {
            select.disabled = true;
            try {
                await saveActions();
            } finally {
                select.disabled = false;
            }
        });
    });
})();
