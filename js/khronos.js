document.addEventListener('DOMContentLoaded', () => {
    // Inicialización de Progress Bars sin Atributo Style
    document.querySelectorAll('.js-khronos-progress').forEach(el => {
        const pct = el.dataset.pct || 0;
        el.style.setProperty('--progress-width', pct + '%');
    });
});

window.gestionarEventoExtracurricular = async function(dia, hora, nombreActual = '') {
    const container = document.getElementById('khronos-container');
    const cursoId = container ? container.getAttribute('data-curso-id') : '0';

    const { value: nombreEvento } = await Swal.fire({
        title: 'ACTIVIDAD EXTRACURRICULAR',
        input: 'text',
        inputValue: nombreActual,
        inputLabel: 'Nombre del Evento / Taller',
        placeholder: 'Ej: Taller de Robótica o Jornada Deportiva',
        showCancelButton: true,
        confirmButtonText: 'GUARDAR EVENTO',
        cancelButtonText: 'CANCELAR',
        customClass: { popup: 'glass-modal-elite rounded-4' }
    });

    if (nombreEvento !== undefined) {
        const formData = new FormData();
        formData.append('curso_id', cursoId);
        formData.append('dia', dia);
        formData.append('hora', hora);
        formData.append('evento', nombreEvento);

        try {
            const res = await fetch('logica/guardar_evento_khronos.php', { method: 'POST', body: formData });
            const d = await res.json();
            if (d.status === 'success') {
                await Swal.fire({ icon: 'success', title: 'Evento Sincronizado', timer: 1000, showConfirmButton: false });
                // Actualizar tabla de eventos sin recargar la página
                const khronosContainer = document.getElementById('khronos-container');
                if (khronosContainer) {
                    const refreshEvento = new Event('evento-guardado', { bubbles: true });
                    khronosContainer.dispatchEvent(refreshEvento);
                }
            } else {
                throw new Error(d.message);
            }
        } catch (e) {
            Swal.fire('ERROR', e.message, 'error');
        }
    }
};