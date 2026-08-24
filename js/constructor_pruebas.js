let sortable;

function initConstructor() {
    cargarPruebas();
    
    const formInfo = document.getElementById('form-info-prueba');
    if (formInfo) {
        formInfo.removeEventListener('submit', guardarCabeceraPrueba);
        formInfo.addEventListener('submit', guardarCabeceraPrueba);
    }

    // Inicializar Sortable
    const lienzo = document.getElementById('lienzo-prueba');
    if (lienzo && typeof Sortable !== 'undefined') {
        if (sortable) sortable.destroy();
        sortable = new Sortable(lienzo, {
            animation: 150,
            handle: '.handle-drag',
            ghostClass: 'bg-primary-light',
            onEnd: function() {
                actualizarPuntaje();
            }
        });
    }
}

// Inicialización SPA-Safe
if (document.readyState === 'complete' || document.readyState === 'interactive') {
    initConstructor();
} else {
    document.addEventListener('DOMContentLoaded', initConstructor);
}

async function cargarPruebas() {
    const lista = document.getElementById('lista-pruebas-ares');
    if (!lista) return;

    try {
        const res = await fetch('logica/api_pruebas.php?accion=listar');
        if (!res.ok) {
            throw new Error("Fallo en la comunicación HTTP con la API.");
        }
        const d = await res.json();
        if (d.status === 'success') {
            if (d.data.length === 0) {
                lista.innerHTML = `<tr>
                                <td colspan="8" class="py-5 text-center">
                                    <div class="d-flex flex-column align-items-center justify-content-center py-4">
                                        <!-- Premium Document SVG -->
                                        <svg width="100" height="100" viewBox="0 0 120 120" fill="none" class="mb-3 text-muted opacity-75">
                                            <rect x="34" y="24" width="56" height="72" rx="8" fill="currentColor" class="opacity-10" />
                                            <rect x="30" y="20" width="56" height="72" rx="8" fill="currentColor" class="opacity-5" stroke="currentColor" stroke-width="2" />
                                            <line x1="40" y1="36" x2="65" y2="36" stroke="currentColor" stroke-width="3" stroke-linecap="round" class="text-primary" />
                                            <line x1="40" y1="48" x2="76" y2="48" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="opacity-25" />
                                            <line x1="40" y1="60" x2="76" y2="60" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="opacity-25" />
                                            <line x1="40" y1="72" x2="60" y2="72" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="opacity-25" />
                                            <rect x="80" y="44" width="14" height="8" rx="2" fill="currentColor" class="text-danger opacity-75" />
                                            <rect x="82" y="64" width="12" height="8" rx="2" fill="currentColor" class="text-warning opacity-75" />
                                        </svg>
                                        <h5 class="fw-bold mb-1 text-uppercase text-primary small-letter-spacing">Sin Pruebas Registradas</h5>
                                        <p class="text-secondary small mb-0">Aún no hay instrumentos de evaluación registrados. ¡Haga clic en Nueva Prueba para comenzar!</p>
                                    </div>
                                </td>
                              </tr>`;
                return;
            }
            lista.innerHTML = d.data.map(p => `
                <tr>
                    <td class="ps-4 fw-bold text-muted small">#${p.id}</td>
                    <td class="fw-bold text-dark">${p.titulo}</td>
                    <td><span class="badge-elite badge-elite--info">${p.materia_nombre}</span></td>
                    <td class="text-center"><span class="badge-elite badge-elite--neutral">${p.total_preguntas} reactivos</span></td>
                    <td class="text-center fw-bold">${p.tiempo_limite} min</td>
                    <td class="text-center">
                        ${p.modalidad == 2 
                            ? '<span class="badge-elite badge-elite--warning">FÍSICO (QR)</span>' 
                            : '<span class="badge-elite badge-elite--primary">PLATAFORMA</span>'}
                    </td>
                    <td class="text-center">
                        ${p.total_asignaciones > 0 
                            ? '<span class="badge-elite badge-elite--success"><i class="bi bi-calendar-check me-1"></i>PROGRAMADO</span>' 
                            : '<span class="badge-elite badge-elite--danger"><i class="bi bi-calendar-x me-1"></i>SIN PROGRAMAR</span>'}
                    </td>
                    <td class="pe-4 text-center">
                        <div class="d-flex justify-content-center align-items-center gap-2 u-nowrap">
                            <button class="btn-elite-icon" onclick="editarPrueba(${p.id})" title="Editar Estructura"><i class="bi bi-pencil-square"></i></button>
                            <button class="btn-elite-icon" onclick="duplicarPrueba(${p.id})" title="Duplicar Prueba"><i class="bi bi-files"></i></button>
                            <button class="btn-elite-icon" onclick="mostrarOpcionesImpresion(${p.id})" title="Imprimir Examen"><i class="bi bi-printer"></i></button>
                            ${p.total_asignaciones > 0
                                ? `<button class="btn-elite-icon text-success" onclick="aplicarPrueba(${p.id})" title="Programado (${p.total_asignaciones} asignaciones)"><i class="bi bi-calendar-check-fill"></i></button>`
                                : `<button class="btn-elite-icon text-danger" onclick="aplicarPrueba(${p.id})" title="Falta programar este examen"><i class="bi bi-calendar-plus-fill"></i></button>`
                            }
                            <button class="btn-elite-icon btn-elite-icon--danger" onclick="eliminarPrueba(${p.id})"><i class="bi bi-trash"></i></button>
                        </div>
                    </td>
                </tr>
            `).join('');
        } else {
            throw new Error(d.message || "Error al recuperar datos del listado de pruebas.");
        }
    } catch (e) {
        lista.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-5 text-danger opacity-75">
                    <i class="bi bi-exclamation-triangle-fill fs-3 mb-2"></i>
                    <div class="fw-bold fs-nano">Fallo de Comunicación Académica</div>
                    <p class="small mb-0">No se pudieron recuperar las pruebas debido a una desconexión o bloqueo temporal de la bóveda.</p>
                </td>
            </tr>`;
        lanzarToastElite('danger', 'No se pudo cargar el listado de evaluaciones.', 'Fallo de Conexión');
    }
}

function abrirConstructor() {
    document.getElementById('view-lista-pruebas').classList.add('d-none');
    document.getElementById('view-constructor-ares').classList.remove('d-none');
    
    // Reset constructor
    document.getElementById('form-info-prueba').reset();
    document.getElementById('construct-prueba-id').value = '0';
    document.getElementById('lienzo-prueba').innerHTML = '<div class="text-center py-5 text-muted empty-lienzo"><i class="bi bi-plus-circle-dotted fs-1 mb-3"></i><p>Arrastre reactivos aquí o haga clic en <i class="bi bi-plus"></i> en el banco.</p></div>';
    document.getElementById('panel-reactivos-banco').classList.add('d-none');
    document.getElementById('status-constructor').innerHTML = '<span class="badge-elite badge-elite--info">MODO CONSTRUCTOR</span>';
    
    // Sincronizar selectores con AresSelectEngine
    if (typeof AresSelectEngine !== 'undefined') {
        document.getElementById('construct-materia').dispatchEvent(new Event('change', { bubbles: true }));
        document.getElementById('construct-modalidad').dispatchEvent(new Event('change', { bubbles: true }));
    }
    
    actualizarPuntaje();
}

function volverALista() {
    document.getElementById('view-lista-pruebas').classList.remove('d-none');
    document.getElementById('view-constructor-ares').classList.add('d-none');
    cargarPruebas();
}

async function guardarCabeceraPrueba(e) {
    if (e) e.preventDefault();
    const btn = document.querySelector('#form-info-prueba button[type="submit"]');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> GUARDANDO...';
    }
    
    const fd = new FormData();
    fd.append('accion', 'guardar');
    fd.append('csrf_token', window.CSRF_TOKEN || '');
    fd.append('id', document.getElementById('construct-prueba-id').value);
    fd.append('titulo', document.getElementById('construct-titulo').value);
    fd.append('materia_id', document.getElementById('construct-materia').value);
    fd.append('tiempo_limite', document.getElementById('construct-tiempo').value);
    fd.append('modalidad', document.getElementById('construct-modalidad').value);
    fd.append('instrucciones', document.getElementById('construct-instrucciones').value);

    try {
        const res = await fetch('logica/api_pruebas.php', { method: 'POST', body: fd });
        const d = await res.json();
        if (d.status === 'success') {
            document.getElementById('construct-prueba-id').value = d.id;
            document.getElementById('panel-reactivos-banco').classList.remove('d-none');
            cargarBancoConstructor(document.getElementById('construct-materia').value);
            lanzarToastElite('success', 'Cabecera guardada. Ahora puede añadir preguntas del banco.', 'Cabecera Guardada');
        } else {
            lanzarToastElite('danger', d.message || 'Error al guardar la prueba', 'Error de Bóveda');
        }
    } catch (err) {
        lanzarToastElite('danger', 'Fallo de comunicación con la bóveda.', 'Falla Crítica');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-save me-2"></i> GUARDAR CABECERA';
        }
    }
}

async function cargarBancoConstructor(materiaId) {
    const res = await fetch(`logica/api_preguntas.php?accion=listar&materia_id=${materiaId}`);
    const d = await res.json();
    if (d.status === 'success') {
        const banco = document.getElementById('banco-disponible');
        banco.innerHTML = d.data.map(p => {
            const isAligned = !!p.dba_num;
            return `
            <div class="list-group-item reactivo-banco-item d-flex justify-content-between align-items-center ${!isAligned ? 'opacity-75' : ''}" data-id="${p.id}" data-enunciado="${limpiarHTML(p.enunciado)}" data-tipo="${p.tipo_nombre}" data-dba="${p.dba_num || ''}" data-area="${p.dba_area_nombre || ''}">
                <div class="text-truncate me-2">
                    <span class="fs-nano text-muted">[${p.tipo_nombre}]</span> 
                    ${isAligned ? `<span class="badge bg-success bg-opacity-10 text-success fs-micro p-1 px-2 rounded-pill border border-success border-opacity-25">DBA ${p.dba_num}</span>` : '<span class="badge bg-danger bg-opacity-10 text-danger fs-micro p-1 px-2 rounded-pill border border-danger border-opacity-25">NO APTO: SIN ALINEACIÓN</span>'}
                    <div class="small ${isAligned ? 'fw-bold' : 'text-muted'}">${limpiarHTML(p.enunciado)}</div>
                </div>
                ${isAligned 
                    ? `<button class="btn-elite-icon btn-elite-icon--sm text-primary" onclick="vincularPregunta(${p.id}, true)"><i class="bi bi-plus-circle-fill"></i></button>`
                    : `<button class="btn-elite-icon btn-elite-icon--sm text-muted cursor-not-allowed" title="Debe alinear este reactivo en el Editor antes de usarlo" disabled><i class="bi bi-slash-circle"></i></button>`
                }
            </div>
            `;
        }).join('');
    }
}

async function vincularPregunta(preguntaId, vincular) {
    const pruebaId = document.getElementById('construct-prueba-id').value;
    if (pruebaId == '0') return;

    // Si ya está vinculada, no duplicar (validar en UI)
    if (vincular && document.querySelector(`#lienzo-prueba [data-id="${preguntaId}"]`)) {
        return;
    }

    const fd = new FormData();
    fd.append('accion', 'vincular_pregunta');
    fd.append('csrf_token', window.CSRF_TOKEN || '');
    fd.append('prueba_id', pruebaId);
    fd.append('pregunta_id', preguntaId);
    fd.append('vincular', vincular);

    const res = await fetch('logica/api_pruebas.php', { method: 'POST', body: fd });
    const d = await res.json();
    if (d.status === 'success') {
        if (vincular) {
            // Añadir al lienzo
            const bancoItem = document.querySelector(`.reactivo-banco-item[data-id="${preguntaId}"]`);
            const enunciado = bancoItem.dataset.enunciado;
            const tipo = bancoItem.dataset.tipo;
            const dba = bancoItem.dataset.dba;
            const area = bancoItem.dataset.area;
            
            const div = document.createElement('div');
            div.className = 'list-group-item reactivo-card-mini p-3 d-flex align-items-center';
            div.dataset.id = preguntaId;
            div.innerHTML = `
                <div class="handle-drag me-3 text-muted"><i class="bi bi-grid-3x2-vertical"></i></div>
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="fs-micro text-muted text-uppercase fw-bold">${tipo}</span>
                        ${dba ? `<span class="badge bg-success bg-opacity-10 text-success fs-micro p-1 px-2 rounded-pill border border-success border-opacity-25">DBA ${dba} (${area})</span>` : '<span class="badge bg-danger bg-opacity-10 text-danger fs-micro p-1 px-2 rounded-pill border border-danger border-opacity-25">SIN ALINEACIÓN MEN</span>'}
                    </div>
                    <div class="small fw-bold text-dark">${enunciado}</div>
                </div>
                <div class="ms-3 d-flex align-items-center">
                    <label class="fs-nano text-muted me-2 text-uppercase fw-bold">Valor (PTS):</label>
                    <input type="number" class="input-elite text-center peso-input u-w-70 u-h-34" value="1.0" step="0.1" min="0.1" onchange="actualizarPuntaje()">
                    <button class="btn-elite-icon btn-elite-icon--danger ms-3" onclick="vincularPregunta(${preguntaId}, false)"><i class="bi bi-trash"></i></button>
                </div>
            `;
            
            const empty = document.querySelector('.empty-lienzo');
            if (empty) empty.remove();
            
            document.getElementById('lienzo-prueba').appendChild(div);
        } else {
            // Quitar del lienzo
            const item = document.querySelector(`#lienzo-prueba [data-id="${preguntaId}"]`);
            if (item) item.remove();
            if (document.getElementById('lienzo-prueba').children.length === 0) {
                document.getElementById('lienzo-prueba').innerHTML = '<div class="text-center py-5 text-muted empty-lienzo"><i class="bi bi-plus-circle-dotted fs-1 mb-3"></i><p>Arrastre reactivos aquí o haga clic en <i class="bi bi-plus"></i> en el banco.</p></div>';
            }
        }
        actualizarPuntaje();
    }
}

