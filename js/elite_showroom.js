// 1. Sincronización Maestra: Cargar paletas desde la base de datos si están disponibles
const palettes = window.elitePalettesData || {
    1: { primary: '#204192', prgb: '32, 65, 146', accent: '#f0bb1c', argb: '240, 187, 28', info: '#0098da', irgb: '0, 152, 218', success: '#059669', srgb: '5, 150, 105', danger: '#dc2626', drgb: '220, 38, 38' }
};

let modified_palettes = new Set();

// Inicialización Maestra v9.3
document.addEventListener('DOMContentLoaded', () => {
    // Restaurar Renombrado por Doble Click
    document.querySelectorAll('.palette-name-elite').forEach(nameTag => {
        nameTag.addEventListener('dblclick', async function() {
            const currentName = this.innerText;
            const swatch = this.closest('.palette-swatch-elite');
            const index = swatch.getAttribute('data-index');
            
            const result = await Swal.fire({
                title: 'RENOMBRAR IDENTIDAD',
                input: 'text',
                inputValue: currentName,
                showCancelButton: true,
                confirmButtonText: 'ACTUALIZAR',
                cancelButtonText: 'CANCELAR',
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn-elite btn-elite--primary mx-2 px-4',
                    cancelButton: 'btn-elite btn-elite--outline mx-2 px-4',
                    input: 'form-control border-2 text-center w-75 mx-auto rounded-pill shadow-sm'
                }
            });
            
            if (result.isConfirmed && result.value) {
                const nuevoNombre = result.value.toUpperCase();
                this.innerText = nuevoNombre;
                
                // Guardar nombre en DB para persistencia
                const fd = new FormData();
                fd.append('nombre', nuevoNombre);
                fd.append('primary_color', palettes[index].primary);
                fd.append('accent_color', palettes[index].accent);
                fd.append('info_color', palettes[index].info);
                try {
                    await fetch('logica/guardar_paleta.php', { method: 'POST', body: fd });
                } catch(e) {}
            }
        });
    });

    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

function hexToRgbJS(hex) {
    hex = hex.replace('#', '');
    if (hex.length === 3) hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
    const r = parseInt(hex.substring(0, 2), 16);
    const g = parseInt(hex.substring(2, 4), 16);
    const b = parseInt(hex.substring(4, 6), 16);
    return `${r}, ${g}, ${b}`;
}

/**
 * ACTUALIZACIÓN QUIRÚRGICA DE COLOR
 */
function updatePaletteColor(index, type, color, el) {
    const p = palettes[index];
    if (!p) return;

    p[type] = color;
    if (type === 'primary') p.prgb = hexToRgbJS(color);
    if (type === 'accent') p.argb = hexToRgbJS(color);
    if (type === 'info') p.irgb = hexToRgbJS(color);

    // Feedback visual inmediato en la muestra (círculo)
    if (el && el.parentElement) {
        el.parentElement.style.setProperty('--el-palette-color', color);
    }

    modified_palettes.add(index);
    setGlobalPalette(index);
}

function setGlobalPalette(index) {
    current_dna_index = index;
    const root = document.documentElement;
    const p = palettes[index];
    if(!p) return;

    root.style.setProperty('--el-primary', p.primary);
    root.style.setProperty('--el-primary-rgb', hexToRgbJS(p.primary));
    root.style.setProperty('--el-accent', p.accent);
    root.style.setProperty('--el-accent-rgb', hexToRgbJS(p.accent));
    
    const sidebar = document.querySelector('.sim-sidebar-elite');
    const cardBar = document.querySelector('.sim-card-bar-elite');
    if (sidebar) sidebar.style.setProperty('--el-primary', p.primary);
    if (cardBar) cardBar.style.setProperty('--el-primary', p.primary);
    
    document.querySelectorAll('.palette-swatch-elite').forEach(sw => sw.classList.remove('active'));
    const activeSwatch = document.querySelector(`.palette-swatch-elite[data-index="${index}"]`);
    if(activeSwatch) activeSwatch.classList.add('active');
}

async function propagacionAdnMaestro() {
    if(!current_dna_index) {
        Swal.fire({ icon: 'info', title: 'SELECCIÓN REQUERIDA', text: 'Elige una identidad institucional.', buttonsStyling: false, customClass: { confirmButton: 'btn-elite btn-elite--primary' } });
        return;
    }

    const p = palettes[current_dna_index];
    const isModified = modified_palettes.has(current_dna_index);

    if (isModified) {
        const res = await Swal.fire({
            title: 'PALETA MODIFICADA',
            text: '¿Deseas guardar estos cambios como una NUEVA paleta en el catálogo?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'SÍ, GUARDAR NUEVA',
            cancelButtonText: 'NO, SOLO APLICAR',
            buttonsStyling: false,
            customClass: { confirmButton: 'btn-elite btn-elite--success mx-2', cancelButton: 'btn-elite btn-elite--primary mx-2' }
        });
        
        if (res.isConfirmed) {
            // Guardar como nueva antes de propagar
            const nameRes = await Swal.fire({
                title: 'NOMBRE DE LA NUEVA IDENTIDAD',
                input: 'text',
                inputPlaceholder: 'Ej: Élite Verano...',
                showCancelButton: true,
                confirmButtonText: 'GUARDAR E INSTALAR',
                buttonsStyling: false,
                customClass: { 
                    confirmButton: 'btn-elite btn-elite--success',
                    input: 'form-control border-2 text-center w-75 mx-auto rounded-pill shadow-sm'
                }
            });
            
            if (nameRes.isConfirmed && nameRes.value) {
                const fd = new FormData();
                fd.append('nombre', nameRes.value.toUpperCase());
                fd.append('primary_color', p.primary);
                fd.append('accent_color', p.accent);
                fd.append('info_color', p.info);
                
                try {
                    const r = await fetch('logica/guardar_paleta.php', { method: 'POST', body: fd });
                    const data = await r.json();
                    if (data.status === 'success') {
                        ejecutarPropagacion(p, data.id);
                    }
                } catch(e) {}
            }
        } else if (res.dismiss === Swal.DismissReason.cancel) {
            ejecutarPropagacion(p, current_dna_index);
        }
    } else {
        ejecutarPropagacion(p, current_dna_index);
    }
}

async function ejecutarPropagacion(p, paletteId) {
    const fd = new FormData();
    fd.append('brand_color', p.primary);
    fd.append('brand_accent', p.accent);
    fd.append('brand_info', p.info);
    fd.append('brand_hover', p.primary);
    fd.append('sidebar_bg', p.primary);
    fd.append('active_palette_id', paletteId);
    
    try {
        await fetch('logica/guardar_estetica.php', { method: 'POST', body: fd });
        await Swal.fire({ 
            icon: 'success', 
            title: 'ADN PROPAGADO', 
            text: 'La identidad institucional ha sido actualizada correctamente.',
            timer: 2000, 
            showConfirmButton: false 
        });
    } catch(e) {}
}

async function eliminarPaleta(id, event) {
    if (event) event.stopPropagation();

    // Bloqueo de seguridad para paletas maestras (asumiendo que las 4 primeras son intocables)
    if (id <= 4) {
        Swal.fire({ icon: 'error', title: 'ACCESO DENEGADO', text: 'No se pueden eliminar las identidades maestras del sistema.' });
        return;
    }

    const res_confirm = await Swal.fire({
        title: '¿ELIMINAR PALETA?',
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'ELIMINAR',
        cancelButtonText: 'CANCELAR',
        buttonsStyling: false,
        customClass: { confirmButton: 'btn-elite btn-elite--danger mx-2', cancelButton: 'btn-elite btn-elite--outline mx-2' }
    });

    if (res_confirm.isConfirmed) {
        try {
            const fd = new FormData();
            fd.append('id', id);
            
            const response = await fetch('logica/eliminar_paleta.php', { method: 'POST', body: fd });
            const data = await response.json();

            if (data.status === 'success') {
                const activeId = document.querySelector('.palette-swatch-elite.active')?.getAttribute('data-index');
                if (id == activeId) {
                    ejecutarPropagacion(palettes[1], 1);
                } else {
                    await Swal.fire({ icon: 'success', title: 'ELIMINADA', timer: 1500, showConfirmButton: false });
                    if (typeof navegarModulo === 'function') navegarModulo('configuracion');
                }
            } else {
                Swal.fire({ icon: 'error', title: 'ERROR', text: data.message });
            }
        } catch (error) {
            Swal.fire('Error', 'Fallo de comunicación con el motor de paletas.', 'error');
        }
    }
}

/**
 * CONFIRMACIÓN DE GUARDADO AL PERDER FOCO
 */
async function confirmPaletteSave(index) {
    const p = palettes[index];
    const currentName = document.getElementById(`palette-name-${index}`).innerText;

    const res = await Swal.fire({
        title: 'CAMBIOS DETECTADOS',
        text: `¿Deseas guardar esta configuración en el catálogo de identidades?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'SÍ, GUARDAR',
        cancelButtonText: 'MANTENER SOLO SESIÓN',
        buttonsStyling: false,
        customClass: { 
            confirmButton: 'btn-elite btn-elite--success mx-2', 
            cancelButton: 'btn-elite btn-elite--outline mx-2' 
        }
    });
    
    if (res.isConfirmed) {
        const nameRes = await Swal.fire({
            title: 'NOMBRE DE LA IDENTIDAD',
            input: 'text',
            inputValue: currentName,
            showCancelButton: true,
            confirmButtonText: 'GUARDAR E INSTALAR',
            buttonsStyling: false,
            customClass: { 
                confirmButton: 'btn-elite btn-elite--success',
                input: 'form-control border-2 text-center w-75 mx-auto rounded-pill shadow-sm'
            }
        });
        
        if (nameRes.isConfirmed && nameRes.value) {
            const fd = new FormData();
            fd.append('nombre', nameRes.value.toUpperCase());
            fd.append('primary_color', p.primary);
            fd.append('accent_color', p.accent);
            fd.append('info_color', p.info);
            
            try {
                const r = await fetch('logica/guardar_paleta.php', { method: 'POST', body: fd });
                const data = await r.json();
                if (data.status === 'success') {
                    ejecutarPropagacion(p, data.id);
                }
            } catch(e) {}
        }
    }
}

/**
 * Motor Independiente de Arquitectura de Navegación
 */
async function actualizarArquitecturaNavegacion() {
    const menuStyle = document.getElementById('menu-style-selector').value;
    
    const result = await Swal.fire({
        title: '¿CAMBIAR ESTRUCTURA?',
        text: 'Se actualizará el modelo de navegación principal para todos los usuarios.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'SÍ, ACTUALIZAR',
        cancelButtonText: 'CANCELAR',
        buttonsStyling: false,
        customClass: { 
            popup: 'rounded-4 shadow-lg border-0',
            confirmButton: 'btn-elite btn-elite--primary mx-2 px-4', 
            cancelButton: 'btn-elite btn-elite--outline mx-2 px-4' 
        }
    });
    
    if (result.isConfirmed) {
        const fd = new FormData();
        fd.append('menu_style', menuStyle);
        
        try {
            await fetch('logica/guardar_estetica.php', { method: 'POST', body: fd });
            await Swal.fire({ 
                icon: 'success', 
                title: '¡ESTRUCTURA ACTUALIZADA!', 
                text: 'El nuevo modelo de navegación ha sido establecido.', 
                timer: 2000, 
                showConfirmButton: false,
                customClass: { popup: 'rounded-4 shadow-lg border-0' }
            });
            window.location.href = window.location.pathname + '?p=configuracion&v=' + new Date().getTime();
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo guardar la configuración estructural.' });
        }
    }
}

/**
 * Motor de Propagación Tipográfica
 */
async function actualizarTipografiaMaestra() {
    const fontName = document.getElementById('font-family-selector').value;
    
    const result = await Swal.fire({
        title: '¿CAMBIAR ADN TEXTUAL?',
        text: 'Se aplicará la fuente ' + fontName + ' a toda la interfaz del sistema.',
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'SÍ, APLICAR FUENTE',
        cancelButtonText: 'CANCELAR',
        buttonsStyling: false,
        customClass: { 
            popup: 'rounded-4 shadow-lg border-0',
            confirmButton: 'btn-elite btn-elite--primary mx-2 px-4', 
            cancelButton: 'btn-elite btn-elite--outline mx-2 px-4' 
        }
    });
    
    if (result.isConfirmed) {
        const fd = new FormData();
        fd.append('school_font', fontName);
        
        try {
            await fetch('logica/guardar_estetica.php', { method: 'POST', body: fd });
            await Swal.fire({ 
                icon: 'success', 
                title: '¡TIPOGRAFÍA ACTUALIZADA!', 
                text: 'La identidad textual ha sido renovada.', 
                timer: 2000, 
                showConfirmButton: false,
                customClass: { popup: 'rounded-4 shadow-lg border-0' }
            });
            window.location.href = window.location.pathname + '?p=configuracion&v=' + new Date().getTime();
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo guardar la configuración tipográfica.' });
        }
    }
}

// Inicialización Maestra v9.2
document.addEventListener('DOMContentLoaded', () => {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

