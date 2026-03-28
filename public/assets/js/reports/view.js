/**
 * view.js — JS extraído de src/Views/reports/view.php
 * Redimensionamento A4 e lógica de impressão genérica para visualização de relatórios.
 */
(function () {
    function mmToPx(mm) {
        var el = document.createElement('div');
        el.style.width = mm + 'mm';
        el.style.position = 'absolute';
        el.style.left = '-9999px';
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
            var available = rect.width - paddingLeft - paddingRight - 8;

            var scale = available / a4w;
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

    var btnPrint = document.getElementById('btnPrint');
    if (btnPrint) {
        btnPrint.addEventListener('click', function () {
            window.print();
        });
    }
})();
