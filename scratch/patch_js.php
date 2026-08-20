<?php
$file = 'c:\xampp\htdocs\sistema_escolar\js\modules\formatos_matricula_builder.js';
$content = file_get_contents($file);

$start = strpos($content, 'async function guardarFormato(e, salir = true) {');
$end = strpos($content, 'window.sincronizarValoresRealesBadges = function() {');

if ($start !== false && $end !== false) {
    $primera_parte = substr($content, 0, $start);
    $segunda_parte = substr($content, $end);
    
    $nuevo_guardar = "async function guardarFormato(e, salir = true) {
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
        Swal.fire('Aviso', 'El lienzo de construccin no puede estar vaco.', 'warning');
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
                contenidoCaja.innerHTML = `<span class=\"ares-variable-badge\" contenteditable=\"false\" data-var=\"\${varCodigo}\">[ \${varCodigo} ]</span>`;
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
                Swal.fire('xito', 'Formato guardado correctamente.', 'success');
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

";

    file_put_contents($file, $primera_parte . $nuevo_guardar . $segunda_parte);
    echo "¡JS parchado!\n";
} else {
    echo "Fallo\n";
}
