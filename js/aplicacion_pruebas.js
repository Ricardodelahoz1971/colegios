let pruebaIdProcesado = false;

function procesarPruebaIdUrl(asignaciones) {
    const urlParams = new URLSearchParams(window.location.search);
    const pruebaId = urlParams.get('prueba_id');
    if (!pruebaId) return;

    // Buscar si ya existe una asignación para este pruebaId
    const asignacionExistente = asignaciones.find(a => a.prueba_id == pruebaId);

    if (asignacionExistente) {
        // ¡Ya está programado! Entramos directamente en modo EDICIÓN
        editarAsignacion(asignacionExistente.id);
    } else {
        // No está programado aún. Abrimos el modal de CREACIÓN con el prueba_id pre-seleccionado
        abrirModalAsignacion();
        const selectPrueba = document.getElementById('asig-prueba-id');
        if (selectPrueba) {
            selectPrueba.value = pruebaId;
            selectPrueba.dispatchEvent(new Event('change')); // Sincronizar visualmente con AresSelectEngine
        }
    }
}

function initAplicacionPruebas() {
    cargarAsignacionesProgramar();
    const form = document.getElementById('form-asignacion-ares');
    if (form) {
        form.addEventListener('submit', guardarAsignacion);
    }
}

// 🎯 Inicialización SPA-Safe
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAplicacionPruebas);
} else {
    initAplicacionPruebas();
}

window.filtrarAsignacionesAres = function() {
    const busqueda = document.getElementById('buscar-asignacion-ares').value.toLowerCase();
    const tarjetas = document.querySelectorAll('#grid-asignaciones-ares > .col-12');
    
    tarjetas.forEach(t => {
        const texto = t.innerText.toLowerCase();
        t.classList.toggle('u-block', texto.includes(busqueda));
        t.classList.toggle('u-hidden', !texto.includes(busqueda));
    });
}

async function cargarAsignacionesProgramar() {
    try {
        const res = await fetch('logica/api_pruebas.php?accion=listar_asignaciones');
        const d = await res.json();
        if (d.status === 'success') {
            const grid = document.getElementById('grid-asignaciones-ares');
            if (!grid) return;

            if (d.data.length === 0) {
                grid.innerHTML = '<div class="col-12 text-center py-5 text-muted"><i class="bi bi-calendar-x fs-1 opacity-25"></i><p class="mt-2">No hay exámenes programados aún.</p></div>';
            } else {
                grid.innerHTML = d.data.map(a => {
                    let badgeClass = 'text-warning';
                    let statusText = 'PROGRAMADO';
                    if (a.estado == 1) {
                        badgeClass = 'text-success';
                        statusText = 'ACTIVO';
                    } else if (a.estado == 2) {
                        badgeClass = 'text-danger';
                        statusText = 'FINALIZADO';
                    }
                    
                    return `
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card card-elite border-0 shadow-sm p-4 position-relative">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <span class="badge bg-light text-dark fs-micro mb-1">ID #${a.id}</span>
                                    <h6 class="fw-bold mb-0 text-truncate text-titulo-elite">${escapeHtml(a.prueba_titulo)}</h6>
                                    <small class="text-muted">${escapeHtml(a.curso_nombre)}</small>
                                    ${a.ambito === 'recuperacion' ? `<span class="badge bg-danger bg-opacity-10 text-danger fs-micro p-1 px-2 rounded-pill mt-1">RECUPERACIÓN</span>` : ''}
                                </div>
                                <span class="badge bg-opacity-10 px-3 py-1 rounded-pill fs-micro ${badgeClass}">${statusText}</span>
                            </div>
                            <div class="small mb-3">
                                <div class="text-muted mb-1"><i class="bi bi-calendar-event me-2"></i><strong>Inicio:</strong> ${a.fecha_inicio}</div>
                                <div class="text-muted"><i class="bi bi-calendar-check me-2"></i><strong>Fin:</strong> ${a.fecha_fin}</div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fs-micro text-muted"><i class="bi bi-key me-1"></i>Clave: <strong>${a.clave_acceso || 'Ninguna'}</strong></span>
                                <button class="btn-elite btn-elite--sm btn-elite--outline" onclick="editarAsignacion(${a.id})">
                                    <i class="bi bi-pencil-square me-1"></i> EDITAR
                                </button>
                            </div>
                        </div>
                    </div>
                    `;
                }).join('');
            }
            
            if (!pruebaIdProcesado) {
                pruebaIdProcesado = true;
                procesarPruebaIdUrl(d.data);
            }
        }
    } catch (e) {
    }
}

