/**
 * ARES EDITOR - NÚCLEO LÓGICO v1.0
 * Gestión Sincrónica y Asincrónica de Reactivos
 */

let quill;
const tiposPregunta = [];
const currentScope = 'mine';

// Relaciones Materia-Área e Índices de desempeño sincronizados dinámicamente desde el backend

function initEditor() {
    const container = document.getElementById('editor-enunciado');
    const form = document.getElementById('form-pregunta-ares');
    
    if (!container || !form) return;

    if (container.classList.contains('ql-container')) return;

    quill = new Quill('#editor-enunciado', {
        theme: 'snow',
        placeholder: 'El editor se activará al iniciar una nueva creación...',
        modules: { 
            toolbar: [
                ['bold', 'italic', 'underline'], 
                [{ 'list': 'ordered'}, { 'list': 'bullet' }], 
                ['link', 'image', 'formula'], 
                ['clean']
            ] 
        }
    });

    // Interceptar botón de Fórmula en Toolbar de Quill
    const toolbar = quill.getModule('toolbar');
    if (toolbar) {
        toolbar.addHandler('formula', function() {
            abrirModalFormulaPremium();
        });
    }

    cargarTipos();
    cargarAreas();
    cargarBanco();

    form.addEventListener('submit', guardarPregunta);
    
    const selectMateria = document.getElementById('materia_id');
    if (selectMateria) {
        selectMateria.addEventListener('change', (e) => {
            sincronizarAreaMEN(e.target.value);
        });
    }

    // Cerrar dropdowns al hacer clic afuera
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.ares-custom-dropdown')) {
            document.querySelectorAll('.ares-dropdown-menu').forEach(menu => {
                menu.classList.remove('show');
                const trigger = menu.previousElementSibling;
                if (trigger) trigger.setAttribute('aria-expanded', 'false');
            });
        }
    });
    
    habilitarForm(false);
    const contMeta = document.getElementById('contenedor-metadatos');
    if (contMeta) {
        contMeta.innerHTML = `<div class='text-center py-5 text-muted'>
            <i class='bi bi-plus-circle fs-1 d-block mb-3 opacity-25'></i>
            <p class='fs-nano fw-bold text-uppercase'>Presione [+] o seleccione un reactivo para activar el laboratorio</p>
        </div>`;
    }

    // Centinela de Edición de Fórmulas Premium (Double Click Override)
    container.addEventListener('dblclick', function(e) {
        const formulaEl = e.target.closest('.ql-formula');
        if (formulaEl) {
            e.preventDefault();
            e.stopPropagation();
            
            const currentFormula = formulaEl.getAttribute('data-value') || '';
            abrirModalFormulaPremium(currentFormula, formulaEl);
        }
    });
}

window.toggleDropdown = function(menuId) {
    const targetMenu = document.getElementById(menuId);
    if (!targetMenu) return;
    
    // Si está deshabilitado su disparador, no hacer nada
    const trigger = targetMenu.previousElementSibling;
    if (trigger && trigger.disabled) return;
    
    // Cerrar los otros
    document.querySelectorAll('.ares-dropdown-menu').forEach(menu => {
        if (menu.id !== menuId) {
            menu.classList.remove('show');
            const trig = menu.previousElementSibling;
            if (trig) trig.setAttribute('aria-expanded', 'false');
        }
    });
    
    const isShown = targetMenu.classList.toggle('show');
    if (trigger) trigger.setAttribute('aria-expanded', isShown ? 'true' : 'false');
    
    if (isShown) {
        // AJUSTE ANTIGRAVEDAD DE POSICIÓN INTELIGENTE (Smart Boundary Check)
        targetMenu.style.left = '';
        targetMenu.style.right = '';
        
        setTimeout(() => {
            const rect = targetMenu.getBoundingClientRect();
            const viewportWidth = window.innerWidth || document.documentElement.clientWidth;
            
            // Si el borde derecho del menú sobresale de la pantalla, lo alineamos a la derecha
            if (rect.right > viewportWidth) {
                targetMenu.style.left = 'auto';
                targetMenu.style.right = '0px';
            }
            
            // Si el borde izquierdo del menú se sale de la pantalla por el otro lado, lo alineamos a la izquierda
            if (rect.left < 0) {
                targetMenu.style.left = '0px';
                targetMenu.style.right = 'auto';
            }
        }, 50);
    }
};

function cambiarScope(scope) {
    currentScope = scope;
    document.querySelectorAll('.nav-link-elite').forEach(btn => btn.classList.remove('active'));
    document.getElementById(`tab-${scope}`).classList.add('active');
    document.getElementById('filtro-materia-banco').classList.toggle('u-hidden', scope === 'mine');
    document.getElementById('btn-crear-reactivo').classList.toggle('u-hidden', scope === 'universal');
    
    if (scope === 'universal') {
        nuevaPregunta();
        habilitarForm(false);
        document.getElementById('badge-estado-editor').innerHTML = '<span class="badge-elite-pill badge-elite-pill--info">MODO EXPLORACIÓN UNIVERSAL</span>';
        document.getElementById('contenedor-metadatos').innerHTML = `<div class='text-center py-5 text-muted'>
            <i class='bi bi-search fs-1 d-block mb-3 opacity-25'></i>
            <p class='fs-nano fw-bold text-uppercase'>Seleccione un reactivo de la bóveda para previsualizar</p>
        </div>`;
    }
    
    cargarBanco();
}

async function cargarTipos() {
    try {
        const r = await fetch('logica/api_preguntas.php?accion=tipos');
        const d = await r.json();
        if (d.status === 'success') {
            tiposPregunta = d.data;
            const select = document.getElementById('tipo_id');
            select.innerHTML = '<option value="">-- Tipo --</option>' + 
                tiposPregunta.map(t => `<option value="${t.id}" data-slug="${t.slug}">${t.nombre}</option>`).join('');
        }
    } catch (err) {}
}

