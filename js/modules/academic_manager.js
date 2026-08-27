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

// --- GESTIÓN DE TALENTO HUMANO (FICHA INTEGRAL VITRINA 06) ---
window.cambiarTabPersonal = function (tabName) {
    document.querySelectorAll('.tab-pers-pane').forEach(el => {
        el.classList.add('d-none');
        el.classList.remove('d-flex');
    });
    document.querySelectorAll('.btn-tab-elite, .btn-tab-pers-elite').forEach(el => el.classList.remove('active'));

    const targetPane = document.getElementById('tab-pers-' + tabName);
    if (targetPane) {
        targetPane.classList.remove('d-none');
        targetPane.classList.add('d-flex');
    }

    const btn = document.querySelector(`button[onclick*="cambiarTabPersonal('${tabName}')"]`);
    if (btn) btn.classList.add('active');

    if (tabName === 'cont' && typeof window.calcularEdadPersonal === 'function') {
        window.calcularEdadPersonal();
    }
};

window.calcularEdadString = function (fechaStr) {
    if (!fechaStr || fechaStr.trim() === '') return '';
    fechaStr = fechaStr.trim();
    let fechaNac;
    if (/^\d{4}-\d{2}-\d{2}/.test(fechaStr)) {
        const partes = fechaStr.split('-');
        fechaNac = new Date(parseInt(partes[0], 10), parseInt(partes[1], 10) - 1, parseInt(partes[2], 10));
    } else if (/^\d{2}\/\d{2}\/\d{4}/.test(fechaStr)) {
        const partes = fechaStr.split('/');
        fechaNac = new Date(parseInt(partes[2], 10), parseInt(partes[1], 10) - 1, parseInt(partes[0], 10));
    } else {
        return '';
    }
    if (isNaN(fechaNac.getTime())) return '';
    const hoy = new Date();
    let edad = hoy.getFullYear() - fechaNac.getFullYear();
    const mes = hoy.getMonth() - fechaNac.getMonth();
    if (mes < 0 || (mes === 0 && hoy.getDate() < fechaNac.getDate())) {
        edad--;
    }
    return edad >= 0 ? edad : 0;
};

window.calcularEdadPersonal = function () {
    const inputFnac = document.getElementById('swal-pers-fnac');
    const inputEdad = document.getElementById('swal-pers-edad');
    if (!inputFnac || !inputEdad) return;

    if (inputFnac.value && inputFnac.value.trim() !== '') {
        const edadCalculada = window.calcularEdadString(inputFnac.value);
        if (edadCalculada !== '') {
            inputEdad.value = edadCalculada;
            return;
        }
    }
    if (inputEdad.value && inputEdad.value.trim() !== '') {
        return;
    }
    inputEdad.value = '';
};

