(function() {
    /**
     * MOTOR DE CONFIGURACIÓN ÉLITE - HEFESTO v18.0
     * Protocolo: Vitrina 06 | Seguridad: Blindada | Reactividad: Total
     */
    
    const sincronizarColoresPaletas = () => {
        document.querySelectorAll('.color-circle-elite-micro[data-bg]').forEach(el => {
            el.style.setProperty('--swatch-bg', el.getAttribute('data-bg'));
        });
    };

    window.sincronizarPestañaElite = () => {
        const hash = window.location.hash;
        if (hash && typeof bootstrap !== 'undefined') {
            const tabEl = document.querySelector(`button[data-bs-target="${hash}"]`);
            if (tabEl) {
                try {
                    const tab = new bootstrap.Tab(tabEl);
                    tab.show();
                } catch(err) {
                    // Ignorar silenciosamente si no se puede abrir la pestaña
                }
            }
        }
    };

    /**
     * 🎨 GESTIÓN DE PALETAS (Sincronización v2.0)
     */
    window.abrirEditorPaleta = async (datos = null) => {
        const esNueva = !datos;
        const result = await Swal.fire({
            title: esNueva ? 'FORJAR NUEVA IDENTIDAD' : 'REFINAR IDENTIDAD',
            html: `
                <div class="p-3">
                    <input id="swal-nombre" class="input-elite mb-3" placeholder="Nombre de la Identidad" value="${datos?.nombre || ''}">
                    <div class="row g-3">
                        <div class="col-4 text-center">
                            <label class="fs-nano fw-bold text-muted d-block mb-2">PRIMARIO</label>
                            <input type="color" id="swal-primary" class="form-control form-control-color w-100 rounded-circle" value="${datos?.p || 'var(--el-primary)'}">
                        </div>
                        <div class="col-4 text-center">
                            <label class="fs-nano fw-bold text-muted d-block mb-2">ACENTO</label>
                            <input type="color" id="swal-accent" class="form-control form-control-color w-100 rounded-circle" value="${datos?.a || 'var(--el-primary)'}">
                        </div>
                        <div class="col-4 text-center">
                            <label class="fs-nano fw-bold text-muted d-block mb-2">INFO</label>
                            <input type="color" id="swal-info" class="form-control form-control-color w-100 rounded-circle" value="${datos?.i || 'var(--el-primary)'}">
                        </div>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: esNueva ? 'FORJAR' : 'CONTINUAR',
            customClass: { confirmButton: 'btn-elite', cancelButton: 'btn-elite--outline' },
            preConfirm: () => {
                const nombre = document.getElementById('swal-nombre').value;
                if (!nombre) return Swal.showValidationMessage('Se requiere un nombre de identidad');
                return {
                    id: datos?.id || null,
                    nombre: nombre,
                    p: document.getElementById('swal-primary').value,
                    a: document.getElementById('swal-accent').value,
                    i: document.getElementById('swal-info').value
                };
            }
        });

        if (result.isConfirmed) {
            const payload = result.value;

            // ⚖️ LEY DE BIFURCACIÓN: Si estamos editando, preguntamos
            if (payload.id) {
                const choice = await Swal.fire({
                    title: 'PROTOCOLO DE GUARDADO',
                    text: '¿Desea actualizar la paleta actual o crear una nueva con estos cambios?',
                    icon: 'question',
                    showDenyButton: true,
                    showCancelButton: true,
                    confirmButtonText: 'ACTUALIZAR ESTA',
                    denyButtonText: 'CREAR PALETA NUEVA',
                    cancelButtonText: 'CANCELAR',
                    customClass: { 
                        confirmButton: 'btn-elite', 
                        denyButton: 'btn-elite--accent', 
                        cancelButton: 'btn-elite--outline' 
                    }
                });

                if (choice.isConfirmed) {
                    ejecutarGuardadoPaleta(payload); // Actualiza
                } else if (choice.isDenied) {
                    payload.id = null; // Forza creación de nueva
                    payload.nombre = payload.nombre + " (COPIA)"; // Diferenciación de Identidad
                    ejecutarGuardadoPaleta(payload);
                }
            } else {
                ejecutarGuardadoPaleta(payload); // Creación directa
            }
        }
    };

    const ejecutarGuardadoPaleta = async (payload) => {
        try {
            const fd = new FormData();
            if (payload.id) fd.append('id', payload.id);
            fd.append('nombre', payload.nombre);
            fd.append('primary_color', payload.p);
            fd.append('accent_color', payload.a);
            fd.append('info_color', payload.i);
            fd.append('accion', 'guardar_paleta');
            fd.append('csrf_token', document.querySelector('input[name="csrf_token"]')?.value || '');

            const res = await fetch('logica/guardar_estetica.php', { method: 'POST', body: fd });
            const data = await res.json();

            if (data.status === 'success') {
                await Swal.fire({title: 'IDENTIDAD FORJADA', text: 'La nueva configuración estética se ha sincronizado en el oráculo.', icon: 'success', timer: 1500, showConfirmButton: false});
                if (typeof navegarModulo === 'function') {
                    navegarModulo('configuracion&success=paleta');
                } else {
                    window.location.href = window.location.href;
                }
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        } catch (error) {
            Swal.fire('FALLO CRÍTICO', 'Error en la sincronización de identidad.', 'error');
        }
    };

    window.aplicarIdentidadElite = async (id) => {
        try {
            const fd = new FormData();
            fd.append('paleta_id', id);
            fd.append('accion', 'aplicar_paleta');
            fd.append('csrf_token', window.CSRF_TOKEN || '');

            const res = await fetch('logica/guardar_estetica.php', { method: 'POST', body: fd });
            const data = await res.json();

            if (data.status === 'success') {
                await Swal.fire('IDENTIDAD APLICADA', 'Sincronizando ADN institucional...', 'success');
                window.location.href = window.location.href;
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        } catch (error) {
            Swal.fire('ERROR DE VUELO', 'No se pudo aplicar la identidad seleccionada.', 'error');
        }
    };

    window.borrarPaleta = async (id, nombre) => {
        const result = await Swal.fire({
            title: '¿ELIMINAR IDENTIDAD?',
            text: `Se eliminará "${nombre}" permanentemente.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'SÍ, ELIMINAR',
            customClass: { confirmButton: 'btn-elite btn-elite--danger px-4', cancelButton: 'btn-elite btn-elite--outline px-4 ms-2' }
        });

        if (result.isConfirmed) {
            try {
                const fd = new FormData();
                fd.append('id', id);
                fd.append('accion', 'borrar_paleta');
                fd.append('csrf_token', document.querySelector('input[name="csrf_token"]')?.value || '');

                const res = await fetch('logica/guardar_estetica.php', { method: 'POST', body: fd });
                const data = await res.json();

                if (data.status === 'success') {
                    await Swal.fire({title: 'IDENTIDAD PURGADA', text: 'Registro eliminado del núcleo.', icon: 'success', timer: 1500, showConfirmButton: false});
                    if (typeof navegarModulo === 'function') {
                        navegarModulo('configuracion&success=paleta');
                    } else {
                        window.location.href = window.location.href;
                    }
                }
            } catch (error) {
                Swal.fire('Error', 'No se pudo eliminar la paleta.', 'error');
            }
        }
    };

    window.actualizarArquitecturaMenu = async (valor) => {
        try {
            const token = window.CSRF_TOKEN || '';
            const fd = new FormData();
            fd.append('menu_style', valor);
            fd.append('csrf_token', token);

            const res = await fetch('logica/guardar_estetica.php', { method: 'POST', body: fd });
            const data = await res.json();

            if (data.status === 'success') {
                await Swal.fire({
                    title: 'ARQUITECTURA ACTUALIZADA',
                    text: 'Sincronizando estética institucional...',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                });
                // Actualizar DOM con la nueva arquitectura de menú sin recargar la página
                if (typeof navegarModulo === 'function') {
                    navegarModulo('configuracion&success=menu');
                } else {
                    // Refrescar tema CSS sin reload
                    document.documentElement.setAttribute('data-menu-style', valor);
                }
            } else {
                Swal.fire('ERROR DE SEGURIDAD', data.message, 'error');
            }
        } catch (err) {
            Swal.fire('FALLO DE COMUNICACIÓN', 'No se pudo conectar con el motor de temas.', 'error');
        }
    };

    window.cambiarTipografiaSistema = async (valor) => {
        try {
            const fd = new FormData();
            fd.append('school_font', valor);
            fd.append('csrf_token', document.querySelector('input[name="csrf_token"]')?.value || '');

            const res = await fetch('logica/guardar_estetica.php', { method: 'POST', body: fd });
            const data = await res.json();

            if (data.status === 'success') {
                await Swal.fire({
                    title: 'TIPOGRAFÍA ACTUALIZADA',
                    text: 'Sincronizando fuentes institucionales...',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                });
                // Actualizar DOM con la nueva tipografía sin recargar la página
                document.documentElement.setAttribute('data-school-font', valor);
                // Refrescar módulo de configuración para ver cambios
                if (typeof navegarModulo === 'function') {
                    navegarModulo('configuracion&success=font');
                }
            }
        } catch (error) {
            Swal.fire('Error', 'Fallo al actualizar tipografía.', 'error');
        }
    };

    /**
     * ☢️ ZONA CRÍTICA: PURGA ATÓMICA
     */
    window.ejecutarLimpiezaIntegral = async () => {
        const result = await Swal.fire({
            title: '¿INICIAR LIMPIEZA INTEGRAL?',
            text: "Se eliminarán todos los datos de vuelo y registros temporales del sistema.",
            icon: 'error',
            showCancelButton: true,
            confirmButtonText: 'INICIAR LIMPIEZA',
            cancelButtonText: 'ABORTAR',
            customClass: { confirmButton: 'btn-elite btn-elite--danger py-3 px-5', cancelButton: 'btn-elite btn-elite--outline py-3 px-5 ms-2' }
        });

        if (result.isConfirmed) {
            try {
                const res = await fetch('logica/purgar_vuelo_total.php');
                const data = await res.json();

                if (data.status === 'success') {
                    await Swal.fire({
                        title: 'PURGA TOTAL',
                        text: 'El sistema ha sido reiniciado a valores de fábrica.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    window.location.href = 'dashboard.php?p=inicio';
                } else {
                    Swal.fire('FALLO EN LIMPIEZA', data.message, 'error');
                }
            } catch (error) {
                Swal.fire('FALLO CRÍTICO', 'No se pudo conectar con el motor de purga.', 'error');
            }
        }
    };

    // --- KHRONOS ENGINE (Timeline Dinámico) ---
    window.actualizarVistaKhronos = () => {
        const activeTab = document.querySelector('#pills-tab-jornadas-khronos button.active');
        const suffix = activeTab ? activeTab.getAttribute('data-suffix') : '';
        const idSuffix = (suffix && suffix !== 'manana') ? '_' + suffix : '';

        const inicio = document.getElementById('khronos_inicio' + idSuffix)?.value || '06:30';
        const duracion = parseInt(document.getElementById('khronos_duracion' + idSuffix)?.value) || 60;
        const totalHoras = parseInt(document.getElementById('khronos_max_horas' + idSuffix)?.value) || 6;
        const container = document.querySelector('.khronos-timeline-elite');
        
        if (!container) return;
        
        let html = '';
        let current = new Date(`2026-01-01T${inicio}:00`);
        const formatTime = (date) => date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false });

        for (let i = 1; i <= totalHoras; i++) {
            const startStr = formatTime(current);
            current.setMinutes(current.getMinutes() + duracion);
            const endStr = formatTime(current);

            html += `
                <div class="timeline-item-elite">
                    <div class="timeline-number">${i < 10 ? '0' + i : i}</div>
                    <div class="timeline-content"><span class="timeline-title">SESIÓN ACADÉMICA ${i}</span></div>
                    <div class="timeline-time">${startStr} - ${endStr}</div>
                </div>
            `;

            const r1 = parseInt(document.getElementById('khronos_descanso_m' + idSuffix)?.value) || 15;
            const r2 = parseInt(document.getElementById('khronos_descanso2_m' + idSuffix)?.value) || 15;
            const pos1 = parseInt(document.getElementById('khronos_descanso_h' + idSuffix)?.value) || 2;
            const pos2 = parseInt(document.getElementById('khronos_descanso2_h' + idSuffix)?.value) || 4;

            if (i === pos1) {
                const rs = formatTime(current);
                current.setMinutes(current.getMinutes() + r1);
                const re = formatTime(current);
                html += `<div class="timeline-break-elite"><i class="bi bi-cup-hot me-2"></i> RECESO 01 (${r1} min) | ${rs} - ${re}</div>`;
            } else if (i === pos2) {
                const rs = formatTime(current);
                current.setMinutes(current.getMinutes() + r2);
                const re = formatTime(current);
                html += `<div class="timeline-break-elite"><i class="bi bi-clock me-2"></i> RECESO 02 (${r2} min) | ${rs} - ${re}</div>`;
            }
        }
        container.innerHTML = html;
    };

    // --- CENTINELA DE AUTO-SAVE (HEFESTO) ---
    const setSyncStatus = (status) => {
        const el = document.getElementById('sync-status-indicator');
        if (!el) return;
        if (status === 'saving') {
            el.classList.add('saving');
            el.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i>Sincronizando...';
        } else {
            el.classList.remove('saving');
            el.innerHTML = '<i class="bi bi-check-all me-1"></i>Sincronizado';
        }
    };

    const debounce = (func, wait) => {
        let timeout;
        return (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => func(...args), wait);
        };
    };

    const debounceSave = debounce(async (form) => {
        try {
            const fd = new FormData(form);
            const actionUrl = form.getAttribute('action') || 'logica/guardar_estetica.php';
            const res = await fetch(actionUrl, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            
            // Capa defensiva contra respuestas corruptas (HTML/errores 500)
            const text = await res.text();
            let result;
            try {
                result = JSON.parse(text);
            } catch (jsonErr) {
                throw new Error("Respuesta inválida del servidor (No JSON). Código HTTP: " + res.status);
            }

            if (result.status === 'success') {
                setSyncStatus('synced');
            } else {
                throw new Error(result.message || "Fallo desconocido en persistencia.");
            }
        } catch (e) { 
            setSyncStatus('error'); 
        }
    }, 800);

    const ajustarAnchoDinamico = (input) => {
        if (!input || ['hidden', 'checkbox', 'radio', 'submit', 'button', 'file', 'time'].includes(input.type)) return;
        
        let len = input.value ? input.value.toString().length : 0;
        if (len === 0 && input.placeholder) {
            len = input.placeholder.length;
        }
        if (len === 0) {
            len = 4;
        }

        input.style.setProperty('padding', '0 0.75rem', 'important');

        let extraOffset = 5;
        if (input.type === 'date') {
            extraOffset = 9;
        } else if (input.type === 'number') {
            extraOffset = 7;
        }

        const baseCh = len + extraOffset;

        input.style.setProperty('width', `${baseCh}ch`, 'important');
        input.style.setProperty('min-width', 'unset', 'important');
        if (input.type === 'date') {
            input.style.setProperty('max-width', 'none', 'important');
        } else {
            input.style.setProperty('max-width', '100%', 'important');
        }
        input.style.setProperty('margin-inline', 'auto', 'important');
        input.style.setProperty('display', 'block', 'important');
    };

    const inicializarAjustesAncho = () => {
        const inputs = document.querySelectorAll('#tab-global input, #tab-jornada input');
        inputs.forEach(input => {
            ajustarAnchoDinamico(input);
            // Evitar duplicación de listeners removiendo la referencia anterior si existe
            input.removeEventListener('input', input._ajustarAnchoHandler);
            input.removeEventListener('change', input._ajustarAnchoHandler);
            
            input._ajustarAnchoHandler = () => ajustarAnchoDinamico(input);
            input.addEventListener('input', input._ajustarAnchoHandler);
            input.addEventListener('change', input._ajustarAnchoHandler);
        });
    };

    // 🏛️ FUNCIÓN MAESTRA DE INICIALIZACIÓN (Evita setTimeout y Race Conditions)
    window.inicializarModuloConfiguracion = () => {
        const forms = [document.getElementById('formBranding'), document.getElementById('formKhronos')];
        
        sincronizarColoresPaletas();
        window.actualizarVistaKhronos();
        window.sincronizarPestañaElite();
        inicializarAjustesAncho();

        document.querySelectorAll('[data-bs-toggle="pill"]').forEach(tab => {
            tab.removeEventListener('shown.bs.tab', tab._shownHandler);
            tab._shownHandler = (e) => {
                if (e.target.id === 'pills-adn-tab') sincronizarColoresPaletas();
                inicializarAjustesAncho();
            };
            tab.addEventListener('shown.bs.tab', tab._shownHandler);
        });

        // Listeners para las sub-pestañas de jornadas en Khronos
        document.querySelectorAll('#pills-tab-jornadas-khronos button').forEach(tab => {
            tab.removeEventListener('shown.bs.tab', tab._jornadaShownHandler);
            tab._jornadaShownHandler = () => {
                window.actualizarVistaKhronos();
                inicializarAjustesAncho();
            };
            tab.addEventListener('shown.bs.tab', tab._jornadaShownHandler);
        });

        forms.forEach(form => {
            if (!form) return;
            
            // Purgar listeners anteriores para evitar fugas de memoria en SPA
            form.removeEventListener('input', form._inputHandler);
            form.removeEventListener('change', form._changeHandler);

            form._inputHandler = (e) => {
                setSyncStatus('saving');
                if (e.target.id === 'school_name') {
                    document.querySelectorAll('.school-name-global').forEach(el => el.innerText = e.target.value);
                }
                if (e.target.type !== 'file') debounceSave(form);
            };

            form._changeHandler = (e) => {
                if (e.target.id === 'input-logo-file') {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = (ev) => document.querySelectorAll('.preview-img-fit, .school-logo-global').forEach(p => p.src = ev.target.result);
                        reader.readAsDataURL(file);
                    }
                }
                debounceSave(form);
            };

            form.addEventListener('input', form._inputHandler);
            form.addEventListener('change', form._changeHandler);
        });
    };

    // Auto-inicializar dinámicamente si el DOM ya está listo o cuando se cargue
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        window.inicializarModuloConfiguracion();
    } else {
        document.addEventListener('DOMContentLoaded', window.inicializarModuloConfiguracion);
    }

    /**
     * 🏛️ CONTROL DE PRIVACIDAD GLOBAL ACADÉMICA
     */
    window.cambiarPrivacidadCatedratico = async (estaActivo) => {
        const setSyncStatus = (status) => {
            const el = document.getElementById('sync-status-indicator');
            if (!el) return;
            if (status === 'saving') {
                el.classList.add('saving');
                el.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i>Sincronizando...';
            } else if (status === 'synced') {
                el.classList.remove('saving');
                el.innerHTML = '<i class="bi bi-check-all me-1"></i>Sincronizado';
            } else {
                el.classList.remove('saving');
                el.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i>Fallo de Sincronización';
            }
        };

        try {
            setSyncStatus('saving');
            const token = document.querySelector('#formGlobalConfig input[name="csrf_token"]')?.value || '';
            const valor = estaActivo ? 'estricto' : 'abierto';

            const fd = new FormData();
            fd.append('clave', 'privacidad_catedratico_sabana');
            fd.append('valor', valor);
            fd.append('csrf_token', token);

            const res = await fetch('logica/guardar_configuracion_global.php', {
                method: 'POST',
                body: fd
            });
            const result = await res.json();

            if (result.status === 'success') {
                setSyncStatus('synced');
            } else {
                setSyncStatus('error');
                Swal.fire({
                    title: 'ERROR DE INTEGRIDAD',
                    text: result.message,
                    icon: 'error',
                    confirmButtonColor: 'var(--el-primary)'
                });
                // Revertir switch visualmente en caso de fallo
                document.getElementById('switch-privacidad').checked = !estaActivo;
            }
        } catch (e) {
            setSyncStatus('error');
            Swal.fire({
                title: 'FALLO DE CONEXIÓN',
                text: 'No se pudo conectar con el motor de configuración global.',
                icon: 'error',
                confirmButtonColor: 'var(--el-primary)'
            });
            // Revertir switch visualmente en caso de fallo
            document.getElementById('switch-privacidad').checked = !estaActivo;
        }
    };

    /**
     * 🏛️ CAMBIAR POLÍTICA INSTITUCIONAL DE RECUPERACIONES
     */
    window.cambiarPoliticaRecuperacion = async (valor) => {
        const setSyncStatus = (status) => {
            const el = document.getElementById('sync-status-indicator');
            if (!el) return;
            if (status === 'saving') {
                el.classList.add('saving');
                el.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i>Sincronizando...';
            } else if (status === 'synced') {
                el.classList.remove('saving');
                el.innerHTML = '<i class="bi bi-check-all me-1"></i>Sincronizado';
            } else {
                el.classList.remove('saving');
                el.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i>Fallo';
            }
        };

        try {
            setSyncStatus('saving');
            const token = document.querySelector('#formGlobalConfig input[name="csrf_token"]')?.value || '';
            const fd = new FormData();
            fd.append('clave', 'politica_recuperacion');
            fd.append('valor', valor);
            fd.append('csrf_token', token);

            const res = await fetch('logica/guardar_configuracion_global.php', { method: 'POST', body: fd });
            const result = await res.json();

            if (result.status === 'success') {
                setSyncStatus('synced');
                Swal.fire({
                    title: 'POLÍTICA ACTUALIZADA',
                    text: 'El oráculo ha sincronizado la nueva directriz de recuperaciones.',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                });
            } else {
                setSyncStatus('error');
                Swal.fire('Error', result.message, 'error');
            }
        } catch (e) {
            setSyncStatus('error');
            Swal.fire('FALLO DE CONEXIÓN', 'No se pudo registrar la directriz.', 'error');
        }
    };

    /**
     * 🏛️ CAMBIAR LÍMITE DE HORAS SEMANALES POR DOCENTE
     */
    window.cambiarLimiteHorasDocente = async (valor) => {
        const setSyncStatus = (status) => {
            const el = document.getElementById('sync-status-indicator');
            if (!el) return;
            if (status === 'saving') {
                el.classList.add('saving');
                el.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i>Sincronizando...';
            } else if (status === 'synced') {
                el.classList.remove('saving');
                el.innerHTML = '<i class="bi bi-check-all me-1"></i>Sincronizado';
            } else {
                el.classList.remove('saving');
                el.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i>Fallo';
            }
        };

        try {
            setSyncStatus('saving');
            const token = document.querySelector('#formGlobalConfig input[name="csrf_token"]')?.value || '';
            const fd = new FormData();
            fd.append('clave', 'limite_horas_docente');
            fd.append('valor', valor);
            fd.append('csrf_token', token);

            const res = await fetch('logica/guardar_configuracion_global.php', { method: 'POST', body: fd });
            const result = await res.json();

            if (result.status === 'success') {
                setSyncStatus('synced');
            } else {
                setSyncStatus('error');
                Swal.fire({
                    title: 'ERROR AL GUARDAR',
                    text: result.message || 'No se pudo guardar la configuración.',
                    icon: 'error'
                });
            }
        } catch (e) {
            setSyncStatus('error');
            Swal.fire({
                title: 'FALLO DE CONEXIÓN',
                text: 'No se pudo conectar con el motor de configuración global.',
                icon: 'error'
            });
        }
    };

    /**
     * 🏛️ CAMBIAR DÍAS DE HOLGURA PARA NIVELACIONES (GRACE PERIOD)
     */
    window.cambiarDiasGraciaRecuperacion = async (valor) => {
        const setSyncStatus = (status) => {
            const el = document.getElementById('sync-status-indicator');
            if (!el) return;
            if (status === 'saving') {
                el.classList.add('saving');
                el.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i>Sincronizando...';
            } else if (status === 'synced') {
                el.classList.remove('saving');
                el.innerHTML = '<i class="bi bi-check-all me-1"></i>Sincronizado';
            } else {
                el.classList.remove('saving');
                el.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i>Fallo';
            }
        };

        try {
            setSyncStatus('saving');
            const token = document.querySelector('#formGlobalConfig input[name="csrf_token"]')?.value || '';
            const fd = new FormData();
            fd.append('clave', 'dias_gracia_recuperaciones');
            fd.append('valor', valor);
            fd.append('csrf_token', token);

            const res = await fetch('logica/guardar_configuracion_global.php', { method: 'POST', body: fd });
            const result = await res.json();

            if (result.status === 'success') {
                setSyncStatus('synced');
                Swal.fire({
                    title: 'DÍAS DE GRACIA ACTUALIZADOS',
                    text: 'El oráculo ha sincronizado los nuevos días de holgura para nivelaciones.',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                });
            } else {
                setSyncStatus('error');
                Swal.fire('Error', result.message, 'error');
            }
        } catch (e) {
            setSyncStatus('error');
            Swal.fire('FALLO DE CONEXIÓN', 'No se pudo registrar la directriz.', 'error');
        }
    };

    /**
     * 🏛️ GUARDAR REGULACIÓN DE ESCALAS Y PLAN DE NOTAS MÍNIMAS
     */
    window.guardarEscalaNotas = async (event) => {
        if (event) event.preventDefault();

        const form = document.getElementById('formEscalaNotas');
        if (!form) return;

        // 1. Validaciones de Escala
        const nMin = parseFloat(document.getElementById('nota_minima').value) || 0;
        const nMax = parseFloat(document.getElementById('nota_maxima').value) || 0;
        const nAp = parseFloat(document.getElementById('nota_aprobacion').value) || 0;
        const rSup = parseFloat(document.getElementById('rango_superior_min').value) || 0;
        const rAlt = parseFloat(document.getElementById('rango_alto_min').value) || 0;
        const rBas = parseFloat(document.getElementById('rango_basico_min').value) || 0;

        if (nMin >= nMax) {
            Swal.fire('Discrepancia', 'La nota mínima no puede ser mayor o igual a la máxima.', 'warning');
            return;
        }
        if (nAp <= nMin || nAp >= nMax) {
            Swal.fire('Discrepancia', 'La nota de aprobación debe estar entre el valor mínimo y máximo.', 'warning');
            return;
        }
        if (rSup <= rAlt || rAlt <= rBas || rSup >= nMax) {
            Swal.fire('Discrepancia de Límites', 'Los rangos no son consistentes: Superior > Alto > Básico.', 'warning');
            return;
        }

        // 2. Validaciones de Dimensiones (Suma 100%)
        let sumaPesos = 0;
        const inputPesos = document.querySelectorAll('.input-dim-peso');
        inputPesos.forEach(inp => {
            sumaPesos += parseFloat(inp.value) || 0;
        });

        if (Math.abs(sumaPesos - 100) > 0.01) {
            Swal.fire('Error de Ponderación', `La suma de pesos de las dimensiones debe ser exactamente 100% (Suma actual: ${sumaPesos}%).`, 'warning');
            return;
        }

        // 3. Confirmación y Envío AJAX
        const confirm = await Swal.fire({
            title: '¿Sellar Regulación?',
            text: 'Las modificaciones de escala se aplicarán globalmente en la visualización e inyección de promedios. ¿Desea proceder?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sellar Ahora',
            cancelButtonText: 'Cancelar',
            customClass: { confirmButton: 'btn-elite px-4', cancelButton: 'btn-elite--outline px-4 ms-2' }
        });

        if (confirm.isConfirmed) {
            Swal.fire({ title: 'Sincronizando Escala...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            
            try {
                const fd = new FormData(form);
                fd.append('csrf_token', window.CSRF_TOKEN || '');
                
                const res = await fetch('logica/guardar_escala.php', { method: 'POST', body: fd });
                const result = await res.json();

                if (result.status === 'success') {
                    await Swal.fire({
                        title: 'ESCALAS SELLADAS',
                        text: result.message,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    // Actualizar DOM con las nuevas escalas sin recargar la página
                    if (typeof navegarModulo === 'function') {
                        navegarModulo('configuracion&success=escala');
                    }
                } else {
                    throw new Error(result.message);
                }
            } catch (err) {
                Swal.fire('FALLO CRÍTICO', err.message || 'Error de sincronización.', 'error');
            }
        }
    };

    /**
     * 🏛️ GUARDAR FECHAS DE PERIODOS ACADÉMICOS (CALENDARIO ESCOLAR)
     */
    window.guardarFechasPeriodos = async (event) => {
        if (event) event.preventDefault();

        const form = document.getElementById('formCalendarioPeriodos');
        if (!form) return;

        // Validaciones en caliente antes de guardar
        const periodos = [];
        const inicioInputs = form.querySelectorAll('input[name^="periodo"][name$="[inicio]"]');
        for (const input of inicioInputs) {
            const name = input.name;
            const match = name.match(/periodo\[(\d+)\]/);
            if (match) {
                const id = parseInt(match[1]);
                const inicioVal = input.value;
                const finVal = form.querySelector(`input[name="periodo[${id}][fin]"]`)?.value;

                if (!inicioVal || !finVal) {
                    Swal.fire('ERROR DE VALIDACIÓN', `Las fechas para el Periodo ${id} no pueden estar vacías.`, 'error');
                    return;
                }

                const tInicio = new Date(inicioVal).getTime();
                const tFin = new Date(finVal).getTime();

                if (isNaN(tInicio) || isNaN(tFin)) {
                    Swal.fire('ERROR DE VALIDACIÓN', `El formato de fecha provisto para el Periodo ${id} no es válido.`, 'error');
                    return;
                }

                if (tInicio >= tFin) {
                    Swal.fire('ERROR DE VALIDACIÓN', `La fecha de inicio del Periodo ${id} debe ser anterior a su fecha de terminación.`, 'error');
                    return;
                }

                periodos.push({ id: id, inicio: tInicio, fin: tFin });
            }
        }

        // Ordenar periodos por su identificador para validar consistencia cronológica contigua
        periodos.sort((a, b) => a.id - b.id);

        for (let i = 1; i < periodos.length; i++) {
            if (periodos[i].inicio < periodos[i-1].fin) {
                Swal.fire('ERROR DE CRONOGRAMA', `Existe superposición de tiempos: el Periodo ${periodos[i].id} inicia antes de la finalización del Periodo ${periodos[i-1].id}.`, 'error');
                return;
            }
        }

        const confirm = await Swal.fire({
            title: '¿Guardar Calendario Escolar?',
            text: 'Las modificaciones en las fechas de los periodos se aplicarán inmediatamente en las APIs de la plataforma. ¿Desea proceder?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Guardar',
            cancelButtonText: 'Cancelar',
            customClass: { confirmButton: 'btn-elite px-4', cancelButton: 'btn-elite--outline px-4 ms-2' }
        });

        if (confirm.isConfirmed) {
            Swal.fire({ title: 'Guardando Calendario...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            
            try {
                const fd = new FormData(form);
                fd.append('csrf_token', window.CSRF_TOKEN || '');
                
                const res = await fetch('logica/guardar_periodos.php', { method: 'POST', body: fd });
                const result = await res.json();

                if (result.status === 'success') {
                    await Swal.fire({
                        title: 'CALENDARIO ESCOLAR GUARDADO',
                        text: result.message,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    // Actualizar DOM con el nuevo calendario sin recargar la página
                    if (typeof navegarModulo === 'function') {
                        navegarModulo('configuracion&success=periodos');
                    }
                } else {
                    throw new Error(result.message);
                }
            } catch (err) {
                Swal.fire('FALLO CRÍTICO', err.message || 'Error de sincronización.', 'error');
            }
        }
    };

    window.agregarRecesoEscolar = async function(event) {
        event.preventDefault();
        const nombreInput = document.getElementById('receso-nombre');
        const inicioInput = document.getElementById('receso-inicio');
        const finInput = document.getElementById('receso-fin');
        if (!nombreInput || !inicioInput || !finInput) return;

        const nombre = nombreInput.value.trim();
        const inicio = inicioInput.value;
        const fin = finInput.value;

        if (!nombre || !inicio || !fin) {
            Swal.fire('Atención', 'Todos los campos son obligatorios.', 'warning');
            return;
        }

        if (new Date(inicio) > new Date(fin)) {
            Swal.fire('Atención', 'La fecha de inicio debe ser anterior a la fecha de fin.', 'warning');
            return;
        }

        try {
            const fd = new FormData();
            fd.append('accion', 'crear');
            fd.append('nombre', nombre);
            fd.append('fecha_inicio', inicio);
            fd.append('fecha_fin', fin);
            fd.append('csrf_token', window.CSRF_TOKEN || '');

            const res = await fetch('logica/gestionar_recesos.php', { method: 'POST', body: fd });
            const result = await res.json();

            if (result.status === 'success') {
                Swal.fire({
                    title: 'RECESO REGISTRADO',
                    text: result.message,
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                });
                
                // Limpiar inputs
                nombreInput.value = '';
                inicioInput.value = '';
                finInput.value = '';

                // Sincronizar dinámicamente el DOM
                const container = document.getElementById('lista-recesos-container');
                if (container) {
                    // Si estaba el cartel de "no hay recesos", vaciar primero
                    if (container.textContent.includes('No hay recesos registrados.')) {
                        container.innerHTML = '';
                    }

                    const formatFechaStr = (f) => {
                        const parts = f.split('-');
                        return `${parts[2]}/${parts[1]}/${parts[0]}`;
                    };

                    const rowId = result.id || Date.now(); // fallback id temporal
                    const rowHtml = `
                        <div class="row align-items-center mb-3 text-center" id="receso-row-${rowId}">
                            <div class="col-4 text-start">
                                <span class="fw-bold text-dark fs-nano receso-display-nombre">${escapeHtml(nombre)}</span>
                            </div>
                            <div class="col-3 fs-nano text-muted receso-display-inicio" data-val="${inicio}">
                                ${formatFechaStr(inicio)}
                            </div>
                            <div class="col-3 fs-nano text-muted receso-display-fin" data-val="${fin}">
                                ${formatFechaStr(fin)}
                            </div>
                            <div class="col-2 d-flex justify-content-center gap-2">
                                <button type="button" class="btn-action-elite text-primary" title="Editar Receso" onclick="window.editarRecesoEscolar(${rowId})">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <button type="button" class="btn-action-elite text-danger" title="Eliminar Receso" onclick="window.eliminarRecesoEscolar(${rowId})">
                                    <i class="bi bi-trash3-fill"></i>
                                </button>
                            </div>
                        </div>
                    `;
                    container.insertAdjacentHTML('beforeend', rowHtml);
                }
            } else {
                throw new Error(result.message);
            }
        } catch (err) {
            Swal.fire('Receso duplicado', err.message || 'Error de sincronización.', 'warning');
        }
    };

    // Pequeño helper interno para limpiar strings en JS
    const escapeHtml = (text) => {
        if (!text) return '';
        return text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    };

    window.editarRecesoEscolar = async function(id) {
        const row = document.getElementById(`receso-row-${id}`);
        if (!row) return;

        const nombreSpan = row.querySelector('.receso-display-nombre');
        const inicioCol = row.querySelector('.receso-display-inicio');
        const finCol = row.querySelector('.receso-display-fin');

        const nombreActual = nombreSpan?.textContent.trim() || '';
        const inicioActual = inicioCol?.dataset.val || '';
        const finActual = finCol?.dataset.val || '';

        const { value: formValues } = await Swal.fire({
            title: 'MODIFICAR RECESO ESCOLAR',
            html: `
                <div class="text-start px-3 mb-2">
                    <label class="form-label fs-nano text-muted fw-bold text-uppercase d-block mb-1">Nombre</label>
                    <input type="text" id="swal-receso-nombre" class="swal2-input m-0 w-100" style="height: 44px; border-radius: var(--el-prestige-radius);" value="${escapeHtml(nombreActual)}" placeholder="Ej: Semana Santa">
                </div>
                <div class="text-start px-3 mb-2">
                    <label class="form-label fs-nano text-muted fw-bold text-uppercase d-block mb-1">Fecha Inicio</label>
                    <input type="date" id="swal-receso-inicio" class="form-control" style="height: 44px; border-radius: var(--el-prestige-radius);" value="${inicioActual}">
                </div>
                <div class="text-start px-3 mb-2">
                    <label class="form-label fs-nano text-muted fw-bold text-uppercase d-block mb-1">Fecha Fin</label>
                    <input type="date" id="swal-receso-fin" class="form-control" style="height: 44px; border-radius: var(--el-prestige-radius);" value="${finActual}">
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'GUARDAR CAMBIOS',
            cancelButtonText: 'CANCELAR',
            customClass: {
                confirmButton: 'btn-elite btn-elite--primary px-3 py-2',
                cancelButton: 'btn-elite btn-elite--outline px-3 py-2 ms-2'
            },
            buttonsStyling: false,
            preConfirm: () => {
                const nombre = document.getElementById('swal-receso-nombre').value.trim();
                const inicio = document.getElementById('swal-receso-inicio').value;
                const fin = document.getElementById('swal-receso-fin').value;

                if (!nombre || !inicio || !fin) {
                    Swal.showValidationMessage('Todos los campos son obligatorios.');
                    return false;
                }
                if (new Date(inicio) > new Date(fin)) {
                    Swal.showValidationMessage('La fecha de inicio debe ser anterior o igual a la de fin.');
                    return false;
                }
                return { nombre, inicio, fin };
            }
        });

        if (!formValues) return;

        try {
            const fd = new FormData();
            fd.append('accion', 'editar');
            fd.append('id', id);
            fd.append('nombre', formValues.nombre);
            fd.append('fecha_inicio', formValues.inicio);
            fd.append('fecha_fin', formValues.fin);
            fd.append('csrf_token', window.CSRF_TOKEN || '');

            const res = await fetch('logica/gestionar_recesos.php', { method: 'POST', body: fd });
            const result = await res.json();

            if (result.status === 'success') {
                Swal.fire({
                    title: 'RECESO MODIFICADO',
                    text: result.message,
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                });

                // Sincronizar dinámicamente el DOM
                if (nombreSpan) nombreSpan.textContent = formValues.nombre;
                
                const formatFechaStr = (f) => {
                    const parts = f.split('-');
                    return `${parts[2]}/${parts[1]}/${parts[0]}`;
                };

                if (inicioCol) {
                    inicioCol.dataset.val = formValues.inicio;
                    inicioCol.textContent = formatFechaStr(formValues.inicio);
                }
                if (finCol) {
                    finCol.dataset.val = formValues.fin;
                    finCol.textContent = formatFechaStr(formValues.fin);
                }
            } else {
                throw new Error(result.message);
            }
        } catch (err) {
            Swal.fire('Receso duplicado', err.message || 'Error de sincronización.', 'warning');
        }
    };

    window.eliminarRecesoEscolar = async function(id) {
        const confirm = await Swal.fire({
            title: '¿ELIMINAR RECESO?',
            text: 'Esta acción no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: 'var(--el-primary)',
            cancelButtonColor: 'var(--el-danger)',
            confirmButtonText: 'SÍ, ELIMINAR',
            cancelButtonText: 'CANCELAR'
        });

        if (!confirm.isConfirmed) return;

        try {
            const fd = new FormData();
            fd.append('accion', 'eliminar');
            fd.append('id', id);
            fd.append('csrf_token', window.CSRF_TOKEN || '');

            const res = await fetch('logica/gestionar_recesos.php', { method: 'POST', body: fd });
            const result = await res.json();

            if (result.status === 'success') {
                Swal.fire({
                    title: 'RECESO ELIMINADO',
                    text: result.message,
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                });
                const row = document.getElementById(`receso-row-${id}`);
                if (row) row.remove();
                
                const container = document.getElementById('lista-recesos-container');
                if (container && container.querySelectorAll('.row').length === 0) {
                    container.innerHTML = '<div class="text-muted fs-nano text-center py-3">No hay recesos registrados.</div>';
                }
            } else {
                throw new Error(result.message);
            }
        } catch (err) {
            Swal.fire('FALLO CRÍTICO', err.message || 'Error de sincronización.', 'error');
        }
    };

    window.addEventListener('hashchange', window.sincronizarPestañaElite);

})();
