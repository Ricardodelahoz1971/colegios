document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('formatos-container');
    if (container) {
        window.SCHOOL_INFO = {
            name: container.getAttribute('data-school-name') || '',
            motto: container.getAttribute('data-school-motto') || '',
            logo: container.getAttribute('data-school-logo') || '',
            anio: container.getAttribute('data-school-anio') || new Date().getFullYear(),
            nit: container.getAttribute('data-colegio-nit') || '',
            resolucion: container.getAttribute('data-colegio-resolucion') || ''
        };
    }
    
    // Inicializar PREVIEW_DATA como objeto vacío por defecto
    window.PREVIEW_DATA = {};

    // Obtener datos de preview desde el endpoint AJAX (POST para pasar la protección CSRF)
    const formData = new FormData();
    formData.append('action', 'obtener_datos_preview');
    formData.append('csrf_token', window.CSRF_TOKEN || '');

    fetch('/sistema_escolar/php/logica/formatos_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor');
        }
        return response.json();
    })
    .then(result => {
        if (result.status === 'success' && result.data) {
            window.PREVIEW_DATA = result.data;
        } else {
            window.PREVIEW_DATA = {};
        }
    })
    .catch(error => {
        console.warn('No se pudieron cargar los datos de preview:', error);
        window.PREVIEW_DATA = {};
    });
    
    // Inicializar el canvas engine al cargar este script (AJAX compatible)
    if (typeof initFormatosBuilder === 'function') {
        initFormatosBuilder();
    }
});

function abrirModalAjustesFormato() {
    // Sincronizar desde los hiddens hacia los inputs del modal
    document.getElementById('modal-formato-nombre').value = document.getElementById('formato-nombre').value;
    document.getElementById('modal-formato-descripcion').value = document.getElementById('formato-descripcion').value;
    document.getElementById('modal-formato-margen-superior').value = document.getElementById('formato-margen-superior').value;
    document.getElementById('modal-formato-margen-inferior').value = document.getElementById('formato-margen-inferior').value;
    document.getElementById('modal-formato-margen-izquierdo').value = document.getElementById('formato-margen-izquierdo').value;
    document.getElementById('modal-formato-margen-derecho').value = document.getElementById('formato-margen-derecho').value;
    document.getElementById('modal-formato-tipo').value = document.getElementById('formato-tipo-documento').value || 'matricula';
    document.getElementById('modal-formato-tamano').value = document.getElementById('formato-tamano-lienzo').value || 'carta';
    
    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalAjustesFormato'));
    modal.show();
}

function abrirReferenciaCatalogo() {
    const modalCatalogo = bootstrap.Modal.getInstance(document.getElementById('modalCatalogoVariables'));
    if (modalCatalogo) {
        modalCatalogo.hide();
    }
    const modalRef = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalReferenciaCatalogo'));
    modalRef.show();
}

if (typeof activeConfigNode === 'undefined') {
    let activeConfigNode = null;
}

