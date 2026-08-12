/**
 * Motor Drag & Drop - Formatos de Matrícula (Elite Architecture)
 */

if (typeof UNIT_CONFIG === 'undefined') {
    var UNIT_CONFIG = {
        MM_TO_PX: 3.78,
        PX_TO_MM: 1 / 3.78,
        CANVAS_WIDTH_PX: 816,
        CANVAS_HEIGHT_PX: 1056,
        CANVAS_WIDTH_MM: 215.9,
        CANVAS_HEIGHT_MM: 279.4
    };
}

function mmToPixels(mm) {
    return mm * UNIT_CONFIG.MM_TO_PX;
}

function pixelsToMm(px) {
    return px * UNIT_CONFIG.PX_TO_MM;
}

function getMargensInPixels() {
    const sup = parseFloat(document.getElementById('formato-margen-superior')?.value || 20);
    const inf = parseFloat(document.getElementById('formato-margen-inferior')?.value || 20);
    const izq = parseFloat(document.getElementById('formato-margen-izquierdo')?.value || 20);
    const der = parseFloat(document.getElementById('formato-margen-derecho')?.value || 20);
    return {
        superior: mmToPixels(sup),
        inferior: mmToPixels(inf),
        izquierdo: mmToPixels(izq),
        derecho: mmToPixels(der)
    };
}

function getMargensInMilimeters() {
    return {
        superior: parseFloat(document.getElementById('formato-margen-superior')?.value || 20),
        inferior: parseFloat(document.getElementById('formato-margen-inferior')?.value || 20),
        izquierdo: parseFloat(document.getElementById('formato-margen-izquierdo')?.value || 20),
        derecho: parseFloat(document.getElementById('formato-margen-derecho')?.value || 20)
    };
}

function initFormatosBuilder() {

    const canvas = document.getElementById('canvas-builder');
    if (canvas) {
        // Remover listeners previos para evitar duplicados en recargas AJAX
        canvas.removeEventListener('dragenter', canvasDragEnter);
        canvas.removeEventListener('dragover', canvasDragOver);
        canvas.removeEventListener('drop', canvasDrop);
        canvas.removeEventListener('click', canvasClickSelection);
        canvas.removeEventListener('dblclick', canvasDobleClick);

        canvas.addEventListener('dragenter', canvasDragEnter);
        canvas.addEventListener('dragover', canvasDragOver);
        canvas.addEventListener('drop', canvasDrop);
        canvas.addEventListener('click', canvasClickSelection);
        canvas.addEventListener('dblclick', canvasDobleClick);

        ajustarAlturaLienzo();
        activeZone = 'body';
        updateZonesUI();
    }
}

function canvasClickSelection(e) {
    document.querySelectorAll('.canvas-block-wrapper').forEach(w => w.classList.remove('selected'));
    const wrapper = e.target.closest('.canvas-block-wrapper');
    if (wrapper) {
        wrapper.classList.add('selected');
        e.stopPropagation();
    }
}

// Limpiar selección al hacer clic fuera
document.addEventListener('click', function(e) {
    if (!e.target.closest('.canvas-block-wrapper') && !e.target.closest('.no-print-bar') && !e.target.closest('.modal') && !e.target.closest('.ares-toolbox')) {
        document.querySelectorAll('.canvas-block-wrapper').forEach(w => w.classList.remove('selected'));
    }
});

function dragStart(e, tipo, codigo, label) {
    const data = JSON.stringify({ tipo, codigo, label });
    e.dataTransfer.setData('text/plain', data);
    e.dataTransfer.effectAllowed = 'copy';
}

function canvasDragEnter(e) {
    e.preventDefault();
}

function canvasDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'copy';
    const canvas = document.getElementById('canvas-builder');
    canvas.classList.add('drag-over');
}

function canvasDrop(e) {
    e.preventDefault();
    const canvas = document.getElementById('canvas-builder');
    canvas.classList.remove('drag-over');

    const emptyState = document.getElementById('canvas-empty-state');
    if (emptyState) emptyState.remove();

    let tipo, codigo, label;
    try {
        const data = JSON.parse(e.dataTransfer.getData('text/plain'));
        tipo = data.tipo;
        codigo = data.codigo;
        label = data.label;
    } catch(err) {
        return;
    }

    if (tipo === 'bloque') {
        const rect = canvas.getBoundingClientRect();
        const dropY = (e.clientY - rect.top);
        const headerEndPx = mmToPixels(50);
        const footerStartPx = UNIT_CONFIG.CANVAS_HEIGHT_PX - mmToPixels(60);

        // Determinar en qué zona cae el drop
        let dropZone = 'body';
        if (dropY < headerEndPx) dropZone = 'header';
        else if (dropY > footerStartPx) dropZone = 'footer';

        // Validar que el drop esté en la zona activa
        if (activeZone !== 'body' && dropZone !== activeZone) {
            const zoneName = activeZone === 'header' ? 'MEMBRETE' : 'PIE DE PÁGINA';
            Swal.fire({
                icon: 'warning',
                title: 'Zona Bloqueada',
                text: `Solo puede soltar bloques en la zona activa (${zoneName}). Doble click para cambiar.`,
                toast: true,
                position: 'bottom-end',
                showConfirmButton: false,
                timer: 3000
            });
            return;
        }

        const x = (e.clientX - rect.left) - 150;
        const y = dropY - 30;
        insertarBloqueEnCanvas(codigo, label, x, y);
    } else if (tipo === 'variable') {
        Swal.fire({
            icon: 'info',
            title: 'Variable no insertada',
            text: 'Las variables (Chips) deben ser soltadas DENTRO de un bloque de "Párrafo de Texto".',
            toast: true,
            position: 'bottom-end',
            showConfirmButton: false,
            timer: 3000
        });
    }
}

if (typeof contadorBloquesAres === 'undefined') {
    var contadorBloquesAres = 0;
}
function generarIdUnicoBloque() {
    contadorBloquesAres++;
    return 'bloque_' + Date.now() + '_' + contadorBloquesAres + '_' + Math.random().toString(36).substring(2, 6);
}