async function cargarBanco() {
    const lista = document.getElementById('lista-reactivos');
    const materiaId = document.getElementById('filtro-materia-banco').value;
    const miId = document.getElementById('card-editor-ares').dataset.userId;
    
    lista.innerHTML = '<div class="text-center py-5"><div class="loader-ball-elite"><div class="balls-1"></div><div class="balls-2"></div><div class="balls-3"></div><div class="balls-4"></div><div class="balls-5"></div><div class="balls-6"></div><div class="balls-7"></div><div class="balls-8"></div><div class="balls-9"></div></div></div>';
    try {
        const r = await fetch(`logica/api_preguntas.php?accion=listar&scope=${currentScope}&materia_id=${materiaId}`);
        const d = await r.json();
        if (d.status === 'success') {
            if (d.data.length === 0) {
                lista.innerHTML = '<div class="p-5 text-center text-muted fs-nano">No hay reactivos registrados.</div>';
                return;
            }
            lista.innerHTML = d.data.map(p => `
                <div class="list-group-item list-group-item-action p-3 border-0 reactivo-item" id="item-${p.id}" onclick="editarPregunta(${p.id})">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <div class="flex-grow-1">
                            <div class="mb-2">
                                <span class="badge bg-primary bg-opacity-10 text-primary fs-nano p-1 px-2 rounded-pill">${p.tipo_nombre}</span>
                            </div>
                            <div class="mb-2">
                                ${p.dba_num ? `<span class="badge bg-success bg-opacity-10 text-success fs-nano p-1 px-2 rounded-pill border border-success border-opacity-25"><i class="bi bi-bookmark-star-fill me-1"></i> DBA ${p.dba_num} (${p.dba_area_nombre})</span>` : '<span class="badge bg-danger bg-opacity-10 text-danger fs-nano p-1 px-2 rounded-pill border border-danger border-opacity-25"><i class="bi bi-exclamation-triangle-fill me-1"></i> PENDIENTE ALINEACIÓN</span>'}
                            </div>
                        </div>
                        <div class="d-flex gap-1 align-items-start">
                            ${p.parent_id ? `<span class="badge bg-warning bg-opacity-10 text-warning fs-nano p-1 px-2 rounded-pill border border-warning border-opacity-25"><i class="bi bi-diagram-2-fill me-1"></i> ADAPTADO</span>` : ''}
                            <span class="text-muted fs-nano">${p.complejidad}</span>
                        </div>
                    </div>
                    <div class="fw-bold text-dark fs-nano text-uppercase d-flex justify-content-between">
                        <span>${p.titulo || 'Sin Título'}</span>
                        <span class="text-muted opacity-50">#${p.id}</span>
                    </div>
                    <div class="text-muted small italic formula-preview-container">${limpiarHTML(p.enunciado)}</div>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <div class="fs-nano text-muted"><i class="bi bi-person-circle me-1"></i> ${p.docente_id == miId ? 'Tú' : p.autor_nombre}</div>
                        ${p.docente_id == miId ? `<button class="btn btn-link p-0 text-danger" onclick="event.stopPropagation(); eliminarPregunta(${p.id})"><i class="bi bi-trash"></i></button>` : ''}
                    </div>
                </div>
            `).join('');
            
            // Renderizar fórmulas de KaTeX dentro de la lista lateral para visualización instantánea y fidelidad premium
            lista.querySelectorAll('.ql-formula').forEach(el => {
                const val = el.getAttribute('data-value');
                if (val && typeof katex !== 'undefined') {
                    try {
                        katex.render(val, el, { throwOnError: false });
                    } catch (err) {
                        el.textContent = val;
                    }
                }
            });
        }
    } catch (err) {
        lista.innerHTML = '<div class="p-5 text-center text-danger fs-nano">Error al cargar el banco de preguntas.</div>';
    }
}

async function editarPregunta(id) {
    const miId = document.getElementById('card-editor-ares').dataset.userId;
    const isAdmin = document.getElementById('card-editor-ares').dataset.isAdmin === 'true';
    try {
        const r = await fetch(`logica/api_preguntas.php?accion=obtener&id=${id}`);
        const d = await r.json();
        if (d.status === 'success') {
            const p = d.data;
            const esPropia = (p.docente_id == miId || isAdmin);
            document.getElementById('pregunta-id').value = p.id;
            document.getElementById('es-propia').value = esPropia ? '1' : '0';
            document.getElementById('titulo_pregunta').value = p.titulo || '';
            document.getElementById('materia_id').value = p.materia_id;
            document.getElementById('tipo_id').value = p.tipo_id;
            document.getElementById('complejidad').value = p.complejidad;
            quill.root.innerHTML = p.enunciado;
            renderizarEstructuraMetadatos(p.tipo_id, p.metadata_json);
            
            if (p.aprendizaje_id) {
                window.PRESELECTED_EVIDENCIA = p.evidencia_id;
                const selectMat = document.getElementById('materia_id');
                const optMat = selectMat ? selectMat.options[selectMat.selectedIndex] : null;
                const areaId = optMat ? optMat.dataset.areaId : (p.dba_area_id || null);
                if (areaId) {
                    document.getElementById('men-area-id').value = areaId;
                    document.getElementById('area-name').innerText = optMat ? (optMat.dataset.areaNombre || "Área General") : (p.dba_area || "Área General");
                    document.getElementById('area-badge').classList.remove('d-none');
                }
                
                await cargarGrados(p.dba_area_id, p.dba_grado);
                await cargarDBAs(p.dba_grado, p.aprendizaje_id);
            } else {
                limpiarAlineacion();
                sincronizarAreaMEN(p.materia_id);
            }

            document.querySelectorAll('.reactivo-item').forEach(el => el.classList.remove('active'));
            const item = document.getElementById(`item-${id}`);
            if (item) item.classList.add('active');
            
            const badgeCont = document.getElementById('badge-estado-editor');
            const btnsCont = document.getElementById('botones-editor');
            if (esPropia) {
                let linajeHtml = p.parent_id ? `<span class="badge-elite-pill badge-elite-pill--warning me-2">ADAPTACIÓN (ORIGEN: ${p.autor_original_nombre})</span>` : '';
                badgeCont.innerHTML = `${linajeHtml}<span class="badge-elite-pill badge-elite-pill--success">EDITANDO MI REACTIVO</span>`;
                btnsCont.innerHTML = `
                    <button type="button" class="btn-elite btn-elite--outline px-4 ares-h-44" onclick="nuevaPregunta()">CANCELAR</button>
                    <button type="submit" class="btn-elite btn-elite--primary px-5 shadow-sm ares-h-44"><i class="bi bi-shield-check me-2"></i> GUARDAR CAMBIOS</button>
                `;
                habilitarForm(true);
            } else {
                badgeCont.innerHTML = `<span class="badge-elite-pill badge-elite-pill--info">VISTA PREVIA (AUTOR: ${p.autor_nombre})</span>`;
                btnsCont.innerHTML = `
                    <button type="button" class="btn-elite btn-elite--outline px-4 ares-h-44" onclick="nuevaPregunta()">CERRAR</button>
                    <button type="button" class="btn-elite btn-elite--primary px-5 shadow-sm ares-h-44" onclick="adaptarPregunta(${p.id})"><i class="bi bi-diagram-2 me-2"></i> ADAPTAR A MI TALLER</button>
                `;
                habilitarForm(false);
            }
        }
    } catch (err) {
        Swal.fire('Error', 'No se pudo cargar el reactivo para edición.', 'error');
    }
}

function habilitarForm(status) {
    document.getElementById('titulo_pregunta').disabled = !status;
    document.getElementById('materia_id').disabled = !status;
    document.getElementById('tipo_id').disabled = !status;
    document.getElementById('complejidad').disabled = !status;
    if (quill) quill.enable(status);
    document.querySelectorAll('#contenedor-metadatos input, #contenedor-metadatos textarea, #contenedor-metadatos select').forEach(el => el.disabled = !status);
    document.querySelectorAll('#panel-alineacion-men select').forEach(el => el.disabled = !status);
    
    // Habilitar/Deshabilitar botones de dropdown personalizados
    const triggerDba = document.getElementById('dba_dropdown_trigger');
    const triggerEvid = document.getElementById('evidencia_dropdown_trigger');
    if (triggerDba) triggerDba.disabled = !status;
    if (triggerEvid) triggerEvid.disabled = !status;

    // Mostrar/ocultar el panel de alineación MEN según el estado del formulario
    const panelAlineacion = document.getElementById('panel-alineacion-men');
    if (panelAlineacion) {
        if (status) {
            panelAlineacion.classList.remove('d-none');
        } else {
            panelAlineacion.classList.add('d-none');
        }
    }

    // Alternar visibilidad de formulario completo vs placeholder vacío
    const form = document.getElementById('form-pregunta-ares');
    const emptyState = document.getElementById('editor-empty-state');
    const badgeCont = document.getElementById('badge-estado-editor');
    if (form && emptyState) {
        if (status) {
            form.classList.remove('d-none');
            emptyState.classList.add('d-none');
            if (badgeCont) badgeCont.classList.remove('d-none');
        } else {
            form.classList.add('d-none');
            emptyState.classList.remove('d-none');
            if (badgeCont) badgeCont.classList.add('d-none');
        }
    }
}