function abrirModalAsignacion() {
    document.getElementById('asignacion-id').value = '0';
    document.getElementById('form-asignacion-ares').reset();
    document.getElementById('btn-eliminar-asignacion-modal').classList.add('d-none');
    
    document.getElementById('asig-ambito-estandar').checked = true;
    toggleAsigAmbitoRecuperacion();

    // Sincronizar todos los selectores visuales de AresSelectEngine
    document.getElementById('asig-prueba-id').dispatchEvent(new Event('change', { bubbles: true }));
    document.getElementById('asig-curso-id').dispatchEvent(new Event('change', { bubbles: true }));
    document.getElementById('asig-act-origen').dispatchEvent(new Event('change', { bubbles: true }));
    document.getElementById('asig-navegacion').dispatchEvent(new Event('change', { bubbles: true }));

    const el = document.getElementById('modalAsignacionAres');
    let modal = bootstrap.Modal.getInstance(el);
    if (!modal) modal = new bootstrap.Modal(el);
    modal.show();
}

async function editarAsignacion(id) {
    try {
        const res = await fetch(`logica/api_pruebas.php?accion=obtener_asignacion&id=${id}`);
        const d = await res.json();
        if (d.status === 'success') {
            const a = d.data;
            document.getElementById('asignacion-id').value = a.id;
            document.getElementById('asig-prueba-id').value = a.prueba_id;
            document.getElementById('asig-curso-id').value = a.curso_id;
            document.getElementById('asig-inicio').value = a.fecha_inicio.replace(' ', 'T');
            document.getElementById('asig-fin').value = a.fecha_fin.replace(' ', 'T');
            document.getElementById('asig-clave').value = a.clave_acceso || '';
            document.getElementById('asig-intentos').value = a.intentos_permitidos || 1;
            document.getElementById('asig-navegacion').value = a.tipo_navegacion || 'libre';
            document.getElementById('asig-resultados').checked = parseInt(a.mostrar_resultados) === 1;

            if (a.ambito === 'recuperacion') {
                document.getElementById('asig-ambito-recuperacion').checked = true;
                await cargarAsigActividadesOrigen(a.recupera_actividad_id);
                toggleAsigAmbitoRecuperacion();
            } else {
                document.getElementById('asig-ambito-estandar').checked = true;
                toggleAsigAmbitoRecuperacion();
            }

            // Sincronizar todos los selectores visuales de AresSelectEngine
            document.getElementById('asig-prueba-id').dispatchEvent(new Event('change', { bubbles: true }));
            document.getElementById('asig-curso-id').dispatchEvent(new Event('change', { bubbles: true }));
            document.getElementById('asig-act-origen').dispatchEvent(new Event('change', { bubbles: true }));
            document.getElementById('asig-navegacion').dispatchEvent(new Event('change', { bubbles: true }));

            document.getElementById('btn-eliminar-asignacion-modal').classList.remove('d-none');
            const el = document.getElementById('modalAsignacionAres');
            let modal = bootstrap.Modal.getInstance(el);
            if (!modal) modal = new bootstrap.Modal(el);
            modal.show();
        }
    } catch(e) {
        Swal.fire('Error', 'No se pudieron cargar los detalles de la programación.', 'error');
    }
}

