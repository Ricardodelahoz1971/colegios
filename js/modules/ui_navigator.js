/**
 * 🎨 UI NAVIGATOR v1.0 - MOTOR DE INTERACCIÓN Y ESTÉTICA
 * Responsable del Modo Noche, Soberanía de Selects y validaciones de acceso.
 */

window.toggleDarkMode = async function() {
    const body = document.body;
    const isDark = body.classList.toggle('dark-theme-mode');
    
    const iconSun = document.getElementById('icon-sun');
    const iconMoon = document.getElementById('icon-moon');
    
    if (isDark) {
        iconSun?.classList.remove('u-hidden');
        iconMoon?.classList.add('u-hidden');
    } else {
        iconSun?.classList.add('u-hidden');
        iconMoon?.classList.remove('u-hidden');
    }

    const formData = new FormData();
    formData.append('dark_mode', isDark ? '1' : '0');
    formData.append('csrf_token', window.CSRF_TOKEN || '');

    try {
        await fetch('logica/guardar_estetica.php', { method: 'POST', body: formData });
    } catch (err) {
    }
};

window.validarFormulario = function(event) {
    let campoUsuario = document.getElementById('usuario');
    let campoPassword = document.getElementById('password');

    if (campoUsuario && campoPassword) {
        if (campoUsuario.value === '' || campoPassword.value === '') {
            Swal.fire({
                icon: 'info',
                title: '¡Faltan datos!',
                text: 'Por favor, no dejes campos vacíos para poder entrar.',
                customClass: { confirmButton: 'btn-elite btn-elite--primary px-4' }
            });
            event.preventDefault();
            return false;
        }
    }
};