function sincronizarAreaMEN(materiaId) {
    const select = document.getElementById('materia_id');
    const option = select ? select.options[select.selectedIndex] : null;
    const areaId = option ? option.dataset.areaId : null;
    const areaNombre = option ? option.dataset.areaNombre : null;
    const badge = document.getElementById('area-badge');
    const areaName = document.getElementById('area-name');
    const areaInput = document.getElementById('men-area-id');

    if (areaId) {
        areaInput.value = areaId;
        areaName.innerText = areaNombre || "Área General";
        badge.classList.remove('d-none');
        cargarGrados(areaId);
    } else {
        badge.classList.add('d-none');
        areaInput.value = "";
    }
}

function limpiarAlineacion() {
    const areaId = document.getElementById('men-area-id');
    const gradoId = document.getElementById('men-grado-id');
    const dbaId = document.getElementById('aprendizaje_id');
    const evidId = document.getElementById('evidencia_id');
    const preview = document.getElementById('preview-dba');
    const badge = document.getElementById('area-badge');

    const triggerDba = document.getElementById('dba_dropdown_trigger');
    const triggerEvid = document.getElementById('evidencia_dropdown_trigger');
    const listDba = document.getElementById('aprendizaje_list');
    const listEvid = document.getElementById('evidencia_list');

    if (areaId) areaId.value = '';
    if (gradoId) gradoId.innerHTML = '<option value="">Grado...</option>';
    if (dbaId) dbaId.value = '';
    if (evidId) evidId.value = '';
    
    if (triggerDba) {
        triggerDba.innerText = '-- Seleccionar DBA --';
        triggerDba.disabled = true;
    }
    if (triggerEvid) {
        triggerEvid.innerText = '-- Seleccionar Evidencia --';
        triggerEvid.disabled = true;
    }
    
    if (listDba) listDba.innerHTML = '<div class="text-muted small italic p-3 text-center">Seleccione un grado para cargar los DBA...</div>';
    if (listEvid) listEvid.innerHTML = '<div class="text-muted small italic p-3 text-center">Seleccione un DBA para cargar las evidencias...</div>';
    
    if (preview) preview.classList.add('d-none');
    if (badge) badge.classList.add('d-none');
}

async function cargarAreas() {
    try {
        const r = await fetch('logica/api_preguntas.php?accion=catalogo_areas');
        const d = await r.json();
        if (d.status === 'success') {
            const select = document.getElementById('men-area-id');
            // Nota: Este select ya no se usa directamente en el UI pero se mantiene como backup lógico
            if (select && select.tagName === 'SELECT') {
                select.innerHTML = '<option value="">-- Seleccionar Área --</option>' + 
                    d.data.map(a => `<option value="${a.id}">${a.nombre_area}</option>`).join('');
            }
        }
    } catch (err) {}
}

async function cargarGrados(areaId, preselected = null) {
    if (!areaId) return;
    try {
        const r = await fetch(`logica/api_preguntas.php?accion=catalogo_grados&area_id=${areaId}`);
        const d = await r.json();
        if (d.status === 'success') {
            const select = document.getElementById('men-grado-id');
            if (select) {
                select.innerHTML = '<option value="">Grado...</option>' + 
                    d.data.map(g => `<option value="${g}" ${g == preselected ? 'selected' : ''}>${g}</option>`).join('');
                if (!preselected) {
                    document.getElementById('aprendizaje_id').innerHTML = '<option value="">-- Seleccionar DBA --</option>';
                    document.getElementById('evidencia_id').innerHTML = '<option value="">-- Seleccionar Evidencia --</option>';
                }
            }
        }
    } catch (err) {}
}

async function cargarDBAs(grado, preselected = null) {
    const areaId = document.getElementById('men-area-id').value;
    const select = document.getElementById('materia_id');
    const option = select ? select.options[select.selectedIndex] : null;
    const disciplina = option ? (option.dataset.disciplina || 'general') : 'general';

    const container = document.getElementById('aprendizaje_list');
    const hiddenInput = document.getElementById('aprendizaje_id');
    const triggerDba = document.getElementById('dba_dropdown_trigger');

    if (!areaId || !grado) return;
    if (container) container.innerHTML = '<div class="text-center py-3"><div class="loader-ball-elite--mini"><div class="balls-1"></div><div class="balls-2"></div><div class="balls-3"></div><div class="balls-4"></div><div class="balls-5"></div><div class="balls-6"></div><div class="balls-7"></div><div class="balls-8"></div><div class="balls-9"></div></div></div>';
    
    try {
        const r = await fetch(`logica/api_preguntas.php?accion=catalogo_dbas&area_id=${areaId}&grado=${grado}&disciplina=${disciplina}`);
        const d = await r.json();
        if (d.status === 'success') {
            if (container) {
                if (d.data.length === 0) {
                    container.innerHTML = '<div class="text-muted small italic p-3 text-center">No hay DBA registrados para este grado.</div>';
                    if (triggerDba) {
                        triggerDba.disabled = true;
                        triggerDba.innerText = '-- Sin DBA disponibles --';
                    }
                    return;
                }
                
                if (triggerDba) {
                    triggerDba.disabled = false;
                    triggerDba.innerText = '-- Seleccionar DBA --';
                }

                container.innerHTML = d.data.map(x => `
                    <div class="ares-custom-list-item dba-item-card" id="dba-item-${x.id}" data-id="${x.id}" data-full="${escapeHtmlAttr(x.enunciado)}" data-num="DBA ${x.num_dba}">
                        <strong>DBA ${x.num_dba}:</strong> ${x.enunciado}
                    </div>
                `).join('');

                // Agregar Event Listeners
                container.querySelectorAll('.dba-item-card').forEach(item => {
                    item.addEventListener('click', function() {
                        container.querySelectorAll('.dba-item-card').forEach(el => el.classList.remove('active'));
                        this.classList.add('active');
                        hiddenInput.value = this.dataset.id;
                        
                        // Actualizar texto del trigger
                        if (triggerDba) triggerDba.innerText = this.dataset.num;
                        
                        // Cerrar dropdown
                        container.classList.remove('show');
                        if (triggerDba) triggerDba.setAttribute('aria-expanded', 'false');
                        
                        // Cargar evidencias del DBA seleccionado
                        cargarEvidencias(this.dataset.id);
                        
                        // Actualizar previsualización
                        actualizarPreviewEvidencia();
                    });
                });

                if (preselected) {
                    const activeItem = document.getElementById(`dba-item-${preselected}`);
                    if (activeItem) {
                        activeItem.classList.add('active');
                        hiddenInput.value = preselected;
                        if (triggerDba) triggerDba.innerText = activeItem.dataset.num;
                    }
                    const evidenciaId = window.PRESELECTED_EVIDENCIA || null;
                    await cargarEvidencias(preselected, evidenciaId);
                } else {
                    hiddenInput.value = "";
                    const evidInput = document.getElementById('evidencia_id');
                    if (evidInput) evidInput.value = "";
                    const evidList = document.getElementById('evidencia_list');
                    if (evidList) evidList.innerHTML = '<div class="text-muted small italic p-3 text-center">Seleccione un DBA para cargar las evidencias...</div>';
                    const triggerEvid = document.getElementById('evidencia_dropdown_trigger');
                    if (triggerEvid) {
                        triggerEvid.disabled = true;
                        triggerEvid.innerText = '-- Seleccionar Evidencia --';
                    }
                    actualizarPreviewEvidencia();
                }
            }
        }
    } catch (err) {}
}