function insertarBloqueEnCanvas(codigo, label, x, y) {
    const canvas = document.getElementById('canvas-builder');
    const idUnico = generarIdUnicoBloque();
    
    const wrapper = document.createElement('div');
    wrapper.className = 'canvas-block-wrapper animate__animated animate__fadeIn';
    wrapper.id = idUnico;
    wrapper.dataset.bloque = codigo;
    wrapper.dataset.zone = activeZone;  // Marcar zona a la que pertenece
    
    // Remover empty state si existe
    const emptyState = document.getElementById('canvas-empty-state');
    if (emptyState) emptyState.remove();

    const margenesMm = getMargensInMilimeters();
    const margenesPx = getMargensInPixels();

    const canvasW = canvas.offsetWidth || UNIT_CONFIG.CANVAS_WIDTH_PX;
    const canvasH = canvas.offsetHeight || UNIT_CONFIG.CANVAS_HEIGHT_PX;

    let estW = 300;
    if (codigo === 'logo' || codigo === 'qr_estudiante') estW = 110;
    if (codigo === 'foto_estudiante') estW = 120;
    if (codigo === 'titulo_colegio') estW = 400;
    if (codigo === 'lema_colegio') estW = 360;
    if (codigo === 'metadatos') estW = 500;
    if (codigo === 'ficha' || codigo === 'calificaciones' || codigo === 'texto_certificacion' || codigo === 'linea') estW = canvasW - margenesPx.izquierdo - margenesPx.derecho;
    if (codigo === 'firmas') estW = canvasW - margenesPx.izquierdo - margenesPx.derecho - 100;

    const maxAnchoSeguro = canvasW - margenesPx.izquierdo - margenesPx.derecho;
    if (estW > maxAnchoSeguro) {
        estW = maxAnchoSeguro;
    }

    const estH = codigo === 'foto_estudiante' ? 140 : (codigo === 'linea' ? 2 : 90);
    const headerEndPx = mmToPixels(50);
    const footerStartPx = UNIT_CONFIG.CANVAS_HEIGHT_PX - mmToPixels(60);

    if (x === undefined || y === undefined) {
        x = Math.max(margenesPx.izquierdo, Math.round((canvasW - estW) / 2));
        // Posición por defecto según zona activa
        if (activeZone === 'header') {
            y = 38;
        } else if (activeZone === 'footer') {
            y = footerStartPx + 15;
        } else {
            y = margenesPx.superior + 15;
        }
    }

    const maxLeft = Math.max(margenesPx.izquierdo, canvasW - margenesPx.derecho - estW);
    x = Math.max(margenesPx.izquierdo, Math.min(x, maxLeft));

    // Restricción de Y según zona
    if (activeZone === 'header') {
        y = Math.max(0, Math.min(y, headerEndPx - 10));
    } else if (activeZone === 'footer') {
        y = Math.max(footerStartPx, Math.min(y, UNIT_CONFIG.CANVAS_HEIGHT_PX - estH));
    } else {
        y = Math.max(headerEndPx, Math.min(y, footerStartPx));
    }

    x = Math.round(x);
    y = Math.round(y);

    wrapper.style.left = x + 'px';
    wrapper.style.top = y + 'px';
    wrapper.style.width = estW + 'px';

    wrapper.dataset.left_mm = pixelsToMm(x).toFixed(2);
    wrapper.dataset.top_mm = pixelsToMm(y).toFixed(2);
    wrapper.dataset.width_mm = pixelsToMm(estW).toFixed(2);
    
    if (codigo === 'linea') {
        wrapper.style.height = '1.5pt';
        wrapper.dataset.height = '1.5pt';
    }
    

    
    // Escuchar evento de arrastre libre
    wrapper.addEventListener('mousedown', iniciarArrastreBloque);
    
    // Controles flotantes
    const controls = `
        <div class="block-controls">
            <button type="button" onclick="moverBloqueArriba('${idUnico}')" title="Subir"><i class="bi bi-arrow-up"></i></button>
            <button type="button" onclick="moverBloqueAbajo('${idUnico}')" title="Bajar"><i class="bi bi-arrow-down"></i></button>
            <button type="button" onclick="abrirConfiguracionBloque('${idUnico}')" title="Ajustes"><i class="bi bi-gear"></i></button>
            <button type="button" onclick="eliminarBloque('${idUnico}')" class="text-danger" title="Eliminar"><i class="bi bi-trash"></i></button>
        </div>
    `;

    let contenido = '';
    
    if (codigo === 'linea') {
        contenido = `
            <div class="block-content-wysiwyg p-0" style="height: 100%; display: flex; align-items: center;">
                <div class="ares-linea-grafica" style="width: 100%; height: 100%; background-color: var(--el-primary); border-radius: 4px;"></div>
                <div class="bloque-backend-html d-none" data-type="linea">[[BLOQUE_LINEA]]</div>
            </div>
        `;

    } else if (codigo === 'texto') {
        contenido = `
            <div class="block-content-texto" contenteditable="true" 
                 placeholder="Escriba aquí el texto. Inserte variables con clic desde el panel izquierdo...">
            </div>
        `;
    } else if (codigo === 'logo') {
        wrapper.dataset.width = '120';
        contenido = `
            <div class="block-content-wysiwyg p-0 text-center">
                <img src="${window.SCHOOL_INFO.logo}" alt="Logo Institucional" class="ares-logo-cabecera" width="120">
                <div class="bloque-backend-html d-none" data-type="logo">[[BLOQUE_LOGO]]</div>
            </div>
        `;
    } else if (codigo === 'titulo_colegio') {
        contenido = `
            <div class="block-content-wysiwyg p-0 m-0 text-center">
                <h3 class="m-0 p-0 text-dark fw-bold text-uppercase ares-titulo-cabecera">${window.SCHOOL_INFO.name}</h3>
                <div class="bloque-backend-html d-none" data-type="titulo_colegio">[[BLOQUE_TITULO_COLEGIO]]</div>
            </div>
        `;
    } else if (codigo === 'lema_colegio') {
        contenido = `
            <div class="block-content-wysiwyg p-0 m-0 text-center">
                <p class="m-0 p-0 text-dark fw-bold small text-uppercase">${window.SCHOOL_INFO.motto}</p>
                <div class="bloque-backend-html d-none" data-type="lema_colegio">[[BLOQUE_LEMA_COLEGIO]]</div>
            </div>
        `;
    } else if (codigo === 'foto_estudiante') {
        contenido = `
            <div class="block-content-wysiwyg p-0 text-center">
                <div class="ares-foto-estudiante-placeholder mx-auto">
                    <i class="bi bi-person-fill fs-2"></i>
                    <span>FOTO 3X4</span>
                </div>
                <div class="bloque-backend-html d-none" data-type="foto_estudiante">[[BLOQUE_FOTO_ESTUDIANTE]]</div>
            </div>
        `;
    } else if (codigo === 'qr_estudiante') {
        contenido = `
            <div class="block-content-wysiwyg p-0 text-center">
                <div class="ares-qr-placeholder mx-auto p-2 border rounded bg-light d-inline-block">
                    <i class="bi bi-qr-code fs-1 text-dark"></i>
                    <div class="fs-nano text-uppercase text-muted fw-bold">QR VALIDACIÓN</div>
                </div>
                <div class="bloque-backend-html d-none" data-type="qr_estudiante">[[BLOQUE_QR_ESTUDIANTE]]</div>
            </div>
        `;
    } else if (codigo === 'texto_certificacion') {
        contenido = `
            <div class="block-content-wysiwyg p-3 text-justify">
                <p class="mb-2">El suscrito Rector y Secretario de la Institución Educativa <strong>${window.SCHOOL_INFO.name}</strong>, con licencia de funcionamiento oficial,</p>
                <p class="fw-bold text-center text-uppercase my-3">CERTIFICAN QUE:</p>
                <p class="mb-0">El(la) estudiante <strong>[[estudiante_nombre]]</strong> identificado(a) con documento N° <strong>[[estudiante_documento]]</strong> ha cursado y aprobado los requisitos institucionales para el año lectivo <strong>2026</strong>.</p>
                <div class="bloque-backend-html d-none" data-type="texto_certificacion">[[BLOQUE_TEXTO_CERTIFICACION]]</div>
            </div>
        `;
    } else if (codigo === 'metadatos') {
        contenido = `
            <div class="block-content-wysiwyg p-0 m-0 text-center">
                <h4 class="m-0 p-0 fw-bold text-uppercase text-secondary">MATRÍCULA AÑO ACADÉMICO 2026</h4>
                <div class="bloque-backend-html d-none" data-type="metadatos">[[BLOQUE_METADATOS]]</div>
            </div>
        `;
    } else if (codigo === 'ficha') {
        contenido = `
            <div class="block-content-wysiwyg p-0">
                <div class="table-responsive mb-2">
                    <table class="table table-bordered table-sm align-middle mb-0">
                        <tbody>
                            <tr class="ares-table-header opacity-75">
                                <th colspan="5" class="text-uppercase fs-nano py-1">I. DATOS PERSONALES DEL ESTUDIANTE</th>
                            </tr>
                            <tr>
                                <td class="bg-light fw-bold">Estudiante</td>
                                <td>JUAN SEBASTIÁN PÉREZ</td>
                                <td class="bg-light fw-bold">Documento</td>
                                <td>T.I. 1098765432</td>
                                <td rowspan="4" class="text-center p-1 bg-light ficha-foto-container">
                                    <div class="d-flex flex-column align-items-center justify-content-center border border-secondary border-dashed rounded ficha-foto-box">
                                        <i class="bi bi-person-bounding-box fs-4 mb-1"></i>
                                        <span>FOTO ALUMNO</span>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="bg-light fw-bold">F. Nacimiento</td>
                                <td>15/05/2012 (13 años)</td>
                                <td class="bg-light fw-bold">Lugar Nac.</td>
                                <td>BOGOTÁ D.C.</td>
                            </tr>
                            <tr>
                                <td class="bg-light fw-bold">RH / Sangre</td>
                                <td>O+ POSITIVO</td>
                                <td class="bg-light fw-bold">Género / Nac.</td>
                                <td>MASCULINO / COLOMBIANA</td>
                            </tr>
                            <tr>
                                <td class="bg-light fw-bold">Dirección</td>
                                <td>CALLE 123 # 45-67</td>
                                <td class="bg-light fw-bold">Contacto / Email</td>
                                <td>300 123 4567 | juan.perez@email.com</td>
                            </tr>
                            <tr class="ares-table-header opacity-75">
                                <th colspan="5" class="text-uppercase fs-nano py-1">II. DATOS DE MATRÍCULA Y REGISTRO</th>
                            </tr>
                            <tr>
                                <td class="bg-light fw-bold">Curso Asignado</td>
                                <td colspan="2">NOVENO A</td>
                                <td class="bg-light fw-bold">Folio Matrícula</td>
                                <td>FOLIO # 2026-084</td>
                            </tr>
                            <tr>
                                <td class="bg-light fw-bold">Fecha Registro</td>
                                <td colspan="2">01/02/2026</td>
                                <td class="bg-light fw-bold">Colegio Anterior</td>
                                <td>INSTITUCIÓN SAN JOSÉ</td>
                            </tr>
                            <tr class="ares-table-header opacity-75">
                                <th colspan="5" class="text-uppercase fs-nano py-1">III. INFORMACIÓN DE PADRES Y ACUDIENTES</th>
                            </tr>
                            <tr>
                                <td class="bg-light fw-bold">Padre / Acudiente</td>
                                <td colspan="2">ROBERTO PÉREZ (C.C. 79123456)</td>
                                <td class="bg-light fw-bold">Contacto / Ocupación</td>
                                <td>310 987 6543 | INGENIERO</td>
                            </tr>
                            <tr>
                                <td class="bg-light fw-bold">Dirección Padre</td>
                                <td colspan="2">AVENIDA COLÓN # 12-34</td>
                                <td class="bg-light fw-bold">Email / Nac.</td>
                                <td>roberto.perez@email.com | COLOMBIANA</td>
                            </tr>
                            <tr>
                                <td class="bg-light fw-bold">Madre / Acudiente</td>
                                <td colspan="2">MARÍA RODRÍGUEZ (C.C. 52987654)</td>
                                <td class="bg-light fw-bold">Contacto / Ocupación</td>
                                <td>315 456 7890 | DOCENTE</td>
                            </tr>
                            <tr>
                                <td class="bg-light fw-bold">Dirección Madre</td>
                                <td colspan="2">AVENIDA COLÓN # 12-34</td>
                                <td class="bg-light fw-bold">Email / Nac.</td>
                                <td>maria.rod@email.com | COLOMBIANA</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="bloque-backend-html d-none" data-type="ficha">[[BLOQUE_FICHA_ESTUDIANTE]]</div>
            </div>
        `;
    } else if (codigo === 'calificaciones') {
        contenido = `
            <div class="block-content-wysiwyg p-0">
                <div class="table-responsive mb-2">
                    <table class="table table-bordered table-striped table-sm text-center align-middle mb-0">
                        <thead class="ares-table-header">
                            <tr>
                                <th>Asignatura</th>
                                <th>Periodo 1</th>
                                <th>Periodo 2</th>
                                <th>Final</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td class="text-start">Matemáticas</td><td>4.5</td><td>4.8</td><td class="fw-bold">4.65</td></tr>
                            <tr><td class="text-start">Ciencias Naturales</td><td>4.0</td><td>4.2</td><td class="fw-bold">4.10</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="bloque-backend-html d-none" data-type="calificaciones">[[BLOQUE_TABLA_NOTAS]]</div>
            </div>
        `;
    } else if (codigo === 'firmas') {
        wrapper.dataset.columnas = '3';
        contenido = `
            <div class="block-content-wysiwyg p-0">
                <div class="d-flex justify-content-around align-items-end mt-4 pt-4 dynamic-firmas-container">
                    <!-- Columna 1: Estudiante y Acudiente vertical -->
                    <div class="firma-grupo-col">
                        <div class="text-center firma-item-canvas">
                            <div class="firma-divisor-canvas mx-auto"></div>
                            <div class="mb-0 fw-bold ares-firma-cargo fs-nano text-uppercase text-secondary" contenteditable="true" placeholder="Cargo">Firma del Estudiante</div>
                            <div class="mb-0 small text-muted ares-firma-nombre fs-nano" contenteditable="true" placeholder="Nombre (Opcional)">[Nombre Estudiante]</div>
                        </div>
                        <div class="text-center firma-item-canvas">
                            <div class="firma-divisor-canvas mx-auto"></div>
                            <div class="mb-0 fw-bold ares-firma-cargo fs-nano text-uppercase text-secondary" contenteditable="true" placeholder="Cargo">Firma del Acudiente</div>
                            <div class="mb-0 small text-muted ares-firma-nombre fs-nano" contenteditable="true" placeholder="Nombre (Opcional)">[Nombre Acudiente]</div>
                        </div>
                    </div>
                    <!-- Columna 2: Rector -->
                    <div class="firma-grupo-col">
                        <div class="text-center firma-item-canvas">
                            <div class="firma-divisor-canvas mx-auto"></div>
                            <div class="mb-0 fw-bold ares-firma-cargo fs-nano text-uppercase text-secondary" contenteditable="true" placeholder="Cargo">Rector Institucional</div>
                            <div class="mb-0 small text-muted ares-firma-nombre fs-nano" contenteditable="true" placeholder="Nombre (Opcional)">${window.SCHOOL_INFO.name ? 'RIGOBERTO ANDRÉS NUBIA' : '[Nombre Rector]'}</div>
                        </div>
                    </div>
                </div>
                <div class="bloque-backend-html d-none" data-type="firmas">[[BLOQUE_FIRMAS]]</div>
            </div>
        `;
    }

    wrapper.innerHTML = controls + contenido;
    canvas.appendChild(wrapper);
    if (typeof agregarNodosRedimension === 'function') {
        agregarNodosRedimension(wrapper);
    }

    // Auto-ajuste de ancho para bloques de línea única: contraer al contenido real
    const tiposSingleLine = ['titulo_colegio', 'lema_colegio', 'metadatos'];
    if (tiposSingleLine.indexOf(codigo) > -1) {
        function ajustarAnchoCabecera(el) {
            el.style.width = 'max-content';
            let anchoReal = el.offsetWidth; // Fuerza reflow
            if (anchoReal > 0) {
                el.style.width = anchoReal + 'px';
                el.dataset.width = anchoReal;
            }
        }
        ajustarAnchoCabecera(wrapper);
        document.fonts.ready.then(function() { ajustarAnchoCabecera(wrapper); });
    }
}

