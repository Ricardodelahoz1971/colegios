/* === SECCIÓN 1: CONFIGURACIÓN Y CONSTANTES === */
if (typeof window.UNIT_CONFIG === 'undefined') {
    window.UNIT_CONFIG = {
        CANVAS_WIDTH_MM: 215.9,
        CANVAS_HEIGHT_MM: 279.4,
        HEADER_LIMIT_MM: 50,
        FOOTER_START_MM: 219.4
    };
}
var UNIT_CONFIG = window.UNIT_CONFIG;

/* === BLOCK_SCHEMA: Propiedades base de cada tipo de bloque ===
 * Solo se usa al CREAR un bloque nuevo. Al recargar desde JSON guardado
 * se respetan las personalizaciones del usuario (dataset.* + JSON BD).
 */
var BLOCK_SCHEMA = {
    logo: {
        ancho_mm: 30,
        alto_mm: 12,
        anchoCompleto: false,
        editable: false,
        html: (si, logo) => `<img class="ares-logo-cabecera" src="${logo}" alt="Logo" />`,
        extraData: {}
    },
    titulo_colegio: {
        ancho_mm: 50,
        alto_mm: 12,
        anchoCompleto: false,
        editable: false,
        html: (si) => `<h3 class="ares-titulo-cabecera">${(si.name || 'Nombre del Colegio').toUpperCase()}</h3>`,
        extraData: {}
    },
    lema_colegio: {
        ancho_mm: 50,
        alto_mm: 12,
        anchoCompleto: false,
        editable: false,
        html: (si) => `<p class="ares-lema-cabecera">${si.motto || 'Lema Institucional'}</p>`,
        extraData: {}
    },
    metadatos: {
        ancho_mm: 50,
        alto_mm: 12,
        anchoCompleto: false,
        editable: false,
        html: () => `<h4>[TIPO DOCUMENTO] N° [FOLIO] - AÑO LECTIVO [AÑO]</h4>`,
        extraData: {}
    },
    texto: {
        ancho_mm: 50,
        alto_mm: 12,
        anchoCompleto: false,
        editable: true,
        html: () => '<div class="block-content-texto" contenteditable="true">Texto libre aquí</div>',
        extraData: {}
    },
    qr_estudiante: {
        ancho_mm: 30,
        alto_mm: 12,
        anchoCompleto: false,
        editable: false,
        html: () => '<div class="qr-placeholder"></div>',
        extraData: {}
    },
    foto_estudiante: {
        ancho_mm: 32,
        alto_mm: 37,
        anchoCompleto: false,
        editable: false,
        html: () => '<div class="foto-placeholder"></div>',
        extraData: {}
    },
    linea: {
        ancho_mm: 0,
        alto_mm: 0.5,
        anchoCompleto: true,
        editable: false,
        html: () => '<div class="ares-linea-grafica"></div>',
        extraData: {}
    },
    ficha: {
        ancho_mm: 0,
        alto_mm: 12,
        anchoCompleto: true,
        editable: true,
        html: () => '<div class="block-content-wysiwyg" contenteditable="true"><p>Contenido de ficha</p></div>',
        extraData: {}
    },
    calificaciones: {
        ancho_mm: 0,
        alto_mm: 12,
        anchoCompleto: true,
        editable: false,
        html: () => '<div class="block-content-wysiwyg" contenteditable="false"><table><tr><td>Materia</td><td>Calificación</td></tr></table></div>',
        extraData: {}
    },
    firmas: {
        ancho_mm: 0,
        alto_mm: 12,
        anchoCompleto: true,
        editable: false,
        html: () => '<div class="dynamic-firmas-container"></div>',
        extraData: { columnas: 3 }
    },
    texto_certificacion: {
        ancho_mm: 0,
        alto_mm: 12,
        anchoCompleto: true,
        editable: true,
        html: () => '<div class="block-content-wysiwyg" contenteditable="true"><p>Texto certificación</p></div>',
        extraData: {}
    }
};
window.BLOCK_SCHEMA = BLOCK_SCHEMA;

/* === BLOCK_STYLE_CONFIG: Estilos base de cada tipo de bloque ===
 * Fuente única: window.BLOCK_CONFIG, inyectado por PHP desde
 * php/logica/formatos_bloques_config.php (misma ficha que usa imprimir_matricula.php).
 * No definir estilos aquí — si un bloque falta, se agrega en el archivo PHP.
 */
var BLOCK_STYLE_CONFIG = Object.fromEntries(
    Object.entries(window.BLOCK_CONFIG || {}).map(([tipo, cfg]) => [tipo, cfg.estilo])
);
window.BLOCK_STYLE_CONFIG = BLOCK_STYLE_CONFIG;

function getHeaderLimit() {
    return parseFloat(document.getElementById('formato-cabecera-mm')?.value || 50);
}

function getMargensInMilimeters() {
    return {
        superior: parseFloat(document.getElementById('formato-margen-superior')?.value || 20),
        inferior: parseFloat(document.getElementById('formato-margen-inferior')?.value || 20),
        izquierdo: parseFloat(document.getElementById('formato-margen-izquierdo')?.value || 20),
        derecho: parseFloat(document.getElementById('formato-margen-derecho')?.value || 20)
    };
}