async function cargarEvidencias(aprendizajeId, preselected = null) {
    if (!aprendizajeId) return;
    const container = document.getElementById('evidencia_list');
    const hiddenInput = document.getElementById('evidencia_id');
    const triggerEvid = document.getElementById('evidencia_dropdown_trigger');
    
    if (container) container.innerHTML = '<div class="text-center py-3"><div class="loader-ball-elite--mini"><div class="balls-1"></div><div class="balls-2"></div><div class="balls-3"></div><div class="balls-4"></div><div class="balls-5"></div><div class="balls-6"></div><div class="balls-7"></div><div class="balls-8"></div><div class="balls-9"></div></div></div>';
    
    try {
        const r = await fetch(`logica/api_preguntas.php?accion=catalogo_evidencias&aprendizaje_id=${aprendizajeId}`);
        const d = await r.json();
        if (d.status === 'success') {
            if (container) {
                if (d.data.length === 0) {
                    container.innerHTML = '<div class="text-muted small italic p-3 text-center">No hay evidencias registradas para este DBA.</div>';
                    if (triggerEvid) {
                        triggerEvid.disabled = true;
                        triggerEvid.innerText = '-- Sin evidencias disponibles --';
                    }
                    return;
                }
                
                if (triggerEvid) {
                    triggerEvid.disabled = false;
                    triggerEvid.innerText = '-- Seleccionar Evidencia --';
                }

                container.innerHTML = d.data.map(x => `
                    <div class="ares-custom-list-item evidencia-item-card" id="evidencia-item-${x.id}" data-id="${x.id}" data-full="${escapeHtmlAttr(x.enunciado)}">
                        ${x.enunciado}
                    </div>
                `).join('');

                // Agregar Event Listeners
                container.querySelectorAll('.evidencia-item-card').forEach(item => {
                    item.addEventListener('click', function() {
                        container.querySelectorAll('.evidencia-item-card').forEach(el => el.classList.remove('active'));
                        this.classList.add('active');
                        hiddenInput.value = this.dataset.id;
                        
                        // Actualizar texto del trigger
                        if (triggerEvid) {
                            const text = this.innerText.trim();
                            triggerEvid.innerText = text.length > 50 ? text.substring(0, 47) + '...' : text;
                        }
                        
                        // Cerrar dropdown
                        container.classList.remove('show');
                        if (triggerEvid) triggerEvid.setAttribute('aria-expanded', 'false');
                        
                        // Actualizar previsualización
                        actualizarPreviewEvidencia();
                    });
                });

                if (preselected) {
                    const activeItem = document.getElementById(`evidencia-item-${preselected}`);
                    if (activeItem) {
                        activeItem.classList.add('active');
                        hiddenInput.value = preselected;
                        if (triggerEvid) {
                            const text = activeItem.innerText.trim();
                            triggerEvid.innerText = text.length > 50 ? text.substring(0, 47) + '...' : text;
                        }
                    }
                    setTimeout(actualizarPreviewEvidencia, 100);
                } else {
                    hiddenInput.value = "";
                    actualizarPreviewEvidencia();
                }
            }
        }
    } catch (err) {}
}

function actualizarPreviewEvidencia() {
    const preview = document.getElementById('preview-dba');
    const dbaInput = document.getElementById('aprendizaje_id');
    const evidenciaInput = document.getElementById('evidencia_id');
    
    if (!dbaInput || !dbaInput.value || !evidenciaInput || !evidenciaInput.value) {
        if (preview) preview.classList.add('d-none');
        return;
    }
    
    const activeDba = document.querySelector('.dba-item-card.active');
    const activeEvidencia = document.querySelector('.evidencia-item-card.active');
    
    const dbaText = activeDba ? activeDba.dataset.num : 'DBA';
    const dbaFull = activeDba ? activeDba.dataset.full : '';
    const evidenciaFull = activeEvidencia ? activeEvidencia.dataset.full : '';

    if (preview) {
        preview.innerHTML = `
            <div class="ares-preview-item">
                <div class="ares-preview-label">${dbaText}:</div>
                <div class="ares-preview-text">${escapeHtml(dbaFull)}</div>
            </div>
            <div class="ares-preview-item">
                <div class="ares-preview-label">EVIDENCIA:</div>
                <div class="ares-preview-text">${escapeHtml(evidenciaFull)}</div>
            </div>
        `;
        preview.classList.remove('d-none');
    }
}

