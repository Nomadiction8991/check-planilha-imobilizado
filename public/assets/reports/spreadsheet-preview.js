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

    async function saveChurchActions() {
        return postJson(config.saveUrl, {
            acoes: {},
            igrejas: collectChurchActions(),
        });
    }

    document.querySelectorAll('.js-preview-church-action').forEach((select) => {
        select.addEventListener('change', async () => {
            select.disabled = true;

            try {
                await saveChurchActions();
            } finally {
                select.disabled = false;
            }
        });
    });
})();