function insertarBloqueDesdeJSON(jsonBlock) {
    const canvas = document.getElementById('canvas-builder');
    document.getElementById('canvas-empty-state')?.remove();

    const left_mm = parseFloat(jsonBlock.left_mm) || 10;
    const top_mm = parseFloat(jsonBlock.top_mm) || 10;
    const width_mm = jsonBlock.width_mm || null;
    const zone = jsonBlock.zone || 'body';

    const left_px = mmToPixels(left_mm);
    const top_px = mmToPixels(top_mm);
    const width_px = width_mm ? mmToPixels(width_mm) : null;

    insertarBloqueEnCanvas(jsonBlock.type, null, left_px, top_px);

    const insertedNode = canvas.lastElementChild;

    insertedNode.dataset.zone = zone;  // Restaurar zona
    insertedNode.dataset.left_mm = left_mm.toFixed(2);
    insertedNode.dataset.top_mm = top_mm.toFixed(2);
    insertedNode.style.left = left_px + 'px';
    insertedNode.style.top = top_px + 'px';

    if (width_mm) {
        insertedNode.dataset.width_mm = width_mm.toFixed(2);
        insertedNode.style.width = width_px + 'px';
    }
    if (jsonBlock.height) {
        insertedNode.dataset.height = jsonBlock.height;
        insertedNode.style.height = jsonBlock.height + 'px';
    }
    
    // Si es texto libre, reemplazar el contenteditable
    if (jsonBlock.type === 'texto' && jsonBlock.content) {
        const cajaTexto = insertedNode.querySelector('.block-content-texto');
        if (cajaTexto) {
            cajaTexto.innerHTML = jsonBlock.content;
            
            // Re-hidratar variables (chips) si había texto puro
            // (Ya no es necesario hacer Regex, el JSON tiene los tags span.ares-variable-badge o texto puro)
        }
    }
    
    // Configuración específica de bloques avanzados
    if (jsonBlock.scale) insertedNode.dataset.scale = jsonBlock.scale;
    if (jsonBlock.size) {
        insertedNode.dataset.size = jsonBlock.size;
        const header = insertedNode.querySelector('.ares-titulo-cabecera, h3, h2, h4, .cabecera-plantilla__nombre');
        if (header) header.style.fontSize = jsonBlock.size + 'px';
    }
    
    if (jsonBlock.type === 'logo' && jsonBlock.width) {
        const img = insertedNode.querySelector('.ares-logo-cabecera');
        if (img) img.setAttribute('width', Math.max(30, parseInt(jsonBlock.width) - 20));
    }
    
    if (jsonBlock.type === 'calificaciones') {
        if (jsonBlock.diseno) insertedNode.dataset.diseno = jsonBlock.diseno;
        if (jsonBlock.filtro) insertedNode.dataset.filtro = jsonBlock.filtro;
        if (jsonBlock.columnas) insertedNode.dataset.columnas = jsonBlock.columnas;
    }

    if (jsonBlock.type === 'firmas') {
        const cols = jsonBlock.columnas || '3';
        insertedNode.dataset.columnas = cols;
        if (typeof actualizarBloqueFirmasCanvas === 'function') {
            actualizarBloqueFirmasCanvas(insertedNode, parseInt(cols), jsonBlock.firmas_data);
        }
    }
    
    // Si el bloque tiene HTML guardado (wysiwyg), restaurarlo exactamente (excepto ficha para forzar actualización visual)
    if (jsonBlock.content && jsonBlock.type !== 'texto' && jsonBlock.type !== 'ficha') {
        const wysiwyg = insertedNode.querySelector('.block-content-wysiwyg');
        if (wysiwyg) {
            wysiwyg.innerHTML = jsonBlock.content;
        }
    }

    // Auto-ajuste de ancho para bloques de línea única cargados desde JSON
    const tiposSingleLineJSON = ['titulo_colegio', 'lema_colegio', 'metadatos'];
    if (tiposSingleLineJSON.indexOf(jsonBlock.type) > -1) {
        function ajustarAnchoCabeceraJSON(el) {
            el.style.width = 'max-content';
            let anchoReal = el.offsetWidth; // Fuerza reflow
            if (anchoReal > 0) {
                el.style.width = anchoReal + 'px';
                el.dataset.width = anchoReal;
            }
        }
        ajustarAnchoCabeceraJSON(insertedNode);
        document.fonts.ready.then(function() { ajustarAnchoCabeceraJSON(insertedNode); });
    }
}

