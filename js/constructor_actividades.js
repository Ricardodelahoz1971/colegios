// --- ORQUESTADOR FRONTERIZO DE ACTIVIDADES ---

function toggleRubricaMode() {
    const modo = document.querySelector('input[name="modo_eval"]:checked').value;
    const panel = document.getElementById('panel-criterios');
    const containerDirecto = document.getElementById('container-directo');
    const containerRubrica = document.getElementById('container-rubrica');
    
    if (modo === 'rubrica') {
        panel.classList.remove('d-none');
        containerRubrica.classList.add('border-primary');
        containerDirecto.classList.remove('border-primary');
        
        // Inicializar con al menos un criterio vacío si no hay ninguno
        const lista = document.getElementById('lista-criterios');
        if (lista.children.length === 0) {
            agregarCriterio();
        }
    } else {
        panel.classList.add('d-none');
        containerDirecto.classList.add('border-primary');
        containerRubrica.classList.remove('border-primary');
    }
}

function agregarCriterio(titulo = '', peso = '', id = '') {
    const lista = document.getElementById('lista-criterios');
    const index = lista.children.length;
    
    const div = document.createElement('div');
    div.className = 'd-flex gap-2 align-items-center mb-2 criterio-fila animate__animated animate__fadeIn';
    div.dataset.index = index;
    
    div.innerHTML = `
        <input type="hidden" class="crit-id" value="${id}">
        <input type="text" class="input-elite flex-grow-1 crit-titulo" placeholder="Ej: Redacción" value="${titulo}" required>
        <div class="input-group w-25">
            <input type="number" class="input-elite text-center crit-peso" placeholder="%" min="1" max="100" value="${peso}" required>
            <span class="input-group-text bg-light">%</span>
        </div>
        <button type="button" class="btn-elite-icon btn-elite-icon--sm btn-elite-icon--danger" onclick="eliminarCriterio(this)">
            <i class="bi bi-trash"></i>
        </button>
    `;
    
    lista.appendChild(div);
}

function eliminarCriterio(btn) {
    const fila = btn.closest('.criterio-fila');
    fila.remove();
}

function cancelarEdicion() {
    const form = document.getElementById('form-actividad');
    form.reset();
    
    document.getElementById('act-id').value = '';
    document.getElementById('lista-criterios').innerHTML = '';
    document.getElementById('titulo-panel-actividad').innerHTML = '<i class="bi bi-magic text-primary me-2"></i> Crear Actividad';
    document.getElementById('btn-registrar-actividad').innerHTML = '<i class="bi bi-save2 me-2"></i> REGISTRAR ACTIVIDAD';
    document.getElementById('btn-cancelar-edicion').classList.add('d-none');
    
    document.getElementById('modo_directo').checked = true;
    toggleRubricaMode();
    
    // Resetear radios de ámbito
    document.getElementById('ambito-estandar').checked = true;
    toggleAmbitoRecuperacion();
}

