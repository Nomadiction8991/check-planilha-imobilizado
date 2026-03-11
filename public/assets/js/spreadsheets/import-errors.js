// spreadsheets/import-errors.js — toggle resolvido via AJAX
(function () {
    var body = document.getElementById('tabela-erros-body');
    if (!body) return;

    function toastFn(msg, ok) {
        var el = document.createElement('div');
        el.className = 'alert alert-' + (ok ? 'success' : 'danger') +
            ' position-fixed bottom-0 end-0 m-3 py-2 px-3 shadow';
        el.style.cssText = 'z-index:9999;min-width:220px;font-size:.85rem;border-radius:8px';
        el.setAttribute('role', 'alert');
        el.setAttribute('aria-live', 'polite');
        el.textContent = msg;
        document.body.appendChild(el);
        setTimeout(function () { el.remove(); }, 2800);
    }

    body.addEventListener('change', async function (e) {
        var chk = e.target;
        if (!chk.classList.contains('chk-resolvido')) return;

        var erroId   = parseInt(chk.dataset.id, 10);
        var resolvido = chk.checked;
        var row      = document.getElementById('erro-row-' + erroId);
        var badge    = document.getElementById('badge-' + erroId);

        // Feedback imediato
        chk.disabled = true;
        if (row) {
            row.className = resolvido ? 'erro-row-resolvido' : 'erro-row-pendente';
        }
        if (badge) {
            badge.innerHTML = resolvido
                ? '<span class="badge badge-resolvido">OK</span>'
                : '<span class="badge badge-pendente">PEND</span>';
        }

        try {
            var resp = await fetch('/spreadsheets/import-errors/resolver', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ erro_id: erroId, resolvido: resolvido })
            });
            var data = await resp.json();

            if (!data.sucesso) {
                throw new Error(data.erro || 'Erro desconhecido');
            }

            var info = document.getElementById('contador-pendentes-info');
            if (info && typeof data.pendentes === 'number') {
                info.dataset.pendentes = data.pendentes;
            }

            toastFn(
                resolvido ? 'Marcado como resolvido ✓' : 'Reaberto como pendente',
                data.sucesso
            );

        } catch (ex) {
            chk.checked = !resolvido;
            if (row) row.className = !resolvido ? 'erro-row-resolvido' : 'erro-row-pendente';
            if (badge) {
                badge.innerHTML = !resolvido
                    ? '<span class="badge badge-resolvido">OK</span>'
                    : '<span class="badge badge-pendente">PEND</span>';
            }
            toastFn('Falha ao salvar: ' + ex.message, false);
        } finally {
            chk.disabled = false;
        }
    });
}());
