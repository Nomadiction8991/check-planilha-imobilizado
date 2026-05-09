(() => {
    'use strict';

    const config = window.importErrorsConfig || {};

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
            throw new Error(data.erro || 'Falha ao atualizar o erro.');
        }

        return data;
    }

    function updatePendingCount(count) {
        document.querySelectorAll('.js-pending-count').forEach((node) => {
            node.textContent = String(count);
        });
    }

    document.addEventListener('change', async (event) => {
        const target = event.target;
        if (!(target instanceof HTMLInputElement) || !target.classList.contains('js-resolve-error')) {
            return;
        }

        if (config.canResolve !== true) {
            target.checked = !target.checked;
            return;
        }

        const errorId = target.dataset.id;
        const resolved = target.checked;
        const statusNode = document.getElementById(`erro-status-${errorId}`);
        target.disabled = true;

        try {
            const data = await postJson(`${config.resolveUrlBase}/${errorId}/resolve`, {
                resolvido: resolved,
            });

            if (statusNode) {
                statusNode.textContent = resolved ? 'Resolvido' : 'Pendente';
            }

            updatePendingCount(data.pendentes);
        } catch (error) {
            target.checked = !resolved;
            window.alert(error.message);
        } finally {
            target.disabled = false;
        }
    });
})();
