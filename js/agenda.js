(() => {
    let ELITE_DATA = {
        cursos: [],
        materias: [],
        esEstudiante: false,
        esDocente: false
    };

    function initData() {
        const container = document.getElementById('agenda-modulo-container');
        if (container) {
            try {
                const cfgRaw = container.getAttribute('data-agenda-cfg');
                if (cfgRaw) {
                    ELITE_DATA = JSON.parse(cfgRaw);
                }
            } catch (e) {
                /* Silencio de radio */
            }
        }
    }

    window.cargarAgenda = async () => {
        initData();
        const filtro = document.getElementById('filtro-curso');
        const cursoId = filtro?.value ?? 0;
        const container = document.getElementById('agenda-container');
        
        if (!container) return;

        container.innerHTML = `
            <div class="col-12 text-center py-5 animate__animated animate__fadeIn">
                <div class="loader-elite-orbital mb-3"></div>
                <p class="mt-3 text-secondary fw-bold small">Sincronizando compromisos académicos...</p>
            </div>`;
        
        try {
            const response = await fetch(`logica/obtener_agenda.php?curso_id=${cursoId}`);
            if (!response.ok) throw new Error('Fallo en la comunicación con el servidor');
            
            const res = await response.json();
            if (res.status === 'success') {
                pintarAgenda(res.data);
            } else {
                container.innerHTML = `<div class="col-12 text-center py-5"><div class="alert alert-danger d-inline-block rounded-4 shadow-sm">${res.message}</div></div>`;
            }
        } catch (error) {
            container.innerHTML = `<div class="col-12 text-center py-5"><div class="alert alert-danger d-inline-block rounded-4 shadow-sm">Error crítico de sincronización.</div></div>`;
        }
    };

    const mostrarFormularioTarea = async (tarea = null) => {
        const esEdicion = !!tarea;
        const tituloDialogo = esEdicion ? 'Modificar Compromiso' : 'Asignar Compromiso Académico';
        
        const cursoActual = document.getElementById('filtro-curso')?.value || "";
        const cursoIdSeleccionado = esEdicion ? tarea.curso_id : cursoActual;

        const opcionesCursos = ELITE_DATA.cursos.map(c => 
            `<option value="${c.id}" ${parseInt(c.id) === parseInt(cursoIdSeleccionado) ? 'selected' : ''}>${c.nombre_curso}</option>`
        ).join('');

        const opcionesMaterias = ELITE_DATA.materias.map(m => 
            `<option value="${m.id}" ${esEdicion && parseInt(m.id) === parseInt(tarea.especialidad_id) ? 'selected' : ''}>${m.nombre_especialidad}</option>`
        ).join('');

        const { value: formValues } = await Swal.fire({
            title: `<div class="elite-swal-title">${tituloDialogo}</div>`,
            html: `
                <div class="elite-swal-content text-start">
                    <input type="hidden" id="swal-task-id" value="${esEdicion ? tarea.id : 0}">
                    
                    <div class="mb-3">
                        <label class="small fw-bold text-muted text-uppercase mb-1">Título de la Tarea</label>
                        <input type="text" id="swal-task-titulo" class="input-elite" placeholder="Ej: Taller de Ecuaciones" value="${esEdicion ? tarea.titulo : ''}">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="small fw-bold text-muted text-uppercase mb-1">Curso / Grado</label>
                            <select id="swal-task-curso" class="select-elite">
                                <option value="" disabled ${!cursoIdSeleccionado ? 'selected' : ''}>Cargando...</option>
                                ${opcionesCursos}
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold text-muted text-uppercase mb-1">Asignatura</label>
                            <select id="swal-task-materia" class="select-elite">
                                <option value="" disabled ${!esEdicion ? 'selected' : ''}>Seleccione...</option>
                                ${opcionesMaterias}
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold text-muted text-uppercase mb-1">Fecha de Entrega</label>
                        <input type="date" id="swal-task-fecha" class="input-elite" value="${esEdicion ? tarea.fecha_entrega : ''}">
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold text-muted text-uppercase mb-1">Instrucciones</label>
                        <textarea id="swal-task-desc" class="input-elite" rows="3" placeholder="Detalle qué debe realizar el estudiante...">${esEdicion ? tarea.descripcion : ''}</textarea>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Guardar Tarea',
            cancelButtonText: 'Cancelar',
            buttonsStyling: false,
            focusConfirm: false,
            customClass: {
                popup: 'elite-swal-popup',
                confirmButton: 'btn-elite btn-elite--primary elite-swal-button',
                cancelButton: 'btn-elite btn-elite--outline elite-swal-button'
            },
            preConfirm: () => {
                const titulo = document.getElementById('swal-task-titulo').value;
                const curso = document.getElementById('swal-task-curso').value;
                const materia = document.getElementById('swal-task-materia').value;
                const fecha = document.getElementById('swal-task-fecha').value;
                
                if (!titulo || !curso || !materia || !fecha) {
                    Swal.showValidationMessage('Campos obligatorios pendientes');
                    return false;
                }
                
                return {
                    id: document.getElementById('swal-task-id').value,
                    titulo, curso_id: curso, especialidad_id: materia, fecha_entrega: fecha,
                    descripcion: document.getElementById('swal-task-desc').value
                };
            }
        });

        if (formValues) {
            try {
                const centinelaRes = await fetch(`logica/api_centinela.php?accion=validar_tarea&fecha=${formValues.fecha_entrega}`);
                const centinelaData = await centinelaRes.json();
                
                let continuar = true;
                if (centinelaData.status === 'success') {
                    if (centinelaData.colision_receso) {
                        const motivo = centinelaData.tipo_receso === 'fin_semana' ? 'un fin de semana' : 'un periodo de receso escolar/vacaciones';
                        const confirmReceso = await Swal.fire({
                            title: '¡Centinela de Integridad!',
                            text: `La fecha de entrega seleccionada cae en ${motivo}. ¿Desea confirmar la asignación de todas formas?`,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Sí, Confirmar',
                            cancelButtonText: 'No, Cambiar fecha',
                            customClass: {
                                confirmButton: 'btn-elite btn-elite--primary px-3',
                                cancelButton: 'btn-elite btn-elite--outline px-3 ms-2'
                            },
                            buttonsStyling: false
                        });
                        continuar = confirmReceso.isConfirmed;
                    }
                    
                    if (continuar && centinelaData.tiempo_critico) {
                        const confirmCritico = await Swal.fire({
                            title: '¡Tiempo Límite Crítico!',
                            text: 'La diferencia entre la fecha actual y la fecha de entrega es inferior a 12 horas. ¿Desea asignar de todas formas?',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Sí, Confirmar',
                            cancelButtonText: 'No, Ajustar',
                            customClass: {
                                confirmButton: 'btn-elite btn-elite--primary px-3',
                                cancelButton: 'btn-elite btn-elite--outline px-3 ms-2'
                            },
                            buttonsStyling: false
                        });
                        continuar = confirmCritico.isConfirmed;
                    }
                }
                
                if (!continuar) return;

                const fd = new FormData();
                Object.keys(formValues).forEach(key => fd.append(key, formValues[key]));
                fd.append('confirmar_centinela', 'true');
                
                const response = await fetch('logica/guardar_tarea.php', { method: 'POST', body: fd });
                const data = await response.json();
                
                if (data.status === 'success') {
                    Swal.fire({ icon: 'success', title: '¡Perfecto!', text: data.message, timer: 1500, showConfirmButton: false, customClass: { popup: 'elite-swal-popup' } });
                    window.cargarAgenda();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message, customClass: { popup: 'elite-swal-popup' } });
                }
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Fallo Técnico', text: 'Error de red.', customClass: { popup: 'elite-swal-popup' } });
            }
        }
    };

    window.nuevaTarea = () => mostrarFormularioTarea();
    window.editarTarea = (t) => mostrarFormularioTarea(t);

    window.borrarTarea = async (id) => {
        const result = await Swal.fire({
            title: '¿Eliminar compromiso?',
            text: "Esta acción no se puede deshacer.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: 'rgb(220, 53, 69)',
            cancelButtonColor: 'rgb(108, 117, 125)',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        });

        if (result.isConfirmed) {
            try {
                const fd = new FormData();
                fd.append('id', id);
                const response = await fetch('logica/borrar_tarea.php', { method: 'POST', body: fd });
                const data = await response.json();
                if (data.status === 'success') {
                    Swal.fire('Eliminado', data.message, 'success');
                    window.cargarAgenda();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            } catch (error) {
                Swal.fire('Error', 'Fallo técnico al intentar eliminar.', 'error');
            }
        }
    };

    const pintarAgenda = (tareas) => {
        const container = document.getElementById('agenda-container');
        if (!container) return;

        if (tareas.length === 0) {
            container.innerHTML = `
                <div class="col-12 text-center py-5 animate__animated animate__fadeIn">
                    <div class="p-5 rounded-4 border border-dashed card-elite-glass">
                        <i class="bi bi-journal-x fs-1 text-primary opacity-25"></i>
                        <h5 class="mt-3 fw-bold text-dark text-uppercase">No hay compromisos pendientes</h5>
                        <p class="small text-secondary mx-auto w-75">La agenda está limpia para los filtros seleccionados.</p>
                    </div>
                </div>`;
            return;
        }

        let html = '';
        tareas.forEach(t => {
            let dropdownHtml = '';
            if (!ELITE_DATA.esEstudiante) {
                dropdownHtml = `
                <button class="btn-action-pill-elite--nano" data-bs-toggle="dropdown">
                    <i class="bi bi-three-dots-vertical"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3">
                    <li><a class="dropdown-item" href="#" onclick='window.editarTarea(${JSON.stringify(t).replace(/'/g, "&apos;")})'><i class="bi bi-pencil margin-inline-end-2 text-primary"></i>Editar Tarea</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="#" onclick="window.borrarTarea(${t.id})"><i class="bi bi-trash margin-inline-end-2"></i>Eliminar</a></li>
                </ul>`;
            }

            html += `
                <div class="col-md-6 col-lg-4 animate__animated animate__fadeInUp">
                    <div class="card-elite-aurora h-100">
                        <div class="card-body p-3 d-flex flex-column h-100">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <span class="badge-elite badge-elite--primary">${t.materia_nom || 'General'}</span>
                                <div class="dropdown">
                                    ${dropdownHtml}
                                </div>
                            </div>
                            <h5 class="fw-bold mb-1 text-secondary-gradient fs-6 text-uppercase">${t.titulo}</h5>
                            <p class="text-secondary small mb-2 text-truncate-2 flex-grow-1 agenda-card-desc">${t.descripcion || 'Sin descripción adicional.'}</p>
                            <div class="mt-auto pt-2 border-top border-light">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="bi bi-clock-history agenda-icon-accent fs-nano"></i>
                                    <span class="fw-bold text-accent text-uppercase agenda-date-label">ENTREGA: ${new Date(t.fecha_entrega + 'T00:00:00').toLocaleDateString('es-CO', { day: '2-digit', month: 'short', year: 'numeric' })}</span>
                                </div>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-person-circle agenda-icon-accent opacity-50 fs-nano"></i>
                                    <span class="text-muted text-truncate agenda-docente-label">${t.docente_nom} - ${t.nombre_curso}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>`;
        });
        container.innerHTML = html;
    };

    window.cargarAgenda();
})();