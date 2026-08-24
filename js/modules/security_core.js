/**
 * 🛡️ SECURITY CORE v1.0 - ESCUDO DE DATOS Y PROTOCOLO CSRF
 * Responsable de la integridad de las peticiones POST y la sincronización de tokens.
 */

window.enviarPostElite = async function(url, params = {}, silencioso = false) {
    try {
        const formData = (params instanceof FormData) ? params : new FormData();
        
        if (!(params instanceof FormData)) {
            formData.append('csrf_token', window.CSRF_TOKEN || '');
            for (const [key, value] of Object.entries(params)) {
                formData.append(key, value);
            }
        } else {
            if (!formData.has('csrf_token')) formData.append('csrf_token', window.CSRF_TOKEN || '');
        }

        // Mostrar Loader de Prestigio (Solo si no es silencioso)
        if (!silencioso) {
            Swal.fire({
                title: 'Procesando...',
                html: 'Sincronizando con la Bóveda de Datos',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
        }

        const response = await fetch(url, { method: 'POST', body: formData });
        
        if (response.redirected) {
            window.location.href = response.url;
            return;
        }

        const data = await response.json();
        
        // 🔒 CERRAR MODAL DE CARGA SI SE ABRIÓ
        if (!silencioso && typeof Swal !== 'undefined' && typeof Swal.close === 'function') {
            Swal.close();
        }

        if (!data) return; 

        if (data.status === 'success') {
            if (!silencioso) {
                if (typeof window.lanzarToastElite === 'function') {
                    window.lanzarToastElite('success', data.message || 'Operación completada.');
                }

                if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    if (typeof navegarModulo === 'function') {
                        window.forceRefreshElite = true;
                        navegarModulo(window.MODULO_ACTUAL || 'inicio');
                    }
                }
            }
            return data; // Devolver datos para procesamiento manual soberano
        } else {
            if (typeof window.lanzarToastElite === 'function') {
                window.lanzarToastElite('danger', data.message || 'Error desconocido', 'Error de Bóveda');
            } else {
                Swal.fire('Error de Bóveda', data.message || 'Error desconocido', 'error');
            }
            return data;
        }
    } catch (error) {
        if (!silencioso) {
            if (typeof Swal !== 'undefined' && typeof Swal.close === 'function') {
                Swal.close();
            }
            if (typeof window.lanzarToastElite === 'function') {
                window.lanzarToastElite('danger', 'El servidor no respondió en el formato esperado o hubo un error de red.', 'Falla Crítica');
            } else {
                Swal.fire('Falla Crítica', 'El servidor no respondió en el formato esperado o hubo un error de red.', 'error');
            }
        }
    }
};

// Inyector Global de CSRF para formularios tradicionales
document.addEventListener('submit', function(e) {
    if (e.target.tagName === 'FORM' && e.target.method.toLowerCase() === 'post') {
        if (!e.target.querySelector('input[name="csrf_token"]')) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'csrf_token';
            input.value = window.CSRF_TOKEN;
            e.target.appendChild(input);
        }
    }
});