async function cargarActividades() {
    const tbody = document.getElementById('tabla-actividades-body');
    try {
        const response = await fetch('logica/api_actividades.php?accion=listar_actividades_profesor');
        const res = await response.json();
        
        if (res.status === 'success') {
            if (res.data.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center py-5 text-muted">
                            No posee actividades académicas creadas.
                        </td>
                    </tr>
                `;
                return;
            }
            
            tbody.innerHTML = res.data.map(act => {
                const badgeDim = `<span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 fs-nano text-uppercase">${act.clase_nota}</span>`;
                const badgeMode = act.tipo_evaluacion === 'rubrica' 
                    ? `<span class="text-primary fw-bold fs-micro"><i class="bi bi-layers"></i> RÚBRICA</span>` 
                    : `<span class="text-secondary fw-bold fs-micro"><i class="bi bi-123"></i> DIRECTO</span>`;
                
                return `
                    <tr>
                        <td class="ps-4 py-3">
                            <div class="fw-bold text-dark">${escapeHtml(act.titulo)}</div>
                            <small class="text-muted">${escapeHtml(act.nombre_curso)} &bull; ${escapeHtml(act.nombre_especialidad)}</small>
                        </td>
                        <td class="text-center py-3">${badgeDim}</td>
                        <td class="text-center py-3">${badgeMode}</td>
                        <td class="pe-4 py-3 text-end">
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn-elite-icon btn-elite-icon--sm btn-elite-icon--primary" onclick="editarActividad(${act.id})" title="Editar">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <button type="button" class="btn-elite-icon btn-elite-icon--sm btn-elite-icon--danger" onclick="eliminarActividad(${act.id})" title="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        } else {
            tbody.innerHTML = `<tr><td colspan="4" class="text-center py-5 text-danger">${escapeHtml(res.message)}</td></tr>`;
        }
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="4" class="text-center py-5 text-danger">Error al conectar con el servidor.</td></tr>`;
    }
}

async function editarActividad(id) {
    try {
        const response = await fetch(`logica/api_actividades.php?accion=obtener_actividad_detalles&actividad_id=${id}`);
        const res = await response.json();
        
        if (res.status === 'success') {
            const act = res.data;
            
            document.getElementById('act-id').value = act.id;
            document.getElementById('act-titulo').value = act.titulo;
            document.getElementById('act-curso').value = act.curso_id;
            
            // Forzar actualización visual si se usan engines de selects
            if (typeof AresSelectEngine !== 'undefined') {
                setTimeout(() => {
                    const evt = new CustomEvent('change', { bubbles: true });
                    document.getElementById('act-curso').dispatchEvent(evt);
                }, 100);
            }
            
            const apiResponse = await fetch('logica/api_actividades.php?accion=listar_actividades_profesor');
            const listRes = await apiResponse.json();
            const matchingAct = listRes.data.find(a => parseInt(a.id) === parseInt(id));
            if (matchingAct) {
                document.getElementById('act-materia').value = matchingAct.especialidad_id;
                document.getElementById('act-clase-nota').value = matchingAct.clase_nota_id;
                
                if (typeof AresSelectEngine !== 'undefined') {
                    setTimeout(() => {
                        const evt = new CustomEvent('change', { bubbles: true });
                        document.getElementById('act-materia').dispatchEvent(evt);
                        document.getElementById('act-clase-nota').dispatchEvent(evt);
                    }, 200);
                }
            }

            if (act.tipo_evaluacion === 'rubrica') {
                document.getElementById('modo_rubrica').checked = true;
                toggleRubricaMode();
                
                const lista = document.getElementById('lista-criterios');
                lista.innerHTML = '';
                
                if (act.criterios && act.criterios.length > 0) {
                    act.criterios.forEach(c => {
                        agregarCriterio(c.titulo, c.peso_porcentaje, c.id);
                    });
                }
            } else {
                document.getElementById('modo_directo').checked = true;
                toggleRubricaMode();
            }

            if (act.ambito === 'recuperacion') {
                document.getElementById('ambito-recuperacion').checked = true;
                document.getElementById('act-recupera-actividad-id').value = act.recupera_actividad_id || '';
                toggleAmbitoRecuperacion();
            } else {
                document.getElementById('ambito-estandar').checked = true;
                toggleAmbitoRecuperacion();
            }
            
            document.getElementById('titulo-panel-actividad').innerHTML = '<i class="bi bi-pencil-square text-primary me-2"></i> Editar Actividad';
            document.getElementById('btn-registrar-actividad').innerHTML = '<i class="bi bi-save2 me-2"></i> ACTUALIZAR ACTIVIDAD';
            document.getElementById('btn-cancelar-edicion').classList.remove('d-none');
            
            document.getElementById('act-titulo').focus();
        } else {
            Swal.fire('Error', res.message, 'error');
        }
    } catch (e) {
        Swal.fire('Error', 'No se pudieron recuperar los detalles de la actividad.', 'error');
    }
}

async function guardarActividad(e) {
    e.preventDefault();
    
    const id = document.getElementById('act-id').value;
    const titulo = document.getElementById('act-titulo').value.trim();
    const curso_id = document.getElementById('act-curso').value;
    const especialidad_id = document.getElementById('act-materia').value;
    const clase_nota_id = document.getElementById('act-clase-nota').value;
    const modo_eval = document.querySelector('input[name="modo_eval"]:checked').value;
    
    if (!titulo || !curso_id || !especialidad_id || !clase_nota_id) {
        Swal.fire('Campos Incompletos', 'Por favor, diligencie toda la información obligatoria.', 'warning');
        return;
    }
    
    const fd = new FormData();
    fd.append('titulo', titulo);
    fd.append('curso_id', curso_id);
    fd.append('especialidad_id', especialidad_id);
    fd.append('clase_nota_id', clase_nota_id);
    fd.append('tipo_evaluacion', modo_eval);
    
    const ambito = document.querySelector('input[name="ambito"]:checked')?.value || 'estandar';
    const recupera_id = document.getElementById('act-recupera-actividad-id')?.value || '';
    fd.append('ambito', ambito);
    if (recupera_id && parseInt(recupera_id) > 0) {
        fd.append('recupera_actividad_id', recupera_id);
    }
    
    fd.append('csrf_token', window.CSRF_TOKEN || '');
    
    if (modo_eval === 'rubrica') {
        const filas = document.querySelectorAll('#lista-criterios .criterio-fila');
        if (filas.length === 0) {
            Swal.fire('Rúbrica Vacía', 'Debe añadir al menos un criterio para la rúbrica.', 'warning');
            return;
        }
        
        let criterios = [];
        let sumaPesos = 0;
        let vacios = false;
        
        filas.forEach(f => {
            const cId = f.querySelector('.crit-id').value;
            const cTitulo = f.querySelector('.crit-titulo').value.trim();
            const cPeso = parseFloat(f.querySelector('.crit-peso').value || '0');
            
            if (!cTitulo || cPeso <= 0) {
                vacios = true;
            }
            
            criterios.push({ id: cId, titulo: cTitulo, peso: cPeso });
            sumaPesos += cPeso;
        });
        
        if (vacios) {
            Swal.fire('Criterios Incompletos', 'Asegúrese de que todos los criterios tengan nombre y peso válido.', 'warning');
            return;
        }
        
        if (Math.abs(sumaPesos - 100) > 0.01) {
            Swal.fire('Pesos Incorrectos', `La suma de los pesos de la rúbrica debe ser exactamente 100%. (Suma actual: ${sumaPesos}%)`, 'warning');
            return;
        }
        
        fd.append('criterios', JSON.stringify(criterios));
    }
    
    let accion = 'guardar_actividad';
    if (id > 0) {
        accion = 'actualizar_actividad';
        fd.append('id', id);
    }
    
    try {
        const response = await fetch(`logica/api_actividades.php?accion=${accion}`, {
            method: 'POST',
            body: fd
        });
        const res = await response.json();
        
        if (res.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Sincronización Exitosa',
                text: res.message,
                timer: 2000,
                showConfirmButton: false
            });
            cancelarEdicion();
            cargarActividades();
        } else {
            Swal.fire('Error al Guardar', res.message, 'error');
        }
    } catch (err) {
        Swal.fire('Error Crítico', 'No se pudo comunicar con el servidor.', 'error');
    }
}