function actualizarPuntaje() {
    const pesos = document.querySelectorAll('.peso-input');
    let total = 0;
    pesos.forEach(p => total += parseFloat(p.value || 0));
    document.getElementById('total-peso').innerText = total.toFixed(1);
}

async function guardarEstructura() {
    const pruebaId = document.getElementById('construct-prueba-id').value;
    if (pruebaId == '0') {
        lanzarToastElite('warning', 'Primero debe guardar la cabecera de la prueba.');
        return;
    }
    const cards = document.querySelectorAll('#lienzo-prueba .reactivo-card-mini');
    const items = Array.from(cards).map((card, i) => ({
        pregunta_id: card.dataset.id,
        peso: card.querySelector('.peso-input').value,
        orden: i + 1
    }));

    const fd = new FormData();
    fd.append('accion', 'actualizar_items');
    fd.append('csrf_token', window.CSRF_TOKEN || '');
    fd.append('prueba_id', pruebaId);
    fd.append('items', JSON.stringify(items));

    try {
        const res = await fetch('logica/api_pruebas.php', { method: 'POST', body: fd });
        const d = await res.json();
        if (d.status === 'success') {
            lanzarToastElite('success', d.message || 'Estructura de la prueba actualizada.', '¡Prueba Ensamblada!');
            volverALista();
        } else {
            lanzarToastElite('danger', d.message || 'Error al guardar estructura', 'Error de Bóveda');
        }
    } catch (err) {
        lanzarToastElite('danger', 'Fallo al guardar la estructura.', 'Falla Crítica');
    }
}