function getCanvasScale() {
    const canvas = document.getElementById('canvas-builder');
    if (!canvas) return 1;
    return canvas.offsetWidth / UNIT_CONFIG.CANVAS_WIDTH_MM;
}

function aplicarEstilosBase(bloque, tipo) {
    if (!bloque || !BLOCK_STYLE_CONFIG[tipo]) return;

    const config = BLOCK_STYLE_CONFIG[tipo];
    const textEl = bloque.querySelector('.ares-titulo-cabecera, .ares-lema-cabecera, .metadatos-titulo-linea, h4, .block-content-texto');

    if (!textEl) return;

    // Aplicar estilos CSS base
    textEl.style.fontSize = config.fontSize + 'pt';
    textEl.style.fontFamily = config.fontFamily;
    textEl.style.fontWeight = config.fontWeight;
    textEl.style.textTransform = config.textTransform;
    textEl.style.textAlign = config.textAlign;
    textEl.style.color = config.color;
    textEl.style.lineHeight = config.lineHeight;

    // NO aplicar padding aquí — CSS se encarga via .ares-titulo-cabecera

    // Guardar en dataset para referencia
    bloque.dataset.styleConfig = tipo;
    Object.entries(config).forEach(([key, value]) => {
        if (key !== 'locked' && key !== 'editable' && key !== 'constraints') {
            bloque.dataset['style_' + key] = value;
        }
    });
}
window.aplicarEstilosBase = aplicarEstilosBase;

function autoAjustarAnchoBloqueTexto(bloque) {
    if (!bloque) return;
    const tipo = bloque.dataset.bloque;
    if (!['titulo_colegio', 'lema_colegio', 'metadatos', 'texto'].includes(tipo) && !bloque.dataset.varCodigo) return;

    const textEl = bloque.querySelector('.ares-titulo-cabecera, .ares-lema-cabecera, .metadatos-titulo-linea, h4, .block-content-texto');
    if (!textEl) return;

    const originalWidth = bloque.style.width;
    bloque.style.width = 'auto';

    const textWidthPx = textEl.scrollWidth;
    const canvas = document.getElementById('canvas-builder');
    if (!canvas) {
        bloque.style.width = originalWidth;
        return;
    }

    const scale = canvas.offsetWidth / UNIT_CONFIG.CANVAS_WIDTH_MM;
    if (scale <= 0) {
        bloque.style.width = originalWidth;
        return;
    }

    const textWidthMm = textWidthPx / scale;
    // 1mm padding a cada lado = 2mm total de gabela
    const gabelaMm = BLOCK_STYLE_CONFIG[tipo]?.padding_mm ? (BLOCK_STYLE_CONFIG[tipo].padding_mm * 2) : 2;
    const finalWidthMm = textWidthMm + gabelaMm;

    bloque.style.width = finalWidthMm.toFixed(2) + 'mm';
    bloque.dataset.width_mm = finalWidthMm.toFixed(2);
}
window.autoAjustarAnchoBloqueTexto = autoAjustarAnchoBloqueTexto;

function determinarZona(y) {
    const headerEndMM = getHeaderLimit();
    const footerStartMM = UNIT_CONFIG.FOOTER_START_MM;
    const scale = getCanvasScale();

    const headerEndPx = headerEndMM * scale;
    const footerStartPx = footerStartMM * scale;

    if (y < headerEndPx) return 'header';
    if (y > footerStartPx) return 'footer';
    return 'body';
}

/* === SECCIÓN 2: INICIALIZACIÓN Y EVENTOS DEL LIENZO === */
var activeZone = 'body';
var zoneOverlays = { header: null, body: null, footer: null };
var bloqueArrastrando = null;
var offsetX = 0;
var offsetY = 0;
var lastSavedRange = null;
var activeRangeBeforeModal = null;
var activeEditableBeforeModal = null;

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
        agregarNodosRedimension(wrapper);
        e.stopPropagation();
    }
}