function eliminarBloque(id) {
    const bloque = document.getElementById(id);
    if(bloque) bloque.remove();
    chequearEmptyState();
}

function moverBloqueArriba(id) {
    const bloque = document.getElementById(id);
    if(bloque && bloque.previousElementSibling) {
        bloque.parentNode.insertBefore(bloque, bloque.previousElementSibling);
    }
}

function moverBloqueAbajo(id) {
    const bloque = document.getElementById(id);
    if(bloque && bloque.nextElementSibling) {
        bloque.parentNode.insertBefore(bloque.nextElementSibling, bloque);
    }
}

function chequearEmptyState() {
    const canvas = document.getElementById('canvas-builder');
    if (!canvas) return;
    const realBlocks = canvas.querySelectorAll('.canvas-block-wrapper');
    if (realBlocks.length === 0) {
        canvas.innerHTML = `
            <div class="canvas-empty-state text-muted text-center py-5" id="canvas-empty-state">
                <i class="bi bi-arrows-move fs-1 opacity-25 d-block mb-3"></i>
                <p class="mt-2 mb-0 fw-bold text-uppercase fs-nano">Arrastre los bloques desde el panel derecho hacia aquí</p>
            </div>
        `;
    }
}

// ---- LÓGICA DE VARIABLES DINÁMICAS (CLIC) ----

if (typeof lastSavedRange === 'undefined') {
    var lastSavedRange = null;
}

// Guardar la selección cada vez que se interactúa con un área editable
document.addEventListener('selectionchange', () => {
    try {
        const selection = window.getSelection();
        if (selection.rangeCount > 0) {
            const range = selection.getRangeAt(0);
            let node = range.commonAncestorContainer;
            if (node && node.nodeType === 3) node = node.parentNode;
            
            if (node && typeof node.closest === 'function' && node.closest('.block-content-texto')) {
                lastSavedRange = range.cloneRange();
            }
        }
    } catch (err) {
        // Ignorar errores de selección para no interrumpir otros eventos
    }
});

function insertarVariable(codigo, label) {
    if (!lastSavedRange) {
        Swal.fire({
            icon: 'info',
            title: 'Seleccione un área de texto',
            text: 'Haga clic dentro de un bloque de texto libre antes de insertar una variable.',
            confirmButtonText: 'Entendido'
        });
        return;
    }

    const html = `<span class="ares-variable-badge" contenteditable="false" data-var="${codigo}">[ ${label} ]</span>&nbsp;`;
    
    const selection = window.getSelection();
    selection.removeAllRanges();
    selection.addRange(lastSavedRange);
    
    document.execCommand('insertHTML', false, html);
    
    // Actualizar el rango guardado después de la inserción
    lastSavedRange = selection.getRangeAt(0).cloneRange();
}

// ---- PERSISTENCIA Y GUARDADO ----