async function editarPrueba(id) {
    abrirConstructor();
    const res = await fetch(`logica/api_pruebas.php?accion=obtener&id=${id}`);
    const d = await res.json();
    if (d.status === 'success') {
        const p = d.data;
        document.getElementById('construct-prueba-id').value = p.id;
        document.getElementById('construct-titulo').value = p.titulo;
        document.getElementById('construct-materia').value = p.materia_id;
        document.getElementById('construct-tiempo').value = p.tiempo_limite;
        document.getElementById('construct-modalidad').value = p.modalidad;
        document.getElementById('construct-instrucciones').value = p.instrucciones;
        
        // Sincronizar selectores con AresSelectEngine
        if (typeof AresSelectEngine !== 'undefined') {
            document.getElementById('construct-materia').dispatchEvent(new Event('change', { bubbles: true }));
            document.getElementById('construct-modalidad').dispatchEvent(new Event('change', { bubbles: true }));
        }
        
        document.getElementById('panel-reactivos-banco').classList.remove('d-none');
        cargarBancoConstructor(p.materia_id);
        
        // Cargar items vinculados
        const lienzo = document.getElementById('lienzo-prueba');
        lienzo.innerHTML = '';
        p.items.forEach(item => {
            const div = document.createElement('div');
            div.className = 'list-group-item reactivo-card-mini p-3 d-flex align-items-center';
            div.dataset.id = item.id;
            div.innerHTML = `
                <div class="handle-drag me-3 text-muted"><i class="bi bi-grid-3x2-vertical"></i></div>
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="fs-micro text-muted text-uppercase fw-bold">${item.tipo_nombre}</span>
                        ${item.dba_num ? `<span class="badge bg-success bg-opacity-10 text-success fs-micro p-1 px-2 rounded-pill border border-success border-opacity-25">DBA ${item.dba_num} (${item.dba_area_nombre})</span>` : '<span class="badge bg-danger bg-opacity-10 text-danger fs-micro p-1 px-2 rounded-pill border border-danger border-opacity-25">SIN ALINEACIÓN MEN</span>'}
                    </div>
                    <div class="small fw-bold text-dark">${limpiarHTML(item.enunciado)}</div>
                </div>
                <div class="ms-3 d-flex align-items-center">
                    <label class="fs-nano text-muted me-2">PESO:</label>
                    <input type="number" class="input-elite text-center peso-input u-w-70 u-h-34" value="${item.peso}" step="0.5" min="0.1" onchange="actualizarPuntaje()">
                    <button class="btn-elite-icon btn-elite-icon--danger ms-3" onclick="vincularPregunta(${item.id}, false)"><i class="bi bi-trash"></i></button>
                </div>
            `;
            lienzo.appendChild(div);
        });
        actualizarPuntaje();
        document.getElementById('status-constructor').innerHTML = `<span class="badge-elite badge-elite--success">EDITANDO PRUEBA #${id}</span>`;
    }
}