function eliminarActividad(id) {
    Swal.fire({
        title: '¿Confirmar Eliminación?',
        text: 'Al borrar esta actividad se perderán las calificaciones asociadas de forma irreversible.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, Eliminar',
        cancelButtonText: 'Cancelar',
        customClass: {
            confirmButton: 'btn-elite btn-elite--danger px-4',
            cancelButton: 'btn-elite btn-elite--outline px-4 ms-2'
        },
        buttonsStyling: false
    }).then(async (result) => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.append('actividad_id', id);
            fd.append('csrf_token', window.CSRF_TOKEN || '');
            
            try {
                const response = await fetch('logica/api_actividades.php?accion=eliminar_actividad', {
                    method: 'POST',
                    body: fd
                });
                const res = await response.json();
                
                if (res.status === 'success') {
                    Swal.fire('Eliminado', res.message, 'success');
                    cargarActividades();
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'No se pudo procesar la solicitud.', 'error');
            }
        }
    });
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

function toggleAmbitoRecuperacion() {
    const ambito = document.querySelector('input[name="ambito"]:checked').value;
    const contOrigen = document.getElementById('contenedor-actividad-origen');
    if (ambito === 'recuperacion') {
        contOrigen.classList.remove('d-none');
        cargarActividadesOrigen();
    } else {
        contOrigen.classList.add('d-none');
        document.getElementById('act-recupera-actividad-id').value = '';
    }
}