async function guardarFormato(e) {
    e.preventDefault();
    
    const id = document.getElementById('formato-id').value;
    const nombre = document.getElementById('formato-nombre').value;
    const descripcion = document.getElementById('formato-descripcion').value;
    const margen_superior = document.getElementById('formato-margen-superior').value;
    const margen_inferior = document.getElementById('formato-margen-inferior').value;
    const margen_izquierdo = document.getElementById('formato-margen-izquierdo').value;
    const margen_derecho = document.getElementById('formato-margen-derecho').value;

    const canvas = document.getElementById('canvas-builder');
    
    if (canvas.querySelectorAll('.canvas-block-wrapper').length === 0) {
        Swal.fire('Aviso', 'El lienzo de construcción no puede estar vacío.', 'warning');
        return;
    }

    let htmlCompilado = '';
    const configJson = [];

    const bloques = canvas.querySelectorAll('.canvas-block-wrapper');
    bloques.forEach(bloque => {
        const tipoBloque = bloque.dataset.bloque;

        if (tipoBloque === 'texto') {
            const contenidoCaja = bloque.querySelector('.block-content-texto').cloneNode(true);

            const chips = contenidoCaja.querySelectorAll('.ares-variable-badge');
            chips.forEach(chip => {
                const varName = chip.dataset.var || '';
                const labelText = chip.textContent.trim();
                chip.removeAttribute('style');
                chip.removeAttribute('contenteditable');
                chip.className = 'ares-variable-badge';
                chip.setAttribute('data-var', varName);
                chip.textContent = labelText;
            });

            const left_mm = parseFloat(bloque.dataset.left_mm) || 10;
            const top_mm = parseFloat(bloque.dataset.top_mm) || 10;
            const width_mm = bloque.dataset.width_mm ? parseFloat(bloque.dataset.width_mm) : null;
            const height_mm = bloque.dataset.height ? parseFloat(bloque.dataset.height) : null;

            const widthAttr = width_mm !== null ? ` data-width_mm="${width_mm}"` : '';
            const heightAttr = height_mm !== null ? ` data-height="${height_mm}"` : '';
            const styleAttrs = `style="position:absolute;left:${left_mm}mm;top:${top_mm}mm;${width_mm !== null ? `width:${width_mm}mm;` : ''}${height_mm !== null ? `height:${height_mm}mm;` : ''}"`;
            htmlCompilado += `<div class="bloque-texto" data-left_mm="${left_mm}" data-top_mm="${top_mm}"${widthAttr}${heightAttr} ${styleAttrs}>${contenidoCaja.innerHTML}</div><br>`;

            configJson.push({
                type: 'texto',
                zone: bloque.dataset.zone || 'body',
                left_mm: left_mm,
                top_mm: top_mm,
                width_mm: width_mm,
                height: height_mm,
                content: contenidoCaja.innerHTML
            });

        } else {
            const htmlBackend = bloque.querySelector('.bloque-backend-html');
            if(htmlBackend) {
                const tipo = htmlBackend.dataset.type;
                const innerTag = htmlBackend.innerHTML;
                const left_mm = parseFloat(bloque.dataset.left_mm) || 10;
                const top_mm = parseFloat(bloque.dataset.top_mm) || 10;
                const width_mm = bloque.dataset.width_mm ? parseFloat(bloque.dataset.width_mm) : null;

                let extraAttrs = ` data-left_mm="${left_mm}" data-top_mm="${top_mm}"`;
                if (width_mm !== null) extraAttrs += ` data-width_mm="${width_mm}"`;
                if (bloque.dataset.height) extraAttrs += ` data-height="${bloque.dataset.height}"`;

                const stylePos = `position:absolute;left:${left_mm}mm;top:${top_mm}mm;${width_mm !== null ? `width:${width_mm}mm;` : ''}${bloque.dataset.height ? `height:${bloque.dataset.height}mm;` : ''}`;

                const jsonBlock = {
                    type: tipo,
                    zone: bloque.dataset.zone || 'body',
                    left_mm: left_mm,
                    top_mm: top_mm,
                    width_mm: width_mm,
                    height: bloque.dataset.height || null,
                    size: bloque.dataset.size || null,
                    content: bloque.querySelector('.block-content-wysiwyg') ? bloque.querySelector('.block-content-wysiwyg').innerHTML : null
                };

                if (tipo === 'titulo_colegio') {
                    const size = bloque.dataset.size || '20';
                    extraAttrs += ` data-size="${size}"`;
                } else if (tipo === 'metadatos') {
                    const size = bloque.dataset.size || '16';
                    extraAttrs += ` data-size="${size}"`;
                }

                // Configs específicas que usamos
                if (tipo === 'calificaciones') {
                    const diseno = bloque.dataset.diseno || 'elite';
                    const filtro = bloque.dataset.filtro || 'todas';
                    const columnas = bloque.dataset.columnas || 'materia,docente,definitiva,estado';
                    extraAttrs += ` data-diseno="${diseno}" data-filtro="${filtro}" data-columnas="${columnas}"`;
                    jsonBlock.diseno = diseno;
                    jsonBlock.filtro = filtro;
                    jsonBlock.columnas = columnas;
                } else if (tipo === 'firmas') {
                    const columnas = bloque.getAttribute('data-columnas') || bloque.dataset.columnas || '3';
                    extraAttrs += ` data-columnas="${columnas}"`;
                    jsonBlock.columnas = columnas;

                    // Extraer los textos específicos escritos en caliente por el usuario desde los firma-item-canvas
                    const fData = [];
                    bloque.querySelectorAll('.dynamic-firmas-container .firma-item-canvas').forEach(div => {
                        fData.push({
                            cargo: div.querySelector('.ares-firma-cargo')?.textContent.trim() || 'Firma',
                            nombre: div.querySelector('.ares-firma-nombre')?.textContent.trim() || ''
                        });
                    });
                    jsonBlock.firmas_data = fData;
                }

                htmlCompilado += `<div class="bloque-avanzado" data-tipo="${tipo}"${extraAttrs} style="${stylePos}">${innerTag}</div><br>`;
                configJson.push(jsonBlock);
            }
        }
    });

    const tipoDocumento = document.getElementById('formato-tipo-documento')?.value || 'matricula';
    const tamanoLienzo = document.getElementById('formato-tamano-lienzo')?.value || 'carta';

    const margenesMm = getMargensInMilimeters();

    // Agregar datos de zonas al JSON
    const zonesData = {
        header_limit_mm: parseFloat(canvas.dataset.zoneHeaderMm || 50),
        footer_limit_mm: parseFloat(canvas.dataset.zoneFooterMm || 219.4)
    };

    const formData = new FormData();
    formData.append('action', 'guardar');
    formData.append('id', id);
    formData.append('nombre', nombre);
    formData.append('descripcion', descripcion);
    formData.append('margen_superior', margenesMm.superior);
    formData.append('margen_inferior', margenesMm.inferior);
    formData.append('margen_izquierdo', margenesMm.izquierdo);
    formData.append('margen_derecho', margenesMm.derecho);
    formData.append('tipo_documento', tipoDocumento);
    formData.append('tamano_lienzo', tamanoLienzo);
    formData.append('contenido_html', htmlCompilado);
    formData.append('configuracion_json', JSON.stringify(configJson));
    formData.append('zonas_config', JSON.stringify(zonesData));
    formData.append('csrf_token', window.CSRF_TOKEN || '');

    const canvasBuilder = document.getElementById('canvas-builder');
    const canvasWidth = canvasBuilder.offsetWidth;
    const canvasHeight = canvasBuilder.offsetHeight;

    console.log('🔍 DEBUG GUARDAR FORMATO:');
    console.log('Nombre:', nombre);
    console.log('Descripción:', descripcion);
    console.log('Tipo Documento:', tipoDocumento);
    console.log('Tamaño Lienzo:', tamanoLienzo);
    console.log('Márgenes:', `Superior=${margen_superior}mm, Inferior=${margen_inferior}mm, Izquierdo=${margen_izquierdo}mm, Derecho=${margen_derecho}mm`);
    console.log('📐 CANVAS ANCHO:', canvasWidth + ' px');
    console.log('📐 CANVAS ALTO:', canvasHeight + ' px');
    console.log('Cantidad de bloques:', configJson.length);

    // Debug detallado de cada bloque
    console.log('📍 Posiciones de bloques guardadas:');
    configJson.forEach((bloque, idx) => {
        console.log(`  Bloque ${idx} (${bloque.type}): left_mm=${bloque.left_mm}, top_mm=${bloque.top_mm}, width_mm=${bloque.width_mm}, height=${bloque.height}`);
    });

    console.log('Estructura JSON completa:', configJson);
    console.log('HTML compilado (primeros 200 chars):', htmlCompilado.substring(0, 200));

    try {
        const res = await fetch('/sistema_escolar/php/logica/formatos_ajax.php', {
            method: 'POST',
            body: formData
        });

        if (!res.ok) {
            throw new Error(`HTTP ${res.status}: ${res.statusText}`);
        }

        const data = await res.json();
        console.log('✅ Respuesta del servidor:', data);

        if (data.status === 'success') {
            Swal.fire('¡Éxito!', data.message, 'success').then(() => {
                window.forceRefreshElite = true;
                if (typeof navegarModulo === 'function') {
                    navegarModulo('formatos_matricula');
                } else {
                    location.reload();
                }
            });
        } else {
            Swal.fire('Error', data.message || 'Error desconocido', 'error');
        }
    } catch (err) {
        console.error('Error al guardar:', err);
        Swal.fire('Error', 'No se pudo guardar la plantilla: ' + err.message, 'error');
    }
}

// ---- CONTROL DE ARRASTRE ABSOLUTO (DRAG & POSITION) ----
if (typeof bloqueArrastrando === 'undefined') {
    var bloqueArrastrando = null;
}

function iniciarArrastreBloque(e) {
    // Ignorar controles flotantes y redimensionador
    if (e.target.closest('.block-controls') || e.target.closest('.resize-handle')) {
        return;
    }
    
    // Si es un campo de texto editable, solo bloquear el arrastre si el usuario ya está escribiendo dentro
    const editable = e.target.closest('[contenteditable="true"]');
    if (editable && document.activeElement === editable) {
        return;
    }
    
    bloqueArrastrando = this;
    const rect = bloqueArrastrando.getBoundingClientRect();

    offsetX = (e.clientX - rect.left);
    offsetY = (e.clientY - rect.top);
    
    bloqueArrastrando.classList.add('dragging');
    
    document.addEventListener('mousemove', arrastrarBloque);
    document.addEventListener('mouseup', detenerArrastreBloque);
}

function arrastrarBloque(e) {
    if (!bloqueArrastrando) return;

    const canvas = document.getElementById('canvas-builder');
    const canvasRect = canvas.getBoundingClientRect();

    const margenesPx = getMargensInPixels();
    const headerEndPx = mmToPixels(50);
    const footerStartPx = UNIT_CONFIG.CANVAS_HEIGHT_PX - mmToPixels(60);

    let left = (e.clientX - canvasRect.left) - offsetX;
    let top = (e.clientY - canvasRect.top) - offsetY;

    const blockW = bloqueArrastrando.offsetWidth;
    const blockH = bloqueArrastrando.offsetHeight;

    const minLeft = margenesPx.izquierdo;
    const maxLeft = Math.max(margenesPx.izquierdo, canvas.offsetWidth - margenesPx.derecho - blockW);

    left = Math.max(minLeft, Math.min(left, maxLeft));
    top = Math.max(0, top);

    // Actualizar zona automáticamente según posición Y
    let newZone = 'body';
    if (top < headerEndPx) {
        newZone = 'header';
    } else if (top > footerStartPx) {
        newZone = 'footer';
    }
    bloqueArrastrando.dataset.zone = newZone;

    left = Math.round(left);
    top = Math.round(top);

    bloqueArrastrando.style.left = left + 'px';
    bloqueArrastrando.style.top = top + 'px';
    bloqueArrastrando.dataset.left_mm = pixelsToMm(left).toFixed(2);
    bloqueArrastrando.dataset.top_mm = pixelsToMm(top).toFixed(2);

    ajustarAlturaLienzo();
}

function detenerArrastreBloque() {
    if (bloqueArrastrando) {
        bloqueArrastrando.classList.remove('dragging');
        bloqueArrastrando = null;
    }
    document.removeEventListener('mousemove', arrastrarBloque);
    document.removeEventListener('mouseup', detenerArrastreBloque);
}