window.nuevoPersonal = async function (btnOrRoles, listaEspecialidades) {
    let listaRoles = [];
    if (btnOrRoles instanceof HTMLElement) {
        try { listaRoles = JSON.parse(btnOrRoles.dataset.roles || '[]'); } catch (e) { listaRoles = []; }
        try { listaEspecialidades = JSON.parse(btnOrRoles.dataset.especialidades || '[]'); } catch (e) { listaEspecialidades = []; }
    } else if (Array.isArray(btnOrRoles)) {
        listaRoles = btnOrRoles;
        listaEspecialidades = listaEspecialidades || [];
    } else if (typeof btnOrRoles === 'string') {
        try { listaRoles = JSON.parse(btnOrRoles); } catch (e) { listaRoles = []; }
        if (typeof listaEspecialidades === 'string') {
            try { listaEspecialidades = JSON.parse(listaEspecialidades); } catch (e) { listaEspecialidades = []; }
        }
    }

    let opcionesRoles = (listaRoles || []).map(rol => `<option value="${rol.id}">${rol.nombre_rol}</option>`).join('');
    let opcionesEspecialidades = (listaEspecialidades || []).map(esp => `<option value="${esp.id}">${esp.nombre_especialidad}</option>`).join('');

    const r = await Swal.fire({
        title: 'Vincular Colaborador a Nómina',
        width: '850px',
        html: `<div class="swal-tab-container">
            <form id="swal-pers-form" onsubmit="return false;" autocomplete="off">
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
                    width: 110px;
                    min-width: 110px;
                    margin-bottom: 0;
                    font-size: 0.8rem;
                    font-weight: bold;
                    color: var(--el-text-secondary);
                    white-space: nowrap;
                }
                .select-tdoc-elite {
                    max-width: 60px;
                    min-width: 60px;
                    margin-inline-end: 8px;
                }
                .tab-edit-pane {
                    height: 365px;
                    min-height: 365px;
                    align-content: flex-start;
                }
                .swal-tab-container .input-elite.input-edad-elite {
                    width: 52px;
                    min-width: 52px;
                    max-width: 52px;
                    padding-inline: 4px;
                    text-align: center;
                    margin-inline-end: 4px;
                }
            </style>
            <div class="d-flex border-bottom mb-3 pb-2 gap-2 justify-content-center">
                <button type="button" class="btn-tab-elite active" onclick="window.cambiarTabPersonal('cred')">Credenciales & Rol</button>
                <button type="button" class="btn-tab-elite" onclick="window.cambiarTabPersonal('cont')">Identificación & Contacto</button>
                <button type="button" class="btn-tab-elite" onclick="window.cambiarTabPersonal('prof')">Perfil Laboral & SOS</button>
            </div>

            <!-- PESTAÑA 1: CREDENCIALES & ROL -->
            <div id="tab-pers-cred" class="tab-edit-pane tab-pers-pane d-flex row g-2 text-start mt-2">
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Nombres</label>
                    <input id="swal-pers-nom" class="input-elite" placeholder="Nombres">
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Apellidos</label>
                    <input id="swal-pers-ape" class="input-elite" placeholder="Apellidos">
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Rol</label>
                    <select id="swal-pers-rol" class="select-elite">
                        <option value="" disabled selected>Seleccione rol...</option>
                        ${opcionesRoles}
                    </select>
                </div>
                <div id="wrapper-pers-esp" class="col-6 d-flex align-items-center u-hidden">
                    <label class="label-horiz-elite">Cátedra / Área</label>
                    <select id="swal-pers-esp" class="select-elite">
                        <option value="">Seleccione materia...</option>
                        ${opcionesEspecialidades}
                    </select>
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Usuario</label>
                    <input id="swal-pers-user" class="input-elite font-monospace fw-bold text-primary" placeholder="Auto L+Iniciales" readonly autocomplete="username">
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Contraseña</label>
                    <input id="swal-pers-pass" type="password" class="input-elite" placeholder="Clave de acceso" value="1234" autocomplete="new-password">
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Email Inst.</label>
                    <input id="swal-pers-email-inst" type="email" class="input-elite" placeholder="correo@institucion.edu.co">
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Estado</label>
                    <select id="swal-pers-estado" class="select-elite">
                        <option value="ACTIVO" selected>ACTIVO</option>
                        <option value="LICENCIA">LICENCIA</option>
                        <option value="RETIRADO">RETIRADO</option>
                    </select>
                </div>
            </div>

            <!-- PESTAÑA 2: IDENTIFICACIÓN & CONTACTO -->
            <div id="tab-pers-cont" class="tab-edit-pane tab-pers-pane d-none row g-2 text-start mt-2">
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Documento</label>
                    <select id="swal-pers-tdoc" class="select-elite select-tdoc-elite">
                        <option value="CC" selected>CC</option>
                        <option value="CE">CE</option>
                        <option value="PAS">PAS</option>
                    </select>
                    <input id="swal-pers-doc" class="input-elite" placeholder="Número Cédula">
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Expedición</label>
                    <input id="swal-pers-doc-exp" class="input-elite" placeholder="Ciudad de expedición">
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">F. Nacimiento</label>
                    <input id="swal-pers-fnac" type="date" class="input-elite" oninput="window.calcularEdadPersonal()" onchange="window.calcularEdadPersonal()">
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Edad / RH</label>
                    <input id="swal-pers-edad" type="text" class="input-elite input-edad-elite" placeholder="Edad" readonly>
                    <select id="swal-pers-gen" class="select-elite" style="max-width:55px;min-width:55px;margin-inline-end:4px;">
                        <option value="M" selected>M</option>
                        <option value="F">F</option>
                        <option value="OTRO">Otro</option>
                    </select>
                    <select id="swal-pers-rh" class="select-elite" style="max-width:65px;min-width:65px;">
                        <option value="O+" selected>O+</option>
                        <option value="O-">O-</option>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                    </select>
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Celular</label>
                    <input id="swal-pers-cel" class="input-elite" placeholder="Número de celular">
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Tel. Fijo</label>
                    <input id="swal-pers-tel" class="input-elite" placeholder="Teléfono residencial">
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Email Pers.</label>
                    <input id="swal-pers-email" type="email" class="input-elite" placeholder="correo@gmail.com">
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Dirección</label>
                    <input id="swal-pers-dir" class="input-elite" placeholder="Dirección de residencia">
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Ciudad</label>
                    <input id="swal-pers-ciudad" class="input-elite" placeholder="Municipio / Ciudad">
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Barrio</label>
                    <input id="swal-pers-barrio" class="input-elite" placeholder="Barrio">
                </div>
            </div>

            <!-- PESTAÑA 3: PERFIL LABORAL & SOS -->
            <div id="tab-pers-prof" class="tab-edit-pane tab-pers-pane d-none row g-2 text-start mt-2">
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Título Univ.</label>
                    <input id="swal-pers-titulo" class="input-elite" placeholder="Ej: Licenciado en Matemáticas">
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Formación</label>
                    <select id="swal-pers-formacion" class="select-elite">
                        <option value="Pregrado" selected>Pregrado / Licenciatura</option>
                        <option value="Especialización">Especialización</option>
                        <option value="Maestría">Maestría</option>
                        <option value="Doctorado">Doctorado</option>
                    </select>
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Escalafón</label>
                    <input id="swal-pers-escalafon" class="input-elite" placeholder="Ej: Decreto 1278 - Grado 2A">
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">F. Ingreso</label>
                    <input id="swal-pers-fingreso" type="date" class="input-elite" value="${new Date().toISOString().split('T')[0]}">
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Contrato</label>
                    <select id="swal-pers-contrato" class="select-elite">
                        <option value="PLANTA" selected>Planta / Indefinido</option>
                        <option value="TÉRMINO FIJO">Término Fijo</option>
                        <option value="SERVICIOS">Prestación de Servicios</option>
                    </select>
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite" for="swal-pers-estado-lab">Estado</label>
                    <select id="swal-pers-estado-lab" name="estado_laboral" class="select-elite">
                        <option value="ACTIVO" selected>ACTIVO</option>
                        <option value="LICENCIA">LICENCIA</option>
                        <option value="RETIRADO">RETIRADO</option>
                    </select>
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">EPS</label>
                    <input id="swal-pers-eps" class="input-elite" placeholder="Entidad de Salud">
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Pensión</label>
                    <input id="swal-pers-pension" class="input-elite" placeholder="Fondo Pensiones">
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">ARL</label>
                    <input id="swal-pers-arl" class="input-elite" placeholder="Administradora Riesgos">
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Contacto SOS</label>
                    <input id="swal-pers-sos-nom" class="input-elite" placeholder="Nombre completo">
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Teléfono SOS</label>
                    <input id="swal-pers-sos-tel" class="input-elite" placeholder="Celular de emergencia">
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Parentesco</label>
                    <input id="swal-pers-sos-par" class="input-elite" placeholder="Vínculo o Parentesco">
                </div>
            </div>
            </form>
        </div>`,
        showCancelButton: true,
        confirmButtonText: 'Guardar',
        cancelButtonText: 'Cancelar',
        customClass: { confirmButton: 'btn-elite px-4 shadow-sm', cancelButton: 'btn-elite btn-elite--outline px-4 ms-2' },
        buttonsStyling: false,
        didOpen: () => {
            const selectRol = document.getElementById('swal-pers-rol');
            const wrapperEsp = document.getElementById('wrapper-pers-esp');
            const selectEsp = document.getElementById('swal-pers-esp');
            const inputNom = document.getElementById('swal-pers-nom');
            const inputApe = document.getElementById('swal-pers-ape');
            const inputUser = document.getElementById('swal-pers-user');
            const inputFnac = document.getElementById('swal-pers-fnac');
            const inputEdad = document.getElementById('swal-pers-edad');

            const generarUsuario = () => {
                let nom = inputNom.value.trim();
                let ape = inputApe ? inputApe.value.trim() : '';
                let textoCompleto = (nom + ' ' + ape).trim();
                if (!textoCompleto) {
                    inputUser.value = '';
                    return;
                }
                let partes = textoCompleto.split(/\s+/);
                let iniciales = '';
                partes.forEach(p => { if (p.length > 0) iniciales += p[0].toUpperCase(); });

                let prefijo = 'U';
                if (selectRol && selectRol.selectedIndex !== -1) {
                    let optionText = selectRol.options[selectRol.selectedIndex].text;
                    let val = selectRol.value;
                    if (val !== "") {
                        let textoLimpio = optionText.replace(/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]/g, '').trim().toLowerCase();
                        if (textoLimpio.includes('docente') || textoLimpio.includes('profesor')) {
                            prefijo = 'L';
                        } else if (textoLimpio.length > 0) {
                            prefijo = textoLimpio.charAt(0).toUpperCase();
                        }
                    }
                }
                inputUser.value = prefijo + iniciales;
            };

            selectRol.addEventListener('change', () => {
                if (selectRol.value === "11") {
                    wrapperEsp.classList.remove('u-hidden');
                } else {
                    wrapperEsp.classList.add('u-hidden');
                    selectEsp.value = "";
                }
                generarUsuario();
            });

            inputNom.addEventListener('input', generarUsuario);
            if (inputApe) inputApe.addEventListener('input', generarUsuario);
            if (inputFnac) {
                ['input', 'change', 'blur', 'keyup'].forEach(evento => {
                    inputFnac.addEventListener(evento, window.calcularEdadPersonal);
                });
                window.calcularEdadPersonal();
            }
        },
        preConfirm: () => {
            const nom = document.getElementById('swal-pers-nom').value.trim();
            const ape = document.getElementById('swal-pers-ape') ? document.getElementById('swal-pers-ape').value.trim() : '';
            const nombreCompleto = ape ? (nom + ' ' + ape) : nom;
            const rol = document.getElementById('swal-pers-rol').value;
            const user = document.getElementById('swal-pers-user').value.trim();
            const pass = document.getElementById('swal-pers-pass').value;

            if (!nombreCompleto || !rol || !user || !pass) {
                Swal.showValidationMessage('El Nombre, Apellidos, Rol, Usuario y Contraseña son obligatorios.');
                return false;
            }

            return {
                nombre: nombreCompleto,
                rol: rol,
                esp: document.getElementById('swal-pers-esp').value,
                user: user,
                pass: pass,
                email: document.getElementById('swal-pers-email-inst') ? document.getElementById('swal-pers-email-inst').value.trim() : '',
                tipo_documento: document.getElementById('swal-pers-tdoc').value,
                documento: document.getElementById('swal-pers-doc').value.trim(),
                documento_expedicion: document.getElementById('swal-pers-doc-exp').value.trim(),
                fecha_nacimiento: document.getElementById('swal-pers-fnac').value,
                edad: document.getElementById('swal-pers-edad').value,
                genero: document.getElementById('swal-pers-gen').value,
                rh: document.getElementById('swal-pers-rh').value,
                celular: document.getElementById('swal-pers-cel').value.trim(),
                telefono_fijo: document.getElementById('swal-pers-tel').value.trim(),
                email_personal: document.getElementById('swal-pers-email').value.trim(),
                direccion: document.getElementById('swal-pers-dir').value.trim(),
                ciudad_residencia: document.getElementById('swal-pers-ciudad').value.trim(),
                barrio: document.getElementById('swal-pers-barrio').value.trim(),
                titulo_profesional: document.getElementById('swal-pers-titulo').value.trim(),
                nivel_formacion: document.getElementById('swal-pers-formacion').value,
                escalafon_docente: document.getElementById('swal-pers-escalafon').value.trim(),
                fecha_ingreso: document.getElementById('swal-pers-fingreso').value,
                tipo_contrato: document.getElementById('swal-pers-contrato').value,
                estado_laboral: document.getElementById('swal-pers-estado').value,
                eps: document.getElementById('swal-pers-eps').value.trim(),
                fondo_pensiones: document.getElementById('swal-pers-pension').value.trim(),
                arl: document.getElementById('swal-pers-arl').value.trim(),
                contacto_emergencia_nombre: document.getElementById('swal-pers-sos-nom').value.trim(),
                contacto_emergencia_telefono: document.getElementById('swal-pers-sos-tel').value.trim(),
                contacto_emergencia_parentesco: document.getElementById('swal-pers-sos-par').value.trim()
            };
        }
    });

    if (r.isConfirmed && r.value) {
        const data = r.value;
        const formData = new FormData();
        Object.keys(data).forEach(k => {
            formData.append(k, data[k] || '');
        });
        if (window.CSRF_TOKEN) formData.append('csrf_token', window.CSRF_TOKEN);

        const resultado = await enviarPostElite('logica/guardar_personal.php', formData, true);
        if (resultado && resultado.status === 'success') {
            window.lanzarToastElite('success', resultado.message || 'Personal vinculado correctamente.');
            window.forceRefreshElite = true;
            if (typeof navegarModulo === 'function') navegarModulo(window.MODULO_ACTUAL || 'personal');
        } else if (resultado && resultado.status !== 'success') {
            window.lanzarToastElite('danger', resultado.message || 'No se pudo vincular al personal.', 'Error de Bóveda');
        }
    }
};