/* === MOTOR DE REDIMENSIÓN POR NODOS (ESQUINAS) === */
function iniciarRedimension(e, handleType, targetWrapper) {
    e.stopPropagation();
    e.preventDefault();
    const wrapper = (targetWrapper && targetWrapper.nodeType === Node.ELEMENT_NODE) ? targetWrapper : e.target.closest('.canvas-block-wrapper');
    if (!wrapper) return;

    const scale = getCanvasScale();
    const startWidth_mm = parseFloat(wrapper.dataset.width_mm) || (wrapper.offsetWidth / scale);
    const startHeight_mm = parseFloat(wrapper.dataset.height_mm) || (wrapper.offsetHeight / scale);
    const startX = e.clientX;
    const startY = e.clientY;
    const startLeft_mm = parseFloat(wrapper.dataset.left_mm) || 0;
    const startTop_mm = parseFloat(wrapper.dataset.top_mm) || 0;

    const wysiwygNodes = Array.from(wrapper.querySelectorAll('.block-content-wysiwyg, .block-content-wysiwyg *')).filter(n => !n.classList?.contains('bloque-backend-html'));
    wysiwygNodes.forEach(node => {
        if (!node.dataset.baseFontSize) {
            node.dataset.baseFontSize = (parseFloat(window.getComputedStyle(node).fontSize) || 14) * 0.75;
        }
    });

    const canvas = document.getElementById('canvas-builder');

    function redimensionar(moveEvent) {
        const dx_mm = (moveEvent.clientX - startX) / scale;
        const dy_mm = (moveEvent.clientY - startY) / scale;

        const margenes = getMargensInMilimeters();
        
        // Si es el logotipo, ajustar solo ancho con contención vertical
        if (wrapper.dataset.bloque === 'logo') {
            let newWidth_mm = startWidth_mm;
            if (handleType === 'br' || handleType === 'tr') {
                const limitWidth_mm = UNIT_CONFIG.CANVAS_WIDTH_MM - margenes.derecho - startLeft_mm;
                newWidth_mm = Math.min(limitWidth_mm, startWidth_mm + dx_mm);
            } else if (handleType === 'bl' || handleType === 'tl') {
                const targetLeft_mm = startLeft_mm + dx_mm;
                const newLeft_mm = Math.max(margenes.izquierdo, targetLeft_mm);
                const appliedDx_mm = newLeft_mm - startLeft_mm;
                newWidth_mm = startWidth_mm - appliedDx_mm;
                
                wrapper.style.left = newLeft_mm.toFixed(2) + 'mm';
                wrapper.dataset.left_mm = newLeft_mm.toFixed(2);
            }
            newWidth_mm = Math.max(10, newWidth_mm);

            // Validar si la altura resultante desborda la zona vertical
            const blockZone = wrapper.dataset.zone || 'body';
            const headerEndMM = getHeaderLimit();
            const footerStartMM = UNIT_CONFIG.FOOTER_START_MM;
            let maxZoneY = UNIT_CONFIG.CANVAS_HEIGHT_MM - margenes.inferior;
            if (blockZone === 'header') {
                maxZoneY = headerEndMM;
            } else if (blockZone === 'body') {
                maxZoneY = footerStartMM;
            }

            wrapper.style.width = newWidth_mm.toFixed(2) + 'mm';
            const currentHeight_mm = wrapper.offsetHeight / scale;
            if (startTop_mm + currentHeight_mm > maxZoneY) {
                const allowedHeight_mm = maxZoneY - startTop_mm;
                const aspect = startWidth_mm / (startHeight_mm || startWidth_mm || 1);
                newWidth_mm = Math.min(newWidth_mm, allowedHeight_mm * aspect);
                newWidth_mm = Math.max(10, newWidth_mm);
            }

            wrapper.style.width = newWidth_mm.toFixed(2) + 'mm';
            wrapper.style.height = 'auto';
            wrapper.dataset.width_mm = newWidth_mm.toFixed(2);
            wrapper.dataset.height_mm = '';
            
            // El logo hereda el ancho del bloque contenedor vía CSS (width: 100%)
            return;
        }
        
        // Para todos los demás bloques
        let newWidth_mm = startWidth_mm;
        if (handleType === 'br' || handleType === 'tr') {
            const limitWidth_mm = UNIT_CONFIG.CANVAS_WIDTH_MM - margenes.derecho - startLeft_mm;
            newWidth_mm = Math.min(limitWidth_mm, startWidth_mm + dx_mm);
        } else if (handleType === 'bl' || handleType === 'tl') {
            const targetLeft_mm = startLeft_mm + dx_mm;
            const newLeft_mm = Math.max(margenes.izquierdo, targetLeft_mm);
            const appliedDx_mm = newLeft_mm - startLeft_mm;
            newWidth_mm = startWidth_mm - appliedDx_mm;
            
            wrapper.style.left = newLeft_mm.toFixed(2) + 'mm';
            wrapper.dataset.left_mm = newLeft_mm.toFixed(2);
        }
        newWidth_mm = Math.max(15, newWidth_mm);
        
        const blockZone = wrapper.dataset.zone || 'body';
        const headerEndMM = getHeaderLimit();
        const footerStartMM = UNIT_CONFIG.FOOTER_START_MM;
        
        let maxZoneY = UNIT_CONFIG.CANVAS_HEIGHT_MM - margenes.inferior;
        if (blockZone === 'header') {
            maxZoneY = headerEndMM;
        } else if (blockZone === 'body') {
            maxZoneY = footerStartMM;
        }

        // Aplicar temporalmente para medir la altura resultante en el DOM
        wrapper.style.width = newWidth_mm.toFixed(2) + 'mm';
        const scaleRatio = newWidth_mm / startWidth_mm;
        wysiwygNodes.forEach(node => {
            const baseSize = parseFloat(node.dataset.baseFontSize);
            const newSize = Math.max(4, baseSize * scaleRatio);
            node.style.fontSize = newSize + 'pt';
        });

        // Validar si la altura resultante desborda la zona vertical
        const currentHeight_mm = wrapper.offsetHeight / scale;
        if (startTop_mm + currentHeight_mm > maxZoneY) {
            const allowedHeight_mm = maxZoneY - startTop_mm;
            const aspect = startWidth_mm / startHeight_mm;
            let estWidth_mm = allowedHeight_mm * aspect;
            
            newWidth_mm = Math.min(newWidth_mm, estWidth_mm);
            newWidth_mm = Math.max(15, newWidth_mm);
            
            wrapper.style.width = newWidth_mm.toFixed(2) + 'mm';
            const finalScaleRatio = newWidth_mm / startWidth_mm;
            wysiwygNodes.forEach(node => {
                const baseSize = parseFloat(node.dataset.baseFontSize);
                const newSize = Math.max(4, baseSize * finalScaleRatio);
                node.style.fontSize = newSize + 'pt';
            });
        }
        
        wrapper.style.height = 'auto';
        wrapper.dataset.width_mm = newWidth_mm.toFixed(2);
        
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
    
    const tipo = wrapper.dataset.bloque;
    if (['titulo_colegio', 'lema_colegio', 'metadatos', 'texto'].includes(tipo) || wrapper.dataset.varCodigo) {
        return;
    }

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

document.addEventListener('click', function(e) {
    if (!e.target.closest('.canvas-block-wrapper') && !e.target.closest('.no-print-bar') && !e.target.closest('.modal') && !e.target.closest('.ares-toolbox')) {
        document.querySelectorAll('.canvas-block-wrapper').forEach(w => w.classList.remove('selected'));
    }
});

function dragStart(e, tipo, code, label) {
    const data = JSON.stringify({ tipo, codigo: code, label });
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

/* === SECCIÓN 3: GESTIÓN DE BLOQUES === */
function insertarBloqueEnCanvas(codigo, label, xPx, yPx, skipZoneRestrictions = false) {
    const canvas = document.getElementById('canvas-builder');
    const idUnico = generarIdUnicoBloque();
    const scale = getCanvasScale();
    const schoolLogo = document.getElementById('formatos-container')?.dataset.schoolLogo || '/sistema_escolar/perseus.png';

    // Leer propiedades base del schema — solo aplica en creación inicial
    const schema = BLOCK_SCHEMA[codigo] || BLOCK_SCHEMA['texto'];

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
    const maxAnchoSeguro = UNIT_CONFIG.CANVAS_WIDTH_MM - margenes.izquierdo - margenes.derecho;

    // Dimensiones desde el schema
    let estW_mm = schema.anchoCompleto ? maxAnchoSeguro : schema.ancho_mm;
    if (codigo === 'firmas') estW_mm = maxAnchoSeguro - 26;
    if (estW_mm > maxAnchoSeguro) estW_mm = maxAnchoSeguro;
    let estH_mm = schema.alto_mm;

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

    if (schema.anchoCompleto) left_mm = margenes.izquierdo;

    if (!skipZoneRestrictions) {
        if (activeZone === 'header') {
            top_mm = Math.max(0, Math.min(top_mm, headerEndMM - 2.65));
        } else if (activeZone === 'footer') {
            top_mm = Math.max(footerStartMM, Math.min(top_mm, UNIT_CONFIG.CANVAS_HEIGHT_MM - estH_mm));
        } else {
            top_mm = Math.max(headerEndMM, Math.min(top_mm, footerStartMM));
        }
    }

    wrapper.dataset.left_mm = left_mm.toFixed(2);
    wrapper.dataset.top_mm = top_mm.toFixed(2);
    wrapper.dataset.width_mm = estW_mm.toFixed(2);

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

    // HTML del bloque desde el schema
    const _si = (typeof window.SCHOOL_INFO !== 'undefined') ? window.SCHOOL_INFO : {};
    const html = typeof schema.html === 'function'
        ? schema.html(_si, schoolLogo)
        : `<div class="block-placeholder">Bloque</div>`;

    wrapper.innerHTML = `
        <div class="block-inner">
            ${html}
            ${controls}
            <div class="bloque-backend-html d-none" data-type="${codigo}"></div>
        </div>
    `;

    // Bloques con configuración extra del schema
    if (codigo === 'firmas') {
        const cols = schema.extraData.columnas || 3;
        const container = wrapper.querySelector('.dynamic-firmas-container');
        for (let i = 0; i < cols; i++) {
            const firmaDiv = document.createElement('div');
            firmaDiv.className = 'firma-item-canvas';
            firmaDiv.innerHTML = `
                <div class="ares-firma-cargo" contenteditable="true">Cargo</div>
                <div class="ares-firma-linea"></div>
                <div class="ares-firma-nombre" contenteditable="true">Nombre</div>
            `;
            container.appendChild(firmaDiv);
        }
        wrapper.dataset.columnas = cols;
    }

    canvas.appendChild(wrapper);

    // Medir y guardar la altura real del contenido en mm
    if (codigo !== 'linea') {
        const realHeight_mm = wrapper.offsetHeight / scale;
        wrapper.dataset.height_mm = realHeight_mm.toFixed(2);
    }

    // Aplicar estilos base del BLOCK_STYLE_CONFIG (si existen)
    if (BLOCK_STYLE_CONFIG[codigo]) {
        aplicarEstilosBase(wrapper, codigo);
    }

    // Auto-ajustar el ancho si es un bloque de texto dinámico o cabecera
    autoAjustarAnchoBloqueTexto(wrapper);

    chequearEmptyState();
    updateZonesUI();
    return wrapper;
}

function insertarBloqueDesdeJSON(jsonBlock) {
    const canvas = document.getElementById('canvas-builder');
    document.getElementById('canvas-empty-state')?.remove();

    const tipoReal = jsonBlock.tipo || jsonBlock.type;
    const xKey = jsonBlock.x_mm !== undefined ? jsonBlock.x_mm : (jsonBlock.left_mm !== undefined ? jsonBlock.left_mm : jsonBlock.left);
    const yKey = jsonBlock.y_mm !== undefined ? jsonBlock.y_mm : (jsonBlock.top_mm !== undefined ? jsonBlock.top_mm : jsonBlock.top);
    const wKey = jsonBlock.w_mm !== undefined ? jsonBlock.w_mm : (jsonBlock.width_mm !== undefined ? jsonBlock.width_mm : jsonBlock.width);
    const hKey = jsonBlock.h_mm !== undefined ? jsonBlock.h_mm : (jsonBlock.height_mm !== undefined ? jsonBlock.height_mm : jsonBlock.height);
    const zoneKey = jsonBlock.zona || jsonBlock.zone || 'body';

    const left_val = parseFloat(xKey);
    const left_mm = isNaN(left_val) ? 10.0 : left_val;
    const top_val = parseFloat(yKey);
    const top_mm = isNaN(top_val) ? 10.0 : top_val;
    const width_mm = parseFloat(wKey) || null;
    const height_mm = parseFloat(hKey) || null;
    const zone = zoneKey;

    const previousActiveZone = activeZone;
    activeZone = zone;

    const insertedNode = insertarBloqueEnCanvas(tipoReal, null, undefined, undefined, true);

    activeZone = previousActiveZone;

    insertedNode.dataset.zone = zone;
    insertedNode.dataset.left_mm = left_mm.toFixed(2);
    insertedNode.dataset.top_mm = top_mm.toFixed(2);
    insertedNode.style.left = left_mm + 'mm';
    insertedNode.style.top = top_mm + 'mm';

    if (width_mm) {
        insertedNode.dataset.width_mm = width_mm.toFixed(2);
        insertedNode.style.width = width_mm + 'mm';
    }
    if (height_mm) {
        insertedNode.dataset.height_mm = height_mm.toFixed(2);
        const esBloqueTextoDinamico = ['titulo_colegio', 'lema_colegio', 'metadatos', 'texto', 'texto_certificacion'].includes(tipoReal);
        if (!esBloqueTextoDinamico) {
            insertedNode.style.height = height_mm + 'mm';
        }
    }

    if (tipoReal === 'texto' && jsonBlock.content) {
        const cajaTexto = insertedNode.querySelector('.block-content-texto');
        if (cajaTexto) {
            // Verificar si el contenido tiene un badge de variable
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = jsonBlock.content;
            const badge = tempDiv.querySelector('.ares-variable-badge');
            if (badge) {
                const varCodigo = badge.getAttribute('data-var');
                insertedNode.dataset.varCodigo = varCodigo;
                
                const previewData = window.PREVIEW_DATA || {};
                const textoReal = previewData[varCodigo] !== undefined && previewData[varCodigo] !== null && previewData[varCodigo] !== ''
                    ? previewData[varCodigo]
                    : badge.textContent;
                
                cajaTexto.textContent = textoReal;
                cajaTexto.style.textAlign = 'center';
                cajaTexto.style.paddingInline = '1mm';
                cajaTexto.style.width = 'auto';
                
                // Asegurar que no tenga handles de redimensión
                insertedNode.querySelectorAll('.resize-handle').forEach(h => h.remove());

                // Auto-ajustar el ancho del bloque al valor real del preview
                autoAjustarAnchoBloqueTexto(insertedNode);
            } else {
                cajaTexto.innerHTML = jsonBlock.content;
            }
        }
    }

    // Restaurar estilos: base de BLOCK_STYLE_CONFIG + overrides personalizados guardados por el usuario.
    // Única aplicación de estilos para este bloque — no se vuelve a tocar más abajo.
    if (BLOCK_STYLE_CONFIG[jsonBlock.type]) {
        aplicarEstilosBase(insertedNode, jsonBlock.type);

        const textEl = insertedNode.querySelector('.ares-titulo-cabecera, .ares-lema-cabecera, .metadatos-titulo-linea, h4, .block-content-texto');
        if (textEl) {
            const fontSizeOverride = jsonBlock.size;
            const colorOverride = jsonBlock.color;

            if (fontSizeOverride) {
                textEl.style.fontSize = fontSizeOverride + 'pt';
            }
            if (colorOverride) {
                textEl.style.color = colorOverride;
                insertedNode.dataset.style_color = colorOverride;
            }
        }
    }

    if (jsonBlock.scale) insertedNode.dataset.scale = jsonBlock.scale;
    if (jsonBlock.size) {
        insertedNode.dataset.size = jsonBlock.size;
        const header = insertedNode.querySelector('.ares-titulo-cabecera, .ares-lema-cabecera, h3, h2, h4, .block-content-texto');
        if (header) header.style.fontSize = jsonBlock.size + 'pt';
    }
    if (jsonBlock.align) {
        insertedNode.dataset.align = jsonBlock.align;
        const header = insertedNode.querySelector('.ares-titulo-cabecera, .ares-lema-cabecera, h3, h2, h4, .block-content-texto');
        if (header) {
            header.style.textAlign = jsonBlock.align;
            header.parentNode.style.textAlign = jsonBlock.align;
        }
    }

    if (jsonBlock.type === 'logo' && jsonBlock.width_mm) {
        // El logo hereda el ancho del bloque contenedor vía CSS (width: 100%)
    }

    if (jsonBlock.type === 'calificaciones') {
        if (jsonBlock.diseno) insertedNode.dataset.diseno = jsonBlock.diseno;
        if (jsonBlock.filtro) insertedNode.dataset.filtro = jsonBlock.filtro;
        if (jsonBlock.columnas) insertedNode.dataset.columnas = jsonBlock.columnas;
    }

    if (tipoReal === 'firmas') {
        const cols = jsonBlock.columnas || '3';
        insertedNode.dataset.columnas = cols;
        if (typeof actualizarBloqueFirmasCanvas === 'function') {
            actualizarBloqueFirmasCanvas(insertedNode, parseInt(cols), jsonBlock.firmas_data);
        }
    }

    if (jsonBlock.content && jsonBlock.type !== 'texto' && jsonBlock.type !== 'ficha') {
        const wysiwyg = insertedNode.querySelector('.block-content-wysiwyg');
        const cabecera = insertedNode.querySelector('.ares-titulo-cabecera, .ares-lema-cabecera, .metadatos-titulo-linea, h4');
        if (wysiwyg) {
            wysiwyg.innerHTML = jsonBlock.content;
        } else if (cabecera) {
            cabecera.innerHTML = jsonBlock.content;
        }
    }

    // Auto-ajustar el ancho si es un bloque de texto de cabecera
    autoAjustarAnchoBloqueTexto(insertedNode);
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

function cambiarTamanoLienzoBuilder(tamano) {
    const canvas = document.getElementById('canvas-builder');
    if (!canvas) return;

    canvas.classList.remove('ares-paper-sheet--carta', 'ares-paper-sheet--media_carta', 'ares-paper-sheet--carne_v', 'ares-paper-sheet--carne_h');
    const claseTamano = 'ares-paper-sheet--' + tamano;
    canvas.classList.add(claseTamano);
}

/* === SECCIÓN 4: MOTOR DRAG & DROP === */
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

    // Convertir coordenadas del cursor directamente a milímetros
    let left_mm = (e.clientX - canvasRect.left - offsetX) / scale;
    let top_mm = (e.clientY - canvasRect.top - offsetY) / scale;

    const blockW_mm = parseFloat(bloqueArrastrando.dataset.width_mm) || (bloqueArrastrando.offsetWidth / scale);
    const margenes = getMargensInMilimeters();

    // Restricciones en milímetros (Ancho de papel carta: 215.9 mm)
    const minLeft = margenes.izquierdo;
    const maxLeft = UNIT_CONFIG.CANVAS_WIDTH_MM - margenes.derecho - blockW_mm;

    const blockH_mm = parseFloat(bloqueArrastrando.dataset.height_mm) || (bloqueArrastrando.offsetHeight / scale);
    const headerEndMM = getHeaderLimit();
    const footerStartMM = UNIT_CONFIG.FOOTER_START_MM;
    const blockZone = bloqueArrastrando.dataset.zone || 'body';

    let minTop = 0;
    let maxTop = UNIT_CONFIG.CANVAS_HEIGHT_MM - blockH_mm;

    if (blockZone === 'header') {
        minTop = margenes.superior;
        maxTop = headerEndMM - blockH_mm;
    } else if (blockZone === 'body') {
        minTop = headerEndMM;
        maxTop = footerStartMM - blockH_mm;
    } else if (blockZone === 'footer') {
        minTop = footerStartMM;
        maxTop = UNIT_CONFIG.CANVAS_HEIGHT_MM - margenes.inferior - blockH_mm;
    }

    left_mm = Math.max(minLeft, Math.min(left_mm, maxLeft));
    top_mm = Math.max(minTop, Math.min(top_mm, maxTop));

    bloqueArrastrando.style.left = left_mm.toFixed(2) + 'mm';
    bloqueArrastrando.style.top = top_mm.toFixed(2) + 'mm';
    bloqueArrastrando.dataset.left_mm = left_mm.toFixed(2);
    bloqueArrastrando.dataset.top_mm = top_mm.toFixed(2);
}

function terminarArrastreBloque() {
    document.removeEventListener('mousemove', arrastrarBloque);
    document.removeEventListener('mouseup', terminarArrastreBloque);
    
    if (bloqueArrastrando && bloqueArrastrando.dataset.bloque !== 'linea') {
        const scale = getCanvasScale();
        const realHeight_mm = bloqueArrastrando.offsetHeight / scale;
        bloqueArrastrando.dataset.height_mm = realHeight_mm.toFixed(2);
    }
    
    ajustarAlturaLienzo();
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

/* === SECCIÓN 5: GESTIÓN DE ZONAS === */
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
    const headerEndMM = getHeaderLimit();
    const footerStartMM = UNIT_CONFIG.FOOTER_START_MM;

    Object.values(zoneOverlays).forEach(ol => ol?.remove());
    zoneOverlays = { header: null, body: null, footer: null };

    canvas.style.position = 'relative';

    const createOverlay = (zone, topMM, heightMM) => {
        const overlay = document.createElement('div');
        overlay.className = 'zone-overlay';
        overlay.dataset.zone = zone;
        overlay.style.cssText = `
            position: absolute;
            top: ${topMM}mm;
            left: 0;
            width: 100%;
            height: ${heightMM}mm;
            background: rgba(var(--el-accent-rgb), 0.20);
            z-index: 500;
            border: 2px dashed var(--el-border-color);
            pointer-events: none;
            display: flex;
            align-items: center;
            justify-content: center;
        `;

        return overlay;
    };

    if (activeZone === 'header') {
        const headerOverlay = createOverlay('header', 0, headerEndMM);
        canvas.appendChild(headerOverlay);
        zoneOverlays.header = headerOverlay;
    }

    if (activeZone === 'body') {
        const bodyHeight = footerStartMM - headerEndMM;
        const bodyOverlay = createOverlay('body', headerEndMM, bodyHeight);
        canvas.appendChild(bodyOverlay);
        zoneOverlays.body = bodyOverlay;
    }

    if (activeZone === 'footer') {
        const footerOverlay = createOverlay('footer', footerStartMM, UNIT_CONFIG.CANVAS_HEIGHT_MM - footerStartMM);
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

function canvasDobleClick(e) {
    e.preventDefault();
    if (window.getSelection) {
        window.getSelection().removeAllRanges();
    }
    const clickY = e.clientY - document.getElementById('canvas-builder').getBoundingClientRect().top;
    toggleZoneEditMode(determinarZona(clickY));
}

/* === SECCIÓN 6: CATÁLOGO DE VARIABLES === */
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
        return insertarBloqueEnCanvas(codigo, label);
    }

    // Obtener el valor real del preview si existe
    const previewData = window.PREVIEW_DATA || {};
    const textoReal = previewData[codigo] !== undefined && previewData[codigo] !== null && previewData[codigo] !== ''
        ? previewData[codigo]
        : `[${label}]`;

    // Crear bloque de texto con la variable
    const wrapper = insertarBloqueEnCanvas('texto', label);
    if (wrapper) {
        wrapper.dataset.varCodigo = codigo;
        
        const editable = wrapper.querySelector('.block-content-texto');
        if (editable) {
            editable.textContent = textoReal;
            if (codigo === 'colegio_nombre') {
                editable.classList.add('ares-titulo-cabecera');
            } else {
                editable.style.textAlign = 'center';
            }
            editable.style.paddingInline = '1mm';
            editable.style.width = 'auto';
        }

        // Forzar el auto-ajuste de la caja contenedora
        autoAjustarAnchoBloqueTexto(wrapper);

        // Medir altura después de insertar al DOM y fuentes cargadas
        const scale = getCanvasScale();
        document.fonts.ready.then(() => {
            const realHeight_mm = wrapper.offsetHeight / scale;
            wrapper.dataset.height_mm = realHeight_mm.toFixed(2);
        });

        // Asegurar que no tenga handles de redimensión (control numérico puro)
        wrapper.querySelectorAll('.resize-handle').forEach(h => h.remove());

        if (editable) {
            editable.focus();
        }

        return wrapper;
    }
    return null;
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

/* === SECCIÓN 7: COMPONENTES ESPECÍFICOS (FIRMAS) === */
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
                <div style="height: 40px; border-top: 1px solid var(--el-border-color);"></div>
                <div class="ares-firma-nombre" contenteditable="true">${firmasFinales[0].nombre}</div>
            </div>
            <div class="firma-item-canvas">
                <div class="ares-firma-cargo" contenteditable="true">${firmasFinales[1].cargo}</div>
                <div style="height: 40px; border-top: 1px solid var(--el-border-color);"></div>
                <div class="ares-firma-nombre" contenteditable="true">${firmasFinales[1].nombre}</div>
            </div>
        `;
        container.appendChild(col1);

        const col2 = document.createElement('div');
        col2.style.flex = '1';
        col2.innerHTML = `
            <div class="firma-item-canvas">
                <div class="ares-firma-cargo" contenteditable="true">${firmasFinales[2].cargo}</div>
                <div style="height: 40px; border-top: 1px solid var(--el-border-color);"></div>
                <div class="ares-firma-nombre" contenteditable="true">${firmasFinales[2].nombre}</div>
            </div>
            <div class="firma-item-canvas">
                <div class="ares-firma-cargo" contenteditable="true">${firmasFinales[3].cargo}</div>
                <div style="height: 40px; border-top: 1px solid var(--el-border-color);"></div>
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
                <div style="height: 40px; border-top: 1px solid var(--el-border-color);"></div>
                <div class="ares-firma-nombre" contenteditable="true">${f.nombre}</div>
            `;
            container.appendChild(firmaDiv);
        }
    }

    bloque.dataset.columnas = numColumnas;
}

/* === SECCIÓN 8: PERSISTENCIA Y GUARDADO === */
async function guardarFormato(e, salir = true) {
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

    const configJson = [];
    const scale = getCanvasScale();
    const bloques = canvas.querySelectorAll('.canvas-block-wrapper');
    
    // Recalcular alturas reales en mm antes de persistir
    bloques.forEach(bloque => {
        autoAjustarAnchoBloqueTexto(bloque);
        
        if (bloque.dataset.bloque !== 'linea') {
            const realHeight_mm = bloque.offsetHeight / scale;
            bloque.dataset.height_mm = realHeight_mm.toFixed(2);
        }
    });

    bloques.forEach(bloque => {
        const tipoBloque = bloque.dataset.bloque;
        const left_val = parseFloat(bloque.dataset.left_mm);
        const left_mm = isNaN(left_val) ? 10.0 : left_val;
        const top_val = parseFloat(bloque.dataset.top_mm);
        const top_mm = isNaN(top_val) ? 10.0 : top_val;
        const width_mm = bloque.dataset.width_mm ? parseFloat(bloque.dataset.width_mm) : null;
        const height_mm = bloque.dataset.height_mm ? parseFloat(bloque.dataset.height_mm) : null;

        if (tipoBloque === 'texto') {
            const contenidoCaja = bloque.querySelector('.block-content-texto').cloneNode(true);

            // Empaquetar el badge (igual que antes)
            if (bloque.dataset.varCodigo) {
                const varCodigo = bloque.dataset.varCodigo;
                contenidoCaja.innerHTML = `<span class="ares-variable-badge" contenteditable="false" data-var="${varCodigo}">[ ${varCodigo} ]</span>`;
            } else {
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
            }

            const size = bloque.dataset.size || '12';
            const align = bloque.dataset.align || 'left';

            configJson.push({
                tipo: 'texto',
                zona: bloque.dataset.zone || 'body',
                x_mm: left_mm,
                y_mm: top_mm,
                w_mm: width_mm,
                h_mm: height_mm,
                size: size,
                align: align,
                content: contenidoCaja.innerHTML
            });

        } else {
            const htmlBackend = bloque.querySelector('.bloque-backend-html');
            if (htmlBackend) {
                const tipo = htmlBackend.dataset.type;
                const innerTag = htmlBackend.innerHTML;

                let jsonBlock = {
                    tipo: tipo,
                    zona: bloque.dataset.zone || 'body',
                    x_mm: left_mm,
                    y_mm: top_mm,
                    w_mm: width_mm,
                    h_mm: height_mm,
                    content: innerTag
                };

                if (bloque.dataset.size) jsonBlock.size = bloque.dataset.size;
                if (bloque.dataset.align) jsonBlock.align = bloque.dataset.align;
                if (bloque.dataset.style_color) jsonBlock.color = bloque.dataset.style_color;

                if (tipo === 'firmas') {
                    const selectores = htmlBackend.querySelectorAll('select.signature-role-select');
                    const fData = [];
                    selectores.forEach(sel => {
                        fData.push({
                            role: sel.value,
                            label: sel.options[sel.selectedIndex]?.text || sel.value
                        });
                    });
                    jsonBlock.firmas_data = fData;
                }

                configJson.push(jsonBlock);
            }
        }
    });

    const tipoDocumento = document.getElementById('formato-tipo-documento')?.value || 'matricula';
    const tamanoLienzo = document.getElementById('formato-tamano-lienzo')?.value || 'carta';

    const margenes = getMargensInMilimeters();

    const zonesData = {
        header_limit_mm: getHeaderLimit(),
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
    formData.append('contenido_html', ''); // ARQUITECTURA V2: HTML MUERTO, SOLO JSON
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
            if (typeof window.lanzarToastElite === 'function') {
                window.lanzarToastElite('success', 'Formato guardado correctamente');
            } else {
                Swal.fire('Éxito', 'Formato guardado correctamente.', 'success');
            }
            
            if (salir) {
                if (typeof cancelarEdicion === 'function') {
                    cancelarEdicion();
                }
                if (typeof navegarModulo === 'function') {
                    window.forceRefreshElite = true;
                    navegarModulo('formatos_matricula', true);
                } else {
                    window.location.reload();
                }
            }
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    } catch (err) {
        Swal.fire('Error', 'No se pudo guardar el formato.', 'error');
    }
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

    // Remover guía anterior
    canvas.querySelector('.ares-safe-zone-guide')?.remove();

    const factorMmPx = 3.78;
    const margenSup = Math.round((parseFloat(document.getElementById('formato-margen-superior').value) || 20) * factorMmPx);
    const margenInf = Math.round((parseFloat(document.getElementById('formato-margen-inferior').value) || 20) * factorMmPx);
    const margenIzq = Math.round((parseFloat(document.getElementById('formato-margen-izquierdo').value) || 20) * factorMmPx);
    const margenDer = Math.round((parseFloat(document.getElementById('formato-margen-derecho').value) || 20) * factorMmPx);

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

function alternarBloqueoCabecera(abierto) {
    if (abierto) {
        activeZone = 'header';
    } else {
        activeZone = 'body';
    }
    
    const label = document.getElementById('switch-cabecera-label');
    if (label) {
        label.className = abierto ? 'small fw-bold text-uppercase text-primary m-0 cursor-pointer' : 'small fw-bold text-uppercase text-secondary m-0 cursor-pointer';
    }
    
    if (typeof updateZonesUI === 'function') {
        updateZonesUI();
    }
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



window.sincronizarValoresRealesBadges = function() {
    const canvas = document.getElementById('canvas-builder');
    if (!canvas || !window.SCHOOL_INFO) return;

    const badgeMap = {
        'colegio_nit': window.SCHOOL_INFO.nit || '',
        'colegio_resolucion': window.SCHOOL_INFO.resolucion || '',
        'school_name': window.SCHOOL_INFO.name || '',
        'school_motto': window.SCHOOL_INFO.motto || ''
    };

    canvas.querySelectorAll('.ares-variable-badge').forEach(badge => {
        const varCode = badge.getAttribute('data-var');
        if (badgeMap.hasOwnProperty(varCode)) {
            badge.textContent = badgeMap[varCode] || `[ ${varCode} ]`;
        }
    });
};