// --- MOTOR DE REDIMENSIÓN POR NODOS (ESQUINAS) ---
function iniciarRedimension(e, handleType, targetWrapper) {
    e.stopPropagation();
    e.preventDefault();
    const wrapper = (targetWrapper && targetWrapper.nodeType === Node.ELEMENT_NODE) ? targetWrapper : e.target.closest('.canvas-block-wrapper');
    if (!wrapper) return;
    
    const startWidth = wrapper.offsetWidth;
    const startHeight = wrapper.offsetHeight;
    const startX = e.clientX;
    const startY = e.clientY;
    const startLeft = parseFloat(wrapper.style.left) || 0;
    const startTop = parseFloat(wrapper.style.top) || 0;
    
    const wysiwygNodes = Array.from(wrapper.querySelectorAll('.block-content-wysiwyg, .block-content-wysiwyg *')).filter(n => !n.classList?.contains('bloque-backend-html'));
    wysiwygNodes.forEach(node => {
        if (!node.dataset.baseFontSize) {
            node.dataset.baseFontSize = parseFloat(window.getComputedStyle(node).fontSize) || 14;
        }
    });

    const canvas = document.getElementById('canvas-builder');

    function redimensionar(moveEvent) {
        const dx = (moveEvent.clientX - startX);
        const dy = (moveEvent.clientY - startY);

        const margenIzq = parseFloat(document.getElementById('formato-margen-izquierdo').value) || 20;
        const margenDer = parseFloat(document.getElementById('formato-margen-derecho').value) || 20;
        const canvasW = canvas.offsetWidth || 816;
        
        const maxRightPos = canvasW - margenDer;
        
        // Si es el logotipo, ajustar solo ancho (la imagen responde)
        if (wrapper.dataset.bloque === 'logo') {
            let newWidth = startWidth;
            if (handleType === 'br' || handleType === 'tr') {
                const limitWidth = maxRightPos - startLeft;
                newWidth = Math.min(limitWidth, startWidth + dx);
            } else if (handleType === 'bl' || handleType === 'tl') {
                const targetLeft = startLeft + dx;
                const newLeft = Math.max(margenIzq, targetLeft);
                const appliedDx = newLeft - startLeft;
                newWidth = startWidth - appliedDx;
                
                wrapper.style.left = newLeft + 'px';
                wrapper.dataset.left = Math.round(newLeft);
            }
            newWidth = Math.max(40, newWidth);
            wrapper.style.width = newWidth + 'px';
            wrapper.style.height = 'auto';
            wrapper.dataset.width = Math.round(newWidth);
            wrapper.dataset.height = '';
            
            const img = wrapper.querySelector('.ares-logo-cabecera');
            if (img) {
                img.setAttribute('width', Math.max(30, newWidth - 20));
            }
            return;
        }
        
        // --- Para todos los demás bloques: Comportamiento Vectorial (Texto Artístico) ---
        let newWidth = startWidth;
        if (handleType === 'br' || handleType === 'tr') {
            const limitWidth = maxRightPos - startLeft;
            newWidth = Math.min(limitWidth, startWidth + dx);
        } else if (handleType === 'bl' || handleType === 'tl') {
            const targetLeft = startLeft + dx;
            const newLeft = Math.max(margenIzq, targetLeft);
            const appliedDx = newLeft - startLeft;
            newWidth = startWidth - appliedDx;
            
            wrapper.style.left = newLeft + 'px';
            wrapper.dataset.left = Math.round(newLeft);
        }
        newWidth = Math.max(50, newWidth);
        
        // Escala delta (de este arrastre) multiplicada por la escala absoluta histórica
        const deltaScale = newWidth / startWidth;
        const startAbsoluteScale = parseFloat(wrapper.dataset.startAbsoluteScale) || 1.0;
        const absoluteScale = startAbsoluteScale * deltaScale;
        
        const scaleRatio = newWidth / startWidth;
        wysiwygNodes.forEach(node => {
            const baseSize = parseFloat(node.dataset.baseFontSize);
            const newSize = Math.max(4, baseSize * scaleRatio);
            node.style.setProperty('font-size', newSize + 'px', 'important');
        });

        wrapper.style.width = newWidth + 'px';
        wrapper.style.height = 'auto';
        wrapper.dataset.width_mm = pixelsToMm(newWidth).toFixed(2);
        
        // Estirar el papel del lienzo dinámicamente si el bloque al redimensionarse crece verticalmente
        ajustarAlturaLienzo();
    }
    
    function detenerRedimension() {
        document.removeEventListener('mousemove', redimensionar);
        document.removeEventListener('mouseup', detenerRedimension);
    }
    
    document.addEventListener('mousemove', redimensionar);
    document.addEventListener('mouseup', detenerRedimension);
}

function agregarNodosRedimension(wrapper) {
    wrapper.querySelectorAll('.resize-handle').forEach(h => h.remove());
    const handles = ['tl', 'tr', 'bl', 'br'];
    handles.forEach(type => {
        const handle = document.createElement('div');
        handle.className = `resize-handle resize-handle-${type}`;
        handle.addEventListener('mousedown', (e) => {
            e.stopPropagation();
            e.stopImmediatePropagation();
            iniciarRedimension(e, type, wrapper);
        });
        wrapper.appendChild(handle);
    });
}

// ==========================================
// 🚀 CATÁLOGO DE CAMPOS Y ESTRUCTURAS EN MODAL
// ==========================================

if (typeof activeRangeBeforeModal === 'undefined') {
    var activeRangeBeforeModal = null;
}
if (typeof activeEditableBeforeModal === 'undefined') {
    var activeEditableBeforeModal = null;
}

function abrirCatalogoVariables() {
    const selection = window.getSelection();
    if (selection.rangeCount > 0) {
        activeRangeBeforeModal = selection.getRangeAt(0).cloneRange();
        const node = activeRangeBeforeModal.startContainer;
        const parentElem = node.nodeType === Node.ELEMENT_NODE ? node : node.parentElement;
        activeEditableBeforeModal = parentElem ? parentElem.closest('.block-content-texto') : null;
    } else {
        activeRangeBeforeModal = null;
        activeEditableBeforeModal = null;
    }
    
    const modalEl = document.getElementById('modalCatalogoVariables');
    if (modalEl) {
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }
}

function seleccionarVariableCatalogo(codigo, label, esBloque = false) {
    // Cerrar modal de forma inmediata usando getOrCreateInstance
    const modalEl = document.getElementById('modalCatalogoVariables');
    if (modalEl) {
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        if (modal) modal.hide();
    }

    // Si es un componente o bloque estructural completo (ej. Ficha Estudiante, Tabla Notas, Logo, Firmas)
    if (esBloque) {
        insertarBloqueEnCanvas(codigo, label);
        return;
    }
    
    // Si es un Chip / Variable dinámica
    let targetEditable = activeEditableBeforeModal;
    
    const currentSelection = window.getSelection();
    if (!targetEditable && currentSelection.rangeCount > 0) {
        const node = currentSelection.getRangeAt(0).startContainer;
        const parentElem = node.nodeType === Node.ELEMENT_NODE ? node : node.parentElement;
        targetEditable = parentElem ? parentElem.closest('.block-content-texto') : null;
    }

    if (targetEditable) {
        // CASO A: Ya existe un cuadro de texto activo. Inyectar exclusivamente la insignia en el cursor (0 anidamiento).
        targetEditable.focus();
        if (activeRangeBeforeModal) {
            currentSelection.removeAllRanges();
            currentSelection.addRange(activeRangeBeforeModal);
        }
        const badgeHtml = `<span class="ares-variable-badge" contenteditable="false" data-var="${codigo}">[ ${label} ]</span>&nbsp;`;
        document.execCommand('insertHTML', false, badgeHtml);
    } else {
        // CASO B: NO hay cuadro de texto enfocado. Crear un nuevo bloque de texto autónomo en el canvas e inyectar la insignia.
        insertarBloqueEnCanvas('texto', 'Párrafo de Texto');
        const canvas = document.getElementById('canvas-builder');
        const ultimoBloque = canvas.querySelector('.canvas-block-wrapper:last-child');
        if (ultimoBloque) {
            const nuevoEditable = ultimoBloque.querySelector('.block-content-texto');
            if (nuevoEditable) {
                nuevoEditable.focus();
                nuevoEditable.innerHTML = `<span class="ares-variable-badge" contenteditable="false" data-var="${codigo}">[ ${label} ]</span>&nbsp;`;
            }
        }
    }
    recalcularCascadaBloques();
}