async function duplicarPrueba(id) {
    const res_conf = await Swal.fire({
        title: '¿Duplicar instrumento?',
        text: "Se creará una copia exacta de esta prueba para su edición independiente.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'SÍ, DUPLICAR',
        confirmButtonColor: 'var(--el-primary)'
    });

    if (res_conf.isConfirmed) {
        const fd = new FormData();
        fd.append('accion', 'duplicar_prueba');
        fd.append('csrf_token', window.CSRF_TOKEN || '');
        fd.append('id', id);

        try {
            const res = await fetch('logica/api_pruebas.php', { method: 'POST', body: fd });
            const d = await res.json();
            if (d.status === 'success') {
                lanzarToastElite('success', d.message || 'Prueba duplicada correctamente.', 'Clonación Exitosa');
                cargarPruebas();
            } else {
                lanzarToastElite('danger', d.message || 'Error al clonar prueba', 'Error de Bóveda');
            }
        } catch (e) {
            lanzarToastElite('danger', 'Fallo de comunicación con la bóveda.', 'Falla Crítica');
        }
    }
}

async function eliminarPrueba(id) {
    const result = await Swal.fire({
        title: '¿Confirmar eliminación?',
        text: "La prueba será eliminada permanentemente.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'SÍ, ELIMINAR',
        customClass: { confirmButton: 'btn-elite btn-elite--danger px-4', cancelButton: 'btn-elite btn-elite--outline px-4 ms-2' }
    });
    if (result.isConfirmed) {
        const fd = new FormData();
        fd.append('accion', 'eliminar_prueba');
        fd.append('csrf_token', window.CSRF_TOKEN || '');
        fd.append('id', id);
        try {
            const res = await fetch('logica/api_pruebas.php', { method: 'POST', body: fd });
            const d = await res.json();
            if (d.status === 'success') {
                lanzarToastElite('success', d.message || 'Prueba purgada correctamente.', 'Prueba Eliminada');
                cargarPruebas();
            } else {
                lanzarToastElite('danger', d.message || 'Error al eliminar la prueba', 'Error de Bóveda');
            }
        } catch (e) {
            lanzarToastElite('danger', 'Fallo de comunicación con la bóveda.', 'Falla Crítica');
        }
    }
}