async function guardarAsignacion(e) {
    e.preventDefault();
    const id = document.getElementById('asignacion-id').value;
    const prueba_id = document.getElementById('asig-prueba-id').value;
    const curso_id = document.getElementById('asig-curso-id').value;
    const fecha_inicio = document.getElementById('asig-inicio').value;
    const fecha_fin = document.getElementById('asig-fin').value;
    const clave_acceso = document.getElementById('asig-clave').value.trim();
    const intentos = document.getElementById('asig-intentos').value;
    const navegacion = document.getElementById('asig-navegacion').value;
    const mostrar_resultados = document.getElementById('asig-resultados').checked ? 1 : 0;

    const ambito = document.querySelector('input[name="asig_ambito"]:checked')?.value || 'estandar';
    const recupera_id = document.getElementById('asig-act-origen')?.value || '';

    if (!prueba_id || !curso_id || !fecha_inicio || !fecha_fin) {
        Swal.fire('Campos Incompletos', 'Por favor diligencie los campos obligatorios.', 'warning');
        return;
    }

    try {
        const centinelaRes = await fetch(`logica/api_centinela.php?accion=validar_examen&fecha=${fecha_inicio}&curso_id=${curso_id}`);
        const centinelaData = await centinelaRes.json();
        
        let continuar = true;
        if (centinelaData.status === 'success') {
            if (centinelaData.colision_receso) {
                const motivo = centinelaData.tipo_receso === 'fin_semana' ? 'un fin de semana' : 'un periodo de receso escolar/vacaciones';
                const confirmReceso = await Swal.fire({
                    title: '¡Centinela de Integridad!',
                    text: `El examen inicia en ${motivo}. ¿Desea confirmar la programación de todas formas?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, Confirmar',
                    cancelButtonText: 'No, Cambiar',
                    customClass: {
                        confirmButton: 'btn-elite btn-elite--primary px-3',
                        cancelButton: 'btn-elite btn-elite--outline px-3 ms-2'
                    },
                    buttonsStyling: false
                });
                continuar = confirmReceso.isConfirmed;
            }
            
            if (continuar && centinelaData.cruce_examenes) {
                const confirmCruce = await Swal.fire({
                    title: '¡Alerta de Sobrecarga de Exámenes!',
                    text: `Los estudiantes ya tienen programados ${centinelaData.cantidad_examenes} exámenes para este día (${centinelaData.examenes_existentes.join(', ')}). ¿Desea confirmar de todas formas?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, Confirmar',
                    cancelButtonText: 'No, Cambiar',
                    customClass: {
                        confirmButton: 'btn-elite btn-elite--danger px-3',
                        cancelButton: 'btn-elite btn-elite--outline px-3 ms-2'
                    },
                    buttonsStyling: false
                });
                continuar = confirmCruce.isConfirmed;
            }
        }
        
        if (!continuar) return;
    } catch (e) {
    }

    const fd = new FormData();
    fd.append('accion', 'guardar_asignacion');
    fd.append('csrf_token', window.CSRF_TOKEN || '');
    fd.append('id', id);
    fd.append('prueba_id', prueba_id);
    fd.append('curso_id', curso_id);
    fd.append('fecha_inicio', fecha_inicio);
    fd.append('fecha_fin', fecha_fin);
    fd.append('clave_acceso', clave_acceso);
    fd.append('intentos_permitidos', intentos);
    fd.append('tipo_navegacion', navegacion);
    fd.append('mostrar_resultados', mostrar_resultados);
    fd.append('ambito', ambito);
    if (ambito === 'recuperacion' && recupera_id) {
        fd.append('recupera_actividad_id', recupera_id);
    }

    try {
        const res = await fetch('logica/api_pruebas.php', { method: 'POST', body: fd });
        const d = await res.json();
        if (d.status === 'success') {
            Swal.fire({ icon: 'success', title: 'Programación Guardada', timer: 1500, showConfirmButton: false });
            const el = document.getElementById('modalAsignacionAres');
            bootstrap.Modal.getInstance(el).hide();
            cargarAsignacionesProgramar();
        } else {
            Swal.fire('Error', d.message, 'error');
        }
    } catch(e) {
        Swal.fire('Error', 'No se pudo guardar la asignación.', 'error');
    }
}

