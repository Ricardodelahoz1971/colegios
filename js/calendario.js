(function() {
    let currentDate = new Date();
    let eventsData = [];
    let esAdminCron = false;

    // Inicializar propiedades desde el DOM
    function initProps() {
        const container = document.getElementById('calendario-container');
        if (container) {
            esAdminCron = container.getAttribute('data-es-admin-cron') === 'true';
            window.CSRF_TOKEN = container.getAttribute('data-csrf-token') || '';
        }
    }

    // EXPORTAR FUNCIONES AL ÁMBITO GLOBAL
    window.fetchEvents = async function() {
        initProps();
        try {
            const resp = await fetch('logica/obtener_eventos.php');
            if (!resp.ok) throw new Error('Error en red');
            eventsData = await resp.json();
            renderCalendar();
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Error de Sincronización', text: 'No se pudieron cargar los eventos del cronograma.', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
        }
    };

    window.saltarAMesElite = function(m) {
        if (currentDate.getMonth() === parseInt(m)) return;
        currentDate.setDate(1);
        currentDate.setMonth(parseInt(m));
        renderCalendar();
    };

    window.prevMonth = function() {
        currentDate.setDate(1);
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar();
    };

    window.nextMonth = function() {
        currentDate.setDate(1);
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar();
    };

    window.nuevoEvento = function() {
        const hoy = new Date().toISOString().split('T')[0];
        abrirModalEvento(hoy);
    };

    function renderCalendar() {
        const monthYear = document.getElementById('calendar-month-year');
        const daysContainer = document.getElementById('calendar-days');
        
        if (!daysContainer || !monthYear) return;
        
        daysContainer.innerHTML = '';
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();
        const firstDay = new Date(year, month, 1).getDay();
        const lastDate = new Date(year, month + 1, 0).getDate();
        const prevLastDate = new Date(year, month, 0).getDate();
        
        const months = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
        monthYear.innerText = `${months[month]} ${year}`;
        
        const label = document.getElementById('month-selector-label');
        if(label) {
            label.value = month;
            // Desencadenar cambio manual para el Ares Select Engine
            if (label.dataset.aresInitialized) {
                const event = new CustomEvent('change', { bubbles: true, detail: 'ares_update' });
                label.dispatchEvent(event);
            }
        }
        
        for (let x = firstDay; x > 0; x--) {
            const div = document.createElement('div');
            div.classList.add('day-elite', 'prev-date');
            div.innerText = prevLastDate - x + 1;
            daysContainer.appendChild(div);
        }
        
        for (let i = 1; i <= lastDate; i++) {
            const div = document.createElement('div');
            div.classList.add('day-elite');
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
            const today = new Date();
            const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
            
            if (i === today.getDate() && month === today.getMonth() && year === today.getFullYear()) {
                div.classList.add('today-elite');
            }
            if (dateStr < todayStr) {
                div.classList.add('past-date-blocked');
            }
            
            div.innerHTML = `<span class="day-number">${i}</span>`;
            const dayEvents = eventsData.filter(e => e.fecha === dateStr);
            dayEvents.forEach(e => {
                const evSpan = document.createElement('div');
                evSpan.classList.add('event-pill-elite');
                evSpan.style.setProperty('--el-event-color', e.color || 'var(--el-primary)');
                let iconCode = `<i class="bi bi-calendar-event me-1"></i>`;
                if (e.tipo === 'NACIONAL') {
                    iconCode = `<span class="flag-colombia" title="Festivo Nacional 🇨🇴"></span><i class="bi bi-calendar-x me-1"></i>`;
                } else if (e.tipo === 'FESTIVO') {
                    iconCode = `<i class="bi bi-calendar-x me-1"></i>`;
                } else if (e.tipo === 'EXAMEN') {
                    iconCode = `<i class="bi bi-journal-check me-1"></i>`;
                }
                
                evSpan.innerHTML = `${iconCode}${e.titulo}`;
                evSpan.onclick = (event) => {
                    event.stopPropagation();
                    verDetalleEvento(e);
                };
                div.appendChild(evSpan);
            });

            if (esAdminCron) {
                if (dateStr >= todayStr) {
                    div.onclick = () => abrirModalEvento(dateStr);
                }
            }
            daysContainer.appendChild(div);
        }
        
        const nextDays = 42 - daysContainer.children.length;
        for (let j = 1; j <= nextDays; j++) {
            const div = document.createElement('div');
            div.classList.add('day-elite', 'next-date');
            div.innerText = j;
            daysContainer.appendChild(div);
        }
    }

    function abrirModalEvento(fecha, e = null) {
        if (!esAdminCron) return;
        const esEdicion = !!e;
        Swal.fire({
            title: esEdicion ? 'Editar Actividad' : 'Programar Actividad',
            html: `
                <div class="text-start px-4">
                    <label for="ev-titulo" class="small fw-bold text-muted">NOMBRE DE LA ACTIVIDAD</label>
                    <input id="ev-titulo" class="swal2-input border-secondary mt-1" placeholder="Ej. Entrega de Notas" value="${e ? e.titulo : ''}">
                </div>
                <div class="text-start px-4 mt-3">
                    <label for="ev-desc" class="small fw-bold text-muted">DESCRIPCIÓN</label>
                    <textarea id="ev-desc" class="swal2-textarea mt-1" placeholder="Detalles de la actividad...">${e ? e.descripcion : ''}</textarea>
                </div>
                <div class="mt-3 text-start px-4">
                    <label for="ev-fecha" class="small fw-bold text-muted">FECHA</label>
                    <input id="ev-fecha" type="date" class="form-control" value="${fecha}">
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Guardar',
            preConfirm: () => {
                return {
                    id: e ? e.id : null,
                    titulo: document.getElementById('ev-titulo').value,
                    desc: document.getElementById('ev-desc').value,
                    fecha: document.getElementById('ev-fecha').value,
                    tipo: 'EVENTO',
                    color: 'rgb(13, 202, 240)'
                }
            }
        }).then(async (res) => {
            if (res.isConfirmed && res.value.titulo) {
                // 🏛️ DETECTOR DEL CENTINELA PREVENTIVO PARA EVENTOS
                try {
                    const centinelaRes = await fetch(`logica/api_centinela.php?accion=validar_tarea&fecha=${res.value.fecha}`);
                    const centinelaData = await centinelaRes.json();
                    
                    if (centinelaData.status === 'success' && centinelaData.colision_receso) {
                        const motivo = centinelaData.tipo_receso === 'fin_semana' ? 'un fin de semana' : 'un periodo de receso escolar/vacaciones';
                        const confirmReceso = await Swal.fire({
                            title: '¡Centinela de Integridad!',
                            text: `El evento que intenta programar coincide con ${motivo}. ¿Desea confirmar la programación de todas formas?`,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Sí, Confirmar',
                            cancelButtonText: 'No, Cancelar',
                            customClass: {
                                confirmButton: 'btn-elite btn-elite--primary px-3',
                                cancelButton: 'btn-elite btn-elite--outline px-3 ms-2'
                            },
                            buttonsStyling: false
                        });
                        if (!confirmReceso.isConfirmed) return;
                    }
                } catch (err) {
                }

                const fd = new FormData();
                Object.keys(res.value).forEach(k => {
                    if(res.value[k] !== null) fd.append(k, res.value[k]);
                });
                if (window.CSRF_TOKEN) {
                    fd.append('csrf_token', window.CSRF_TOKEN);
                }
                fetch(esEdicion ? 'logica/editar_evento.php' : 'logica/guardar_evento.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(() => window.fetchEvents());
            }
        });
    }

    window.abrirModalEvento = abrirModalEvento;

    window.confirmarEliminarEvento = function(id) {
        Swal.fire({
            title: '¿ELIMINAR ACTIVIDAD?',
            text: 'Esta acción no se puede deshacer y removerá permanentemente el evento.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'ELIMINAR',
            cancelButtonText: 'CANCELAR',
            buttonsStyling: false,
            customClass: {
                confirmButton: 'btn-elite btn-elite--danger mx-2',
                cancelButton: 'btn-elite btn-elite--outline mx-2'
            }
        }).then((res) => {
            if (res.isConfirmed) {
                const fd = new FormData();
                fd.append('id', id);
                if (window.CSRF_TOKEN) {
                    fd.append('csrf_token', window.CSRF_TOKEN);
                }
                fetch('logica/borrar_evento.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'success') {
                        window.fetchEvents();
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'No se pudo eliminar el evento.' });
                    }
                });
            }
        });
    };

    function verDetalleEvento(e) {
        Swal.fire({
            title: `<span class="calendar-event-title-elite" id="swal-event-title">${e.titulo}</span>`,
            html: `
                <div class="text-start p-3 bg-light rounded border shadow-sm">
                    <p class="mb-2"><strong>Fecha:</strong> ${e.fecha}</p>
                    <p class="mb-3"><strong>Detalle:</strong> ${e.descripcion || 'Sin descripción adicional.'}</p>
                    ${(esAdminCron && e.tipo !== 'NACIONAL' && e.tipo !== 'FESTIVO' && (!e.id || e.id < 99000)) ? `
                        <div class="d-flex justify-content-end gap-2 border-top pt-3 mt-2">
                            <button class="btn-action-elite text-primary" title="Editar Actividad" onclick="Swal.close(); window.abrirModalEvento('${e.fecha}', ${JSON.stringify(e).replace(/"/g, '&quot;')})">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <button class="btn-action-elite text-danger" title="Eliminar Actividad" onclick="Swal.close(); window.confirmarEliminarEvento(${e.id})">
                                <i class="bi bi-trash3-fill"></i>
                            </button>
                        </div>
                    ` : (e.tipo === 'FESTIVO' && e.id >= 99000 ? `
                        <div class="text-end border-top pt-2 mt-2 small text-muted">
                            <i class="bi bi-info-circle me-1"></i>Gestionar desde Configuración
                        </div>
                    ` : '')}
                </div>
            `,
            showCloseButton: true,
            showConfirmButton: false,
            showCancelButton: false,
            didOpen: () => {
                const title = document.getElementById('swal-event-title');
                if (title) title.style.setProperty('--event-color', e.color);
            }
        });
    }

    // Inicio SPA-Resiliente
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        window.fetchEvents();
    } else {
        document.addEventListener('DOMContentLoaded', window.fetchEvents);
    }
})();