// 🛡️ ALGORITMO INTELIGENTE 2D ANTI-SOLAPAMIENTO EN EL BUILDER (RESPETA OBJETOS LADO A LADO)
function recalcularCascadaBloques() {
    const canvas = document.getElementById('canvas-builder');
    if (!canvas) return;
    const bloques = Array.from(canvas.querySelectorAll('.canvas-block-wrapper'));

    const grupoCabecera = ['logo', 'foto_estudiante', 'titulo_colegio', 'lema_colegio', 'metadatos'];

    bloques.forEach(actual => {
        const tipoA = actual.dataset.bloque || '';
        // Excluir elementos del membrete de empujar o ser empujados por cascada
        if (grupoCabecera.indexOf(tipoA) > -1) {
            return;
        }

        const leftA = parseFloat(actual.style.left) || 0;
        const widthA = actual.offsetWidth || parseFloat(actual.style.width) || 100;
        const rightA = leftA + widthA;

        const topA = parseFloat(actual.style.top) || 0;
        const heightA = actual.offsetHeight || 50;
        const bottomA = topA + heightA;

        bloques.forEach(siguiente => {
            if (actual === siguiente) return;

            const tipoB = siguiente.dataset.bloque || '';
            if (grupoCabecera.indexOf(tipoB) > -1) {
                return;
            }

            const leftB = parseFloat(siguiente.style.left) || 0;
            const widthB = siguiente.offsetWidth || parseFloat(siguiente.style.width) || 100;
            const rightB = leftB + widthB;

            const topB = parseFloat(siguiente.style.top) || 0;

            // Verificar si se cruzan en el eje X (Misma columna o franja horizontal)
            const seSolapanEnX = (leftA < rightB - 10) && (rightA > leftB + 10);

            // Solo si se solapan en X y B está dentro o debajo de la cota vertical de A
            if (seSolapanEnX && topB >= topA - 10 && topB < bottomA + 10) {
                const nuevoTop = Math.round(bottomA + 15);
                siguiente.style.top = nuevoTop + 'px';
                siguiente.dataset.top = nuevoTop;
            }
        });
    });
}

function cambiarTamanoLienzoBuilder(tamano) {
    const canvas = document.getElementById('canvas-builder');
    if (!canvas) return;
    
    canvas.classList.remove('ares-paper-sheet--carta', 'ares-paper-sheet--media_carta', 'ares-paper-sheet--carne_v', 'ares-paper-sheet--carne_h');
    const claseTamano = 'ares-paper-sheet--' + tamano;
    canvas.classList.add(claseTamano);
}

function actualizarFiltroCatalogoContextual(tipoDocumento) {
    // Resaltar u ocultar cards de variables según el contexto del documento
    const modalCatalogo = document.getElementById('modalCatalogoVariables');
    if (!modalCatalogo) return;
    
    // Todos visibles por defecto
    modalCatalogo.querySelectorAll('.ares-var-card').forEach(card => card.classList.remove('d-none'));
}

function ajustarAlturaLienzo() {
    const canvas = document.getElementById('canvas-builder');
    if (!canvas) return;
    
    // La hoja física en el editor mantiene su altura Carta estándar (1056px) para una maquetación fidedigna
    canvas.style.height = '1056px';
    actualizarZonaSeguraLienzo();
}

function actualizarZonaSeguraLienzo() {
    const canvas = document.getElementById('canvas-builder');
    if (!canvas) return;

    canvas.querySelector('.ares-safe-zone-guide')?.remove();

    const factor = 3.78;
    const margenSup = Math.round((parseFloat(document.getElementById('formato-margen-superior').value) || 20) * factor);
    const margenInf = Math.round((parseFloat(document.getElementById('formato-margen-inferior').value) || 20) * factor);
    const margenIzq = Math.round((parseFloat(document.getElementById('formato-margen-izquierdo').value) || 20) * factor);
    const margenDer = Math.round((parseFloat(document.getElementById('formato-margen-derecho').value) || 20) * factor);

    // Altura base física de una hoja Carta (1056px)
    const alturaPapelCarta = 1056;

    const guide = document.createElement('div');
    guide.className = 'ares-safe-zone-guide';
    guide.style.position = 'absolute';
    guide.style.top = margenSup + 'px';
    guide.style.left = margenIzq + 'px';
    guide.style.width = (canvas.offsetWidth - margenIzq - margenDer) + 'px';
    // La altura segura visual queda confinada a la hoja Carta real de forma estricta
    guide.style.height = (alturaPapelCarta - margenSup - margenInf) + 'px';
    guide.style.border = '1px dashed rgba(var(--el-primary-rgb), 0.35)';
    guide.style.pointerEvents = 'none';
    guide.style.zIndex = '1';
    
    canvas.appendChild(guide);

    // Dibujar la línea de advertencia física de corte de página (Page Cut Line) estilo Office
    canvas.querySelector('.ares-page-cut-line')?.remove();
    const cutLine = document.createElement('div');
    cutLine.className = 'ares-page-cut-line';
    cutLine.style.position = 'absolute';
    cutLine.style.top = (alturaPapelCarta - margenInf) + 'px';
    cutLine.style.left = '0';
    cutLine.style.width = '100%';
    cutLine.style.height = '0';
    cutLine.style.borderTop = '2px dashed rgba(220, 53, 69, 0.45)'; // Rojo tenue de advertencia
    cutLine.style.pointerEvents = 'none';
    cutLine.style.zIndex = '2';
    
    // Etiqueta flotante indicativa de Fin de Página
    const labelCut = document.createElement('span');
    labelCut.textContent = 'FIN DE PÁGINA 1 (LÍMITE DE IMPRESIÓN)';
    labelCut.style.position = 'absolute';
    labelCut.style.right = '15px';
    labelCut.style.top = '-16px';
    labelCut.style.fontSize = '9px';
    labelCut.style.fontWeight = 'bold';
    labelCut.style.color = 'rgba(220, 53, 69, 0.6)';
    labelCut.style.fontFamily = 'var(--el-font-institutional)';
    cutLine.appendChild(labelCut);

    canvas.appendChild(cutLine);
}