async function eliminarAsignacion(id) {
    const confirm = await Swal.fire({
        title: '¿Cancelar programación?',
        text: 'Los estudiantes ya no podrán presentar este examen.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, Cancelar'
    });

    if (confirm.isConfirmed) {
        const fd = new FormData();
        fd.append('accion', 'eliminar_asignacion');
        fd.append('csrf_token', window.CSRF_TOKEN || '');
        fd.append('id', id);

        try {
            const res = await fetch('logica/api_pruebas.php', { method: 'POST', body: fd });
            const d = await res.json();
            if (d.status === 'success') {
                Swal.fire({ icon: 'success', title: 'Programación Cancelada', timer: 1500, showConfirmButton: false });
                const el = document.getElementById('modalAsignacionAres');
                bootstrap.Modal.getInstance(el).hide();
                cargarAsignacionesProgramar();
            }
        } catch(e) {
            Swal.fire('Error', 'No se pudo cancelar la programación.', 'error');
        }
    }
}

function toggleAsigAmbitoRecuperacion() {
    const ambito = document.querySelector('input[name="asig_ambito"]:checked')?.value || 'estandar';
    const cont = document.getElementById('asig-contenedor-actividad-origen');
    if (ambito === 'recuperacion') {
        cont.classList.remove('d-none');
        cargarAsigActividadesOrigen();
    } else {
        cont.classList.add('d-none');
        document.getElementById('asig-act-origen').value = '';
    }
}

async function cargarAsigActividadesOrigen(preselectId = null) {
    const curso_id = document.getElementById('asig-curso-id').value;
    const selectOrigen = document.getElementById('asig-act-origen');
    if (!curso_id) {
        selectOrigen.innerHTML = '<option value="">[Seleccione Grupo Objetivo primero...]</option>';
        return;
    }

    try {
        const response = await fetch(`logica/api_actividades.php?accion=listar_actividades_profesor&filtrar_periodo=1`);
        const res = await response.json();
        
        if (res.status === 'success') {
            const filtradas = res.data.filter(a => parseInt(a.curso_id) === parseInt(curso_id) && a.ambito === 'estandar');
            if (filtradas.length === 0) {
                selectOrigen.innerHTML = '<option value="">No hay actividades estándar en este curso</option>';
                return;
            }
            
            selectOrigen.innerHTML = '<option value="">[Seleccionar actividad...]</option>' + 
                filtradas.map(a => `<option value="${a.id}" ${preselectId && parseInt(a.id) === parseInt(preselectId) ? 'selected' : ''}>${escapeHtml(a.titulo)} (${escapeHtml(a.clase_nota)})</option>`).join('');
            
            // Forzar actualización visual de AresSelectEngine
            selectOrigen.dispatchEvent(new Event('change', { bubbles: true }));
        } else {
            selectOrigen.innerHTML = '<option value="">Error al cargar actividades</option>';
            selectOrigen.dispatchEvent(new Event('change', { bubbles: true }));
        }
    } catch (e) {
        selectOrigen.innerHTML = '<option value="">Error de conexión</option>';
        selectOrigen.dispatchEvent(new Event('change', { bubbles: true }));
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&apos;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

// Vinculación al scope global
window.procesarPruebaIdUrl = procesarPruebaIdUrl;
window.initAplicacionPruebas = initAplicacionPruebas;
window.abrirModalAsignacion = abrirModalAsignacion;
window.editarAsignacion = editarAsignacion;
window.guardarAsignacion = guardarAsignacion;
window.eliminarAsignacion = eliminarAsignacion;
window.toggleAsigAmbitoRecuperacion = toggleAsigAmbitoRecuperacion;
window.cargarAsigActividadesOrigen = cargarAsigActividadesOrigen;
window.escapeHtml = escapeHtml;