function filtrarBancoConstruct() {
    const txt = document.getElementById('buscar-banco-construct').value.toLowerCase();
    document.querySelectorAll('.reactivo-banco-item').forEach(item => {
        item.classList.toggle('u-hidden', !item.textContent.toLowerCase().includes(txt));
    });
}

function limpiarHTML(html) {
    const tmp = document.createElement("DIV");
    tmp.innerHTML = html;
    return tmp.textContent || tmp.innerText || "";
}

function mostrarOpcionesImpresion(id) {
    Swal.fire({
        title: 'LOGÍSTICA DE IMPRESIÓN ARES',
        html: `
            <p class="text-muted small mb-4">Seleccione el modo de despliegue físico para este examen:</p>
            <div class="d-grid gap-3">
                <button onclick="lanzarImpresion(${id}, 3); Swal.close();" class="btn-elite btn-elite--primary py-3">
                    <i class="bi bi-people-fill me-2"></i> IMPRESIÓN MASIVA (CURSO 3A)
                </button>
                <button onclick="lanzarHojaControl(${id}, 3); Swal.close();" class="btn-elite btn-elite--secondary py-3">
                    <i class="bi bi-qr-code-scan me-2"></i> GENERAR HOJA QR CONTROL
                </button>
                <button onclick="lanzarImpresion(${id}, 0); Swal.close();" class="btn-elite btn-elite--outline py-2 mt-2">
                    <i class="bi bi-file-earmark-pdf me-2"></i> Imprimir Plantilla Individual (Vacía)
                </button>
            </div>
        `,
        showConfirmButton: false,
        showCancelButton: true,
        cancelButtonText: 'CERRAR',
        customClass: {
            container: 'ares-swal-container',
            popup: 'ares-swal-popup'
        }
    });
}