function abrirConfiguracionBloque(idUnico) {
    activeConfigNode = document.getElementById(idUnico);
    if(!activeConfigNode) return;
    
    const tipo = activeConfigNode.dataset.bloque;
    
    // Ocultar todas las secciones del modal primero
    document.getElementById('config-section-logo').classList.add('d-none');
    document.getElementById('config-section-titulo').classList.add('d-none');
    document.getElementById('config-section-metadatos').classList.add('d-none');
    document.getElementById('config-section-tablas').classList.add('d-none');
    document.getElementById('config-section-firmas').classList.add('d-none');
    document.getElementById('config-section-linea').classList.add('d-none');
    
    if (tipo === 'logo') {
        Swal.fire({ icon: 'info', title: 'Componente básico', text: 'El logo se ajusta redimensionándolo directamente desde sus esquinas.', timer: 2500, showConfirmButton: false });
        return;
    } else if (tipo === 'titulo_colegio' || tipo === 'lema_colegio' || tipo === 'texto') {
        document.getElementById('config-section-titulo').classList.remove('d-none');
        const size = activeConfigNode.dataset.size || (tipo === 'titulo_colegio' ? '20' : '12');
        document.getElementById('config-titulo-size').value = size;
    } else if (tipo === 'metadatos') {
        document.getElementById('config-section-metadatos').classList.remove('d-none');
        const size = activeConfigNode.dataset.size || '16';
        document.getElementById('config-metadatos-size').value = size;
    } else if (tipo === 'linea') {
        document.getElementById('config-section-linea').classList.remove('d-none');
        const grosor = activeConfigNode.dataset.height || '1.5pt';
        const valorNumerico = parseFloat(grosor);
        document.getElementById('config-linea-grosor').value = valorNumerico;
        document.getElementById('config-linea-grosor-value').textContent = valorNumerico + ' pt';
    } else if (tipo === 'calificaciones') {
        document.getElementById('config-section-tablas').classList.remove('d-none');
        // Cargar valores existentes o por defecto
        const disenoSelect = document.getElementById('config-diseno');
        disenoSelect.value = activeConfigNode.dataset.diseno || 'elite';
        disenoSelect.dispatchEvent(new Event('change', { bubbles: true }));

        const filtroSelect = document.getElementById('config-filtro');
        filtroSelect.value = activeConfigNode.dataset.filtro || 'todas';
        filtroSelect.dispatchEvent(new Event('change', { bubbles: true }));
        
        const cols = (activeConfigNode.dataset.columnas || 'materia,docente,definitiva,estado').split(',');
        document.getElementById('col-materia').checked = cols.includes('materia');
        document.getElementById('col-docente').checked = cols.includes('docente');
        document.getElementById('col-definitiva').checked = cols.includes('definitiva');
        document.getElementById('col-estado').checked = cols.includes('estado');
    } else if (tipo === 'firmas') {
        document.getElementById('config-section-firmas').classList.remove('d-none');
        const selectCols = document.getElementById('config-firmas-columnas');
        selectCols.value = activeConfigNode.getAttribute('data-columnas') || activeConfigNode.dataset.columnas || '2';
        selectCols.dispatchEvent(new Event('change', { bubbles: true }));
    } else {
        // Bloques sin opciones configurables
        Swal.fire({ icon: 'info', title: 'Componente básico', text: 'Este bloque no requiere configuración adicional.', timer: 2000, showConfirmButton: false });
        return;
    }
    
    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalConfigBloque'));
    modal.show();
}

function guardarAjustesBloque(e) {
    e.preventDefault();
    if (!activeConfigNode) return;
    
    const tipo = activeConfigNode.dataset.bloque;
    
    if (tipo === 'logo') {
        // Obsoleto, logo usa redimensionado
        return;
    } else if (tipo === 'titulo_colegio' || tipo === 'lema_colegio' || tipo === 'texto') {
        const size = document.getElementById('config-titulo-size').value;
        activeConfigNode.dataset.size = size;
        const selector = tipo === 'titulo_colegio' ? '.ares-titulo-cabecera, h3, h2' : 
                        (tipo === 'lema_colegio' ? '.ares-lema-cabecera, p' : '.block-content-texto');
        const header = activeConfigNode.querySelector(selector);
        if (header) {
            header.style.fontSize = size + 'pt';
        }
        if (typeof autoAjustarAnchoBloqueTexto === 'function') {
            autoAjustarAnchoBloqueTexto(activeConfigNode);
        }
    } else if (tipo === 'metadatos') {
        const size = document.getElementById('config-metadatos-size').value;
        activeConfigNode.dataset.size = size;
        const header = activeConfigNode.querySelector('h4');
        if (header) {
            header.style.fontSize = size + 'pt';
        }
        if (typeof autoAjustarAnchoBloqueTexto === 'function') {
            autoAjustarAnchoBloqueTexto(activeConfigNode);
        }
    } else if (tipo === 'calificaciones') {
        const diseno = document.getElementById('config-diseno').value;
        const filtro = document.getElementById('config-filtro').value;
        activeConfigNode.dataset.diseno = diseno;
        activeConfigNode.dataset.filtro = filtro;
        
        const cols = [];
        if (document.getElementById('col-materia').checked) cols.push('materia');
        if (document.getElementById('col-docente').checked) cols.push('docente');
        if (document.getElementById('col-definitiva').checked) cols.push('definitiva');
        if (document.getElementById('col-estado').checked) cols.push('estado');
        
        const colsJoined = cols.join(',');
        activeConfigNode.setAttribute('data-columnas', colsJoined);
        activeConfigNode.dataset.columnas = colsJoined;
    } else if (tipo === 'firmas') {
        const cols = document.getElementById('config-firmas-columnas').value;
        activeConfigNode.setAttribute('data-columnas', cols);
        activeConfigNode.dataset.columnas = cols;
        
        // Disparar regeneración visual en el canvas si existe la función correspondiente
        if (typeof actualizarBloqueFirmasCanvas === 'function') {
            actualizarBloqueFirmasCanvas(activeConfigNode, parseInt(cols));
        }
    } else if (tipo === 'linea') {
        const grosor = document.getElementById('config-linea-grosor').value;
        activeConfigNode.dataset.height = grosor + 'pt';
        const lineaGrafica = activeConfigNode.querySelector('.ares-linea-grafica');
        if (lineaGrafica) {
            lineaGrafica.style['height'] = grosor + 'pt';
        }
        activeConfigNode.style['height'] = '15px';
    }

    bootstrap.Modal.getInstance(document.getElementById('modalConfigBloque')).hide();
    Swal.fire({ icon: 'success', title: 'Ajustes Aplicados', timer: 1500, showConfirmButton: false });
}

