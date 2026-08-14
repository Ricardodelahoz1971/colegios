/**
 * Motor Drag & Drop - Formatos de Matrícula (Elite Architecture)
 * 100% MILÍMETROS - Sin conversiones px
 */

if (typeof UNIT_CONFIG === 'undefined') {
    var UNIT_CONFIG = {
        CANVAS_WIDTH_MM: 215.9,
        CANVAS_HEIGHT_MM: 279.4,
        HEADER_LIMIT_MM: 50,
        FOOTER_START_MM: 219.4
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

    const data = JSON.parse(e.dataTransfer.getData('text/plain'));
    const rect = canvas.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;

    const dropZone = determinarZona(y);

    if (activeZone !== 'body' && dropZone !== activeZone) {
        const zoneName = activeZone === 'header' ? 'CABECERA' : 'PIE DE PÁGINA';
        Swal.fire('Zona Bloqueada', `Estás en ${zoneName}. Arrastra bloques solo dentro de la zona activa.`, 'warning');
        return;
    }

    insertarBloqueEnCanvas(data.tipo, data.label, x, y);
}

function determinarZona(y) {
    const headerEndMM = UNIT_CONFIG.HEADER_LIMIT_MM;
    const footerStartMM = UNIT_CONFIG.FOOTER_START_MM;
    const scale = getCanvasScale();

    const headerEndPx = headerEndMM * scale;
    const footerStartPx = footerStartMM * scale;

    if (y < headerEndPx) return 'header';
    if (y > footerStartPx) return 'footer';
    return 'body';
}

function getCanvasScale() {
    const canvas = document.getElementById('canvas-builder');
    if (!canvas) return 1;
    return canvas.offsetWidth / UNIT_CONFIG.CANVAS_WIDTH_MM;
}

function insertarBloqueEnCanvas(codigo, label, xPx, yPx, skipZoneRestrictions = false) {
    const canvas = document.getElementById('canvas-builder');
    const idUnico = generarIdUnicoBloque();
    const scale = getCanvasScale();

    const wrapper = document.createElement('div');
    wrapper.className = 'canvas-block-wrapper animate__animated animate__fadeIn';
    wrapper.id = idUnico;
    wrapper.dataset.bloque = codigo;
    wrapper.dataset.zone = activeZone;

    const emptyState = document.getElementById('canvas-empty-state');
    if (emptyState) emptyState.remove();

    const margenes = getMargensInMilimeters();
    const headerEndMM = UNIT_CONFIG.HEADER_LIMIT_MM;
    const footerStartMM = UNIT_CONFIG.FOOTER_START_MM;

    let estW_mm = 50;
    if (codigo === 'logo' || codigo === 'qr_estudiante') estW_mm = 30;
    if (codigo === 'foto_estudiante') estW_mm = 32;
    if (codigo === 'titulo_colegio') estW_mm = 105;
    if (codigo === 'lema_colegio') estW_mm = 95;
    if (codigo === 'metadatos') estW_mm = 132;
    if (codigo === 'ficha' || codigo === 'calificaciones' || codigo === 'texto_certificacion' || codigo === 'linea') {
        estW_mm = UNIT_CONFIG.CANVAS_WIDTH_MM - margenes.izquierdo - margenes.derecho;
    }
    if (codigo === 'firmas') {
        estW_mm = UNIT_CONFIG.CANVAS_WIDTH_MM - margenes.izquierdo - margenes.derecho - 26;
    }

    const maxAnchoSeguro = UNIT_CONFIG.CANVAS_WIDTH_MM - margenes.izquierdo - margenes.derecho;
    if (estW_mm > maxAnchoSeguro) {
        estW_mm = maxAnchoSeguro;
    }

    let estH_mm = codigo === 'foto_estudiante' ? 37 : (codigo === 'linea' ? 0.5 : 24);

    // Convertir posición de px a mm
    let left_mm = (xPx / scale);
    let top_mm = (yPx / scale);

    if (xPx === undefined || yPx === undefined) {
        left_mm = (UNIT_CONFIG.CANVAS_WIDTH_MM - estW_mm) / 2;
        if (activeZone === 'header') {
            top_mm = 5;
        } else if (activeZone === 'footer') {
            top_mm = footerStartMM + 4;
        } else {
            top_mm = margenes.superior + 4;
        }
    }

    const maxLeft = Math.max(margenes.izquierdo, UNIT_CONFIG.CANVAS_WIDTH_MM - margenes.derecho - estW_mm);
    left_mm = Math.max(margenes.izquierdo, Math.min(left_mm, maxLeft));

    if (!skipZoneRestrictions) {
        if (activeZone === 'header') {
            top_mm = Math.max(0, Math.min(top_mm, headerEndMM - 2.65));
        } else if (activeZone === 'footer') {
            top_mm = Math.max(footerStartMM, Math.min(top_mm, UNIT_CONFIG.CANVAS_HEIGHT_MM - estH_mm));
        } else {
            top_mm = Math.max(headerEndMM, Math.min(top_mm, footerStartMM));
        }
    }

    // Guardar en MM directamente
    wrapper.dataset.left_mm = left_mm.toFixed(2);
    wrapper.dataset.top_mm = top_mm.toFixed(2);
    wrapper.dataset.width_mm = estW_mm.toFixed(2);

    // Aplicar SOLO en MM al CSS (no px)
    wrapper.style.left = left_mm + 'mm';
    wrapper.style.top = top_mm + 'mm';
    wrapper.style.width = estW_mm + 'mm';

    if (codigo === 'linea') {
        wrapper.style.height = estH_mm + 'mm';
        wrapper.dataset.height = estH_mm + 'mm';
    }

    wrapper.addEventListener('mousedown', iniciarArrastreBloque);

    const controls = `
        <div class="block-controls">
            <button type="button" onclick="moverBloqueArriba('${idUnico}')" title="Subir"><i class="bi bi-arrow-up"></i></button>
            <button type="button" onclick="moverBloqueAbajo('${idUnico}')" title="Bajar"><i class="bi bi-arrow-down"></i></button>
            <button type="button" onclick="abrirConfiguracionBloque('${idUnico}')" title="Ajustes"><i class="bi bi-gear"></i></button>
            <button type="button" onclick="eliminarBloque('${idUnico}')" class="text-danger" title="Eliminar"><i class="bi bi-trash"></i></button>
        </div>
    `;

    const headerHTML = {
        'logo': '<img class="ares-logo-cabecera" src="/sistema_escolar/perseus.png" width="120" alt="Logo" />',
        'titulo_colegio': '<h3 class="ares-titulo-cabecera">Nombre del Colegio</h3>',
        'lema_colegio': '<p class="ares-lema-cabecera">Lema Institucional</p>',
        'metadatos': '<h4>Año Lectivo 2024-2025</h4>'
    };

    const contentHTML = {
        'texto': '<div class="block-content-texto" contenteditable="true">Texto libre aquí</div>',
        'qr_estudiante': '<div class="qr-placeholder" style="width: 100%; height: 100%; background: #f0f0f0; border: 1px dashed #ccc;"></div>',
        'foto_estudiante': '<div class="foto-placeholder" style="width: 100%; height: 100%; background: #e8e8e8; border: 1px solid #999;"></div>',
        'linea': '<div class="ares-linea-grafica" style="width: 100%; height: 100%; background-color: var(--el-primary);"></div>',
        'ficha': '<div class="block-content-wysiwyg" contenteditable="true"><p>Contenido de ficha</p></div>',
        'calificaciones': '<div class="block-content-wysiwyg" contenteditable="false"><table><tr><td>Materia</td><td>Calificación</td></tr></table></div>',
        'firmas': '<div class="dynamic-firmas-container"></div>',
        'texto_certificacion': '<div class="block-content-wysiwyg" contenteditable="true"><p>Texto certificación</p></div>'
    };

    const html = headerHTML[codigo] || contentHTML[codigo] || '<div class="block-placeholder">Bloque</div>';

    wrapper.innerHTML = `
        <div class="block-inner">
            ${html}
            ${controls}
            <div class="bloque-backend-html d-none" data-type="${codigo}"></div>
        </div>
    `;

    if (codigo === 'firmas') {
        const cols = 3;
        const container = wrapper.querySelector('.dynamic-firmas-container');
        for (let i = 0; i < cols; i++) {
            const firmaDiv = document.createElement('div');
            firmaDiv.className = 'firma-item-canvas';
            firmaDiv.innerHTML = `
                <div class="ares-firma-cargo" contenteditable="true">Cargo</div>
                <div style="height: 40px; border-top: 1px solid #000;"></div>
                <div class="ares-firma-nombre" contenteditable="true">Nombre</div>
            `;
            container.appendChild(firmaDiv);
        }
        wrapper.dataset.columnas = cols;
    }

    canvas.appendChild(wrapper);
    chequearEmptyState();
    updateZonesUI();
}

function insertarBloqueDesdeJSON(jsonBlock) {
    const canvas = document.getElementById('canvas-builder');
    document.getElementById('canvas-empty-state')?.remove();

    const left_mm = parseFloat(jsonBlock.left_mm) || parseFloat(jsonBlock.left) || 10;
    const top_mm = parseFloat(jsonBlock.top_mm) || parseFloat(jsonBlock.top) || 10;
    const width_mm = parseFloat(jsonBlock.width_mm) || parseFloat(jsonBlock.width) || null;
    const height_mm = parseFloat(jsonBlock.height_mm) || parseFloat(jsonBlock.height) || null;
    const zone = jsonBlock.zone || 'body';

    const scale = getCanvasScale();
    const left_px = left_mm * scale;
    const top_px = top_mm * scale;

    const previousActiveZone = activeZone;
    activeZone = jsonBlock.zone || 'body';

    insertarBloqueEnCanvas(jsonBlock.type, null, left_px, top_px, true);

    activeZone = previousActiveZone;

    const insertedNode = canvas.lastElementChild;

    insertedNode.dataset.zone = zone;
    insertedNode.dataset.left_mm = left_mm.toFixed(2);
    insertedNode.dataset.top_mm = top_mm.toFixed(2);

    if (width_mm) {
        insertedNode.dataset.width_mm = width_mm.toFixed(2);
        insertedNode.style.width = width_mm + 'mm';
    }
    if (height_mm) {
        insertedNode.dataset.height_mm = height_mm.toFixed(2);
        insertedNode.style.height = height_mm + 'mm';
    }

    if (jsonBlock.type === 'texto' && jsonBlock.content) {
        const cajaTexto = insertedNode.querySelector('.block-content-texto');
        if (cajaTexto) {
            cajaTexto.innerHTML = jsonBlock.content;
        }
    }

    if (jsonBlock.scale) insertedNode.dataset.scale = jsonBlock.scale;
    if (jsonBlock.size) {
        insertedNode.dataset.size = jsonBlock.size;
        const header = insertedNode.querySelector('.ares-titulo-cabecera, h3, h2, h4, .cabecera-plantilla__nombre');
        if (header) header.style.fontSize = jsonBlock.size + 'px';
    }

    if (jsonBlock.type === 'logo' && jsonBlock.width_mm) {
        const img = insertedNode.querySelector('.ares-logo-cabecera');
        if (img) img.setAttribute('width', Math.max(30, jsonBlock.width_mm * 3.78 - 20));
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

    if (jsonBlock.content && jsonBlock.type !== 'texto' && jsonBlock.type !== 'ficha') {
        const wysiwyg = insertedNode.querySelector('.block-content-wysiwyg');
        if (wysiwyg) {
            wysiwyg.innerHTML = jsonBlock.content;
        }
    }

    const tiposSingleLine = ['titulo_colegio', 'lema_colegio', 'metadatos'];
    if (tiposSingleLine.indexOf(jsonBlock.type) > -1) {
        function ajustarAnchoJSON(el) {
            const s = getCanvasScale();
            el.style.width = 'max-content';
            let anchoRealMm = el.offsetWidth / s;
            if (anchoRealMm > 0) {
                el.style.width = anchoRealMm + 'mm';
                el.dataset.width_mm = anchoRealMm.toFixed(2);
            }
        }
        ajustarAnchoJSON(insertedNode);
        document.fonts.ready.then(() => ajustarAnchoJSON(insertedNode));
    }
}

function eliminarBloque(id) {
    const bloque = document.getElementById(id);
    if (bloque) bloque.remove();
    chequearEmptyState();
}

function moverBloqueArriba(id) {
    const bloque = document.getElementById(id);
    if (bloque && bloque.previousElementSibling) {
        bloque.parentNode.insertBefore(bloque, bloque.previousElementSibling);
    }
}

function moverBloqueAbajo(id) {
    const bloque = document.getElementById(id);
    if (bloque && bloque.nextElementSibling) {
        bloque.parentNode.insertBefore(bloque.nextElementSibling, bloque);
    }
}

function generarIdUnicoBloque() {
    return 'bloque-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
}

function chequearEmptyState() {
    const canvas = document.getElementById('canvas-builder');
    const bloques = canvas.querySelectorAll('.canvas-block-wrapper');
    const emptyState = document.getElementById('canvas-empty-state');

    if (bloques.length === 0 && !emptyState) {
        canvas.innerHTML += `
            <div class="canvas-empty-state text-muted text-center py-5" id="canvas-empty-state">
                <div class="icon-circle-elite bg-primary bg-opacity-10 text-primary mx-auto mb-3 ares-icon-lg">
                    <i class="bi bi-layout-text-window"></i>
                </div>
                <p class="mt-2 mb-0 fw-bold text-uppercase fs-nano">Lienzo Técnico A4</p>
                <p class="small text-muted mt-1">Arrastre los bloques desde el panel izquierdo hacia este documento</p>
            </div>
        `;
    }
}

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
        const left_mm = parseFloat(bloque.dataset.left_mm) || 10;
        const top_mm = parseFloat(bloque.dataset.top_mm) || 10;
        const width_mm = bloque.dataset.width_mm ? parseFloat(bloque.dataset.width_mm) : null;
        const height_mm = bloque.dataset.height_mm ? parseFloat(bloque.dataset.height_mm) : null;

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

            const widthAttr = width_mm !== null ? ` data-width_mm="${width_mm}"` : '';
            const heightAttr = height_mm !== null ? ` data-height_mm="${height_mm}"` : '';
            const stylePos = `position:absolute;left:${left_mm}mm;top:${top_mm}mm;${width_mm !== null ? `width:${width_mm}mm;` : ''}${height_mm !== null ? `height:${height_mm}mm;` : ''}`;
            htmlCompilado += `<div class="bloque-texto" data-left_mm="${left_mm}" data-top_mm="${top_mm}"${widthAttr}${heightAttr} style="${stylePos}">${contenidoCaja.innerHTML}</div><br>`;

            configJson.push({
                type: 'texto',
                zone: bloque.dataset.zone || 'body',
                left_mm: left_mm,
                top_mm: top_mm,
                width_mm: width_mm,
                height_mm: height_mm,
                content: contenidoCaja.innerHTML
            });

        } else {
            const htmlBackend = bloque.querySelector('.bloque-backend-html');
            if (htmlBackend) {
                const tipo = htmlBackend.dataset.type;
                const innerTag = htmlBackend.innerHTML;

                let extraAttrs = ` data-left_mm="${left_mm}" data-top_mm="${top_mm}"`;
                if (width_mm !== null) extraAttrs += ` data-width_mm="${width_mm}"`;
                if (height_mm !== null) extraAttrs += ` data-height_mm="${height_mm}"`;

                const stylePos = `position:absolute;left:${left_mm}mm;top:${top_mm}mm;${width_mm !== null ? `width:${width_mm}mm;` : ''}${height_mm !== null ? `height:${height_mm}mm;` : ''}`;

                const jsonBlock = {
                    type: tipo,
                    zone: bloque.dataset.zone || 'body',
                    left_mm: left_mm,
                    top_mm: top_mm,
                    width_mm: width_mm,
                    height_mm: height_mm,
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

    const margenes = getMargensInMilimeters();

    const zonesData = {
        header_limit_mm: UNIT_CONFIG.HEADER_LIMIT_MM,
        footer_limit_mm: UNIT_CONFIG.FOOTER_START_MM
    };

    const formData = new FormData();
    formData.append('action', 'guardar');
    formData.append('id', id);
    formData.append('nombre', nombre);
    formData.append('descripcion', descripcion);
    formData.append('margen_superior', margenes.superior);
    formData.append('margen_inferior', margenes.inferior);
    formData.append('margen_izquierdo', margenes.izquierdo);
    formData.append('margen_derecho', margenes.derecho);
    formData.append('tipo_documento', tipoDocumento);
    formData.append('tamano_lienzo', tamanoLienzo);
    formData.append('contenido_html', htmlCompilado);
    formData.append('configuracion_json', JSON.stringify(configJson));
    formData.append('zonas_config', JSON.stringify(zonesData));
    formData.append('csrf_token', window.CSRF_TOKEN || '');

    try {
        const res = await fetch('/sistema_escolar/php/logica/formatos_ajax.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.status === 'success') {
            Swal.fire('Éxito', 'Formato guardado correctamente.', 'success');
            setTimeout(() => {
                navegarModulo('formatos_matricula', true);
            }, 1000);
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    } catch (err) {
        Swal.fire('Error', 'No se pudo guardar el formato.', 'error');
    }
}

let bloqueArrastrando = null;
let offsetX = 0;
let offsetY = 0;
let lastSavedRange = null;

function iniciarArrastreBloque(e) {
    if (e.target.closest('.block-controls')) return;

    bloqueArrastrando = e.target.closest('.canvas-block-wrapper');
    if (!bloqueArrastrando) return;

    const rect = bloqueArrastrando.getBoundingClientRect();

    offsetX = e.clientX - rect.left;
    offsetY = e.clientY - rect.top;

    document.addEventListener('mousemove', arrastrarBloque);
    document.addEventListener('mouseup', terminarArrastreBloque);
    e.preventDefault();
}

function arrastrarBloque(e) {
    if (!bloqueArrastrando) return;

    const canvas = document.getElementById('canvas-builder');
    const canvasRect = canvas.getBoundingClientRect();
    const scale = getCanvasScale();

    let xPx = e.clientX - canvasRect.left - offsetX;
    let yPx = e.clientY - canvasRect.top - offsetY;

    xPx = Math.max(0, Math.min(xPx, canvasRect.width));
    yPx = Math.max(0, Math.min(yPx, canvasRect.height));

    const blockW = bloqueArrastrando.offsetWidth;

    const margenes = getMargensInMilimeters();

    const margenesPx = {
        izquierdo: margenes.izquierdo * scale,
        derecho: margenes.derecho * scale
    };

    const maxLeft = Math.max(margenesPx.izquierdo, canvasRect.width - margenesPx.derecho - blockW);
    xPx = Math.max(margenesPx.izquierdo, Math.min(xPx, maxLeft));

    // Convertir a MM
    let left_mm = xPx / scale;
    let top_mm = yPx / scale;

    bloqueArrastrando.style.left = left_mm + 'mm';
    bloqueArrastrando.style.top = top_mm + 'mm';
    bloqueArrastrando.dataset.left_mm = left_mm.toFixed(2);
    bloqueArrastrando.dataset.top_mm = top_mm.toFixed(2);
}

function terminarArrastreBloque() {
    document.removeEventListener('mousemove', arrastrarBloque);
    document.removeEventListener('mouseup', terminarArrastreBloque);
    bloqueArrastrando = null;
}

function ajustarAlturaLienzo() {
    const canvas = document.getElementById('canvas-builder');
    if (!canvas) return;

    const bloques = canvas.querySelectorAll('.canvas-block-wrapper');
    let maxBottom = UNIT_CONFIG.CANVAS_HEIGHT_MM;

    bloques.forEach(bloque => {
        const top_mm = parseFloat(bloque.dataset.top_mm) || 0;
        const height_mm = parseFloat(bloque.dataset.height_mm) || 30;
        const bottom = top_mm + height_mm;
        if (bottom > maxBottom) {
            maxBottom = bottom;
        }
    });

    const minHeight = UNIT_CONFIG.CANVAS_HEIGHT_MM;
    const finalHeight = Math.max(minHeight, maxBottom + 10);
    canvas.style.minHeight = finalHeight + 'mm';
}

function insertarVariable(codigo, label) {
    if (!lastSavedRange) return;

    const html = `<span class="ares-variable-badge" contenteditable="false" data-var="${codigo}">[ ${label} ]</span>&nbsp;`;

    const selection = window.getSelection();
    selection.removeAllRanges();
    selection.addRange(lastSavedRange);

    document.execCommand('insertHTML', false, html);
    lastSavedRange = selection.getRangeAt(0).cloneRange();
}

let activeZone = 'body';
let zoneOverlays = { header: null, body: null, footer: null };

function toggleZoneEditMode(zone) {
    if (activeZone === zone) {
        activeZone = 'body';
    } else {
        activeZone = zone;
    }
    updateZonesUI();
}

function updateZonesUI() {
    const canvas = document.getElementById('canvas-builder');
    const headerEndMM = UNIT_CONFIG.HEADER_LIMIT_MM;
    const footerStartMM = UNIT_CONFIG.FOOTER_START_MM;

    Object.values(zoneOverlays).forEach(ol => ol?.remove());
    zoneOverlays = { header: null, body: null, footer: null };

    canvas.style.position = 'relative';

    const createOverlay = (zone, topMM, heightMM, bgColor, label) => {
        const overlay = document.createElement('div');
        overlay.className = 'zone-overlay';
        overlay.dataset.zone = zone;
        overlay.style.cssText = `
            position: absolute;
            top: ${topMM}mm;
            left: 0;
            width: 100%;
            height: ${heightMM}mm;
            background: ${bgColor};
            z-index: 500;
            border: 2px dashed var(--el-border-color, #ccc);
            pointer-events: none;
            display: flex;
            align-items: center;
            justify-content: center;
        `;

        const label_el = document.createElement('span');
        label_el.style.cssText = 'color: var(--el-text-muted); font-size: 12px; font-weight: bold;';
        label_el.textContent = label;
        overlay.appendChild(label_el);

        return overlay;
    };

    if (activeZone === 'header') {
        const headerOverlay = createOverlay('header', 0, headerEndMM, 'rgba(231, 76, 60, 0.15)', 'CABECERA ACTIVA');
        canvas.appendChild(headerOverlay);
        zoneOverlays.header = headerOverlay;
    }

    if (activeZone === 'body' || activeZone === 'body') {
        const bodyHeight = footerStartMM - headerEndMM;
        const bodyOverlay = activeZone === 'body'
            ? createOverlay('body', headerEndMM, bodyHeight, 'rgba(255, 255, 255, 0)', '')
            : null;
        if (bodyOverlay) {
            canvas.appendChild(bodyOverlay);
            zoneOverlays.body = bodyOverlay;
        }
    }

    if (activeZone === 'footer') {
        const footerOverlay = createOverlay('footer', footerStartMM, UNIT_CONFIG.CANVAS_HEIGHT_MM - footerStartMM, 'rgba(52, 152, 219, 0.15)', 'PIE DE PÁGINA ACTIVO');
        canvas.appendChild(footerOverlay);
        zoneOverlays.footer = footerOverlay;
    }

    document.querySelectorAll('.canvas-block-wrapper').forEach(bloque => {
        const bloqueZone = bloque.dataset.zone || 'body';
        const isActive = (activeZone === 'body' && bloqueZone === 'body') ||
                         (activeZone === 'header' && bloqueZone === 'header') ||
                         (activeZone === 'footer' && bloqueZone === 'footer');

        bloque.classList.remove('header-locked', 'body-locked');

        if (isActive) {
            bloque.style.opacity = '';
            bloque.style.pointerEvents = 'auto';
        } else {
            bloque.classList.add('header-locked');
            bloque.style.pointerEvents = 'none';
        }
    });
}

let activeRangeBeforeModal = null;
let activeEditableBeforeModal = null;

function canvasDobleClick(e) {
    const clickY = e.clientY - document.getElementById('canvas-builder').getBoundingClientRect().top;
    toggleZoneEditMode(determinarZona(clickY));
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
    const modalEl = document.getElementById('modalCatalogoVariables');
    if (modalEl) {
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        if (modal) modal.hide();
    }

    if (esBloque) {
        insertarBloqueEnCanvas(codigo, label);
        return;
    }

    let targetEditable = activeEditableBeforeModal;

    const currentSelection = window.getSelection();
    if (!targetEditable && currentSelection.rangeCount > 0) {
        const node = currentSelection.getRangeAt(0).startContainer;
        const parentElem = node.nodeType === Node.ELEMENT_NODE ? node : node.parentElement;
        targetEditable = parentElem ? parentElem.closest('.block-content-texto') : null;
    }

    if (targetEditable) {
        targetEditable.focus();
        if (activeRangeBeforeModal) {
            currentSelection.removeAllRanges();
            currentSelection.addRange(activeRangeBeforeModal);
        }
        const badgeHtml = `<span class="ares-variable-badge" contenteditable="false" data-var="${codigo}">[ ${label} ]</span>&nbsp;`;
        document.execCommand('insertHTML', false, badgeHtml);
    } else {
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
}

function cambiarTamanoLienzoBuilder(tamano) {
    const canvas = document.getElementById('canvas-builder');
    if (!canvas) return;

    canvas.classList.remove('ares-paper-sheet--carta', 'ares-paper-sheet--media_carta', 'ares-paper-sheet--carne_v', 'ares-paper-sheet--carne_h');
    const claseTamano = 'ares-paper-sheet--' + tamano;
    canvas.classList.add(claseTamano);
}

function actualizarBloqueFirmasCanvas(bloque, numColumnas, dataHeredada) {
    if (!bloque) return;
    const container = bloque.querySelector('.dynamic-firmas-container');
    if (!container) return;

    const firmasViejas = [];
    container.querySelectorAll('.firma-item-canvas').forEach(div => {
        firmasViejas.push({
            cargo: div.querySelector('.ares-firma-cargo')?.textContent.trim() || '',
            nombre: div.querySelector('.ares-firma-nombre')?.textContent.trim() || ''
        });
    });

    const defaultFirmas = [
        { cargo: 'Firma del Estudiante', nombre: '[Nombre Estudiante]' },
        { cargo: 'Firma del Acudiente', nombre: '[Nombre Acudiente]' },
        { cargo: 'Rector Institucional', nombre: window.SCHOOL_INFO?.name ? 'RIGOBERTO ANDRÉS NUBIA' : '[Nombre Rector]' },
        { cargo: 'Secretaría Académica', nombre: '[Nombre Secretaria]' }
    ];

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

    container.innerHTML = '';

    if (numColumnas === 2) {
        const col1 = document.createElement('div');
        col1.style.flex = '1';
        col1.innerHTML = `
            <div class="firma-item-canvas">
                <div class="ares-firma-cargo" contenteditable="true">${firmasFinales[0].cargo}</div>
                <div style="height: 40px; border-top: 1px solid #000;"></div>
                <div class="ares-firma-nombre" contenteditable="true">${firmasFinales[0].nombre}</div>
            </div>
            <div class="firma-item-canvas">
                <div class="ares-firma-cargo" contenteditable="true">${firmasFinales[1].cargo}</div>
                <div style="height: 40px; border-top: 1px solid #000;"></div>
                <div class="ares-firma-nombre" contenteditable="true">${firmasFinales[1].nombre}</div>
            </div>
        `;
        container.appendChild(col1);

        const col2 = document.createElement('div');
        col2.style.flex = '1';
        col2.innerHTML = `
            <div class="firma-item-canvas">
                <div class="ares-firma-cargo" contenteditable="true">${firmasFinales[2].cargo}</div>
                <div style="height: 40px; border-top: 1px solid #000;"></div>
                <div class="ares-firma-nombre" contenteditable="true">${firmasFinales[2].nombre}</div>
            </div>
            <div class="firma-item-canvas">
                <div class="ares-firma-cargo" contenteditable="true">${firmasFinales[3].cargo}</div>
                <div style="height: 40px; border-top: 1px solid #000;"></div>
                <div class="ares-firma-nombre" contenteditable="true">${firmasFinales[3].nombre}</div>
            </div>
        `;
        container.appendChild(col2);
    } else {
        for (let f of firmasFinales) {
            const firmaDiv = document.createElement('div');
            firmaDiv.className = 'firma-item-canvas';
            firmaDiv.innerHTML = `
                <div class="ares-firma-cargo" contenteditable="true">${f.cargo}</div>
                <div style="height: 40px; border-top: 1px solid #000;"></div>
                <div class="ares-firma-nombre" contenteditable="true">${f.nombre}</div>
            `;
            container.appendChild(firmaDiv);
        }
    }

    bloque.dataset.columnas = numColumnas;
}
