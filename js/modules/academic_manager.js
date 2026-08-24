/**
 * 🏛️ ACADEMIC MANAGER v1.0 - MOTOR DE GESTIÓN DE BÓVEDA
 * Responsable de la lógica de administración de Roles, Personal, Áreas, Cursos y Alumnos.
 */

// El helper universal window.lanzarToastElite reside de forma centralizada en dashboard.js

// --- GESTIÓN DE ROLES ---
window.nuevoRol = async function () {
    const result = await Swal.fire({
        title: 'Nuevo Perfil Escolar',
        input: 'text',
        inputPlaceholder: 'Ej: Coordinador...',
        showCancelButton: true,
        confirmButtonText: 'Guardar',
        customClass: { confirmButton: 'btn-elite btn-elite--primary px-4', cancelButton: 'btn-elite btn-elite--outline px-4 ms-2' },
        buttonsStyling: false
    });
    if (result.isConfirmed && result.value) {
        enviarPostElite('logica/guardar_rol.php', { nombre_rol: result.value });
    }
};

window.editarRol = async function (id, nombre) {
    const result = await Swal.fire({
        title: 'Editar Perfil Escolar',
        input: 'text',
        inputValue: nombre,
        showCancelButton: true,
        confirmButtonText: 'Actualizar',
        customClass: { confirmButton: 'btn-elite btn-elite--success px-4', cancelButton: 'btn-elite btn-elite--outline px-4 ms-2' },
        buttonsStyling: false
    });
    if (result.isConfirmed && result.value) {
        enviarPostElite('logica/editar_rol.php', { id: id, nombre_rol: result.value });
    }
};

window.borrarRol = async function (id, nombre) {
    if (nombre === 'Administrador' || id === 1) {
        await Swal.fire({ icon: 'error', title: 'Acceso Denegado', text: 'El rol de Administrador matriz no puede ser eliminado.', customClass: { confirmButton: 'btn-elite btn-elite--primary px-4' } });
        return;
    }
    const result = await Swal.fire({
        title: '¿Estás seguro?',
        text: `Eliminarás el rol "${nombre}".`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        customClass: { confirmButton: 'btn-elite btn-elite--primary px-4', cancelButton: 'btn-elite btn-elite--outline px-4 ms-2' },
        buttonsStyling: false
    });
    if (result.isConfirmed) {
        enviarPostElite('logica/borrar_rol.php', { id: id });
    }
};

window.gestionarPermisos = async function (id, nombre, actuales, todos, tipo = 'rol', base = [], nombreRol = '', esCustom = 0) {
    let checkboxes = '';
    const actualesArray = Array.isArray(actuales) ? actuales : [];
    const baseArray = Array.isArray(base) ? base : [];
    const todosArray = Array.isArray(todos) ? todos : [];

    const actualesStr = actualesArray.map(v => String(v));
    const baseStr = baseArray.map(v => String(v));
    const nombreParaCheck = (tipo === 'rol' ? (nombre || '') : (nombreRol || '')).toLowerCase();
    const isAdmin = nombreParaCheck.includes('administrador');

    todosArray.forEach(p => {
        let pId = String(p.id);
        let chkAttr = (isAdmin || (tipo === 'rol' && actualesStr.includes(pId)) || (esCustom == 1 && actualesStr.includes(pId)) || (tipo !== 'rol' && esCustom == 0 && baseStr.includes(pId))) ? 'checked' : '';
        let disAttr = isAdmin ? 'disabled' : '';
        let belongsToBase = baseStr.includes(pId);
        let labelColor = 'text-dark';

        let subtextoHtml = '';
        if (isAdmin) {
            subtextoHtml = `<div class="permiso-subtexto-elite"><i class="bi bi-shield-lock me-1"></i>Privilegio Maestro Permanente</div>`;
        } else if (belongsToBase) {
            subtextoHtml = `<div class="permiso-subtexto-elite"><i class="bi bi-person-check me-1"></i>Sugerido por Rol (${nombreRol})</div>`;
        }

        checkboxes += `
            <div class="form-check text-start mb-2 border-bottom pb-2 d-flex align-items-start">
                <input class="form-check-input swal-permiso-chk chk-elite-outline shadow-sm me-2 mt-1" type="checkbox" value="${p.id}" id="perm_${p.id}" ${chkAttr} ${disAttr}>
                <label class="form-check-label cursor-pointer flex-grow-1" for="perm_${p.id}">
                    <div class="fw-semibold ${labelColor} fs-md-elite">${p.nombre.toUpperCase()}</div>
                    ${subtextoHtml}
                </label>
            </div>
        `;
    });

    let subtitulo = isAdmin
        ? `<span class="text-danger fw-bold">PROTOCOLOS CRÍTICOS: El acceso del Administrador es total y permanente.</span>`
        : `Personalizando accesos para: <span class="text-primary fw-bold">${nombre}</span><br><small class="text-muted italic">※ Al guardar, este usuario dejará de heredar cambios del Rol para usar su propia matriz.</small>`;

    let footerHtml = (esCustom == 1 && !isAdmin)
        ? `<div class="mt-3 pt-2 border-top"><button type="button" class="btn btn-link text-danger btn-sm text-decoration-none fw-bold" onclick="restaurarRol(${id})">↺ Restaurar a valores del Rol (${nombreRol})</button></div>`
        : '';

    const result = await Swal.fire({
        title: '<h4 class="fw-bold mb-0">MATRIZ DE ACCESO SEGURA</h4>',
        html: `<p class="mb-4 mt-2 text-start small text-muted lh-sm">${subtitulo}</p><div class="px-3 scroll-perms-elite">${checkboxes}</div>${footerHtml}`,
        showCancelButton: true,
        confirmButtonText: 'Aprobar Ajustes',
        cancelButtonText: 'Cancelar',
        customClass: { confirmButton: 'btn-elite btn-elite--primary px-4 shadow-sm', cancelButton: 'btn-elite btn-elite--outline px-4 ms-2' },
        buttonsStyling: false,
        preConfirm: () => {
            let seleccionados = [];
            document.querySelectorAll('.swal-permiso-chk:checked:not(:disabled)').forEach(chk => { seleccionados.push(chk.value); });
            return seleccionados;
        }
    });

    if (result.isConfirmed) {
        let seleccionados = result.value;
        let url = tipo === 'rol' ? 'logica/guardar_permisos_rol.php' : 'logica/guardar_permisos_usuario.php';

        const params = {
            permisos: JSON.stringify(seleccionados),
            csrf_token: window.CSRF_TOKEN
        };
        params[tipo === 'rol' ? 'rol_id' : 'usuario_id'] = id;

        enviarPostElite(url, params);
    }
};

