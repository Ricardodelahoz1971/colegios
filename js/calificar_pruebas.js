document.addEventListener('DOMContentLoaded', () => {
    // 1. Inicializar variables globales basadas en el contenedor
    const container = document.getElementById('gradebook-container');
    if (container) {
        window.AresEscala = {
            nota_minima: parseFloat(container.getAttribute('data-nota-minima')) || 0.0,
            nota_maxima: parseFloat(container.getAttribute('data-nota-maxima')) || 5.0,
            nota_aprobacion: parseFloat(container.getAttribute('data-nota-aprobacion')) || 3.0
        };
        window.AresPoliticaRecuperacion = container.getAttribute('data-politica') || 'reemplazo';
    }

    // 2. Inicializar Perseus si está definido
    if (typeof Perseus !== 'undefined') {
        Perseus.init();
    }
});