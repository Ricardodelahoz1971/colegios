function initMatriculados() {
    const container = document.getElementById('matriculados-container');
    if (container) {
        try {
            window.formatosDisponibles = JSON.parse(container.getAttribute('data-formatos-activos')) || [];
        } catch (e) {
            window.formatosDisponibles = [];
        }
    }
}

if (document.readyState === 'complete' || document.readyState === 'interactive') {
    initMatriculados();
} else {
    document.addEventListener('DOMContentLoaded', initMatriculados);
}

function abrirSelectorImpresion(estudianteId, nombreEstudiante) {
    const container = document.getElementById('matriculados-container');
    if (container) {
        try {
            window.formatosDisponibles = JSON.parse(container.getAttribute('data-formatos-activos')) || [];
        } catch (e) {
            window.formatosDisponibles = window.formatosDisponibles || [];
        }
    }
    const listadoFormatos = window.formatosDisponibles || [];
    if (listadoFormatos.length === 0) {
        lanzarToastElite('warning', 'No hay formatos de documentos activos. Por favor active o cree alguno en Configuración.');
        return;
    }
    
    // Mapeo de nombres de categorías
    const categorias = {
        'matricula': '📄 Matrículas Académicas',
        'carne': '🪪 Carnés Escolares (ID CR-80)',
        'certificado': '📜 Certificados de Curso / Estudio',
        'constancia': '📑 Constancias / Paz y Salvo'
    };

    // Crear opciones agrupadas por categoría
    const inputOptions = {};
    listadoFormatos.forEach(f => {
        const catName = categorias[f.tipo_documento] || '📄 Documentos Institucionales';
        if (!inputOptions[catName]) {
            inputOptions[catName] = {};
        }
        const lienzoLabel = f.tamano_lienzo ? ` (${f.tamano_lienzo.toUpperCase().replace('_', ' ')})` : '';
        inputOptions[catName][f.id] = f.nombre + lienzoLabel;
    });

    Swal.fire({
        title: `EMISIÓN DE DOCUMENTO`,
        text: `Seleccione el formato a imprimir para: ${nombreEstudiante}`,
        input: 'select',
        inputOptions: inputOptions,
        inputPlaceholder: '--- Seleccione el Documento a Emitir ---',
        showCancelButton: true,
        confirmButtonText: 'EMITIR DOCUMENTO',
        cancelButtonText: 'CANCELAR',
        confirmButtonColor: 'var(--el-primary)',
        background: document.body.classList.contains('dark-theme-mode') ? 'var(--el-bg-card)' : 'var(--el-white)',
        color: document.body.classList.contains('dark-theme-mode') ? 'var(--el-dark)' : 'var(--el-dark)',
        inputValidator: (value) => {
            if (!value) {
                return 'Debe seleccionar un formato de documento';
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const formatoId = result.value;
            window.open(`../imprimir_matricula.php?estudiante_id=${estudianteId}&formato_id=${formatoId}`, '_blank');
        }
    });
}

function ejecutarAccionMasivo(sel) {
    if (!sel || !sel.value) return;
    const val = sel.value;
    
    if (val === 'formato') {
        window.location.href = 'logica/descargar_plantilla_matricula.php';
        lanzarToastElite('success', 'Descargando plantilla oficial de matrícula...');
    } else if (val === 'carga') {
        if (typeof window.abrirCargaMasiva === 'function') {
            window.abrirCargaMasiva();
        }
    }
    
    // Restablecer el select al placeholder "MASIVO"
    sel.value = '';
}

// Vincular al scope global
window.abrirSelectorImpresion = abrirSelectorImpresion;
window.ejecutarAccionMasivo = ejecutarAccionMasivo;