window.restaurarRol = async function (id) {
    const r = await Swal.fire({
        title: '¿Restaurar Herencia?',
        text: 'Este usuario volverá a obedecer los permisos estándar de su Rol asignado.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, restaurar Rol',
        customClass: { confirmButton: 'btn-elite btn-elite--primary px-4', cancelButton: 'btn-elite btn-elite--outline px-4 ms-2' },
        buttonsStyling: false
    });
    if (r.isConfirmed) {
        enviarPostElite('logica/restaurar_rol_usuario.php', { id: id });
    }
};

// --- GESTIÓN DE PERSONAL ---
window.nuevoPersonal = async function (listaRoles, listaEspecialidades) {
    let opcionesRoles = listaRoles.map(rol => `<option value="${rol.id}">${rol.nombre_rol}</option>`).join('');
    let opcionesEspecialidades = listaEspecialidades ? listaEspecialidades.map(esp => `<option value="${esp.id}">${esp.nombre_especialidad}</option>`).join('') : '';

    const result = await Swal.fire({
        title: 'Registro de Personal',
        html: `
            <div class="text-center mb-3">
                <div class="d-inline-flex align-items-center justify-content-center bg-primary-faded text-primary rounded-circle size-60">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
            </div>
            <input id="swal-nom" class="swal2-input border-secondary" placeholder="Nombre Completo">
            <input id="swal-user" class="swal2-input border-secondary" placeholder="Nombre de Usuario">
            <input id="swal-pass" type="password" class="swal2-input border-secondary" placeholder="Contraseña Temporal">
            <select id="swal-rol" class="form-select border-secondary text-secondary w-75 m-auto mt-3 border-2"><option value="" disabled selected>Rol Institucional...</option>${opcionesRoles}</select>
            <select id="swal-esp" class="form-select border-secondary text-secondary w-75 m-auto mt-3 border-2 u-hidden"><option value="">Ninguna área (Alta Admin)</option>${opcionesEspecialidades}</select>
        `,
        showCancelButton: true,
        confirmButtonText: 'Registrar Empleado',
        customClass: { confirmButton: 'btn-elite px-4', cancelButton: 'btn-elite btn-elite--outline px-4 ms-2' },
        buttonsStyling: false,
        showLoaderOnConfirm: true,
        preConfirm: async () => {
            const nombre = document.getElementById('swal-nom').value.trim();
            const user = document.getElementById('swal-user').value.trim();
            const pass = document.getElementById('swal-pass').value;
            const rol = document.getElementById('swal-rol').value;
            const esp = document.getElementById('swal-esp').value;

            if (!nombre || !user || !pass || !rol) {
                lanzarToastElite('danger', 'Por favor complete todos los campos obligatorios.');
                return false;
            }

            try {
                const formData = new FormData();
                formData.append('csrf_token', window.CSRF_TOKEN || '');
                formData.append('nombre', nombre);
                formData.append('user', user);
                formData.append('pass', pass);
                formData.append('rol', rol);
                formData.append('esp', esp);

                const response = await fetch('logica/guardar_personal.php', { method: 'POST', body: formData });
                if (!response.ok) throw new Error('Error en la comunicación HTTP.');

                const data = await response.json();
                if (data.status === 'success') {
                    return data;
                } else {
                    lanzarToastElite('danger', data.message || 'Error de registro');
                    return false;
                }
            } catch (err) {
                lanzarToastElite('danger', err.message || 'No se pudo contactar con el servidor.');
                return false;
            }
        },
        didOpen: () => {
            const selectRol = document.getElementById('swal-rol');
            const selectEsp = document.getElementById('swal-esp');
            const inputNom = document.getElementById('swal-nom');
            const inputUser = document.getElementById('swal-user');

            const generarUsuario = () => {
                if (!inputNom.value.trim()) {
                    inputUser.value = '';
                    return;
                }
                let partes = inputNom.value.trim().split(' ');
                let iniciales = '';
                partes.forEach(p => { if (p.length > 0) iniciales += p[0].toUpperCase(); });

                let prefijo = 'U';
                if (selectRol && selectRol.selectedIndex !== -1) {
                    let optionText = selectRol.options[selectRol.selectedIndex].text;
                    let val = selectRol.value;
                    if (val !== "") {
                        let textoLimpio = optionText.replace(/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]/g, '').trim();
                        let textoLimpioLower = textoLimpio.toLowerCase();
                        if (textoLimpioLower.includes('docente') || textoLimpioLower.includes('profesor')) {
                            prefijo = 'L'; // Licenciado (estándar docente)
                        } else if (textoLimpio.length > 0) {
                            prefijo = textoLimpio.charAt(0).toUpperCase(); // Tomar primera letra
                        }
                    }
                }
                inputUser.value = prefijo + iniciales;
            };

            selectRol.addEventListener('change', () => {
                if (selectRol.value === "11") { selectEsp.classList.remove('u-hidden'); }
                else { selectEsp.classList.add('u-hidden'); selectEsp.value = ""; }
                generarUsuario();
            });

            inputNom.addEventListener('input', generarUsuario);
        }
    });

    if (result.isConfirmed && result.value) {
        sessionStorage.setItem('reabrir_nuevo_personal', 'true');
        lanzarToastElite('success', result.value.message || 'Personal registrado correctamente.');
        if (typeof navegarModulo === 'function') {
            window.forceRefreshElite = true;
            navegarModulo(window.MODULO_ACTUAL || 'inicio');
        }
    }
};

