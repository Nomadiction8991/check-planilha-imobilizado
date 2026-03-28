/**
 * signature.js — JS extraído de src/Views/reports/signature.php
 * Lógica do pad de assinatura em modo paisagem (canvas + signature_pad lib).
 */
(function () {
    var canvas = document.getElementById('sign_canvas');
    var wrapper = document.getElementById('canvasWrapper');
    var btnFull = document.getElementById('btnFull');
    var btnSave = document.getElementById('btnSave');
    var btnClear = document.getElementById('btnClear');
    var btnCancel = document.getElementById('btnCancel');
    var signaturePad = null;

    function resizeCanvasForLandscape(width) {
        var vw = window.innerWidth;
        var vh = window.innerHeight;
        var cssW = width || Math.max(1200, Math.floor(Math.max(vw, vh) * 1.2));
        var cssH = Math.max(90, Math.floor(cssW / 8));
        canvas.style.width = cssW + 'px';
        canvas.style.height = cssH + 'px';
        var dpr = window.devicePixelRatio || 1;
        canvas.width = Math.floor(cssW * dpr);
        canvas.height = Math.floor(cssH * dpr);
        var ctx = canvas.getContext('2d');
        try {
            ctx.setTransform(1, 0, 0, 1, 0, 0);
        } catch (e) { }
        ctx.scale(dpr, dpr);
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, cssW, cssH);
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
    }

    function initSIGNATUREPAD() {
        if (typeof SignaturePad === 'undefined') {
            var s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js';
            s.onload = function () {
                signaturePad = new SignaturePad(canvas, {
                    backgroundColor: 'rgb(255,255,255)',
                    penColor: 'black'
                });
            };
            document.head.appendChild(s);
        } else {
            signaturePad = new SignaturePad(canvas, {
                backgroundColor: 'rgb(255,255,255)',
                penColor: 'black'
            });
        }
    }

    // Try to enter fullscreen and lock orientation on user gesture
    async function enterFullscreenAndLock() {
        try {
            if (document.documentElement.requestFullscreen) await document.documentElement.requestFullscreen();
            if (screen && screen.orientation && screen.orientation.lock) {
                try {
                    await screen.orientation.lock('landscape');
                } catch (e) { }
            }
        } catch (e) {
            console.warn('fullscreen/orientation failed', e);
        }
    }

    btnFull.addEventListener('click', async function () {
        await enterFullscreenAndLock();
        resizeCanvasForLandscape();
        initSIGNATUREPAD();
        try {
            wrapper.scrollLeft = Math.max(0, (canvas.clientWidth - wrapper.clientWidth) / 2);
        } catch (e) { }
    });

    btnClear.addEventListener('click', function () {
        if (signaturePad) signaturePad.clear();
        try {
            var ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            resizeCanvasForLandscape(canvas.clientWidth);
        } catch (e) { }
    });

    btnCancel.addEventListener('click', function () {
        try {
            localStorage.removeItem('signature_temp');
        } catch (e) { }
        history.back();
    });

    btnSave.addEventListener('click', function () {
        var data = null;
        if (signaturePad) {
            if (signaturePad.isEmpty()) data = null;
            else data = signaturePad.toDataURL('image/png');
        } else {
            data = canvas.toDataURL('image/png');
        }
        if (!data) {
            if (!confirm('Assinatura vazia. Deseja salvar em branco?')) return;
        }
        try {
            localStorage.setItem('signature_temp', data);
        } catch (e) {
            console.error(e);
        }
        history.back();
    });

    // initial layout
    resizeCanvasForLandscape();
    initSIGNATUREPAD();
})();
