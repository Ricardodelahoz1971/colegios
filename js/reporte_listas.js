(function() {
    window.imprimirSeleccionados = function() {
        const selected = Array.from(document.querySelectorAll('.chk-curso-print:checked')).map(el => el.value);
        if (selected.length === 0) {
            Swal.fire('Atención', 'Seleccione al menos un curso para imprimir.', 'warning');
            return;
        }
        navegarModulo('reporte_listas&id=' + selected.join(','));
    };

    function initRep() {
        if (typeof inicializarImpresion === 'function') {
            const container = document.getElementById('reporte-listas-container');
            const schoolName = container ? container.getAttribute('data-school-name') : 'SISTEMA ESCOLAR';
            inicializarImpresion(schoolName);
        } else {
            // Re-intento si el motor de reportes aún se está inyectando
            setTimeout(initRep, 100);
        }
    }

    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        initRep();
    } else {
        document.addEventListener('DOMContentLoaded', initRep);
    }
})();