function lanzarImpresion(id, cursoId) {
    const ts = new Date().getTime();
    let url = `../imprimir_examen.php?id=${id}&v=${ts}`;
    if (cursoId > 0) url += `&curso_id=${cursoId}`;
    window.open(url, '_blank');
}

function lanzarHojaControl(id, cursoId) {
    const ts = new Date().getTime();
    const url = `../imprimir_codigos.php?id=${id}&curso_id=${cursoId}&v=${ts}`;
    window.open(url, '_blank');
}

function aplicarPrueba(id) {
    navegarModulo('aplicacion_pruebas&prueba_id=' + id);
}

// Vinculación al scope global
window.initConstructor = initConstructor;
window.cargarPruebas = cargarPruebas;
window.abrirConstructor = abrirConstructor;
window.volverALista = volverALista;
window.guardarCabeceraPrueba = guardarCabeceraPrueba;
window.cargarBancoConstructor = cargarBancoConstructor;
window.vincularPregunta = vincularPregunta;
window.actualizarPuntaje = actualizarPuntaje;
window.guardarEstructura = guardarEstructura;
window.editarPrueba = editarPrueba;
window.duplicarPrueba = duplicarPrueba;
window.eliminarPrueba = eliminarPrueba;
window.filtrarBancoConstruct = filtrarBancoConstruct;
window.limpiarHTML = limpiarHTML;
window.mostrarOpcionesImpresion = mostrarOpcionesImpresion;
window.lanzarImpresion = lanzarImpresion;
window.lanzarHojaControl = lanzarHojaControl;
window.aplicarPrueba = aplicarPrueba;