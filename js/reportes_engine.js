/**
 * 📑 REPORTES ENGINE v10.0 - MOTOR DE REPORTES ACADÉMICOS
 * Extraído para centralizar la lógica de impresión y selección masiva.
 */

function imprimirSeleccionados() {
    const ids = Array.from(document.querySelectorAll('.chk-curso-print:checked')).map(c => c.value);
    if (ids.length === 0) {
        Swal.fire({ icon: 'info', title: 'Sin selección', text: 'Marque algún curso para imprimir.' });
        return;
    }
    window.location.href = 'dashboard.php?p=reporte_listas&id=' + ids.join(',');
}

/**
 * Lógica de Activación de Impresión (Página Limpia)
 */
function inicializarImpresion(schoolName) {
    window.onload = function() {
        document.title = "Reporte_" + schoolName.replace(/\s+/g, '_') + "_" + new Date().getTime();
        setTimeout(function() { window.print(); }, 1000);
    };
    window.onafterprint = function() {
        window.location.href = 'dashboard.php?p=reporte_listas';
    };
}
