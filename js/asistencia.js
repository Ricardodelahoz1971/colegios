(function() {
    window.cambiarFiltroAsistencia = async function() {
        const curso = document.getElementById('asistencia-curso').value;
        const fecha = document.getElementById('asistencia-fecha').value;
        
        const newUrl = `dashboard.php?p=asistencia&curso_id=${curso}&fecha=${fecha}`;
        window.history.pushState({path:newUrl}, '', newUrl);

        const mainContent = document.querySelector('.main-content-fixed') || document.body;
        mainContent.classList.add('u-opacity-muted');

        try {
            const r = await fetch(newUrl + '&raw=1');
            if (!r.ok) throw new Error('Error en la comunicación con el servidor.');
            
            const html = await r.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const nuevoContenido = doc.querySelector('#asistencia-container-master');
            
            if (nuevoContenido) {
                document.querySelector('#asistencia-container-master').innerHTML = nuevoContenido.innerHTML;
                mainContent.classList.remove('u-opacity-muted');
                mainContent.classList.add('u-opacity-full');
            } else {
                navegarModulo(`asistencia&curso_id=${curso}&fecha=${fecha}`);
            }
        } catch (e) {
            navegarModulo(`asistencia&curso_id=${curso}&fecha=${fecha}`);
        }
    };

    window.guardarAsistencia = async function(cursoId, fecha) {
        const filas = document.querySelectorAll('.alumno-fila-asist');
        const datos = [];

        filas.forEach(f => {
            const id = f.dataset.id;
            const check = f.querySelector(`input[name="asist_${id}"]:checked`);
            const estado = check ? check.value : 'P';
            const obs = f.querySelector('.asist-obs').value;
            datos.push({ id, estado, obs });
        });

        Swal.fire({
            title: 'Guardando Asistencia...',
            text: 'Sincronizando con la bóveda institucional.',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        try {
            const fd = new FormData();
            fd.append('curso_id', cursoId);
            fd.append('fecha', fecha);
            fd.append('asistencias', JSON.stringify(datos));
            fd.append('csrf_token', window.CSRF_TOKEN || '');

            const r = await fetch('logica/guardar_asistencia.php', { method: 'POST', body: fd });
            const res = await r.json();

            if(res.status === 'success') {
                await Swal.fire({
                    icon: 'success',
                    title: '¡Control Guardado!',
                    text: res.message,
                    timer: 2000,
                    showConfirmButton: false
                });

                if(res.ha_faltado) {
                    await Swal.fire({
                        icon: 'info',
                        title: 'Notificación de Seguridad',
                        text: 'Se han generado alertas de inasistencia para Coordinación.',
                        confirmButtonText: 'Entendido'
                    });
                }
            } else {
                throw new Error(res.message || 'Error en el guardado.');
            }
        } catch (e) {
            Swal.fire('Error Crítico', e.message || 'No se pudo conectar con el servidor.', 'error');
        }
    };
})();