function prepararNuevoFormato() {
    document.getElementById('editor-title-label').textContent = 'Crear Formato Multidocumento';
    document.getElementById('formato-id').value = '0';
    document.getElementById('formato-nombre').value = '';
    document.getElementById('formato-descripcion').value = '';
    document.getElementById('formato-margen-superior').value = '20';
    document.getElementById('formato-margen-inferior').value = '20';
    document.getElementById('formato-cabecera-mm').value = '50';
    const modalCab = document.getElementById('modal-formato-cabecera-mm');
    if (modalCab) modalCab.value = '50';
    document.getElementById('formato-tipo-documento').value = 'matricula';
    document.getElementById('formato-tamano-lienzo').value = 'carta';
    if (typeof cambiarTamanoLienzoBuilder === 'function') cambiarTamanoLienzoBuilder('carta');

    // Resetear zona activa a CUERPO (body)
    activeZone = 'body';

    const canvas = document.getElementById('canvas-builder');
    if(canvas) {
        if (typeof initFormatosBuilder === 'function') initFormatosBuilder();
        canvas.innerHTML = `
            <div class="canvas-empty-state text-muted text-center py-5" id="canvas-empty-state">
                <div class="icon-circle-elite bg-primary bg-opacity-10 text-primary mx-auto mb-3 ares-icon-lg">
                    <i class="bi bi-layout-text-window"></i>
                </div>
                <p class="mt-2 mb-0 fw-bold text-uppercase fs-nano">Lienzo Técnico Ares Multi-Paper</p>
                <p class="small text-muted mt-1">Arrastre los bloques desde el panel izquierdo hacia este documento</p>
            </div>
        `;
        if (typeof updateZonesUI === 'function') updateZonesUI();
    }
}

function cancelarEdicion() {
    const triggerEl = document.querySelector('#formatos-tabs button[id="tab-lista-btn"]');
    if (triggerEl) {
        const tab = bootstrap.Tab.getOrCreateInstance(triggerEl);
        tab.show();
    }
}