window.editarPersonal = async function (id, nombreActual, usuarioActual, rolActual, listaRoles, espActual, listaEspecialidades) {
    let opcionesRoles = listaRoles.map(rol => `<option value="${rol.id}" ${rol.id == rolActual ? 'selected' : ''}>${rol.nombre_rol}</option>`).join('');
    let opcionesEspecialidades = listaEspecialidades ? listaEspecialidades.map(esp => `<option value="${esp.id}" ${esp.id == espActual ? 'selected' : ''}>${esp.nombre_especialidad}</option>`).join('') : '';
    const result = await Swal.fire({
        title: 'Actualizar Personal',
        html: `
            <div class="text-center mb-3">
                <div class="d-inline-flex align-items-center justify-content-center bg-primary-faded text-primary rounded-circle size-60">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
            </div>
            <input id="swal-nom" class="swal2-input border-secondary text-dark" value="${nombreActual}">
            <input id="swal-user" class="swal2-input border-secondary text-dark" value="${usuarioActual}">
            <input id="swal-pass" type="password" class="swal2-input border-secondary text-dark" placeholder="Nueva Contraseña (Opcional)">
            <select id="swal-rol" class="form-select border-secondary w-75 m-auto mt-3 border-2">${opcionesRoles}</select>
            <select id="swal-esp" class="form-select border-secondary w-75 m-auto mt-3 border-2 ${rolActual == 11 ? '' : 'u-hidden'}"><option value="" ${!espActual ? 'selected' : ''}>Ninguna área (Alta Admin)</option>${opcionesEspecialidades}</select>
        `,
        showCancelButton: true,
        confirmButtonText: 'Aprobar Cambios',
        customClass: { confirmButton: 'btn-elite px-4 shadow-sm', cancelButton: 'btn-elite btn-elite--outline px-4 ms-2' },
        buttonsStyling: false,
        showLoaderOnConfirm: true,
        preConfirm: async () => {
            const nombre = document.getElementById('swal-nom').value.trim();
            const user = document.getElementById('swal-user').value.trim();
            const pass = document.getElementById('swal-pass').value;
            const rol = document.getElementById('swal-rol').value;
            const esp = document.getElementById('swal-esp').value;

            if (!nombre || !user || !rol) {
                lanzarToastElite('danger', 'El nombre, usuario y rol son obligatorios.');
                return false;
            }

            try {
                const formData = new FormData();
                formData.append('csrf_token', window.CSRF_TOKEN || '');
                formData.append('id', id);
                formData.append('nombre', nombre);
                formData.append('user', user);
                formData.append('pass', pass);
                formData.append('rol', rol);
                formData.append('esp', esp);

                const response = await fetch('logica/editar_personal.php', { method: 'POST', body: formData });
                if (!response.ok) throw new Error('Error en la comunicación HTTP.');

                const data = await response.json();
                if (data.status === 'success') {
                    return data;
                } else {
                    lanzarToastElite('danger', data.message || 'Error de edición');
                    return false;
                }
            } catch (err) {
                lanzarToastElite('danger', err.message || 'No se pudo contactar con el servidor.');
                return false;
            }
        },
        didOpen: () => {
            const selectRol = document.getElementById('swal-rol');
            const selectEsp = document.getElementById('swal-esp');

            selectRol.addEventListener('change', () => {
                if (selectRol.value === "11") {
                    selectEsp.classList.remove('u-hidden');
                } else {
                    selectEsp.classList.add('u-hidden');
                    selectEsp.value = "";
                }
            });
        }
    });
    if (result.isConfirmed && result.value) {
        lanzarToastElite('success', result.value.message || 'Personal actualizado correctamente.');
        if (typeof navegarModulo === 'function') {
            window.forceRefreshElite = true;
            navegarModulo(window.MODULO_ACTUAL || 'inicio');
        }
    }
};

