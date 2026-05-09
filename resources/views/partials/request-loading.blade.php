<style>
    .request-loading-overlay {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: grid;
        place-items: center;
        padding: 24px;
        background: rgba(21, 18, 14, 0.46);
        backdrop-filter: blur(10px);
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity 0.18s ease, visibility 0.18s ease;
    }

    .request-loading-overlay.is-visible {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }

    .request-loading-overlay__panel {
        display: grid;
        justify-items: center;
        gap: 12px;
        min-width: min(280px, 100%);
        padding: 28px 30px;
        border: 1px solid var(--line);
        border-radius: var(--radius-lg);
        background: var(--surface-strong);
        box-shadow: var(--shadow-strong);
        color: var(--ink);
        text-align: center;
    }

    .request-loading-overlay__spinner {
        width: 54px;
        height: 54px;
        border-radius: 999px;
        border: 4px solid rgba(31, 111, 95, 0.14);
        border-top-color: var(--accent);
        animation: request-loading-spin 0.8s linear infinite;
    }

    .request-loading-overlay__label {
        font-size: 14px;
        font-weight: 600;
        letter-spacing: 0.01em;
    }

    .request-loading-overlay__track {
        width: min(180px, 100%);
        height: 8px;
        overflow: hidden;
        border: 1px solid rgba(31, 111, 95, 0.18);
        border-radius: 999px;
        background: rgba(31, 111, 95, 0.08);
    }

    .request-loading-overlay__bar {
        width: 42%;
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, rgba(31, 111, 95, 0.42), var(--accent));
        box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.08) inset;
        animation: request-loading-bar 1.25s ease-in-out infinite;
    }

    .request-loading-overlay__sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }

    body.is-loading {
        overflow: hidden;
        cursor: progress;
    }

    body.is-loading * {
        cursor: progress !important;
    }

    html[data-theme='dark'] .request-loading-overlay {
        background: rgba(6, 8, 10, 0.6);
    }

    html[data-theme='dark'] .request-loading-overlay__panel {
        background:
            radial-gradient(circle at top, rgba(145, 221, 209, 0.08), transparent 42%),
            linear-gradient(180deg, rgba(32, 30, 26, 0.98), rgba(24, 22, 19, 0.96));
    }

    html[data-theme='dark'] .request-loading-overlay__spinner {
        border-color: rgba(145, 221, 209, 0.16);
        border-top-color: var(--accent);
    }

    html[data-theme='dark'] .request-loading-overlay__track {
        border-color: rgba(145, 221, 209, 0.18);
        background: rgba(145, 221, 209, 0.08);
    }

    html[data-theme='dark'] .request-loading-overlay__bar {
        background: linear-gradient(90deg, rgba(145, 221, 209, 0.42), var(--accent));
    }

    @keyframes request-loading-spin {
        to {
            transform: rotate(360deg);
        }
    }

    @keyframes request-loading-bar {
        0% {
            transform: translateX(-120%);
        }

        50% {
            transform: translateX(10%);
        }

        100% {
            transform: translateX(120%);
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .request-loading-overlay,
        .request-loading-overlay__spinner {
            transition: none;
            animation: none;
        }
    }
</style>

<div class="request-loading-overlay" data-request-loading aria-hidden="true">
    <div class="request-loading-overlay__panel" role="status" aria-live="polite" aria-atomic="true">
        <span class="request-loading-overlay__spinner" aria-hidden="true"></span>
        <span class="request-loading-overlay__label">Carregando...</span>
        <span class="request-loading-overlay__track" aria-hidden="true">
            <span class="request-loading-overlay__bar"></span>
        </span>
        <span class="request-loading-overlay__sr-only">Aguarde enquanto a ação é processada.</span>
    </div>
</div>

<script>
    (() => {
        const loader = document.querySelector('[data-request-loading]');

        if (!loader) {
            return;
        }

        const minVisibleMs = 180;
        const nativeFetch = typeof window.fetch === 'function' ? window.fetch.bind(window) : null;
        const nativeXhrOpen = XMLHttpRequest.prototype.open;
        const nativeXhrSend = XMLHttpRequest.prototype.send;

        let activeRequests = 0;
        let visibleSince = 0;
        let hideTimer = null;

        function syncBodyState(visible) {
            document.body.classList.toggle('is-loading', visible);
        }

        function setVisible(visible) {
            loader.classList.toggle('is-visible', visible);
            loader.setAttribute('aria-hidden', visible ? 'false' : 'true');
            syncBodyState(visible);
        }

        function showLoader() {
            if (hideTimer !== null) {
                window.clearTimeout(hideTimer);
                hideTimer = null;
            }

            if (activeRequests === 0) {
                visibleSince = window.performance.now();
                setVisible(true);
            }

            activeRequests += 1;
        }

        function hideLoader() {
            activeRequests = Math.max(0, activeRequests - 1);

            if (activeRequests > 0) {
                return;
            }

            const elapsed = window.performance.now() - visibleSince;
            const remaining = Math.max(0, minVisibleMs - elapsed);

            if (hideTimer !== null) {
                window.clearTimeout(hideTimer);
            }

            hideTimer = window.setTimeout(() => {
                if (activeRequests === 0) {
                    setVisible(false);
                }

                hideTimer = null;
            }, remaining);
        }

        function shouldIgnoreAnchor(anchor, event) {
            if (!anchor || anchor.hasAttribute('download')) {
                return true;
            }

            if (
                anchor.dataset.noLoader === 'true'
                || anchor.dataset.loadingIgnore === 'true'
                || anchor.target === '_blank'
                || anchor.target === '_parent'
                || anchor.target === '_top'
            ) {
                return true;
            }

            if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) {
                return true;
            }

            const href = anchor.getAttribute('href') || '';

            if (
                href === ''
                || href.startsWith('#')
                || href.startsWith('mailto:')
                || href.startsWith('tel:')
                || href.startsWith('javascript:')
            ) {
                return true;
            }

            try {
                const targetUrl = new URL(anchor.href, window.location.href);
                return targetUrl.origin !== window.location.origin;
            } catch (error) {
                return true;
            }
        }

        function shouldIgnoreForm(form) {
            return (
                form.dataset.noLoader === 'true'
                || form.dataset.loadingIgnore === 'true'
                || form.target === '_blank'
                || form.target === '_parent'
                || form.target === '_top'
            );
        }

        function headerValue(headers, name) {
            if (!headers) {
                return null;
            }

            const lookup = name.toLowerCase();

            if (headers instanceof Headers) {
                return headers.get(name) ?? headers.get(lookup);
            }

            if (Array.isArray(headers)) {
                const entry = headers.find(([key]) => String(key).toLowerCase() === lookup);
                return entry ? entry[1] : null;
            }

            if (typeof headers === 'object') {
                const entry = Object.entries(headers).find(([key]) => key.toLowerCase() === lookup);
                return entry ? entry[1] : null;
            }

            return null;
        }

        function shouldIgnoreFetch(args) {
            const [input, init] = args;
            const ignoreValues = ['1', 'true', 'yes'];

            if (typeof Request !== 'undefined' && input instanceof Request) {
                const requestHeader = headerValue(input.headers, 'X-No-Loader');
                if (requestHeader !== null && ignoreValues.includes(String(requestHeader).toLowerCase())) {
                    return true;
                }
            }

            const initHeader = headerValue(init?.headers, 'X-No-Loader');
            return initHeader !== null && ignoreValues.includes(String(initHeader).toLowerCase());
        }

        if (nativeFetch !== null) {
            window.fetch = (...args) => {
                if (shouldIgnoreFetch(args)) {
                    return nativeFetch(...args);
                }

                showLoader();

                try {
                    return Promise.resolve(nativeFetch(...args)).finally(hideLoader);
                } catch (error) {
                    hideLoader();
                    throw error;
                }
            };
        }

        XMLHttpRequest.prototype.open = function open(...args) {
            this.__requestLoadingTracked = false;
            return nativeXhrOpen.apply(this, args);
        };

        XMLHttpRequest.prototype.send = function send(...args) {
            if (!this.__requestLoadingTracked) {
                this.__requestLoadingTracked = true;
                this.addEventListener('loadend', hideLoader, { once: true });
                showLoader();
            }

            try {
                return nativeXhrSend.apply(this, args);
            } catch (error) {
                hideLoader();
                throw error;
            }
        };

        document.addEventListener('submit', (event) => {
            const form = event.target;

            if (event.defaultPrevented || !(form instanceof HTMLFormElement) || shouldIgnoreForm(form)) {
                return;
            }

            showLoader();
        });

        document.addEventListener('click', (event) => {
            if (!(event.target instanceof Element)) {
                return;
            }

            const anchor = event.target.closest('a[href]');

            if (event.defaultPrevented || !(anchor instanceof HTMLAnchorElement) || shouldIgnoreAnchor(anchor, event)) {
                return;
            }

            showLoader();
        });

        window.addEventListener('pageshow', () => {
            activeRequests = 0;

            if (hideTimer !== null) {
                window.clearTimeout(hideTimer);
                hideTimer = null;
            }

            setVisible(false);
        });
    })();
</script>
