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
        if (!buscador) return;
        
        const termino = (valor !== null ? valor : buscador.value).toLowerCase();
        const tabla = document.querySelector('.tabla-datos');
        if (!tabla) return;

        const filas = tabla.querySelectorAll('tbody tr');
        const btnClear = document.querySelector('.btn-clear-search');
        
        filas.forEach(fila => {
            const texto = fila.textContent.toLowerCase();
            fila.classList.toggle('u-hidden', !texto.includes(termino));
        });

        if (btnClear) btnClear.classList.toggle('u-hidden', termino.length === 0);
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
        const btnDangerClass = 'btn-elite btn-elite--danger px-4';
        const btnWarningClass = 'btn-elite btn-elite--warning px-4';

        if (error === 'clave') {
            Swal.fire({ icon: 'error', title: 'Acceso Denegado', text: 'La contraseña es incorrecta.', customClass: { confirmButton: btnDangerClass } });
        } else if (error === 'usuario') {
            Swal.fire({ icon: 'question', title: '¿Quién eres?', text: 'Ese nombre de usuario no existe.', customClass: { confirmButton: btnDangerClass } });
        } else if (error === 'duplicado') {
            Swal.fire({ icon: 'warning', title: 'Atención', text: 'Esta identificación ya existe.', customClass: { confirmButton: btnWarningClass } });
        } else if (error === 'protegido') {
            Swal.fire({ icon: 'error', title: 'Acción Bloqueada', text: 'Este recurso está protegido.', customClass: { confirmButton: btnDangerClass } });
        } else if (error === 'protegido_alumnos') {
            Swal.fire({ icon: 'error', title: 'Acción Bloqueada', text: 'No puedes eliminar un curso que aún tiene alumnos matriculados.', customClass: { confirmButton: btnDangerClass } });
        } else if (error === 'duplicado_materia') {
            Swal.fire({ icon: 'warning', title: 'Ojo con eso', text: 'Esta materia ya ha sido asignada a este curso anteriormente.', customClass: { confirmButton: btnWarningClass } });
        }

        const successToast = (title) => {
            Swal.fire({ icon: 'success', title: title, timer: 1500, showConfirmButton: false });
        };

        if (mensaje === 'rol_creado') successToast('¡Rol Guardado!');
        if (mensaje === 'rol_editado') successToast('¡Rol Actualizado!');
        if (mensaje === 'rol_borrado') successToast('¡Rol Eliminado!');
        if (mensaje === 'permisos_rol_actualizados') successToast('¡Leyes de Rol Actualizadas!');
        if (mensaje === 'permisos_personalizados') successToast('¡Permisos Custom Aplicados con Éxito!');
        if (mensaje === 'personal_registrado') successToast('¡Personal Registrado!');
        if (mensaje === 'personal_editado') successToast('¡Perfil Actualizado!');
        if (mensaje === 'personal_borrado') successToast('¡Baja Confirmada!');
        if (mensaje === 'insertado') successToast('¡Alumno Matriculado!');
        if (mensaje === 'especialidad_creada') successToast('¡Nueva Especialidad!');
        if (mensaje === 'especialidad_editada') successToast('¡Especialidad Actualizada!');
        if (mensaje === 'especialidad_borrada') successToast('¡Especialidad Eliminada!');
        if (mensaje === 'area_creada') successToast('¡Nueva Área!');
        if (mensaje === 'area_editada') successToast('¡Área Actualizada!');
        if (mensaje === 'area_borrada') successToast('¡Área Eliminada!');
        if (mensaje === 'curso_creado') successToast('¡Curso Creado!');
        if (mensaje === 'curso_editado') successToast('¡Curso Actualizado!');
        if (mensaje === 'curso_borrado') successToast('¡Curso Eliminado!');
        if (mensaje === 'carga_guardada') successToast('¡Materia Asignada!');
        if (mensaje === 'carga_eliminada') successToast('¡Materia Retirada!');
        if (mensaje === 'guardado') successToast('¡Sincronización Élite Exitosa!');
        if (mensaje === 'reset') successToast('¡ADN de Fábrica Restaurado!');

        window.history.replaceState({}, document.title, window.location.pathname);
    }
});
