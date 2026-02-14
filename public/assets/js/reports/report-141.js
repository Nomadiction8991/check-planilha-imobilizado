/**
 * report-141.js — JS extraído de src/Views/reports/report-141.php
 * Responsável pelo redimensionamento A4 e lógica de impressão.
 */
(function () {
    // Calcula px a partir de mm usando elemento temporário
    function mmToPx(mm) {
        var el = document.createElement('div');
        el.style.position = 'absolute';
        el.style.left = '-9999px';
        el.style.width = mm + 'mm';
        document.body.appendChild(el);
        var px = el.getBoundingClientRect().width;
        document.body.removeChild(el);
        return px;
    }

    function fitAll() {
        var a4w = mmToPx(210);
        var a4h = mmToPx(297);
        document.querySelectorAll('.a4-viewport').forEach(function (vp) {
            var scaled = vp.querySelector('.a4-scaled');
            if (!scaled) return;
            var rect = vp.getBoundingClientRect();
            var style = getComputedStyle(vp);
            var paddingLeft = parseFloat(style.paddingLeft) || 0;
            var paddingRight = parseFloat(style.paddingRight) || 0;
            // largura útil dentro do viewport
            var available = rect.width - paddingLeft - paddingRight - 8;
            var scale = available / a4w;
            if (!isFinite(scale) || scale <= 0) scale = 0.5;
            // limitar entre 0.25 e 1
            scale = Math.max(0.25, Math.min(1, scale));

            scaled.style.width = a4w + 'px';
            scaled.style.height = a4h + 'px';
            scaled.style.transformOrigin = 'top left';
            scaled.style.transform = 'scale(' + scale + ')';

            var paddingTop = parseFloat(style.paddingTop) || 0;
            var targetH = Math.round(a4h * scale + paddingTop + 4);
            vp.style.height = targetH + 'px';
            vp.style.overflow = 'hidden';
        });
    }

    var debounce = function (fn, wait) {
        var t;
        return function () {
            clearTimeout(t);
            t = setTimeout(fn, wait);
        };
    };

    window.addEventListener('resize', debounce(fitAll, 120));
    window.addEventListener('load', fitAll);
    document.addEventListener('DOMContentLoaded', fitAll);

    // Função global de impressão simplificada
    window.validarEImprimir = function () {
        window.print();
    };

    // Handler do botão imprimir
    document.addEventListener('click', function (e) {
        var btn = e.target && e.target.closest && e.target.closest('#btnPrint');
        if (btn) {
            e.preventDefault();
            try {
                if (typeof window.validarEImprimir === 'function') {
                    window.validarEImprimir();
                } else {
                    window.print();
                }
            } catch (err) {
                console.error('print handler error', err);
                window.print();
            }
        }
    });
})();