window.editarPersonal = async function (id, dOrBtn, listaRoles, listaEspecialidades) {
    let d = {};
    if (dOrBtn instanceof HTMLElement) {
        try { d = JSON.parse(dOrBtn.dataset.personal || '{}'); } catch (e) { d = {}; }
        try { listaRoles = JSON.parse(dOrBtn.dataset.roles || '[]'); } catch (e) { listaRoles = []; }
        try { listaEspecialidades = JSON.parse(dOrBtn.dataset.especialidades || '[]'); } catch (e) { listaEspecialidades = []; }
    } else if (typeof dOrBtn === 'object' && dOrBtn !== null) {
        d = dOrBtn;
    } else if (typeof dOrBtn === 'string') {
        try { d = JSON.parse(dOrBtn); } catch (e) { d = {}; }
    }
    d = d || {};

    if (typeof listaRoles === 'string') {
        try { listaRoles = JSON.parse(listaRoles); } catch (e) { listaRoles = []; }
    }
    if (typeof listaEspecialidades === 'string') {
        try { listaEspecialidades = JSON.parse(listaEspecialidades); } catch (e) { listaEspecialidades = []; }
    }
    listaRoles = Array.isArray(listaRoles) ? listaRoles : [];
    listaEspecialidades = Array.isArray(listaEspecialidades) ? listaEspecialidades : [];

    let nombres = '', apellidos = '';
    if (d.nombre) {
        let partes = d.nombre.trim().split(/\s+/);
        if (partes.length >= 4) {
            nombres = partes[0] + ' ' + partes[1];
            apellidos = partes.slice(2).join(' ');
        } else if (partes.length === 3) {
            nombres = partes[0];
            apellidos = partes.slice(1).join(' ');
        } else if (partes.length === 2) {
            nombres = partes[0];
            apellidos = partes[1];
        } else {
            nombres = d.nombre;
        }
    }

    let edadCalculada = d.edad;
    if ((edadCalculada === undefined || edadCalculada === null || edadCalculada === '' || edadCalculada == 0) && d.fecha_nacimiento) {
        let fn = new Date(d.fecha_nacimiento + 'T00:00:00');
        if (!isNaN(fn.getTime())) {
            let hoy = new Date();
            let age = hoy.getFullYear() - fn.getFullYear();
            let m = hoy.getMonth() - fn.getMonth();
            if (m < 0 || (m === 0 && hoy.getDate() < fn.getDate())) age--;
            if (age >= 0) edadCalculada = age;
        }
    }

    let opcionesRoles = (listaRoles || []).map(rol => `<option value="${rol.id}" ${rol.id == (d.rol_id || 0) ? 'selected' : ''}>${rol.nombre_rol}</option>`).join('');
    let opcionesEspecialidades = (listaEspecialidades || []).map(esp => `<option value="${esp.id}" ${esp.id == (d.especialidad_id || 0) ? 'selected' : ''}>${esp.nombre_especialidad}</option>`).join('');

    const r = await Swal.fire({
        title: 'Expediente: ' + (d.nombre || ''),
        width: '850px',
        html: `<div class="swal-tab-container">
            <form id="swal-pers-form" onsubmit="return false;" autocomplete="off">
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
                    width: 110px;
                    min-width: 110px;
                    margin-bottom: 0;
                    font-size: 0.8rem;
                    font-weight: bold;
                    color: var(--el-text-secondary);
                    white-space: nowrap;
                }
                .select-tdoc-elite {
                    max-width: 60px;
                    min-width: 60px;
                    margin-inline-end: 8px;
                }
                .tab-edit-pane {
                    height: 365px;
                    min-height: 365px;
                    align-content: flex-start;
                }
                .swal-tab-container .input-elite.input-edad-elite {
                    width: 52px;
                    min-width: 52px;
                    max-width: 52px;
                    padding-inline: 4px;
                    text-align: center;
                    margin-inline-end: 4px;
                }
            </style>
            <div class="d-flex border-bottom mb-3 pb-2 gap-2 justify-content-center">
                <button type="button" class="btn-tab-elite active" onclick="window.cambiarTabPersonal('cred')">Credenciales & Rol</button>
                <button type="button" class="btn-tab-elite" onclick="window.cambiarTabPersonal('cont')">Identificación & Contacto</button>
                <button type="button" class="btn-tab-elite" onclick="window.cambiarTabPersonal('prof')">Perfil Laboral & SOS</button>
            </div>

            <!-- PESTAÑA 1: CREDENCIALES & ROL -->
            <div id="tab-pers-cred" class="tab-edit-pane tab-pers-pane d-flex row g-2 text-start mt-2">
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Nombres</label>
                    <input id="swal-pers-nom" class="input-elite" value="${nombres}">
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Apellidos</label>
                    <input id="swal-pers-ape" class="input-elite" value="${apellidos}">
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Rol</label>
                    <select id="swal-pers-rol" class="select-elite">
                        ${opcionesRoles}
                    </select>
                </div>
                <div id="wrapper-pers-esp" class="col-6 d-flex align-items-center ${d.rol_id == 11 ? '' : 'u-hidden'}">
                    <label class="label-horiz-elite">Cátedra / Área</label>
                    <select id="swal-pers-esp" class="select-elite">
                        <option value="">Seleccione materia...</option>
                        ${opcionesEspecialidades}
                    </select>
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Usuario</label>
                    <input id="swal-pers-user" class="input-elite font-monospace fw-bold text-primary" value="${d.usuario || ''}" autocomplete="username">
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Nueva Clave</label>
                    <input id="swal-pers-pass" type="password" class="input-elite" placeholder="En blanco para mantener" autocomplete="new-password">
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Email Inst.</label>
                    <input id="swal-pers-email-inst" type="email" class="input-elite" value="${d.email_usuario || ''}">
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Estado</label>
                    <select id="swal-pers-estado" class="select-elite">
                        <option value="ACTIVO" ${d.estado_laboral === 'ACTIVO' ? 'selected' : ''}>ACTIVO</option>
                        <option value="LICENCIA" ${d.estado_laboral === 'LICENCIA' ? 'selected' : ''}>LICENCIA</option>
                        <option value="RETIRADO" ${d.estado_laboral === 'RETIRADO' ? 'selected' : ''}>RETIRADO</option>
                    </select>
                </div>
            </div>

            <!-- PESTAÑA 2: IDENTIFICACIÓN & CONTACTO -->
            <div id="tab-pers-cont" class="tab-edit-pane tab-pers-pane d-none row g-2 text-start mt-2">
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Documento</label>
                    <select id="swal-pers-tdoc" class="select-elite select-tdoc-elite">
                        <option value="CC" ${d.tipo_documento === 'CC' ? 'selected' : ''}>CC</option>
                        <option value="CE" ${d.tipo_documento === 'CE' ? 'selected' : ''}>CE</option>
                        <option value="PAS" ${d.tipo_documento === 'PAS' ? 'selected' : ''}>PAS</option>
                    </select>
                    <input id="swal-pers-doc" class="input-elite" placeholder="Número Cédula" value="${d.documento || ''}">
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Expedición</label>
                    <input id="swal-pers-doc-exp" class="input-elite" placeholder="Expedida en" value="${d.documento_expedicion || ''}">
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">F. Nacimiento</label>
                    <input id="swal-pers-fnac" type="date" class="input-elite" value="${d.fecha_nacimiento || ''}" oninput="window.calcularEdadPersonal()" onchange="window.calcularEdadPersonal()">
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Edad / RH</label>
                    <input id="swal-pers-edad" type="text" class="input-elite input-edad-elite" placeholder="Edad" value="${edadCalculada !== undefined && edadCalculada !== null ? edadCalculada : ''}" readonly autocomplete="off">
                    <select id="swal-pers-gen" class="select-elite" style="max-width:55px;min-width:55px;margin-inline-end:4px;">
                        <option value="M" ${d.genero === 'M' ? 'selected' : ''}>M</option>
                        <option value="F" ${d.genero === 'F' ? 'selected' : ''}>F</option>
                        <option value="OTRO" ${d.genero === 'OTRO' ? 'selected' : ''}>Otro</option>
                    </select>
                    <select id="swal-pers-rh" class="select-elite" style="max-width:65px;min-width:65px;">
                        <option value="O+" ${d.rh === 'O+' ? 'selected' : ''}>O+</option>
                        <option value="O-" ${d.rh === 'O-' ? 'selected' : ''}>O-</option>
                        <option value="A+" ${d.rh === 'A+' ? 'selected' : ''}>A+</option>
                        <option value="A-" ${d.rh === 'A-' ? 'selected' : ''}>A-</option>
                        <option value="B+" ${d.rh === 'B+' ? 'selected' : ''}>B+</option>
                        <option value="B-" ${d.rh === 'B-' ? 'selected' : ''}>B-</option>
                        <option value="AB+" ${d.rh === 'AB+' ? 'selected' : ''}>AB+</option>
                        <option value="AB-" ${d.rh === 'AB-' ? 'selected' : ''}>AB-</option>
                    </select>
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Celular</label>
                    <input id="swal-pers-cel" class="input-elite" placeholder="Celular" value="${d.celular || ''}">
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Tel. Fijo</label>
                    <input id="swal-pers-tel" class="input-elite" placeholder="Teléfono Fijo" value="${d.telefono_fijo || ''}">
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Email Pers.</label>
                    <input id="swal-pers-email" type="email" class="input-elite" placeholder="correo@gmail.com" value="${d.email_personal || ''}">
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Dirección</label>
                    <input id="swal-pers-dir" class="input-elite" placeholder="Dirección" value="${d.direccion || ''}">
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Ciudad</label>
                    <input id="swal-pers-ciudad" class="input-elite" placeholder="Ciudad" value="${d.ciudad_residencia || ''}">
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Barrio</label>
                    <input id="swal-pers-barrio" class="input-elite" placeholder="Barrio" value="${d.barrio || ''}">
                </div>
            </div>

            <!-- PESTAÑA 3: PERFIL LABORAL & SOS -->
            <div id="tab-pers-prof" class="tab-edit-pane tab-pers-pane d-none row g-2 text-start mt-2">
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Título Univ.</label>
                    <input id="swal-pers-titulo" class="input-elite" placeholder="Título Profesional" value="${d.titulo_profesional || ''}">
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Formación</label>
                    <select id="swal-pers-formacion" class="select-elite">
                        <option value="Pregrado" ${d.nivel_formacion === 'Pregrado' ? 'selected' : ''}>Pregrado / Licenciatura</option>
                        <option value="Especialización" ${d.nivel_formacion === 'Especialización' ? 'selected' : ''}>Especialización</option>
                        <option value="Maestría" ${d.nivel_formacion === 'Maestría' ? 'selected' : ''}>Maestría</option>
                        <option value="Doctorado" ${d.nivel_formacion === 'Doctorado' ? 'selected' : ''}>Doctorado</option>
                    </select>
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Escalafón</label>
                    <input id="swal-pers-escalafon" class="input-elite" placeholder="Escalafón MEN" value="${d.escalafon_docente || ''}">
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">F. Ingreso</label>
                    <input id="swal-pers-fingreso" type="date" class="input-elite" value="${d.fecha_ingreso || ''}">
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Contrato</label>
                    <select id="swal-pers-contrato" class="select-elite">
                        <option value="PLANTA" ${d.tipo_contrato === 'PLANTA' ? 'selected' : ''}>Planta / Indefinido</option>
                        <option value="TÉRMINO FIJO" ${d.tipo_contrato === 'TÉRMINO FIJO' ? 'selected' : ''}>Término Fijo</option>
                        <option value="SERVICIOS" ${d.tipo_contrato === 'SERVICIOS' ? 'selected' : ''}>Prestación de Servicios</option>
                    </select>
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite" for="swal-pers-estado-lab">Estado</label>
                    <select id="swal-pers-estado-lab" name="estado_laboral" class="select-elite">
                        <option value="ACTIVO" ${d.estado_laboral === 'ACTIVO' ? 'selected' : ''}>ACTIVO</option>
                        <option value="LICENCIA" ${d.estado_laboral === 'LICENCIA' ? 'selected' : ''}>LICENCIA</option>
                        <option value="RETIRADO" ${d.estado_laboral === 'RETIRADO' ? 'selected' : ''}>RETIRADO</option>
                    </select>
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">EPS</label>
                    <input id="swal-pers-eps" class="input-elite" placeholder="Entidad de Salud" value="${d.eps || ''}">
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Pensión</label>
                    <input id="swal-pers-pension" class="input-elite" placeholder="Fondo Pensiones" value="${d.fondo_pensiones || ''}">
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">ARL</label>
                    <input id="swal-pers-arl" class="input-elite" placeholder="Administradora Riesgos" value="${d.arl || ''}">
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Contacto SOS</label>
                    <input id="swal-pers-sos-nom" class="input-elite" placeholder="Nombre completo" value="${d.contacto_emergencia_nombre || ''}">
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Teléfono SOS</label>
                    <input id="swal-pers-sos-tel" class="input-elite" placeholder="Celular de emergencia" value="${d.contacto_emergencia_telefono || ''}">
                </div>
                <div class="col-6 d-flex align-items-center">
                    <label class="label-horiz-elite">Parentesco</label>
                    <input id="swal-pers-sos-par" class="input-elite" placeholder="Vínculo o Parentesco" value="${d.contacto_emergencia_parentesco || ''}">
                </div>
            </div>
            </form>
        </div>`,
        showCancelButton: true,
        confirmButtonText: 'Guardar',
        cancelButtonText: 'Cancelar',
        customClass: { confirmButton: 'btn-elite px-4 shadow-sm', cancelButton: 'btn-elite btn-elite--outline px-4 ms-2' },
        buttonsStyling: false,
        didOpen: () => {
            const selectRol = document.getElementById('swal-pers-rol');
            const wrapperEsp = document.getElementById('wrapper-pers-esp');
            const selectEsp = document.getElementById('swal-pers-esp');
            const inputNom = document.getElementById('swal-pers-nom');
            const inputApe = document.getElementById('swal-pers-ape');
            const inputUser = document.getElementById('swal-pers-user');
            const inputFnac = document.getElementById('swal-pers-fnac');
            const inputEdad = document.getElementById('swal-pers-edad');

            const generarUsuario = () => {
                let nom = inputNom.value.trim();
                let ape = inputApe ? inputApe.value.trim() : '';
                let textoCompleto = (nom + ' ' + ape).trim();
                if (!textoCompleto) {
                    inputUser.value = '';
                    return;
                }
                let partes = textoCompleto.split(/\s+/);
                let iniciales = '';
                partes.forEach(p => { if (p.length > 0) iniciales += p[0].toUpperCase(); });

                let prefijo = 'U';
                if (selectRol && selectRol.selectedIndex !== -1) {
                    let optionText = selectRol.options[selectRol.selectedIndex].text;
                    let val = selectRol.value;
                    if (val !== "") {
                        let textoLimpio = optionText.replace(/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]/g, '').trim().toLowerCase();
                        if (textoLimpio.includes('docente') || textoLimpio.includes('profesor')) {
                            prefijo = 'L';
                        } else if (textoLimpio.length > 0) {
                            prefijo = textoLimpio.charAt(0).toUpperCase();
                        }
                    }
                }
                inputUser.value = prefijo + iniciales;
            };

            if (selectRol) {
                selectRol.addEventListener('change', () => {
                    if (selectRol.value === "11") {
                        if (wrapperEsp) wrapperEsp.classList.remove('u-hidden');
                    } else {
                        if (wrapperEsp) wrapperEsp.classList.add('u-hidden');
                        if (selectEsp) selectEsp.value = "";
                    }
                });
            }

            if (inputNom) inputNom.addEventListener('input', generarUsuario);
            if (inputApe) inputApe.addEventListener('input', generarUsuario);
            if (inputFnac) {
                ['input', 'change', 'blur', 'keyup'].forEach(evento => {
                    inputFnac.addEventListener(evento, window.calcularEdadPersonal);
                });
                window.calcularEdadPersonal();
            }
        },
        preConfirm: () => {
            const nom = document.getElementById('swal-pers-nom').value.trim();
            const ape = document.getElementById('swal-pers-ape') ? document.getElementById('swal-pers-ape').value.trim() : '';
            const nombreCompleto = ape ? (nom + ' ' + ape) : nom;
            const rol = document.getElementById('swal-pers-rol').value;
            const user = document.getElementById('swal-pers-user').value.trim();

            if (!nombreCompleto || !rol || !user) {
                Swal.showValidationMessage('El Nombre, Apellidos, Rol y Usuario son obligatorios.');
                return false;
            }

            return {
                id: id,
                nombre: nombreCompleto,
                rol: rol,
                esp: document.getElementById('swal-pers-esp') ? document.getElementById('swal-pers-esp').value : '',
                user: user,
                pass: document.getElementById('swal-pers-pass').value,
                email: document.getElementById('swal-pers-email-inst') ? document.getElementById('swal-pers-email-inst').value.trim() : '',
                tipo_documento: document.getElementById('swal-pers-tdoc').value,
                documento: document.getElementById('swal-pers-doc').value.trim(),
                documento_expedicion: document.getElementById('swal-pers-doc-exp').value.trim(),
                fecha_nacimiento: document.getElementById('swal-pers-fnac').value,
                edad: document.getElementById('swal-pers-edad').value,
                genero: document.getElementById('swal-pers-gen').value,
                rh: document.getElementById('swal-pers-rh').value,
                celular: document.getElementById('swal-pers-cel').value.trim(),
                telefono_fijo: document.getElementById('swal-pers-tel').value.trim(),
                email_personal: document.getElementById('swal-pers-email').value.trim(),
                direccion: document.getElementById('swal-pers-dir').value.trim(),
                ciudad_residencia: document.getElementById('swal-pers-ciudad').value.trim(),
                barrio: document.getElementById('swal-pers-barrio').value.trim(),
                titulo_profesional: document.getElementById('swal-pers-titulo').value.trim(),
                nivel_formacion: document.getElementById('swal-pers-formacion').value,
                escalafon_docente: document.getElementById('swal-pers-escalafon').value.trim(),
                fecha_ingreso: document.getElementById('swal-pers-fingreso').value,
                tipo_contrato: document.getElementById('swal-pers-contrato').value,
                estado_laboral: document.getElementById('swal-pers-estado').value,
                eps: document.getElementById('swal-pers-eps').value.trim(),
                fondo_pensiones: document.getElementById('swal-pers-pension').value.trim(),
                arl: document.getElementById('swal-pers-arl').value.trim(),
                contacto_emergencia_nombre: document.getElementById('swal-pers-sos-nom').value.trim(),
                contacto_emergencia_telefono: document.getElementById('swal-pers-sos-tel').value.trim(),
                contacto_emergencia_parentesco: document.getElementById('swal-pers-sos-par').value.trim()
            };
        }
    });

    if (r.isConfirmed && r.value) {
        const data = r.value;
        const formData = new FormData();
        Object.keys(data).forEach(k => {
            formData.append(k, data[k] || '');
        });
        if (window.CSRF_TOKEN) formData.append('csrf_token', window.CSRF_TOKEN);

        const resultado = await enviarPostElite('logica/editar_personal.php', formData, true);
        if (resultado && resultado.status === 'success') {
            window.lanzarToastElite('success', resultado.message || 'Expediente actualizado con éxito.');
            window.forceRefreshElite = true;
            if (typeof navegarModulo === 'function') navegarModulo(window.MODULO_ACTUAL || 'personal');
        } else if (resultado && resultado.status !== 'success') {
            window.lanzarToastElite('danger', resultado.message || 'No se pudo actualizar el expediente.', 'Error de Bóveda');
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
    let opciones = '<option value="" selected>Sin Área (Opcional)</option>' + areasJSON.map(a => `<option value="${a.id}">${a.nombre_area}</option>`).join('');
    let opcionesNiv = '';
    for (let i = 1; i <= 11; i++) {
        opcionesNiv += `<option value="${i}">${i}</option>`;
    }
    const r = await Swal.fire({
        title: 'Nueva Asignatura / Materia',
        html: `<div class="text-start">
                <label class="small fw-bold text-secondary mb-1">Nombre de la Especialidad / Materia</label>
                <input id="esp-nom" class="input-elite mb-3" placeholder="Ej: Matemáticas">
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
        showCancelButton: true, confirmButtonText: 'Registrar', cancelButtonText: 'Cancelar',
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
    let opciones = '<option value="">Sin Área</option>' + areasJSON.map(a => `<option value="${a.id}" ${a.id == areaActual ? 'selected' : ''}>${a.nombre_area}</option>`).join('');
    let opcionesDesde = '';
    let opcionesHasta = '';
    for (let i = 1; i <= 11; i++) {
        opcionesDesde += `<option value="${i}" ${i == nivelDesdeActual ? 'selected' : ''}>${i}</option>`;
        opcionesHasta += `<option value="${i}" ${i == nivelHastaActual ? 'selected' : ''}>${i}</option>`;
    }
    const r = await Swal.fire({
        title: 'Editar Asignatura / Materia',
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
        showCancelButton: true, confirmButtonText: 'Guardar Cambios', cancelButtonText: 'Cancelar',
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
        title: 'Nuevo Curso / Grado',
        html: `<div class="text-start">
                <label class="small fw-bold text-secondary mb-1">Nombre del Curso</label>
                <input id="c-nom" class="input-elite mb-3">
                <label class="small fw-bold text-secondary mb-1">Jornada</label>
                <select id="c-jor" class="select-elite mb-3">${opcionesJornada}</select>
                <label class="small fw-bold text-secondary mb-1">Director / Tutor</label>
                <select id="c-tut" class="select-elite">${opciones}</select>
               </div>`,
        showCancelButton: true, confirmButtonText: 'Registrar', cancelButtonText: 'Cancelar',
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
        title: 'Editar Curso / Tutor',
        html: `<div class="text-start">
                <label class="small fw-bold text-secondary mb-1">Nombre del Curso</label>
                <input id="c-nom" class="input-elite mb-3" value="${nombre}">
                <label class="small fw-bold text-secondary mb-1">Jornada</label>
                <select id="c-jor" class="select-elite mb-3">${opcionesJornada}</select>
                <label class="small fw-bold text-secondary mb-1">Director / Tutor</label>
                <select id="c-tut" class="select-elite">${opciones}</select>
               </div>`,
        showCancelButton: true, confirmButtonText: 'Guardar Cambios', cancelButtonText: 'Cancelar',
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
        title: '¿Eliminar Curso?', text: `Se borrará el curso "${nombre}".`, icon: 'warning', showCancelButton: true, confirmButtonText: 'Sí, Eliminar', cancelButtonText: 'Cancelar',
        customClass: { confirmButton: 'btn-elite btn-elite--danger px-4', cancelButton: 'btn-elite btn-elite--outline px-4 ms-2' }, buttonsStyling: false
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
        const resultado = await enviarPostElite('logica/editar_estudiante.php', formData, true);
        if (resultado && resultado.status === 'success') {
            window.lanzarToastElite('success', resultado.message || 'Expediente actualizado con éxito.');
            window.forceRefreshElite = true;
            if (typeof navegarModulo === 'function') navegarModulo(window.MODULO_ACTUAL || 'matriculados');
        } else if (resultado && resultado.status !== 'success') {
            window.lanzarToastElite('danger', resultado.message || 'No se pudo actualizar el expediente.', 'Error de Bóveda');
        }
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