// Inicialización
document.addEventListener('DOMContentLoaded', () => {
    // 🛡️ SOBERANÍA SELECTS: Desactivado por orden del Ing. Ricardo (Uso de CSS Nativo Vitrina 06)
    
    // Validación de Login
    const form = document.querySelector('form');
    if (form) form.addEventListener('submit', window.validarFormulario);

    // --- 🔍 MOTOR UNIVERSAL DE BÚSQUEDA ÉLITE ---
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('search-elite')) {
            const wrapper = e.target.closest('.search-wrapper-elite');
            if (wrapper) {
                const btnClear = wrapper.querySelector('.search-clear-elite');
                if (btnClear) {
                    btnClear.classList.toggle('u-flex', e.target.value.length > 0);
                    btnClear.classList.toggle('u-hidden', e.target.value.length === 0);
                }
            }
        }
    });

    document.addEventListener('click', function(e) {
        const btnClear = e.target.closest('.search-clear-elite');
        if (btnClear) {
            const wrapper = btnClear.closest('.search-wrapper-elite');
            const input = wrapper ? wrapper.querySelector('.search-elite') : null;
            if (input) {
                input.value = '';
                btnClear.classList.add('u-hidden');
                btnClear.classList.remove('u-flex');
                input.focus();
                input.dispatchEvent(new Event('input', { bubbles: true }));
            }
        }
    });

    // --- LÓGICA DE BÚSQUEDA DINÁMICA UNIVERSAL (Filtros SPA) ---
    window.aplicarFiltroElite = function(valor = null) {
        const buscador = document.querySelector('.buscador-dinamico');
        if (!buscador && valor === null) return;
        
        const rawTerm = (valor !== null ? valor : (buscador?.value || '')).toString().trim();
        const tabla = document.querySelector('.tabla-datos');
        if (!tabla) return;

        const filas = tabla.querySelectorAll('tbody tr');
        const btnClear = document.querySelector('.btn-clear-search');
        
        if (!rawTerm) {
            filas.forEach(fila => fila.classList.remove('u-hidden'));
            if (btnClear) btnClear.classList.add('u-hidden');
            return;
        }

        const tokens = rawTerm
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .split(/\s+/)
            .filter(t => t.length > 0);

        filas.forEach(fila => {
            if (fila.querySelector('td[colspan]')) return;

            const rowText = (fila.textContent || '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase();

            const matchesAll = tokens.every(token => rowText.includes(token));
            fila.classList.toggle('u-hidden', !matchesAll);
        });

        if (btnClear) btnClear.classList.toggle('u-hidden', rawTerm.length === 0);
    };

    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('buscador-dinamico')) window.aplicarFiltroElite();
    });

    document.addEventListener('click', function(e) {
        const btnClear = e.target.closest('.btn-clear-search');
        if (btnClear) {
            const wrapper = btnClear.closest('.search-wrapper-elite');
            const input = wrapper ? wrapper.querySelector('.buscador-dinamico') : null;
            if (input) {
                input.value = '';
                window.aplicarFiltroElite('');
                input.focus();
            }
        }
    });

    // --- Lector de Parámetros URL (SweetAlerts de Feedback) ---

    // --- Lector de Parámetros URL (SweetAlerts de Feedback) ---
    const urlParams = new URLSearchParams(window.location.search);
    const error = urlParams.get('error');
    const mensaje = urlParams.get('mensaje');

    if (error || mensaje) {
        if (error === 'clave') {
            lanzarToastElite('danger', 'La contraseña ingresada es incorrecta.', 'Acceso Denegado');
        } else if (error === 'usuario') {
            lanzarToastElite('danger', 'Ese nombre de usuario no existe.', 'Usuario No Encontrado');
        } else if (error === 'duplicado') {
            lanzarToastElite('warning', 'Esta identificación ya se encuentra registrada en el sistema.');
        } else if (error === 'protegido') {
            lanzarToastElite('danger', 'Este recurso institucional está protegido por el sistema.', 'Acción Bloqueada');
        } else if (error === 'protegido_alumnos') {
            lanzarToastElite('danger', 'No puedes eliminar un curso que aún tiene alumnos matriculados.', 'Acción Bloqueada');
        } else if (error === 'duplicado_materia') {
            lanzarToastElite('warning', 'Esta materia ya ha sido asignada a este curso anteriormente.');
        }

        const emitirToast = (title) => {
            if (typeof window.lanzarToastElite === 'function') {
                window.lanzarToastElite('success', title);
            }
        };

        if (mensaje === 'rol_creado') emitirToast('¡Rol Guardado!');
        if (mensaje === 'rol_editado') emitirToast('¡Rol Actualizado!');
        if (mensaje === 'rol_borrado') emitirToast('¡Rol Eliminado!');
        if (mensaje === 'permisos_rol_actualizados') emitirToast('¡Leyes de Rol Actualizadas!');
        if (mensaje === 'permisos_personalizados') emitirToast('¡Permisos Custom Aplicados con Éxito!');
        if (mensaje === 'personal_registrado') emitirToast('¡Personal Registrado!');
        if (mensaje === 'personal_editado') emitirToast('¡Perfil Actualizado!');
        if (mensaje === 'personal_borrado') emitirToast('¡Baja Confirmada!');
        if (mensaje === 'insertado') emitirToast('¡Alumno Matriculado!');
        if (mensaje === 'especialidad_creada') emitirToast('¡Nueva Especialidad!');
        if (mensaje === 'especialidad_editada') emitirToast('¡Especialidad Actualizada!');
        if (mensaje === 'especialidad_borrada') emitirToast('¡Especialidad Eliminada!');
        if (mensaje === 'area_creada') emitirToast('¡Nueva Área!');
        if (mensaje === 'area_editada') emitirToast('¡Área Actualizada!');
        if (mensaje === 'area_borrada') emitirToast('¡Área Eliminada!');
        if (mensaje === 'curso_creado') emitirToast('¡Curso Creado!');
        if (mensaje === 'curso_editado') emitirToast('¡Curso Actualizado!');
        if (mensaje === 'curso_borrado') emitirToast('¡Curso Eliminado!');
        if (mensaje === 'carga_guardada') emitirToast('¡Materia Asignada!');
        if (mensaje === 'carga_eliminada') emitirToast('¡Materia Retirada!');
        if (mensaje === 'guardado') emitirToast('¡Sincronización Élite Exitosa!');
        if (mensaje === 'reset') emitirToast('¡ADN de Fábrica Restaurado!');

        window.history.replaceState({}, document.title, window.location.pathname);
    }
});