function editarFormato(id) {
    const formData = new FormData();
    formData.append('action', 'obtener');
    formData.append('id', id);
    formData.append('csrf_token', window.CSRF_TOKEN || '');

    fetch('/sistema_escolar/php/logica/formatos_ajax.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                document.getElementById('editor-title-label').textContent = 'Editar Formato';
                document.getElementById('formato-id').value = res.data.id;
                document.getElementById('formato-nombre').value = res.data.nombre;
                document.getElementById('formato-descripcion').value = res.data.descripcion || '';
                document.getElementById('formato-margen-superior').value = res.data.margen_superior;
                document.getElementById('formato-margen-inferior').value = res.data.margen_inferior;
                document.getElementById('formato-margen-izquierdo').value = res.data.margen_izquierdo || 20;
                document.getElementById('formato-margen-derecho').value = res.data.margen_derecho || 20;
                document.getElementById('formato-cabecera-mm').value = 50;
                const modalCabInput = document.getElementById('modal-formato-cabecera-mm');
                if (modalCabInput) modalCabInput.value = 50;
                document.getElementById('formato-tipo-documento').value = res.data.tipo_documento || 'matricula';
                document.getElementById('formato-tamano-lienzo').value = res.data.tamano_lienzo || 'carta';

                // Resetear zona activa a CUERPO (body)
                activeZone = 'body';

                if (typeof cambiarTamanoLienzoBuilder === 'function') {
                    cambiarTamanoLienzoBuilder(res.data.tamano_lienzo || 'carta');
                }

                const canvas = document.getElementById('canvas-builder');
                if (typeof initFormatosBuilder === 'function') initFormatosBuilder();

                canvas.innerHTML = '';

                if (res.data.configuracion_json) {
                    try {
                        const blocks = Array.isArray(res.data.configuracion_json)
                            ? res.data.configuracion_json
                            : JSON.parse(res.data.configuracion_json);
                        blocks.forEach(block => {
                            insertarBloqueDesdeJSON(block);
                        });
                        if (typeof window.sincronizarValoresRealesBadges === 'function') {
                            window.sincronizarValoresRealesBadges();
                        }
                        if (typeof ajustarAlturaLienzo === 'function') ajustarAlturaLienzo();

                        // Cargar zonas si existen
                        if (res.data.zonas_config) {
                            const zonas = typeof res.data.zonas_config === 'string' ? JSON.parse(res.data.zonas_config) : res.data.zonas_config;
                            const headerLimit = zonas.header_limit_mm || 50;
                            document.getElementById('formato-cabecera-mm').value = headerLimit;
                            const modalCabeceraInput = document.getElementById('modal-formato-cabecera-mm');
                            if (modalCabeceraInput) modalCabeceraInput.value = headerLimit;
                            canvas.dataset.zoneHeaderMm = headerLimit;
                            canvas.dataset.zoneFooterMm = zonas.footer_limit_mm || 219.4;
                        }

                        if (typeof updateZonesUI === 'function') updateZonesUI();
                    } catch(e) {
                        canvas.innerHTML = '<div class="alert alert-danger m-4">Error al cargar la plantilla (JSON Invalido).</div>';
                    }
                } else if (res.data.contenido_html) {
                    // Fallback para formatos viejos que no tienen JSON (antes de la migración)
                    canvas.innerHTML = '<div class="alert alert-warning m-4"><i class="bi bi-exclamation-triangle me-2"></i><strong>Formato Heredado:</strong> Esta es una plantilla antigua guardada en HTML bruto. Recomendamos recrearla para evitar problemas de compatibilidad.</div>' + res.data.contenido_html;
                    canvas.querySelectorAll('.canvas-block-wrapper').forEach(wrapper => {
                        wrapper.addEventListener('mousedown', iniciarArrastreBloque);
                    });
                    if (typeof window.sincronizarValoresRealesBadges === 'function') {
                        window.sincronizarValoresRealesBadges();
                    }
                    if (typeof ajustarAlturaLienzo === 'function') ajustarAlturaLienzo();
                } else {
                    canvas.innerHTML = `
                        <div class="canvas-empty-state text-muted text-center py-5" id="canvas-empty-state">
                            <div class="icon-circle-elite bg-primary bg-opacity-10 text-primary mx-auto mb-3 ares-icon-lg">
                                <i class="bi bi-layout-text-window"></i>
                            </div>
                            <p class="mt-2 mb-0 fw-bold text-uppercase fs-nano">Lienzo Técnico A4</p>
                            <p class="small text-muted mt-1">Arrastre los bloques desde el panel izquierdo hacia este documento</p>
                        </div>
                    `;
                }
                
                const triggerEl = document.querySelector('#tab-editor-btn');
                if (triggerEl) {
                    const tab = bootstrap.Tab.getOrCreateInstance(triggerEl);
                    tab.show();
                }
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        });
}

function eliminarFormato(id) {
    Swal.fire({
        title: '¿Está seguro de eliminar esta plantilla?',
        text: "Esta acción no se puede deshacer.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: 'var(--el-danger)',
        cancelButtonColor: 'var(--el-text-muted)',
        confirmButtonText: 'Sí, Eliminar'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'eliminar');
            formData.append('id', id);
            formData.append('csrf_token', window.CSRF_TOKEN || '');

            fetch('/sistema_escolar/php/logica/formatos_ajax.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(async (res) => {
                if (res.status === 'success') {
                    // Remover tarjeta sin recarga
                    const tarjeta = document.querySelector(`.format-card[data-id="${id}"]`);
                    if (tarjeta) {
                        tarjeta.remove();
                        Swal.fire('Eliminado', 'Plantilla removida correctamente', 'success');
                    } else {
                        // Si no encuentra la tarjeta, navegar al módulo para refrescar
                        await Swal.fire('Eliminado', 'Plantilla removida correctamente', 'success');
                        navegarModulo('formatos_matricula', true);
                    }
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            });
        }
    });
}

// Vincular al scope global
window.abrirModalAjustesFormato = abrirModalAjustesFormato;
window.abrirReferenciaCatalogo = abrirReferenciaCatalogo;
window.abrirConfiguracionBloque = abrirConfiguracionBloque;
window.guardarAjustesBloque = guardarAjustesBloque;
window.prepararNuevoFormato = prepararNuevoFormato;
window.cancelarEdicion = cancelarEdicion;
window.editarFormato = editarFormato;
window.eliminarFormato = eliminarFormato;