// Helpers locales para sanitización y escape de HTML
function escapeHtmlAttr(str) {
    if (!str) return '';
    return str.replace(/&/g, '&amp;')
              .replace(/"/g, '&quot;')
              .replace(/'/g, '&apos;')
              .replace(/</g, '&lt;')
              .replace(/>/g, '&gt;');
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, '&amp;')
              .replace(/</g, '&lt;')
              .replace(/>/g, '&gt;');
}


async function adaptarPregunta(id) {
    const { isConfirmed } = await Swal.fire({
        title: '¿Adaptar reactivo?',
        text: 'Se creará una copia en su taller vinculada al original.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'SÍ, ADAPTAR',
        confirmButtonColor: 'var(--el-primary)'
    });
    if (isConfirmed) {
        const csrfToken = document.getElementById('card-editor-ares').dataset.csrf;
        const formData = new FormData();
        formData.append('accion', 'adaptar');
        formData.append('csrf_token', csrfToken);
        formData.append('id', id);
        try {
            const r = await fetch('logica/api_preguntas.php', { method: 'POST', body: formData });
            const d = await r.json();
            if (d.status === 'success') {
                Swal.fire('Éxito', d.message, 'success');
                cambiarScope('mine');
                editarPregunta(d.nuevo_id);
            } else { throw new Error(d.message); }
        } catch (e) { Swal.fire('Error', e.message, 'error'); }
    }
}

async function guardarPregunta(e) {
    e.preventDefault();
    if (document.getElementById('es-propia').value === '0') return;
    const tipoId = document.getElementById('tipo_id').value;
    const select = document.getElementById('tipo_id');
    const option = select.options[select.selectedIndex];
    const slug = option ? option.dataset.slug : '';
    let metadata = {};

    if (slug === 'seleccion_multiple') {
        const items = document.querySelectorAll('#opciones-sm .input-group');
        metadata.opciones = Array.from(items).map((item, i) => ({ 
            t: item.querySelector('input[type="text"]').value, 
            c: item.querySelector('input[type="radio"]').checked 
        }));
    } else if (slug === 'abierta') { 
        metadata.guia = document.getElementById('guia-abierta').value; 
    } else if (slug === 'emparejamiento') {
        const items = document.querySelectorAll('#pares-match .row');
        metadata.pares = Array.from(items).map(item => ({ 
            a: item.querySelectorAll('input')[0].value, 
            b: item.querySelectorAll('input')[1].value 
        }));
    } else if (slug === 'completar') { 
        metadata.respuestas = document.getElementById('respuestas-completar').value; 
    } else if (slug === 'canvas_reactivo') {
        const jsonText = document.getElementById('canvas-json-data').value;
        try {
            metadata = JSON.parse(jsonText || '{}');
        } catch (err) {
            metadata = {};
        }
    }
    
    const csrfToken = document.getElementById('card-editor-ares').dataset.csrf;
    const formData = new FormData();
    formData.append('accion', 'guardar');
    formData.append('csrf_token', csrfToken);
    formData.append('id', document.getElementById('pregunta-id').value);
    formData.append('titulo', document.getElementById('titulo_pregunta').value);
    formData.append('materia_id', document.getElementById('materia_id').value);
    formData.append('tipo_id', tipoId);
    formData.append('complejidad', document.getElementById('complejidad').value);
    formData.append('enunciado', quill.root.innerHTML);
    formData.append('metadata', JSON.stringify(metadata));
    formData.append('aprendizaje_id', document.getElementById('aprendizaje_id').value);
    formData.append('evidencia_id', document.getElementById('evidencia_id').value);
    try {
        const r = await fetch('logica/api_preguntas.php', { method: 'POST', body: formData });
        const d = await r.json();
        if (d.status === 'success') {
            Swal.fire({ icon: 'success', title: '¡Guardado!', text: d.message, timer: 1500, showConfirmButton: false });
            cargarBanco();
            cerrarLaboratorio();
        } else { throw new Error(d.message); }
    } catch (err) { Swal.fire('Error', err.message, 'error'); }
}

async function eliminarPregunta(id) {
    const result = await Swal.fire({
        title: '¿Confirmar eliminación?',
        text: "Esta acción purgará el reactivo permanentemente.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: 'var(--el-danger)',
        confirmButtonText: 'SÍ, ELIMINAR',
        cancelButtonText: 'CANCELAR'
    });
    if (result.isConfirmed) {
        const csrfToken = document.getElementById('card-editor-ares').dataset.csrf;
        const formData = new FormData();
        formData.append('accion', 'eliminar');
        formData.append('csrf_token', csrfToken);
        formData.append('id', id);
        try {
            const r = await fetch('logica/api_preguntas.php', { method: 'POST', body: formData });
            const d = await r.json();
            if (d.status === 'success') {
                cargarBanco();
                if (document.getElementById('pregunta-id').value == id) nuevaPregunta();
            } else { throw new Error(d.message); }
        } catch (e) { Swal.fire('Error', e.message, 'error'); }
    }
}

function nuevaPregunta() {
    const form = document.getElementById('form-pregunta-ares');
    if (!form) return;
    
    form.reset();
    document.getElementById('pregunta-id').value = '0';
    document.getElementById('es-propia').value = '1';
    document.getElementById('titulo_pregunta').value = '';
    if (quill) quill.root.innerHTML = '';
    habilitarForm(true);
    limpiarAlineacion();
    
    document.getElementById('contenedor-metadatos').innerHTML = '<div class="text-center py-4"><i class="bi bi-info-circle fs-3 mb-2"></i><p>Seleccione un tipo de pregunta para configurar.</p></div>';
    document.getElementById('badge-estado-editor').innerHTML = '<span class="badge-elite-pill badge-elite-pill--info">NUEVO REACTIVO ACTIVADO</span>';
    
    document.getElementById('botones-editor').innerHTML = `
        <button type="button" class="btn-elite btn-elite--outline px-4 ares-h-44" onclick="cerrarLaboratorio()">CANCELAR</button>
        <button type="submit" class="btn-elite btn-elite--primary px-5 shadow-sm ares-h-44"><i class="bi bi-shield-check me-2"></i> GUARDAR EN BÓVEDA</button>
    `;

    document.getElementById('materia_id').focus();
}

function clickNuevoReactivo() {
    if (currentScope !== 'mine') cambiarScope('mine');
    nuevaPregunta();
}

function cerrarLaboratorio() {
    const form = document.getElementById('form-pregunta-ares');
    if (form) form.reset();
    
    document.getElementById('pregunta-id').value = '0';
    document.getElementById('es-propia').value = '1';
    document.getElementById('titulo_pregunta').value = '';
    if (quill) {
        quill.root.innerHTML = '';
        quill.enable(false);
    }
    
    limpiarAlineacion();
    habilitarForm(false);
    
    document.getElementById('contenedor-metadatos').innerHTML = `<div class='text-center py-5 text-muted'>
        <i class='bi bi-plus-circle fs-1 d-block mb-3 opacity-25'></i>
        <p class='fs-nano fw-bold text-uppercase'>Presione [+] o seleccione un reactivo para activar el laboratorio</p>
    </div>`;
    
    document.getElementById('badge-estado-editor').innerHTML = '<span class="badge-elite-pill badge-elite-pill--neutral">LABORATORIO INACTIVO</span>';
    
    document.getElementById('botones-editor').innerHTML = `
        <button type="button" class="btn-elite btn-elite--outline px-4 ares-h-44" onclick="nuevaPregunta()">CANCELAR</button>
        <button type="submit" class="btn-elite btn-elite--primary px-5 shadow-sm ares-h-44" disabled><i class="bi bi-shield-check me-2"></i> GUARDAR EN BÓVEDA</button>
    `;
    
    document.querySelectorAll('.reactivo-item').forEach(el => el.classList.remove('active'));
}

function limpiarHTML(html) { 
    if (!html) return "";
    const tmp = document.createElement("DIV"); 
    tmp.innerHTML = html; 
    
    // Vaciar el contenido interno repetitivo de las fórmulas para dejar la base limpia para KaTeX
    tmp.querySelectorAll('.ql-formula').forEach(el => {
        el.innerHTML = ''; 
    });
    
    // Eliminar etiquetas de bloque y saltos de línea para evitar desconfigurar el listado lateral
    const htmlLimpio = tmp.innerHTML
        .replace(/<\/?p[^>]*>/gi, ' ')
        .replace(/<\/?div[^>]*>/gi, ' ')
        .replace(/<br\s*\/?>/gi, ' ');
        
    return htmlLimpio.trim(); 
}

function filtrarBanco() {
    const txt = document.getElementById('buscar-pregunta').value.toLowerCase();
    document.querySelectorAll('.reactivo-item').forEach(item => {
        const matches = item.textContent.toLowerCase().includes(txt);
        item.classList.toggle('u-hidden', !matches);
    });
}

function renderizarEstructuraMetadatos(tipoId, data = null, autoOpenCanvas = false) {
    const select = document.getElementById('tipo_id');
    const option = select.options[select.selectedIndex];
    const slug = option ? option.dataset.slug : '';
    const container = document.getElementById('contenedor-metadatos');
    if (!slug) return;
    
    let html = `<h6 class="fw-bold text-dark mb-3 text-uppercase fs-nano"><i class="bi bi-gear-fill me-2"></i> Configuración (${option.text})</h6>`;
    switch (slug) {
        case 'seleccion_multiple':
            const opciones = data ? (typeof data === 'string' ? JSON.parse(data).opciones : data.opciones) : [{t:'', c:false}, {t:'', c:false}, {t:'', c:false}, {t:'', c:false}];
            html += `<div id="opciones-sm">`;
            opciones.forEach((op, i) => { 
                html += `<div class="input-group mb-2">
                    <div class="input-group-text">
                        <input class="form-check-input" type="radio" name="opcion_correcta" id="opt-radio-${i}" value="${i}" ${op.c ? 'checked' : ''} aria-label="Correcta" required>
                    </div>
                    <label class="visually-hidden" for="opt-text-${i}">Opción ${i+1}</label>
                    <input type="text" id="opt-text-${i}" class="input-elite" value="${op.t}" placeholder="Respuesta..." required>
                </div>`; 
            });
            html += `</div>`; 
            break;
        case 'abierta': 
            const guiaVal = data ? (typeof data === 'string' ? JSON.parse(data).guia : data.guia) : '';
            html += `<div class="mb-2">
                <label for="guia-abierta" class="form-label fw-bold text-uppercase fs-nano text-muted">Guía de Respuesta Sugerida</label>
                <textarea class="input-elite" id="guia-abierta" name="guia-abierta" rows="3">${guiaVal}</textarea>
            </div>`; 
            break;
        case 'emparejamiento':
            const pares = data ? (typeof data === 'string' ? JSON.parse(data).pares : data.pares) : [{a:'', b:''}, {a:'', b:''}, {a:'', b:''}];
            html += `<div id="pares-match">`;
            pares.forEach((p, i) => {
                 html += `<div class="row g-2 mb-2">
                    <div class="col-6"><input type="text" class="input-elite" placeholder="Concepto A" value="${p.a}" required></div>
                    <div class="col-6"><input type="text" class="input-elite" placeholder="Vínculo B" value="${p.b}" required></div>
                 </div>`;
            });
            html += `</div>`;
            break;
        case 'completar':
            const respVal = data ? (typeof data === 'string' ? JSON.parse(data).respuestas : data.respuestas) : '';
            html += `<div class="mb-2">
                <label for="respuestas-completar" class="form-label fw-bold text-uppercase fs-nano text-muted">Palabras Correctas (Separadas por coma)</label>
                <input type="text" class="input-elite" id="respuestas-completar" value="${respVal}" placeholder="Ej: ADN, núcleo, célula" required>
            </div>`;
            break;
        case 'canvas_reactivo':
            html += `
                <div class="text-center py-4">
                    <button type="button" class="btn-elite btn-elite--primary px-4 ares-h-44" id="btn-abrir-canvas-disenador">
                        <i class="bi bi-palette-fill me-2"></i> 🎨 DISEÑAR CON ARES CANVAS
                    </button>
                    <textarea id="canvas-json-data" name="canvas_json_data" class="d-none"></textarea>
                </div>
            `;
            container.innerHTML = html;
            
            const txtArea = document.getElementById('canvas-json-data');
            if (txtArea && data) {
                txtArea.value = (typeof data === 'string') ? data : JSON.stringify(data);
            }

            const btnAbrir = document.getElementById('btn-abrir-canvas-disenador');
            if (btnAbrir) {
                btnAbrir.addEventListener('click', () => {
                    const modal = document.getElementById('modal-ares-canvas-fullscreen');
                    if (modal) {
                        if (modal.parentNode !== document.body) {
                            document.body.appendChild(modal);
                        }
                        modal.classList.add('modal-canvas--open');
                        const jsonVal = document.getElementById('canvas-json-data').value;
                        if (window.initCanvasEditor) {
                            window.initCanvasEditor(jsonVal);
                        }
                    }
                });

                if (autoOpenCanvas) {
                    btnAbrir.click();
                }
            }
            break;
    }
    
    if (slug !== 'canvas_reactivo') {
        container.innerHTML = html;
    }
    
    if (document.getElementById('es-propia').value === '0') habilitarForm(false);
}

if (document.readyState === 'complete' || document.readyState === 'interactive') {
    setTimeout(initEditor, 50);
} else {
    document.addEventListener('DOMContentLoaded', initEditor);
}

function abrirModalFormulaPremium(currentFormula = '', formulaEl = null) {
    const isEditing = !!formulaEl;
    
    // Categorías y plantillas preconfiguradas con LaTeX y display para renderizar con KaTeX
    const categories = {
        algebra: {
            name: 'Álgebra',
            symbols: [
                { latex: '\\frac{#?}{#?}', display: '\\frac{a}{b}' },
                { latex: '#?^{#?}', display: 'x^a' },
                { latex: '#?_{#?}', display: 'x_a' },
                { latex: '#?_{#?}^{#?}', display: 'x_a^b' },
                { latex: '\\sqrt{#?}', display: '\\sqrt{x}' },
                { latex: '\\sqrt[#?]{#?}', display: '\\sqrt[n]{x}' },
                { latex: '\\sum_{#?}^{#?}', display: '\\sum_{i}^{n}' },
                { latex: '\\prod_{#?}^{#?}', display: '\\prod_{i}^{n}' },
                { latex: '\\left( #? \\right)', display: '(x)' },
                { latex: '\\left[ #? \\right]', display: '[x]' },
                { latex: '\\left\\{ #? \\right\\}', display: '\\{x\\}' },
                { latex: '\\left| #? \\right|', display: '|x|' },
                { latex: '\\pm', display: '\\pm' },
                { latex: '\\cdot', display: '\\cdot' },
                { latex: '\\times', display: '\\times' },
                { latex: '\\div', display: '\\div' },
                { latex: '\\log_{#?}(#?)', display: '\\log_b(x)' },
                { latex: '\\ln(#?)', display: '\\ln(x)' },
                { latex: '\\begin{pmatrix} #? & #? \\\\ #? & #? \\end{pmatrix}', display: '\\begin{pmatrix} a & b \\\\ c & d \\end{pmatrix}' }
            ]
        },
        calculo: {
            name: 'Cálculo',
            symbols: [
                { latex: '\\lim_{#? \\to #?}', display: '\\lim_{x\\to a}' },
                { latex: '\\int_{#?}^{#?}', display: '\\int_a^b' },
                { latex: '\\iint_{#?}^{#?}', display: '\\iint' },
                { latex: '\\iiint_{#?}^{#?}', display: '\\iiint' },
                { latex: '\\oint_{#?}', display: '\\oint' },
                { latex: '\\frac{d#?}{d#?}', display: '\\frac{dy}{dx}' },
                { latex: '\\frac{\\partial #?}{\\partial #?}', display: '\\frac{\\partial y}{\\partial x}' },
                { latex: '\\infty', display: '\\infty' },
                { latex: '\\to', display: '\\to' },
                { latex: '\\sin(#?)', display: '\\sin(x)' },
                { latex: '\\cos(#?)', display: '\\cos(x)' },
                { latex: '\\tan(#?)', display: '\\tan(x)' },
                { latex: '\\arcsin(#?)', display: '\\arcsin' },
                { latex: '\\arccos(#?)', display: '\\arccos' },
                { latex: '\\arctan(#?)', display: '\\arctan' }
            ]
        },
        quimica: {
            name: 'Química',
            symbols: [
                { latex: '\\rightarrow', display: '\\rightarrow' },
                { latex: '\\rightleftharpoons', display: '\\rightleftharpoons' },
                { latex: '\\text{#?}_{#?}', display: 'X_a' },
                { latex: '\\text{#?}', display: '\\text{Text}' },
                { latex: '\\text{=}', display: '=' },
                { latex: '\\equiv', display: '\\equiv' },
                { latex: '\\uparrow', display: '\\uparrow' },
                { latex: '\\downarrow', display: '\\downarrow' },
                { latex: '#?^{+}', display: 'X^+' },
                { latex: '#?^{-}', display: 'X^-' },
                { latex: '\\Delta', display: '\\Delta' },
                { latex: '^{\\circ}\\text{C}', display: '^{\\circ}\\text{C}' },
                { latex: '\\text{H}_{2}\\text{O}', display: '\\text{H}_{2}\\text{O}' },
                { latex: '\\text{CO}_{2}', display: '\\text{CO}_{2}' },
                { latex: '\\text{O}_{2}', display: '\\text{O}_{2}' },
                { latex: '\\text{HCl}', display: '\\text{HCl}' },
                { latex: '\\text{H}_{2}\\text{SO}_{4}', display: '\\text{H}_{2}\\text{SO}_{4}' },
                { latex: '\\text{C}_{6}\\text{H}_{12}\\text{O}_{6}', display: '\\text{C}_{6}\\text{H}_{12}\\text{O}_{6}' },
                { latex: '\\text{NaOH}', display: '\\text{NaOH}' },
                { latex: '\\text{NaCl}', display: '\\text{NaCl}' },
                { latex: '\\text{NH}_{4}^{+}', display: '\\text{NH}_{4}^{+}' }
            ]
        },
        fisica: {
            name: 'Física',
            symbols: [
                { latex: '\\vec{#?}', display: '\\vec{v}' },
                { latex: '\\hat{i}', display: '\\hat{i}' },
                { latex: '\\hat{j}', display: '\\hat{j}' },
                { latex: '\\hat{k}', display: '\\hat{k}' },
                { latex: '\\Delta', display: '\\Delta' },
                { latex: '\\bar{#?}', display: '\\bar{x}' },
                { latex: 'g', display: 'g' },
                { latex: 'c', display: 'c' },
                { latex: 'h', display: 'h' },
                { latex: '\\mu', display: '\\mu' },
                { latex: '\\Omega', display: '\\Omega' },
                { latex: '\\text{Å}', display: '\\text{Å}' },
                { latex: '\\rho', display: '\\rho' },
                { latex: '\\lambda', display: '\\lambda' },
                { latex: '\\nu', display: '\\nu' },
                { latex: '\\omega', display: '\\omega' },
                { latex: '\\tau', display: '\\tau' },
                { latex: '\\phi', display: '\\phi' },
                { latex: 'F = m \\cdot a', display: 'F=m\\cdot a' },
                { latex: 'E = m \\cdot c^{2}', display: 'E=mc^2' },
                { latex: 'V = I \\cdot R', display: 'V=I\\cdot R' },
                { latex: 'v = \\lambda \\cdot f', display: 'v=\\lambda f' }
            ]
        },
        simbolos: {
            name: 'Griegos/Simb.',
            symbols: [
                { latex: '\\alpha', display: '\\alpha' },
                { latex: '\\beta', display: '\\beta' },
                { latex: '\\gamma', display: '\\gamma' },
                { latex: '\\delta', display: '\\delta' },
                { latex: '\\epsilon', display: '\\epsilon' },
                { latex: '\\theta', display: '\\theta' },
                { latex: '\\lambda', display: '\\lambda' },
                { latex: '\\mu', display: '\\mu' },
                { latex: '\\pi', display: '\\pi' },
                { latex: '\\rho', display: '\\rho' },
                { latex: '\\sigma', display: '\\sigma' },
                { latex: '\\phi', display: '\\phi' },
                { latex: '\\omega', display: '\\omega' },
                { latex: '\\Gamma', display: '\\Gamma' },
                { latex: '\\Delta', display: '\\Delta' },
                { latex: '\\Theta', display: '\\Theta' },
                { latex: '\\Lambda', display: '\\Lambda' },
                { latex: '\\Sigma', display: '\\Sigma' },
                { latex: '\\Phi', display: '\\Phi' },
                { latex: '\\Omega', display: '\\Omega' },
                { latex: '\\approx', display: '\\approx' },
                { latex: '\\neq', display: '\\neq' },
                { latex: '\\le', display: '\\le' },
                { latex: '\\ge', display: '\\ge' },
                { latex: '\\in', display: '\\in' },
                { latex: '\\notin', display: '\\notin' },
                { latex: '\\subset', display: '\\subset' },
                { latex: '\\cup', display: '\\cup' },
                { latex: '\\cap', display: '\\cap' },
                { latex: '\\emptyset', display: '\\emptyset' },
                { latex: '\\implies', display: '\\implies' },
                { latex: '\\iff', display: '\\iff' }
            ]
        }
    };

    // Construir pestañas y paneles de categorías dinámicamente
    let tabsHtml = `<div class="ares-wysiwyg-tabs nav-elite-tabs mb-3 d-flex p-1 rounded-pill ares-h-44">`;
    let panelsHtml = `<div class="ares-wysiwyg-panels">`;
    
    let isFirst = true;
    for (const key in categories) {
        tabsHtml += `
            <button type="button" class="nav-link-elite ${isFirst ? 'active' : ''} rounded-pill border-0" onclick="window.switchWysiwygTab('${key}')">
                ${categories[key].name}
            </button>
        `;
        
        panelsHtml += `
            <div id="wysiwyg-panel-${key}" class="ares-wysiwyg-panel ${isFirst ? 'active' : ''}">
                <div class="ares-wysiwyg-grid">
        `;
        
        categories[key].symbols.forEach((sym, idx) => {
            panelsHtml += `
                <button type="button" class="ares-keycap-btn" data-latex="${escapeHtmlAttr(sym.latex)}">
                    <span id="keycap-${key}-${idx}"></span>
                </button>
            `;
        });
        
        panelsHtml += `
                </div>
            </div>
        `;
        isFirst = false;
    }
    tabsHtml += `</div>`;
    panelsHtml += `</div>`;

    // Registrar función global de cambio de pestaña
    window.switchWysiwygTab = function(categoryKey) {
        const keys = Object.keys(categories);
        document.querySelectorAll('.ares-wysiwyg-tabs .nav-link-elite').forEach((btn, idx) => {
            btn.classList.toggle('active', keys[idx] === categoryKey);
        });
        document.querySelectorAll('.ares-wysiwyg-panel').forEach(panel => {
            panel.classList.toggle('active', panel.id === `wysiwyg-panel-${categoryKey}`);
        });
    };

    const htmlContent = `
        <div class="text-start p-1">
            <label for="swal-formula-input" class="form-label fw-bold text-uppercase fs-nano text-muted mb-2">Editor Visual Científico:</label>
            <math-field id="swal-formula-input" class="w-100 mb-3 fs-3 border rounded-3 p-3 text-primary bg-white shadow-sm ares-swal-field"></math-field>
            
            <div class="ares-wysiwyg-picker">
                <label class="form-label fw-bold text-uppercase fs-nano text-muted mb-3"><i class="bi bi-grid-3x3-gap-fill text-primary me-1"></i> Asistente de Símbolos y Ecuaciones:</label>
                ${tabsHtml}
                ${panelsHtml}
            </div>
            
            <div class="fs-nano text-muted italic text-center mt-3">
                <i class="bi bi-mouse2-fill text-primary me-1"></i> Haga clic en cualquier plantilla para insertarla. Use su teclado físico para completar valores.
            </div>
        </div>
    `;

    const procesarFormula = async () => {
        const result = await Swal.fire({
            width: '52rem',
            title: isEditing ? 'Modificar Ecuación' : 'Nueva Ecuación',
            html: htmlContent,
            showCancelButton: true,
            confirmButtonText: isEditing ? 'ACTUALIZAR' : 'INSERTAR',
            cancelButtonText: 'CANCELAR',
            confirmButtonColor: 'var(--el-primary)',
            cancelButtonColor: 'var(--el-border-strong)',
            didOpen: () => {
                const mathField = document.getElementById('swal-formula-input');
                if (mathField) {
                    // Asignar el valor actual en código LaTeX
                    mathField.value = currentFormula;

                    // Deshabilitar teclado virtual nativo y botón de menú
                    mathField.virtualKeyboardMode = "off";
                    mathField.showMenuIcon = false;

                    // Enfocar el editor visual
                    setTimeout(() => {
                        mathField.focus();
                    }, 100);
                }

                // Renderizar KaTeX en cada uno de los keycaps de símbolos
                for (const key in categories) {
                    categories[key].symbols.forEach((sym, idx) => {
                        const spanEl = document.getElementById(`keycap-${key}-${idx}`);
                        if (spanEl && typeof katex !== 'undefined') {
                            try {
                                katex.render(sym.display, spanEl, { throwOnError: false });
                            } catch (err) {
                                spanEl.textContent = sym.display;
                            }
                        }
                    });
                }

                // Asociar clic de botones para insertar el LaTeX en la posición del cursor de mathfield
                document.querySelectorAll('.ares-keycap-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const mf = document.getElementById('swal-formula-input');
                        if (mf) {
                            const latex = this.getAttribute('data-latex');
                            mf.insert(latex);
                            // Devolver el foco inmediatamente para continuar digitando o insertando
                            setTimeout(() => {
                                mf.focus();
                            }, 50);
                        }
                    });
                });
            },
            preConfirm: () => {
                const mathField = document.getElementById('swal-formula-input');
                const val = mathField ? mathField.value.trim() : '';
                if (!val) {
                    Swal.showValidationMessage('¡La ecuación no puede estar vacía!');
                }
                return val;
            }
        });

        if (result.isConfirmed) {
            // Reemplazar placeholders propietarios de MathLive por llaves vacías estándar de LaTeX
            const formulaValue = result.value.replace(/\\placeholder(\{\})?/g, '{}');

            const canvasModal = document.getElementById('modal-ares-canvas-fullscreen');
            const isCanvasOpen = (canvasModal && canvasModal.classList.contains('modal-canvas--open')) ||
                                 (document.getElementById('ares-canvas-board') !== null && !canvasModal) ||
                                 window.location.search.includes('p=ares_canvas_lab') ||
                                 window.location.search.includes('page=ares_canvas_lab');

            if (isEditing) {
                if (formulaEl.classList.contains('canvas-math-formula')) {
                    formulaEl.setAttribute('data-formula', formulaValue);
                    if (typeof katex !== 'undefined') {
                        try {
                            const html = katex.renderToString(formulaValue, { throwOnError: false });
                            formulaEl.innerHTML = html;
                        } catch (e) { formulaEl.textContent = formulaValue; }
                    } else {
                        formulaEl.textContent = formulaValue;
                    }
                    if (window.canvasSaveState) window.canvasSaveState();
                    if (window.canvasUpdateConnections) window.canvasUpdateConnections();
                } else if (formulaEl.classList.contains('type-formula')) {
                    formulaEl.setAttribute('data-formula', formulaValue);
                    const contentDiv = formulaEl.querySelector('.canvas-node-content');
                    if (contentDiv) {
                        if (typeof katex !== 'undefined') {
                            try {
                                const html = katex.renderToString(formulaValue, { throwOnError: false });
                                contentDiv.innerHTML = `<span class="canvas-math-formula-rendered">${html}</span>`;
                            } catch (e) {
                                contentDiv.textContent = formulaValue;
                            }
                        } else {
                            contentDiv.textContent = formulaValue;
                        }
                    }
                    if (window.canvasSaveState) window.canvasSaveState();
                    if (window.canvasUpdateConnections) window.canvasUpdateConnections();
                } else {
                    // Actualizar fórmula existente en Quill
                    formulaEl.setAttribute('data-value', formulaValue);
                    if (typeof katex !== 'undefined') {
                        try {
                            katex.render(formulaValue, formulaEl, { throwOnError: false });
                        } catch (e) { formulaEl.textContent = formulaValue; }
                    } else {
                        formulaEl.textContent = formulaValue;
                    }
                    if (quill) quill.update();
                }
            } else {
                if (isCanvasOpen) {
                    const selNode = document.querySelector('.canvas-node--selected');
                    if (selNode && selNode.classList.contains('type-text')) {
                        const contentDiv = selNode.querySelector('.canvas-node-content');
                        if (contentDiv) {
                            if (typeof katex !== 'undefined') {
                                try {
                                    const html = katex.renderToString(formulaValue, { throwOnError: false });
                                    const formulaHtml = `<span class="canvas-math-formula" contenteditable="false" data-formula="${formulaValue.replace(/"/g, '&quot;')}">${html}</span>&nbsp;`;
                                    contentDiv.focus();
                                    const selection = window.getSelection();
                                    if (selection.rangeCount > 0 && contentDiv.contains(selection.getRangeAt(0).commonAncestorContainer)) {
                                        const range = selection.getRangeAt(0);
                                        range.deleteContents();
                                        const tempDiv = document.createElement('div');
                                        tempDiv.innerHTML = formulaHtml;
                                        const nodeToInsert = tempDiv.firstChild;
                                        range.insertNode(nodeToInsert);
                                        range.setStartAfter(nodeToInsert);
                                        range.setEndAfter(nodeToInsert);
                                        selection.removeAllRanges();
                                        selection.addRange(range);
                                    } else {
                                        contentDiv.innerHTML += formulaHtml;
                                    }
                                    if (window.canvasSaveState) window.canvasSaveState();
                                    if (window.canvasUpdateConnections) window.canvasUpdateConnections();
                                } catch (err) {
                                    // Silent catch to prevent audit errors while preserving application flow
                                }
                            }
                        }
                    } else {
                        await Swal.fire({
                            title: 'Información',
                            text: 'Seleccione un bloque de texto activo en el lienzo para insertar la ecuación.',
                            icon: 'info',
                            confirmButtonColor: 'var(--el-primary)'
                        });
                    }
                } else {
                    // Insertar nueva fórmula en la posición del cursor en Quill
                    const range = quill.getSelection(true);
                    if (range) {
                        quill.insertEmbed(range.index, 'formula', formulaValue);
                        quill.setSelection(range.index + 1);
                    }
                }
            }
        }
    };
    procesarFormula();
}

window.toggleLatexHelp = function() {
    const panel = document.getElementById('latex-help-panel');
    if (panel) {
        panel.classList.toggle('d-none');
    }
};

window.abrirModalFormulaPremium = abrirModalFormulaPremium;
