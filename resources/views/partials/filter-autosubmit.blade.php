<script>
    (() => {
        document.querySelectorAll('form[data-filter-autosubmit]').forEach((form) => {
            if (form.dataset.filterAutosubmitReady === 'true') {
                return;
            }

            form.dataset.filterAutosubmitReady = 'true';
            const submitButton = form.querySelector('button[type="submit"]');
            const status = form.querySelector('[data-filter-status]');
            const serverFields = form.querySelectorAll('[data-filter-server]');
            const searchInput = form.querySelector('[data-filter-search]');
            const statusMessage = form.dataset.filterMessage || 'Atualizando resultados…';
            let submitTimer = 0;
            let lastSignature = new URLSearchParams(new FormData(form)).toString();

            const getSignature = () => new URLSearchParams(new FormData(form)).toString();
            const resetPage = () => {
                ['page', 'pagina'].forEach((pageName) => {
                    const fields = form.querySelectorAll(`[name="${pageName}"]`);
                    fields.forEach((field) => {
                        field.value = '';
                        field.disabled = true;
                    });
                });
            };
            const submitFilterIfChanged = () => {
                submitTimer = 0;
                const signature = getSignature();
                if (signature === lastSignature) {
                    return;
                }

                lastSignature = signature;
                resetPage();
                form.dataset.filterSubmitting = 'true';
                if (status) {
                    status.textContent = statusMessage;
                }
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.setAttribute('aria-busy', 'true');
                }

                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
            };
            const scheduleSubmit = (delay) => {
                window.clearTimeout(submitTimer);
                submitTimer = window.setTimeout(submitFilterIfChanged, delay);
            };

            form.addEventListener('submit', () => {
                window.clearTimeout(submitTimer);
                lastSignature = getSignature();
                form.dataset.filterSubmitting = 'true';
            });

            serverFields.forEach((field) => {
                field.addEventListener('change', () => scheduleSubmit(80));
            });

            if (searchInput) {
                searchInput.addEventListener('input', () => scheduleSubmit(350));
                searchInput.addEventListener('search', () => scheduleSubmit(0));
                searchInput.addEventListener('change', () => scheduleSubmit(0));
            }
        });
    })();
</script>