function actualizarBloqueFirmasCanvas(bloque, numColumnas, dataHeredada) {
    if (!bloque) return;
    const container = bloque.querySelector('.dynamic-firmas-container');
    if (!container) return;

    // Si no viene data heredada, leer textos actuales plano (máximo 4 firmas) para no perderlos
    const firmasViejas = [];
    container.querySelectorAll('.firma-item-canvas').forEach(div => {
        firmasViejas.push({
            cargo: div.querySelector('.ares-firma-cargo')?.textContent.trim() || '',
            nombre: div.querySelector('.ares-firma-nombre')?.textContent.trim() || ''
        });
    });

    // Definición por defecto (plana, se agrupa después)
    const defaultFirmas = [
        { cargo: 'Firma del Estudiante', nombre: '[Nombre Estudiante]' },
        { cargo: 'Firma del Acudiente', nombre: '[Nombre Acudiente]' },
        { cargo: 'Rector Institucional', nombre: window.SCHOOL_INFO.name ? 'RIGOBERTO ANDRÉS NUBIA' : '[Nombre Rector]' },
        { cargo: 'Secretaría Académica', nombre: '[Nombre Secretaria]' }
    ];

    // Obtener los datos finales combinando dataHeredada, firmasViejas y defaults
    const firmasFinales = [];
    for (let idx = 0; idx < 4; idx++) {
        let cargo = '';
        let nombre = '';

        if (dataHeredada && dataHeredada[idx]) {
            cargo = dataHeredada[idx].cargo;
            nombre = dataHeredada[idx].nombre;
        } else if (firmasViejas[idx] && firmasViejas[idx].cargo !== '') {
            cargo = firmasViejas[idx].cargo;
            nombre = firmasViejas[idx].nombre;
        } else {
            cargo = defaultFirmas[idx].cargo;
            nombre = defaultFirmas[idx].nombre;
        }

        firmasFinales.push({ cargo: cargo, nombre: nombre });
    }

    let html = '';

    // CONSTRUCCIÓN DEL LAYOUT AGRUPADO VERTICAL EN 2 COLUMNAS PRINCIPALES
    if (numColumnas === 3) {
        // 3 Firmas: 
        // Columna 1: Estudiante + Acudiente (vertical)
        // Columna 2: Rector (único)
        html += `
            <div class="firma-grupo-col">
                <div class="text-center firma-item-canvas">
                    <div class="firma-divisor-canvas mx-auto"></div>
                    <div class="mb-0 fw-bold ares-firma-cargo fs-nano text-uppercase text-secondary" contenteditable="true" placeholder="Cargo">${firmasFinales[0].cargo}</div>
                    <div class="mb-0 small text-muted ares-firma-nombre fs-nano" contenteditable="true" placeholder="Nombre (Opcional)">${firmasFinales[0].nombre}</div>
                </div>
                <div class="text-center firma-item-canvas">
                    <div class="firma-divisor-canvas mx-auto"></div>
                    <div class="mb-0 fw-bold ares-firma-cargo fs-nano text-uppercase text-secondary" contenteditable="true" placeholder="Cargo">${firmasFinales[1].cargo}</div>
                    <div class="mb-0 small text-muted ares-firma-nombre fs-nano" contenteditable="true" placeholder="Nombre (Opcional)">${firmasFinales[1].nombre}</div>
                </div>
            </div>
            <div class="firma-grupo-col">
                <div class="text-center firma-item-canvas">
                    <div class="firma-divisor-canvas mx-auto"></div>
                    <div class="mb-0 fw-bold ares-firma-cargo fs-nano text-uppercase text-secondary" contenteditable="true" placeholder="Cargo">${firmasFinales[2].cargo}</div>
                    <div class="mb-0 small text-muted ares-firma-nombre fs-nano" contenteditable="true" placeholder="Nombre (Opcional)">${firmasFinales[2].nombre}</div>
                </div>
            </div>
        `;
    } else if (numColumnas === 4) {
        // 4 Firmas:
        // Columna 1: Estudiante + Acudiente (vertical)
        // Columna 2: Rector + Secretaria (vertical)
        html += `
            <div class="firma-grupo-col">
                <div class="text-center firma-item-canvas">
                    <div class="firma-divisor-canvas mx-auto"></div>
                    <div class="mb-0 fw-bold ares-firma-cargo fs-nano text-uppercase text-secondary" contenteditable="true" placeholder="Cargo">${firmasFinales[0].cargo}</div>
                    <div class="mb-0 small text-muted ares-firma-nombre fs-nano" contenteditable="true" placeholder="Nombre (Opcional)">${firmasFinales[0].nombre}</div>
                </div>
                <div class="text-center firma-item-canvas">
                    <div class="firma-divisor-canvas mx-auto"></div>
                    <div class="mb-0 fw-bold ares-firma-cargo fs-nano text-uppercase text-secondary" contenteditable="true" placeholder="Cargo">${firmasFinales[1].cargo}</div>
                    <div class="mb-0 small text-muted ares-firma-nombre fs-nano" contenteditable="true" placeholder="Nombre (Opcional)">${firmasFinales[1].nombre}</div>
                </div>
            </div>
            <div class="firma-grupo-col">
                <div class="text-center firma-item-canvas">
                    <div class="firma-divisor-canvas mx-auto"></div>
                    <div class="mb-0 fw-bold ares-firma-cargo fs-nano text-uppercase text-secondary" contenteditable="true" placeholder="Cargo">${firmasFinales[2].cargo}</div>
                    <div class="mb-0 small text-muted ares-firma-nombre fs-nano" contenteditable="true" placeholder="Nombre (Opcional)">${firmasFinales[2].nombre}</div>
                </div>
                <div class="text-center firma-item-canvas">
                    <div class="firma-divisor-canvas mx-auto"></div>
                    <div class="mb-0 fw-bold ares-firma-cargo fs-nano text-uppercase text-secondary" contenteditable="true" placeholder="Cargo">${firmasFinales[3].cargo}</div>
                    <div class="mb-0 small text-muted ares-firma-nombre fs-nano" contenteditable="true" placeholder="Nombre (Opcional)">${firmasFinales[3].nombre}</div>
                </div>
            </div>
        `;
    }

    container.innerHTML = html;
}

// 🛡️ LIMPIEZA DE PEGADO (PASTE) EN EDITABLES DE TEXTO PARA PREVENIR FORMATOS ILÍCITOS
document.addEventListener('paste', function(e) {
    const target = e.target.closest('.block-content-texto, [contenteditable="true"]');
    if (target) {
        e.preventDefault();
        const text = (e.originalEvent || e).clipboardData.getData('text/plain');
        document.execCommand('insertText', false, text);
    }
});

// ════════════════════════════════════════════════════════════════════════════════════════
// 📍 SISTEMA DE 3 ZONAS - Header, Body, Footer con toggle doble click
// ════════════════════════════════════════════════════════════════════════════════════════

let activeZone = 'body';  // 'header', 'body', 'footer'
let zoneOverlays = {
    header: null,
    body: null,
    footer: null
};

function canvasDobleClick(e) {
    const wrapper = e.target.closest('.canvas-block-wrapper');
    if (wrapper) return;

    const canvas = document.getElementById('canvas-builder');
    if (!canvas) return;

    const rect = canvas.getBoundingClientRect();
    const clickY = e.clientY - rect.top;
    const margenes = getMargensInPixels();
    const headerEndPx = mmToPixels(50);
    const footerStartPx = UNIT_CONFIG.CANVAS_HEIGHT_PX - mmToPixels(60);

    let targetZone = 'body';
    if (clickY < headerEndPx) {
        targetZone = 'header';
    } else if (clickY > footerStartPx) {
        targetZone = 'footer';
    }

    // Toggle: si la zona está activa, desactivar; si no, activar
    if (activeZone === targetZone) {
        activeZone = 'body';
    } else {
        activeZone = targetZone;
    }

    updateZonesUI();
    console.log(`✓ Zona activa: ${activeZone}`);
}

function updateZonesUI() {
    const canvas = document.getElementById('canvas-builder');
    const headerEndPx = mmToPixels(50);
    const footerStartPx = UNIT_CONFIG.CANVAS_HEIGHT_PX - mmToPixels(60);

    // Remover overlays anteriores
    Object.values(zoneOverlays).forEach(ol => ol?.remove());
    zoneOverlays = { header: null, body: null, footer: null };

    canvas.style.position = 'relative';

    // Crear overlay para zona ACTIVA
    const createOverlay = (zone, top, height, bgColor, label) => {
        const overlay = document.createElement('div');
        overlay.className = 'zone-overlay';
        overlay.dataset.zone = zone;
        overlay.style.cssText = `
            position: absolute;
            top: ${top}px;
            left: 0;
            width: 100%;
            height: ${height}px;
            background: ${bgColor};
            z-index: 500;
            border: 2px dashed #666;
            pointer-events: none;
            display: flex;
            align-items: center;
            justify-content: center;
        `;

        const label_el = document.createElement('span');
        label_el.style.cssText = `
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            pointer-events: none;
        `;
        label_el.textContent = label;
        overlay.appendChild(label_el);

        return overlay;
    };

    // Header overlay (si está activo)
    if (activeZone === 'header') {
        const headerOverlay = createOverlay('header', 0, headerEndPx, 'rgba(231, 76, 60, 0.15)', 'MEMBRETE ACTIVO');
        canvas.appendChild(headerOverlay);
        zoneOverlays.header = headerOverlay;
    }

    // Body overlay (si está activo O si es la única zona)
    if (activeZone === 'body' || activeZone === 'body') {
        const bodyHeight = footerStartPx - headerEndPx;
        const bodyOverlay = activeZone === 'body'
            ? createOverlay('body', headerEndPx, bodyHeight, 'rgba(255, 255, 255, 0)', '')
            : null;
        if (bodyOverlay) {
            canvas.appendChild(bodyOverlay);
            zoneOverlays.body = bodyOverlay;
        }
    }

    // Footer overlay (si está activo)
    if (activeZone === 'footer') {
        const footerOverlay = createOverlay('footer', footerStartPx, UNIT_CONFIG.CANVAS_HEIGHT_PX - footerStartPx, 'rgba(52, 152, 219, 0.15)', 'PIE DE PÁGINA ACTIVO');
        canvas.appendChild(footerOverlay);
        zoneOverlays.footer = footerOverlay;
    }

    // Mostrar/ocultar bloques según zona activa
    document.querySelectorAll('.canvas-block-wrapper').forEach(bloque => {
        const bloqueZone = bloque.dataset.zone || 'body';
        const isActive = (activeZone === 'body' && bloqueZone === 'body') ||
                         (activeZone === 'header' && bloqueZone === 'header') ||
                         (activeZone === 'footer' && bloqueZone === 'footer');

        // Remover clases de bloqueo antiguas
        bloque.classList.remove('header-locked', 'body-locked');

        if (isActive) {
            // Zona activa: completamente visible y editable
            bloque.style.opacity = '';
            bloque.style.pointerEvents = 'auto';
        } else {
            // Zona inactiva: aplicar clase de atenuación
            bloque.classList.add('header-locked');
            bloque.style.pointerEvents = 'none';
        }
    });
}
