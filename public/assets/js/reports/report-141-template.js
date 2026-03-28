/**
 * report-141-template.js — JS extraído de src/Views/reports/report-141-template.php
 * Detecção de edição nos campos do template.
 */
document.addEventListener('DOMContentLoaded', function () {
    // Permitir edição visual dos campos
    var campos = document.querySelectorAll('.a4 input[type="text"], .a4 textarea, .a4 input[type="checkbox"]');

    campos.forEach(function (campo) {
        // Quando o campo for editado, marcar como editado
        campo.addEventListener('input', function () {
            this.classList.add('editado');
        });

        campo.addEventListener('change', function () {
            this.classList.add('editado');
        });
    });

    // Permitir edição de checkbox
    var checkboxes = document.querySelectorAll('.a4 input[type="checkbox"]');
    checkboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            this.classList.add('editado');
        });
    });
});