function actualizarRecuperaId(val) {
    document.getElementById('act-recupera-actividad-id').value = val;
}

async function cargarActividadesOrigen() {
    const ambito = document.querySelector('input[name="ambito"]:checked')?.value || 'estandar';
    if (ambito !== 'recuperacion') return;

    const curso_id = document.getElementById('act-curso').value;
    const especialidad_id = document.getElementById('act-materia').value;
    const selectOrigen = document.getElementById('act-origen');
    
    if (!curso_id || !especialidad_id) {
        selectOrigen.innerHTML = '<option value="">[Seleccione Curso y Materia primero...]</option>';
        return;
    }

    try {
        const response = await fetch(`logica/api_actividades.php?accion=listar_actividades_filtro&curso_id=${curso_id}&especialidad_id=${especialidad_id}&filtrar_periodo=1`);
        const res = await response.json();
        
        if (res.status === 'success') {
            const filtradas = res.data.filter(a => a.ambito === 'estandar');
            if (filtradas.length === 0) {
                selectOrigen.innerHTML = '<option value="">No hay actividades estándar para recuperar</option>';
                return;
            }
            
            const origId = document.getElementById('act-recupera-actividad-id').value;
            selectOrigen.innerHTML = '<option value="">[Seleccionar actividad...]</option>' + 
                filtradas.map(a => `<option value="${a.id}" ${parseInt(a.id) === parseInt(origId) ? 'selected' : ''}>${escapeHtml(a.titulo)}</option>`).join('');
            
            // Forzar actualización de AresSelectEngine
            selectOrigen.dispatchEvent(new Event('change', { bubbles: true }));
        } else {
            selectOrigen.innerHTML = '<option value="">Error al cargar actividades</option>';
            selectOrigen.dispatchEvent(new Event('change', { bubbles: true }));
        }
    } catch (e) {
        selectOrigen.innerHTML = '<option value="">Error de conexión</option>';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    cargarActividades();
    toggleRubricaMode();
    
    // Auto-completar si viene por parámetro de recuperación
    const urlParams = new URLSearchParams(window.location.search);
    const paramCurso = urlParams.get('curso_id');
    const paramMateria = urlParams.get('especialidad_id');
    const paramAmbito = urlParams.get('ambito');
    if (paramCurso && paramMateria) {
        document.getElementById('act-curso').value = paramCurso;
        document.getElementById('act-materia').value = paramMateria;
        
        // Disparar cambio visual si se usa AresSelectEngine u otros componentes
        const evt = new CustomEvent('change', { bubbles: true });
        document.getElementById('act-curso').dispatchEvent(evt);
        document.getElementById('act-materia').dispatchEvent(evt);
    }
    
    if (paramAmbito === 'recuperacion') {
        document.getElementById('ambito-recuperacion').checked = true;
        toggleAmbitoRecuperacion();
    }
});

// Vinculación al scope global
window.toggleRubricaMode = toggleRubricaMode;
window.agregarCriterio = agregarCriterio;
window.eliminarCriterio = eliminarCriterio;
window.cancelarEdicion = cancelarEdicion;
window.cargarActividades = cargarActividades;
window.editarActividad = editarActividad;
window.guardarActividad = guardarActividad;
window.eliminarActividad = eliminarActividad;
window.escapeHtml = escapeHtml;
window.toggleAmbitoRecuperacion = toggleAmbitoRecuperacion;
window.actualizarRecuperaId = actualizarRecuperaId;
window.cargarActividadesOrigen = cargarActividadesOrigen;