window.borrarPersonal = async function (id, nombre) {
    if (id === 1) {
        await Swal.fire({ icon: 'error', title: 'Operación Prohibida', text: 'El superusuario principal no puede ser eliminado.' });
        return;
    }
    const result = await Swal.fire({
        title: 'Baja Institucional',
        text: `Se retirará el acceso a ${nombre}.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Dar de Baja',
        customClass: { confirmButton: 'btn-elite btn-elite--primary px-4', cancelButton: 'btn-elite btn-elite--outline px-4 ms-2' },
        buttonsStyling: false
    });
    if (result.isConfirmed) {
        enviarPostElite('logica/borrar_personal.php', { id: id });
    }
};

// --- GESTIÓN DE ÁREAS Y MATERIAS ---
window.nuevaArea = async function () {
    const result = await Swal.fire({
        title: 'Crear Área Curricular', input: 'text', showCancelButton: true, confirmButtonText: 'Crear',
        customClass: { confirmButton: 'btn-elite px-4', cancelButton: 'btn-elite btn-elite--outline px-4 ms-2' }, buttonsStyling: false
    });
    if (result.isConfirmed && result.value) enviarPostElite('logica/guardar_area.php', { nombre_area: result.value });
};

window.editarArea = async function (id, nombre) {
    const result = await Swal.fire({
        title: 'Editar Área', input: 'text', inputValue: nombre, showCancelButton: true, confirmButtonText: 'Validar Cambio',
        customClass: { confirmButton: 'btn-elite px-4', cancelButton: 'btn-elite btn-elite--outline px-4 ms-2' }, buttonsStyling: false
    });
    if (result.isConfirmed && result.value) enviarPostElite('logica/editar_area.php', { id: id, nombre_area: result.value });
};

window.borrarArea = async function (id, nombre) {
    const result = await Swal.fire({
        title: '¿Eliminar Área?', text: `Se borrará "${nombre}".`, icon: 'warning', showCancelButton: true, confirmButtonText: 'Borrar',
        customClass: { confirmButton: 'btn-elite btn-elite--primary px-4', cancelButton: 'btn-elite btn-elite--outline px-4 ms-2' }, buttonsStyling: false
    });
    if (result.isConfirmed) enviarPostElite('logica/borrar_area.php', { id: id });
};

window.nuevaEspecialidad = async function (areasJSON) {
    let opciones = '<option value="" disabled selected>Seleccione el Área...</option>' + areasJSON.map(a => `<option value="${a.id}">${a.nombre_area}</option>`).join('');
    let opcionesNiv = '';
    for (let i = 1; i <= 11; i++) {
        opcionesNiv += `<option value="${i}">${i}</option>`;
    }
    const r = await Swal.fire({
        title: 'Nueva Materia',
        html: `<div class="text-start">
                <label class="small fw-bold text-secondary mb-1">Nombre de la Especialidad / Materia</label>
                <input id="esp-nom" class="input-elite mb-3">
                <label class="small fw-bold text-secondary mb-1">Área Académica</label>
                <select id="esp-area" class="select-elite mb-3">${opciones}</select>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="small fw-bold text-secondary mb-1">Grado Inicial</label>
                        <select id="esp-desde" class="select-elite" data-ares-ignore="true">${opcionesNiv}</select>
                    </div>
                    <div class="col-6">
                        <label class="small fw-bold text-secondary mb-1">Grado Final</label>
                        <select id="esp-hasta" class="select-elite" data-ares-ignore="true">${opcionesNiv}</select>
                    </div>
                </div>
               </div>`,
        showCancelButton: true, confirmButtonText: 'Ensamblar',
        customClass: { confirmButton: 'btn-elite px-4', cancelButton: 'btn-elite btn-elite--outline px-4 ms-2' }, buttonsStyling: false,
        preConfirm: () => {
            const desde = parseInt(document.getElementById('esp-desde').value);
            const hasta = parseInt(document.getElementById('esp-hasta').value);
            if (desde > hasta) {
                lanzarToastElite('danger', 'El grado inicial no puede ser mayor al grado final.');
                return false;
            }
            return {
                nombre_especialidad: document.getElementById('esp-nom').value,
                area_id: document.getElementById('esp-area').value,
                nivel_desde: desde,
                nivel_hasta: hasta
            };
        }
    });
    if (r.isConfirmed && r.value.nombre_especialidad) enviarPostElite('logica/guardar_especialidad.php', r.value);
};

window.editarEspecialidad = async function (id, nombre, areaActual, areasJSON, nivelDesdeActual = 1, nivelHastaActual = 11) {
    let opciones = areasJSON.map(a => `<option value="${a.id}" ${a.id == areaActual ? 'selected' : ''}>${a.nombre_area}</option>`).join('');
    let opcionesDesde = '';
    let opcionesHasta = '';
    for (let i = 1; i <= 11; i++) {
        opcionesDesde += `<option value="${i}" ${i == nivelDesdeActual ? 'selected' : ''}>${i}</option>`;
        opcionesHasta += `<option value="${i}" ${i == nivelHastaActual ? 'selected' : ''}>${i}</option>`;
    }
    const r = await Swal.fire({
        title: 'Editar Materia',
        html: `<div class="text-start">
                <label class="small fw-bold text-secondary mb-1">Nombre de la Especialidad / Materia</label>
                <input id="esp-nom" class="input-elite mb-3" value="${nombre}">
                <label class="small fw-bold text-secondary mb-1">Área Académica</label>
                <select id="esp-area" class="select-elite mb-3">${opciones}</select>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="small fw-bold text-secondary mb-1">Grado Inicial</label>
                        <select id="esp-desde" class="select-elite" data-ares-ignore="true">${opcionesDesde}</select>
                    </div>
                    <div class="col-6">
                        <label class="small fw-bold text-secondary mb-1">Grado Final</label>
                        <select id="esp-hasta" class="select-elite" data-ares-ignore="true">${opcionesHasta}</select>
                    </div>
                </div>
               </div>`,
        showCancelButton: true, confirmButtonText: 'Corregir',
        customClass: { confirmButton: 'btn-elite px-4', cancelButton: 'btn-elite btn-elite--outline px-4 ms-2' }, buttonsStyling: false,
        preConfirm: () => {
            const desde = parseInt(document.getElementById('esp-desde').value);
            const hasta = parseInt(document.getElementById('esp-hasta').value);
            if (desde > hasta) {
                lanzarToastElite('danger', 'El grado inicial no puede ser mayor al grado final.');
                return false;
            }
            return {
                id: id,
                nombre_especialidad: document.getElementById('esp-nom').value,
                area_id: document.getElementById('esp-area').value,
                nivel_desde: desde,
                nivel_hasta: hasta
            };
        }
    });
    if (r.isConfirmed) enviarPostElite('logica/editar_especialidad.php', r.value);
};

window.borrarEspecialidad = async function (id, nombre) {
    const result = await Swal.fire({
        title: 'Depuración Física', text: `Se suprimirá "${nombre}".`, icon: 'warning', showCancelButton: true, confirmButtonText: 'Borrar',
        customClass: { confirmButton: 'btn-elite btn-elite--primary px-4', cancelButton: 'btn-elite btn-elite--outline px-4 ms-2' }, buttonsStyling: false
    });
    if (result.isConfirmed) enviarPostElite('logica/borrar_especialidad.php', { id: id });
};

window.nuevoCurso = async function (tutoresJSON) {
    let opciones = '<option value="">Sin Tutor</option>' + tutoresJSON.map(t => `<option value="${t.id}">${t.nombre}</option>`).join('');
    const jornadas = ["Mañana", "Tarde", "Noche", "Única"];
    let opcionesJornada = jornadas.map(j => `<option value="${j}">${j}</option>`).join('');
    const r = await Swal.fire({
        title: 'Aperturar Nivel',
        html: `<div class="text-start">
                <label class="small fw-bold text-secondary mb-1">Nombre del Curso</label>
                <input id="c-nom" class="input-elite mb-3">
                <label class="small fw-bold text-secondary mb-1">Jornada</label>
                <select id="c-jor" class="select-elite mb-3">${opcionesJornada}</select>
                <label class="small fw-bold text-secondary mb-1">Director / Tutor</label>
                <select id="c-tut" class="select-elite">${opciones}</select>
               </div>`,
        showCancelButton: true, confirmButtonText: 'Registrar',
        customClass: { confirmButton: 'btn-elite px-4', cancelButton: 'btn-elite btn-elite--outline px-4 ms-2' }, buttonsStyling: false,
        showLoaderOnConfirm: true,
        preConfirm: async () => {
            const nombre_curso = document.getElementById('c-nom').value.trim();
            const tutor_id = document.getElementById('c-tut').value;
            const jornada = document.getElementById('c-jor').value;

            if (!nombre_curso) {
                lanzarToastElite('danger', 'El nombre del curso es obligatorio.');
                return false;
            }

            try {
                const formData = new FormData();
                formData.append('csrf_token', window.CSRF_TOKEN || '');
                formData.append('nombre_curso', nombre_curso);
                formData.append('tutor_id', tutor_id);
                formData.append('jornada', jornada);

                const response = await fetch('logica/guardar_curso.php', { method: 'POST', body: formData });
                if (!response.ok) throw new Error('Error en la comunicación HTTP.');

                const data = await response.json();
                if (data.status === 'success') {
                    return data;
                } else {
                    lanzarToastElite('danger', data.message || 'Error de registro');
                    return false;
                }
            } catch (err) {
                lanzarToastElite('danger', err.message || 'No se pudo contactar con el servidor.');
                return false;
            }
        }
    });

    if (r.isConfirmed && r.value) {
        lanzarToastElite('success', r.value.message || 'Curso creado correctamente.');
        if (typeof navegarModulo === 'function') {
            window.forceRefreshElite = true;
            navegarModulo(window.MODULO_ACTUAL || 'inicio');
        }
    }
};

window.editarCurso = async function (id, nombre, tutorActual, jornadaActual, tutoresJSON) {
    let opciones = '<option value="">Sin Tutor</option>' + tutoresJSON.map(t => `<option value="${t.id}" ${t.id == tutorActual ? 'selected' : ''}>${t.nombre}</option>`).join('');
    const jornadas = ["Mañana", "Tarde", "Noche", "Única"];
    let opcionesJornada = jornadas.map(j => `<option value="${j}" ${j === jornadaActual ? 'selected' : ''}>${j}</option>`).join('');
    const r = await Swal.fire({
        title: 'Edición de jefe de grupo',
        html: `<div class="text-start">
                <label class="small fw-bold text-secondary mb-1">Nombre del Curso</label>
                <input id="c-nom" class="input-elite mb-3" value="${nombre}">
                <label class="small fw-bold text-secondary mb-1">Jornada</label>
                <select id="c-jor" class="select-elite mb-3">${opcionesJornada}</select>
                <label class="small fw-bold text-secondary mb-1">Director / Tutor</label>
                <select id="c-tut" class="select-elite">${opciones}</select>
               </div>`,
        showCancelButton: true, confirmButtonText: 'Corregir',
        customClass: { confirmButton: 'btn-elite px-4', cancelButton: 'btn-elite btn-elite--outline px-4 ms-2' }, buttonsStyling: false,
        showLoaderOnConfirm: true,
        preConfirm: async () => {
            const nombre_curso = document.getElementById('c-nom').value.trim();
            const tutor_id = document.getElementById('c-tut').value;
            const jornada = document.getElementById('c-jor').value;

            if (!nombre_curso) {
                lanzarToastElite('danger', 'El nombre del curso es obligatorio.');
                return false;
            }

            try {
                const formData = new FormData();
                formData.append('csrf_token', window.CSRF_TOKEN || '');
                formData.append('id', id);
                formData.append('nombre_curso', nombre_curso);
                formData.append('tutor_id', tutor_id);
                formData.append('jornada', jornada);

                const response = await fetch('logica/editar_curso.php', { method: 'POST', body: formData });
                if (!response.ok) throw new Error('Error en la comunicación HTTP.');

                const data = await response.json();
                if (data.status === 'success') {
                    return data;
                } else {
                    lanzarToastElite('danger', data.message || 'Error de edición');
                    return false;
                }
            } catch (err) {
                lanzarToastElite('danger', err.message || 'No se pudo contactar con el servidor.');
                return false;
            }
        }
    });

    if (r.isConfirmed && r.value) {
        lanzarToastElite('success', r.value.message || 'Curso actualizado correctamente.');
        if (typeof navegarModulo === 'function') {
            window.forceRefreshElite = true;
            navegarModulo(window.MODULO_ACTUAL || 'inicio');
        }
    }
};

window.borrarCurso = async function (id, nombre) {
    const result = await Swal.fire({
        title: 'Colapso de Nivel', text: `Se borrará el curso "${nombre}".`, icon: 'warning', showCancelButton: true, confirmButtonText: 'Confirmar',
        customClass: { confirmButton: 'btn-elite btn-elite--primary px-4', cancelButton: 'btn-elite btn-elite--outline px-4 ms-2' }, buttonsStyling: false
    });
    if (result.isConfirmed) enviarPostElite('logica/borrar_curso.php', { id: id });
};

// --- GESTIÓN DE ESTUDIANTES ---
window.abrirCargaMasiva = async function () {
    const result = await Swal.fire({
        title: 'Carga Masiva de Alumnos',
        html: `<input type="file" id="archivo_csv" class="form-control" accept=".csv">`,
        showCancelButton: true, confirmButtonText: 'Procesar',
        customClass: { confirmButton: 'btn-elite px-4', cancelButton: 'btn-elite btn-elite--outline px-4 ms-2' }, buttonsStyling: false,
        preConfirm: () => { return document.getElementById('archivo_csv').files[0]; }
    });

    if (result.isConfirmed && result.value) {
        const formData = new FormData();
        formData.append('archivo_csv', result.value);
        formData.append('csrf_token', window.CSRF_TOKEN);

        enviarPostElite('logica/procesar_carga_masiva.php', formData);
    }
};

window.cambiarTabEditar = function (tabName) {
    document.querySelectorAll('.tab-edit-pane').forEach(el => {
        el.classList.add('d-none');
        el.classList.remove('d-flex');
    });
    document.querySelectorAll('.btn-tab-elite').forEach(el => el.classList.remove('active'));

    const targetPane = document.getElementById('tab-edit-' + tabName);
    if (targetPane) {
        targetPane.classList.remove('d-none');
        targetPane.classList.add('d-flex');
    }

    const btn = document.querySelector(`button[onclick="window.cambiarTabEditar('${tabName}')"]`);
    if (btn) btn.classList.add('active');
};

window.editarEstudiante = async function (id, d, listaCursos) {
    let selectCursos = '<select id="swal-curso" class="select-elite"><option value="">No Asignado</option>' + listaCursos.map(c => `<option value="${c.id}" ${c.id == d.curso_id ? 'selected' : ''}>${c.nombre_curso}</option>`).join('') + '</select>';

    // Opciones del RH con pre-selección
    const gruposRH = ["O+", "O-", "A+", "A-", "B+", "B-", "AB+", "AB-"];
    let selectRH = `<select id="swal-rh" class="select-elite"><option value="" disabled ${!d.rh ? 'selected' : ''}>Seleccione...</option>`;
    gruposRH.forEach(g => {
        selectRH += `<option value="${g}" ${d.rh === g ? 'selected' : ''}>${g}</option>`;
    });
    selectRH += `</select>`;

    let fotoUrl = '';
    if (d.foto && d.foto !== 'null') {
        const cleanPath = d.foto.replace(/^\//, ''); // quitar slash inicial si existe
        const pathParts = window.location.pathname.split('/');
        const subFolder = pathParts[1] ? '/' + pathParts[1] + '/' : '/';
        
        if (cleanPath.startsWith('uploads/')) {
            fotoUrl = subFolder + cleanPath;
        } else {
            fotoUrl = subFolder + 'uploads/fotos/' + cleanPath;
        }
    }

    const r = await Swal.fire({
        title: 'Propiedades del Expediente',
        width: '850px',
        html: `<div class="swal-tab-container">
                <style>
                    .btn-tab-elite {
                        height: 44px;
                        border: 2px solid var(--el-primary);
                        border-radius: var(--el-radius-sub);
                        background: transparent;
                        color: var(--el-primary);
                        font-weight: bold;
                        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                        cursor: pointer;
                        padding-inline: 15px;
                    }
                    .btn-tab-elite.active {
                        background: var(--el-primary);
                        color: var(--el-white);
                    }
                    .label-horiz-elite {
                        width: 115px;
                        min-width: 115px;
                        margin-bottom: 0;
                        font-size: 0.8rem;
                        font-weight: bold;
                        color: var(--el-text-secondary);
                    }
                    .select-tdoc-elite {
                        max-width: 60px;
                        min-width: 60px;
                        margin-inline-end: 8px;
                    }
                    
                    /* Evitar que el modal baile congelando la altura de las pestañas */
                    .tab-edit-pane {
                        height: 365px;
                        min-height: 365px;
                        align-content: flex-start;
                    }
                    
                    .swal-tab-container #swal-tdoc,
                    .swal-tab-container #swal-rh,
                    .swal-tab-container #swal-curso {
                        max-width: 60px;
                        min-width: 60px;
                        flex-grow: 0;
                    }
                    .swal-tab-container #swal-ident,
                    .swal-tab-container #swal-padre-doc,
                    .swal-tab-container #swal-madre-doc {
                        max-width: 120px;
                        min-width: 120px;
                        flex-grow: 0;
                    }
                    .swal-tab-container #swal-nom,
                    .swal-tab-container #swal-ape,
                    .swal-tab-container #swal-fnac,
                    .swal-tab-container #swal-lnac,
                    .swal-tab-container #swal-nac,
                    .swal-tab-container #swal-padre-nac,
                    .swal-tab-container #swal-madre-nac,
                    .swal-tab-container #swal-padre-cel,
                    .swal-tab-container #swal-madre-cel,
                    .swal-tab-container #swal-padre-prof,
                    .swal-tab-container #swal-madre-prof,
                    .swal-tab-container #swal-folio {
                        max-width: 170px;
                        min-width: 170px;
                        flex-grow: 0;
                    }
                    .swal-tab-container {
                        position: relative;
                    }
                    .swal-foto-preview-container-top {
                        position: absolute;
                        top: -65px;
                        right: 5px;
                        width: 90px;
                        height: 110px;
                        border-radius: var(--el-radius-sub);
                        border: 1px solid var(--el-border);
                        background: var(--el-bg-neutral-light);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        overflow: hidden;
                        box-shadow: var(--el-shadow-sm);
                    }
                    .swal-foto-preview-top {
                        width: 100%;
                        height: 100%;
                        object-fit: cover;
                    }
                    .swal-foto-preview-placeholder-top {
                        color: var(--el-text-secondary);
                        font-size: 1.8rem;
                        text-align: center;
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        justify-content: center;
                    }
                    
                    /* Prevenir recorte del marco superior de foto en SweetAlert2 */
                    html.swal2-shown body.swal2-shown div.swal2-container div.swal2-popup {
                        overflow: visible;
                    }
                    html.swal2-shown body.swal2-shown div.swal2-container div.swal2-popup div.swal2-html-container {
                        overflow: visible;
                    }
                </style>
                <div class="swal-foto-preview-container-top shadow-sm">
                    ${fotoUrl ? `<img src="${fotoUrl}" class="swal-foto-preview-top">` : `<div class="swal-foto-preview-placeholder-top"><i class="bi bi-person-fill"></i><span class="fs-nano text-muted" style="display:block;margin-top:2px;font-size:0.65rem;">SIN FOTO</span></div>`}
                </div>
                <div class="d-flex border-bottom mb-3 pb-2 gap-2 justify-content-center" style="padding-inline-end: 100px;">
                    <button type="button" class="btn-tab-elite active" onclick="window.cambiarTabEditar('estudiante')">Estudiante</button>
                    <button type="button" class="btn-tab-elite" onclick="window.cambiarTabEditar('padre')">Padre / Acudiente</button>
                    <button type="button" class="btn-tab-elite" onclick="window.cambiarTabEditar('madre')">Madre / Acudiente</button>
                </div>
                
                <!-- PESTAÑA 1: ESTUDIANTE -->
                <div id="tab-edit-estudiante" class="tab-edit-pane d-flex row g-2 text-start mt-2">
                    <div class="col-6 d-flex align-items-center"><label class="label-horiz-elite">Nombres</label><input id="swal-nom" class="input-elite" value="${d.nombre || ''}"></div>
                    <div class="col-6 d-flex align-items-center"><label class="label-horiz-elite">Apellidos</label><input id="swal-ape" class="input-elite" value="${d.apellido || ''}"></div>
                    <div class="col-6 d-flex align-items-center">
                        <label class="label-horiz-elite">Documento</label>
                        <select id="swal-tdoc" class="select-elite select-tdoc-elite">
                            <option value="RC" ${d.tipo_documento == 'RC' ? 'selected' : ''}>RC</option>
                            <option value="TI" ${d.tipo_documento == 'TI' ? 'selected' : ''}>TI</option>
                            <option value="CC" ${d.tipo_documento == 'CC' ? 'selected' : ''}>CC</option>
                        </select>
                        <input id="swal-ident" class="input-elite" value="${d.identificacion || ''}">
                    </div>
                    <div class="col-6 d-flex align-items-center"><label class="label-horiz-elite">RH / Sanguíneo</label>${selectRH}</div>
                    <div class="col-6 d-flex align-items-center"><label class="label-horiz-elite">Curso</label>${selectCursos}</div>
                    <div class="col-6 d-flex align-items-center"><label class="label-horiz-elite">F. Nacimiento</label><input type="date" id="swal-fnac" class="input-elite" value="${d.fecha_nacimiento || ''}"></div>
                    <div class="col-6 d-flex align-items-center"><label class="label-horiz-elite">Lugar Nac.</label><input id="swal-lnac" class="input-elite" value="${d.lugar_nacimiento || ''}"></div>
                    <div class="col-6 d-flex align-items-center"><label class="label-horiz-elite">Nacionalidad</label><input id="swal-nac" class="input-elite" value="${d.nacionalidad || ''}"></div>
                    <div class="col-6 d-flex align-items-center"><label class="label-horiz-elite">Dirección</label><input id="swal-dir" class="input-elite" value="${d.direccion_estudiante || ''}"></div>
                    <div class="col-6 d-flex align-items-center"><label class="label-horiz-elite">Folio Matrícula</label><input id="swal-folio" class="input-elite" value="${d.folio_matricula || ''}" readonly></div>
                    <div class="col-6 d-flex align-items-center"><label class="label-horiz-elite">Colegio Ant.</label><input id="swal-colant" class="input-elite" value="${d.colegio_anterior || ''}"></div>
                    <div class="col-6 d-flex align-items-center">
                        <label class="label-horiz-elite"><i class="bi bi-camera me-1 text-primary"></i> Foto 3x4</label>
                        <input type="file" id="swal-foto" accept="image/*" class="form-control form-control-sm">
                    </div>
                </div>

                <!-- PESTAÑA 2: PADRE -->
                <div id="tab-edit-padre" class="tab-edit-pane d-none row g-2 text-start mt-2">
                    <div class="col-12 d-flex align-items-center"><label class="label-horiz-elite">Nombre Padre</label><input id="swal-padre-nom" class="input-elite" value="${d.padre_nombre || ''}"></div>
                    <div class="col-12 d-flex align-items-center">
                        <label class="label-horiz-elite">Documento</label>
                        <select id="swal-padre-tdoc" class="select-elite select-tdoc-elite">
                            <option value="CC" ${d.padre_tipo_documento == 'CC' ? 'selected' : ''}>CC</option>
                            <option value="CE" ${d.padre_tipo_documento == 'CE' ? 'selected' : ''}>CE</option>
                            <option value="TI" ${d.padre_tipo_documento == 'TI' ? 'selected' : ''}>TI</option>
                        </select>
                        <input id="swal-padre-doc" class="input-elite" value="${d.padre_documento || ''}" style="margin-inline-end: 8px;">
                        <input id="swal-padre-doc-exp" class="input-elite" placeholder="Expedida en" value="${d.padre_documento_expedicion || ''}" style="max-width: 110px;">
                    </div>
                    <div class="col-6 d-flex align-items-center"><label class="label-horiz-elite">Nacionalidad</label><input id="swal-padre-nac" class="input-elite" value="${d.padre_nacionalidad || ''}"></div>
                    <div class="col-6 d-flex align-items-center"><label class="label-horiz-elite">Celular</label><input id="swal-padre-cel" class="input-elite" value="${d.padre_celular || ''}"></div>
                    <div class="col-12 d-flex align-items-center"><label class="label-horiz-elite">Dirección</label><input id="swal-padre-dir" class="input-elite" value="${d.padre_direccion || ''}"></div>
                    <div class="col-6 d-flex align-items-center"><label class="label-horiz-elite">Profesión</label><input id="swal-padre-prof" class="input-elite" value="${d.padre_profesion || ''}"></div>
                    <div class="col-6 d-flex align-items-center"><label class="label-horiz-elite">Email</label><input type="email" id="swal-padre-mail" class="input-elite" value="${d.padre_email || ''}"></div>
                </div>

                <!-- PESTAÑA 3: MADRE -->
                <div id="tab-edit-madre" class="tab-edit-pane d-none row g-2 text-start mt-2">
                    <div class="col-12 d-flex align-items-center"><label class="label-horiz-elite">Nombre Madre</label><input id="swal-madre-nom" class="input-elite" value="${d.madre_nombre || ''}"></div>
                    <div class="col-12 d-flex align-items-center">
                        <label class="label-horiz-elite">Documento</label>
                        <select id="swal-madre-tdoc" class="select-elite select-tdoc-elite">
                            <option value="CC" ${d.madre_tipo_documento == 'CC' ? 'selected' : ''}>CC</option>
                            <option value="CE" ${d.madre_tipo_documento == 'CE' ? 'selected' : ''}>CE</option>
                            <option value="TI" ${d.madre_tipo_documento == 'TI' ? 'selected' : ''}>TI</option>
                        </select>
                        <input id="swal-madre-doc" class="input-elite" value="${d.madre_documento || ''}" style="margin-inline-end: 8px;">
                        <input id="swal-madre-doc-exp" class="input-elite" placeholder="Expedida en" value="${d.madre_documento_expedicion || ''}" style="max-width: 110px;">
                    </div>
                    <div class="col-6 d-flex align-items-center"><label class="label-horiz-elite">Nacionalidad</label><input id="swal-madre-nac" class="input-elite" value="${d.madre_nacionalidad || ''}"></div>
                    <div class="col-6 d-flex align-items-center"><label class="label-horiz-elite">Celular</label><input id="swal-madre-cel" class="input-elite" value="${d.madre_celular || ''}"></div>
                    <div class="col-12 d-flex align-items-center"><label class="label-horiz-elite">Dirección</label><input id="swal-madre-dir" class="input-elite" value="${d.madre_direccion || ''}"></div>
                    <div class="col-6 d-flex align-items-center"><label class="label-horiz-elite">Profesión</label><input id="swal-madre-prof" class="input-elite" value="${d.madre_profesion || ''}"></div>
                    <div class="col-6 d-flex align-items-center"><label class="label-horiz-elite">Email</label><input type="email" id="swal-madre-mail" class="input-elite" value="${d.madre_email || ''}"></div>
                </div>
               </div>`,
        showCancelButton: true, confirmButtonText: 'Guardar',
        customClass: { confirmButton: 'btn-elite px-4', cancelButton: 'btn-elite btn-elite--outline px-4 ms-2' }, buttonsStyling: false,
        didOpen: () => {
            const fotoInput = document.getElementById('swal-foto');
            if (fotoInput) {
                fotoInput.addEventListener('change', function() {
                    const file = this.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const container = document.querySelector('.swal-foto-preview-container-top');
                            if (container) {
                                container.innerHTML = `<img src="${e.target.result}" class="swal-foto-preview-top">`;
                            }
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }
        },
        preConfirm: () => {
            const fotoInput = document.getElementById('swal-foto');
            return {
                id: id,
                nombre: document.getElementById('swal-nom').value,
                apellido: document.getElementById('swal-ape').value,
                identificacion: document.getElementById('swal-ident').value,
                tipo_documento: document.getElementById('swal-tdoc').value,
                curso_id: document.getElementById('swal-curso').value,
                tipo_sangre: document.getElementById('swal-rh').value,
                promedio: d.promedio,
                fotoFile: fotoInput && fotoInput.files.length > 0 ? fotoInput.files[0] : null,

                // Campos adicionales
                fecha_nacimiento: document.getElementById('swal-fnac').value,
                lugar_nacimiento: document.getElementById('swal-lnac').value,
                nacionalidad: document.getElementById('swal-nac').value,
                direccion_estudiante: document.getElementById('swal-dir').value,
                folio_matricula: document.getElementById('swal-folio').value,
                colegio_anterior: document.getElementById('swal-colant').value,

                padre_nombre: document.getElementById('swal-padre-nom').value,
                padre_tipo_documento: document.getElementById('swal-padre-tdoc').value,
                padre_documento: document.getElementById('swal-padre-doc').value,
                padre_documento_expedicion: document.getElementById('swal-padre-doc-exp').value,
                padre_nacionalidad: document.getElementById('swal-padre-nac').value,
                padre_celular: document.getElementById('swal-padre-cel').value,
                padre_direccion: document.getElementById('swal-padre-dir').value,
                padre_profesion: document.getElementById('swal-padre-prof').value,
                padre_email: document.getElementById('swal-padre-mail').value,

                madre_nombre: document.getElementById('swal-madre-nom').value,
                madre_tipo_documento: document.getElementById('swal-madre-tdoc').value,
                madre_documento: document.getElementById('swal-madre-doc').value,
                madre_documento_expedicion: document.getElementById('swal-madre-doc-exp').value,
                madre_nacionalidad: document.getElementById('swal-madre-nac').value,
                madre_celular: document.getElementById('swal-madre-cel').value,
                madre_direccion: document.getElementById('swal-madre-dir').value,
                madre_profesion: document.getElementById('swal-madre-prof').value,
                madre_email: document.getElementById('swal-madre-mail').value
            }
        }
    });

    if (r.isConfirmed && r.value) {
        const data = r.value;
        const formData = new FormData();
        formData.append('id', data.id);
        formData.append('nombre', data.nombre);
        formData.append('apellido', data.apellido);
        formData.append('identificacion', data.identificacion);
        formData.append('tipo_documento', data.tipo_documento);
        formData.append('curso_id', data.curso_id);
        formData.append('tipo_sangre', data.tipo_sangre);
        formData.append('promedio', data.promedio);

        // Campos adicionales
        formData.append('fecha_nacimiento', data.fecha_nacimiento);
        formData.append('lugar_nacimiento', data.lugar_nacimiento);
        formData.append('nacionalidad', data.nacionalidad);
        formData.append('direccion_estudiante', data.direccion_estudiante);
        formData.append('folio_matricula', data.folio_matricula);
        formData.append('colegio_anterior', data.colegio_anterior);

        formData.append('padre_nombre', data.padre_nombre);
        formData.append('padre_tipo_documento', data.padre_tipo_documento);
        formData.append('padre_documento', data.padre_documento);
        formData.append('padre_documento_expedicion', data.padre_documento_expedicion);
        formData.append('padre_nacionalidad', data.padre_nacionalidad);
        formData.append('padre_celular', data.padre_celular);
        formData.append('padre_direccion', data.padre_direccion);
        formData.append('padre_profesion', data.padre_profesion);
        formData.append('padre_email', data.padre_email);

        formData.append('madre_nombre', data.madre_nombre);
        formData.append('madre_tipo_documento', data.madre_tipo_documento);
        formData.append('madre_documento', data.madre_documento);
        formData.append('madre_documento_expedicion', data.madre_documento_expedicion);
        formData.append('madre_nacionalidad', data.madre_nacionalidad);
        formData.append('madre_celular', data.madre_celular);
        formData.append('madre_direccion', data.madre_direccion);
        formData.append('madre_profesion', data.madre_profesion);
        formData.append('madre_email', data.madre_email);

        if (window.CSRF_TOKEN) formData.append('csrf_token', window.CSRF_TOKEN);
        if (data.fotoFile) {
            formData.append('foto', data.fotoFile);
        }
        enviarPostElite('logica/editar_estudiante.php', formData);
    }
};

window.borrarEstudiante = async function (id, nombre) {
    const r = await Swal.fire({
        title: 'Baja Académica', text: `Eliminarás a: ${nombre}.`, icon: 'warning', showCancelButton: true, confirmButtonText: 'Proceder',
        customClass: { confirmButton: 'btn-elite btn-elite--primary px-4', cancelButton: 'btn-elite btn-elite--outline px-4 ms-2' }, buttonsStyling: false
    });
    if (r.isConfirmed) {
        enviarPostElite('logica/borrar_estudiante.php', { id: id });
    }
};

// 📌 RECURRENCIA ACADÉMICA: Reabrir modal de personal automáticamente para registros secuenciales
document.addEventListener('DOMContentLoaded', () => {
    if (sessionStorage.getItem('reabrir_nuevo_personal') === 'true') {
        sessionStorage.removeItem('reabrir_nuevo_personal');
        setTimeout(() => {
            const btn = document.querySelector('button[onclick^="nuevoPersonal"]');
            if (btn) btn.click();
        }, 300);
    }
});
