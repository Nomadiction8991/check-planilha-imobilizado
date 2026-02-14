function copiarCodigos() {
    const codigosField = document.getElementById('codigosField');
    codigosField.select();
    codigosField.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(codigosField.value).then(() => {
        const btn = document.activeElement;
    });
}

function filtrarPorDependencia() {
    const dependencia = document.getElementById('filtroDependencia').value;
    const url = new URL(window.location);
    if (dependencia) url.searchParams.set('dependencia', dependencia);
    else url.searchParams.delete('dependencia');
    window.location.href = url.toString();
}
