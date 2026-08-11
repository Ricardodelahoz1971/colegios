/**
 * 🛡️ PERSEUS ENGINE v1.0 - PROYECTO PERSEUS
 * Motor de Calificación, Auditoría y Escaneo QR de Ares.
 */

window.Perseus = (function() {
    let asigSeleccionadaId = null;
    let asigTitulo = '';
    let asigPruebaId = null;
    let asigModalidad = null;
    let asigCursoNombre = '';
    let entregaActualId = null;
    let pruebaEstructura = [];
    let scanner = null;

    // --- ESTADO ADICIONAL DE ACTIVIDADES (FASE 1) ---
    let activeTab = 'examenes'; // 'examenes' o 'actividades'
    let actividadSeleccionadaId = null;
    let actividadSeleccionadaTitulo = '';
    let focusMode = {
        actividad: null,
        estudiantes: [],
        currentIndex: 0,
        modal: null
    };

    // --- INICIALIZACIÓN ---
    function init() {
        cargarAsignaciones();
        
        const inputNota = document.getElementById('nota-manual');
        if (inputNota) {
            inputNota.addEventListener('input', actualizarNotaFinal);
        }

        const inputRecup = document.getElementById('nota-recuperacion-prueba');
        if (inputRecup) {
            inputRecup.addEventListener('input', actualizarNotaFinal);
        }

        // Inicialización de Focus Mode modal
        const modalEl = document.getElementById('modalFocusMode');
        if (modalEl) {
            focusMode.modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            // 🛡️ BLINDAJE DE PORTAL: Mover al final del body para evitar problemas de apilamiento z-index
            if (modalEl.parentNode !== document.body) {
                document.body.appendChild(modalEl);
            }
        }

        const selectDigital = document.getElementById('focus-soporte-digital');
        if (selectDigital) {
            selectDigital.addEventListener('change', function() {
                const opt = selectDigital.options[selectDigital.selectedIndex];
                const inputJustRecup = document.getElementById('focus-justificacion-recuperacion');
                if (opt && opt.value !== '' && inputJustRecup) {
                    const titulo = opt.dataset.titulo;
                    const nota = opt.dataset.nota;
                    inputJustRecup.value = `Evidencia digital: ${titulo} (Nota: ${nota})`;
                } else if (inputJustRecup) {
                    inputJustRecup.value = '';
                }
            });
        }
    }

    // --- GESTIÓN DE NOTAS ---
    function actualizarNotaFinal() {
        const autoEl = document.getElementById('nota-automatica');
        const manualEl = document.getElementById('nota-manual');
        const finalEl = document.getElementById('nota-final-calc');
        const recupEl = document.getElementById('nota-recuperacion-prueba');
        
        if (!autoEl || !manualEl || !finalEl) return;

        let auto = parseFloat(autoEl.innerText) || 0;
        let man = parseFloat(manualEl.value) || 0;
        let original = auto + man;
        let finalNota = original;

        if (recupEl && recupEl.value !== '') {
            let recup = parseFloat(recupEl.value) || 0;
            
            // Validar límites según la escala cargada
            const escala = window.AresEscala || { nota_minima: 1.0, nota_maxima: 5.0, nota_aprobacion: 3.0 };
            if (recup > escala.nota_maxima) {
                recup = escala.nota_maxima;
                recupEl.value = recup.toFixed(1);
            }
            if (recup < escala.nota_minima) {
                recup = escala.nota_minima;
                recupEl.value = recup.toFixed(1);
            }

            if (recup > original) {
                const politica = window.AresPoliticaRecuperacion || 'reemplazo';
                const aprobacion = escala.nota_aprobacion;

                if (politica === 'promedio') {
                    finalNota = (original + recup) / 2.0;
                } else if (politica === 'tope_aprobacion') {
                    finalNota = Math.max(original, Math.min(recup, aprobacion));
                } else {
                    finalNota = recup;
                }
            }
        }
        finalEl.innerText = finalNota.toFixed(1);
    }

    function recalcularTotalManual() {
        let total = 0;
        document.querySelectorAll('.input-manual-pregunta').forEach(inp => {
            let val = parseFloat(inp.value) || 0;
            let max = parseFloat(inp.dataset.peso) || 0;
            if (val > max) { inp.value = max; val = max; }
            if (val < 0) { inp.value = 0; val = 0; }
            total += val;
        });
        const manualEl = document.getElementById('nota-manual');
        if (manualEl) {
            manualEl.value = total.toFixed(1);
            actualizarNotaFinal();
        }
    }

    // --- COMUNICACIÓN CON BÓVEDA ---
    async function cargarAsignaciones() {
        const cont = document.getElementById('lista-asignaciones-calificar');
        if (!cont) return;

        cont.innerHTML = '<div class="text-center p-5"><div class="loader-ball-elite"><div class="balls-1"></div><div class="balls-2"></div><div class="balls-3"></div><div class="balls-4"></div><div class="balls-5"></div><div class="balls-6"></div><div class="balls-7"></div><div class="balls-8"></div><div class="balls-9"></div></div></div>';
        
        try {
            if (activeTab === 'examenes') {
                const response = await fetch('logica/api_pruebas.php?accion=listar_asignaciones_profesor');
                const d = await response.json();
                if (d.status === 'success') {
                    if (d.data.length === 0) {
                        cont.innerHTML = '<div class="p-4 text-center text-muted fs-nano">No hay evaluaciones programadas.</div>';
                        return;
                    }
                    
                    cont.innerHTML = '<div class="list-group list-group-flush rounded-bottom">' + d.data.map(a => {
                        const isFisico = (parseInt(a.modalidad) === 2);
                        const total = parseInt(a.total_estudiantes) || 0;
                        const entregas = parseInt(a.total_entregas) || 0;
                        const pct = total > 0 ? (entregas / total) * 100 : 0;
                        
                        let statusBadge = '';
                        if (entregas === 0) {
                            statusBadge = '<span class="badge bg-light text-muted border fs-nano text-uppercase">Pendiente</span>';
                        } else if (entregas < total) {
                            statusBadge = '<span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 fs-nano text-uppercase">En Proceso</span>';
                        } else {
                            statusBadge = '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 fs-nano text-uppercase">Completado</span>';
                        }

                        const typeBadge = isFisico 
                            ? '<span class="text-warning fw-bold fs-micro ms-2"><i class="bi bi-qr-code"></i> FÍSICO</span>' 
                            : '<span class="text-primary fw-bold fs-micro ms-2"><i class="bi bi-display"></i> DIGITAL</span>';
                        
                        return `
                        <button id="btn-asig-${a.id}" class="list-group-item list-group-item-action border-0 py-3 px-4 border-bottom ${asigSeleccionadaId === a.id ? 'active bg-primary text-white shadow' : ''}">
                            <div class="d-flex w-100 justify-content-between align-items-start mb-2">
                                <h6 class="mb-0 fw-bold ${asigSeleccionadaId === a.id ? 'text-white' : 'text-dark'}">${a.titulo}</h6>
                                ${statusBadge}
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="fs-nano fw-bold text-uppercase opacity-75">${a.curso_nombre} ${typeBadge}</span>
                                <span class="fs-nano fw-bold">${entregas} / ${total}</span>
                            </div>
                            <div class="progress-ares">
                                <div id="bar-asig-${a.id}" class="progress-bar ${entregas === total ? 'bg-success' : 'bg-primary'}" style="width: ${pct}%"></div>
                            </div>
                        </button>`;
                    }).join('') + '</div>';

                    d.data.forEach(a => {
                        const btn = document.getElementById(`btn-asig-${a.id}`);
                        if (btn) btn.onclick = () => seleccionarAsignacion(a.id, a.titulo, a.prueba_id, a.modalidad, a.curso_nombre);
                    });
                }
            } else {
                // Actividades
                const response = await fetch('logica/api_actividades.php?accion=listar_actividades_profesor');
                const d = await response.json();
                if (d.status === 'success') {
                    if (d.data.length === 0) {
                        cont.innerHTML = '<div class="p-4 text-center text-muted fs-nano">No hay actividades creadas.</div>';
                        return;
                    }
                    
                    cont.innerHTML = '<div class="list-group list-group-flush rounded-bottom">' + d.data.map(a => {
                        const hasGrades = parseInt(a.tiene_notas) > 0;
                        let statusBadge = hasGrades
                            ? '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 fs-nano text-uppercase">Evaluada</span>'
                            : '<span class="badge bg-light text-muted border fs-nano text-uppercase">Pendiente</span>';

                        const modeBadge = a.tipo_evaluacion === 'rubrica' 
                            ? '<span class="text-primary fw-bold fs-micro ms-2"><i class="bi bi-layers"></i> RÚBRICA</span>' 
                            : '<span class="text-secondary fw-bold fs-micro ms-2"><i class="bi bi-123"></i> DIRECTO</span>';
                        
                        return `
                        <button id="btn-act-${a.id}" class="list-group-item list-group-item-action border-0 py-3 px-4 border-bottom ${actividadSeleccionadaId === a.id ? 'active bg-primary text-white shadow' : ''}">
                            <div class="d-flex w-100 justify-content-between align-items-start mb-2">
                                <h6 class="mb-0 fw-bold ${actividadSeleccionadaId === a.id ? 'text-white' : 'text-dark'}">${a.titulo}</h6>
                                ${statusBadge}
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-0">
                                <span class="fs-nano fw-bold text-uppercase opacity-75">${a.nombre_curso} ${modeBadge}</span>
                                <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 fs-nano text-uppercase">${a.clase_nota}</span>
                            </div>
                        </button>`;
                    }).join('') + '</div>';

                    d.data.forEach(a => {
                        const btn = document.getElementById(`btn-act-${a.id}`);
                        if (btn) btn.onclick = () => seleccionarActividad(a.id, a.titulo);
                    });
                }
            }
        } catch(e) {}
    }

    async function seleccionarAsignacion(id, titulo, prueba_id, modalidad, curso_nombre) {
        // Usar variables cacheadas si los parámetros no vienen definidos (ej. llamadas de refresco)
        id = id || asigSeleccionadaId;
        titulo = titulo || asigTitulo;
        prueba_id = prueba_id || asigPruebaId;
        modalidad = modalidad || asigModalidad;
        curso_nombre = curso_nombre || asigCursoNombre;

        asigSeleccionadaId = id;
        asigTitulo = titulo;
        asigPruebaId = prueba_id;
        asigModalidad = modalidad;
        asigCursoNombre = curso_nombre;

        const isFisico = (parseInt(modalidad) === 2);
        const titleEl = document.getElementById('titulo-entregas');
        if (titleEl) {
            titleEl.innerHTML = `<i class="bi bi-people me-2 text-primary"></i> ${titulo}`;
        }
        
        cargarAsignaciones();
        
        const contEnt = document.getElementById('contenedor-entregas');
        if (contEnt) contEnt.innerHTML = '<div class="text-center py-5"><div class="loader-ball-elite"><div class="balls-1"></div><div class="balls-2"></div><div class="balls-3"></div><div class="balls-4"></div><div class="balls-5"></div><div class="balls-6"></div><div class="balls-7"></div><div class="balls-8"></div><div class="balls-9"></div></div></div>';
        
        if (scanner) {
            try {
                await scanner.stop();
                scanner = null;
            } catch(e) {
                // Silenciar fallo residual de cámara asíncrona
            }
        }

        try {
            const d = await enviarPostElite('logica/api_pruebas.php', { accion: 'listar_entregas_asignacion', asignacion_id: id, prueba_id: prueba_id }, true);
            if (d.status === 'success') {
                const badgeTotal = document.getElementById('badge-total-entregas');
                if (badgeTotal) badgeTotal.innerText = `${d.data.length} Entregas`;
                pruebaEstructura = d.estructura_prueba;
                
                if (isFisico) {
                    renderizarWorkstationQROptimizada(id, titulo, curso_nombre, d.data);
                } else {
                    renderizarTablaEntregas(d.data);
                }
            } else {
                throw new Error(d.message || "Fallo en la comunicación con la API.");
            }
        } catch (e) {
            if (contEnt) {
                contEnt.innerHTML = `
                    <div class="text-center py-5 text-danger opacity-75">
                        <i class="bi bi-exclamation-triangle-fill fs-2 mb-3"></i>
                        <h6 class="fw-bold">Error de Carga Académica</h6>
                        <p class="small mb-0">No se pudieron recuperar las entregas de esta asignación.</p>
                    </div>`;
            }
            Swal.fire({
                icon: 'error',
                title: 'FALLO DE CONEXIÓN',
                text: 'Fallo crítico de red o bloqueo de bóveda. Reintente de nuevo en unos momentos.',
                customClass: { popup: 'rounded-4' }
            });
        }
    }

    // --- RENDERIZADO DE INTERFAZ ---
    function renderizarTablaEntregas(entregas) {
        const contEnt = document.getElementById('contenedor-entregas');
        if (entregas.length === 0) {
            contEnt.innerHTML = '<div class="text-center py-5 text-muted fs-nano">Aún no hay entregas registradas.</div>';
            return;
        }

        let html = `
            <div class="table-responsive">
                <table class="table table-hover align-middle border-light">
                    <thead class="table-light text-muted small text-uppercase fs-nano">
                        <tr>
                            <th>Estudiante</th>
                            <th>Estado</th>
                            <th>Máquina</th>
                            <th>Docente</th>
                            <th>Final</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>`;
        
        entregas.forEach(e => {
            let notaFinal = e.calificacion_final_calculada !== null && e.calificacion_final_calculada !== undefined
                ? parseFloat(e.calificacion_final_calculada).toFixed(1)
                : (parseFloat(e.calificacion_automatica) + parseFloat(e.calificacion_manual)).toFixed(1);
            
            let estadoVal = parseInt(e.estado);
            let calificado = (estadoVal === 2 || estadoVal === 3);
            let badgeHtml = "";
            
            if (!e.id || estadoVal === 0) {
                badgeHtml = `<span id="badge-estado-${e.estudiante_id}" class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary fs-nano text-uppercase">Sin Entregar</span>`;
            } else if (calificado) {
                if (estadoVal === 3) {
                    badgeHtml = `<span id="badge-estado-${e.estudiante_id}" class="badge bg-warning bg-opacity-10 text-warning border border-warning fs-nano text-uppercase"><i class="bi bi-arrow-repeat me-1"></i> Re-calificado</span>`;
                } else {
                    badgeHtml = `<span id="badge-estado-${e.estudiante_id}" class="badge bg-success bg-opacity-10 text-success border border-success fs-nano text-uppercase"><i class="bi bi-check-circle-fill me-1"></i> Calificado</span>`;
                }
            } else {
                badgeHtml = `<span id="badge-estado-${e.estudiante_id}" class="badge bg-info bg-opacity-10 text-info border border-info fs-nano text-uppercase">Revisión</span>`;
            }

            html += `
                <tr id="row-estudiante-${e.estudiante_id}">
                    <td class="fw-bold text-dark fs-nano">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-2 avatar-student"><i class="bi bi-person"></i></div>
                            ${e.estudiante_nombre}
                        </div>
                    </td>
                    <td>${badgeHtml}</td>
                    <td id="nota-auto-${e.estudiante_id}" class="text-muted fs-nano">${e.id ? parseFloat(e.calificacion_automatica).toFixed(1) : '-'}</td>
                    <td id="nota-manual-${e.estudiante_id}" class="text-muted fs-nano">${e.id ? parseFloat(e.calificacion_manual).toFixed(1) : '-'}</td>
                    <td id="nota-final-${e.estudiante_id}" class="fw-bold ${e.id ? (calificado ? (parseFloat(notaFinal) >= (window.AresEscala?.nota_aprobacion || 3.0) ? 'text-success' : 'text-danger') : 'text-primary') : 'text-primary'} fs-sub">${e.id ? notaFinal : '-'}</td>
                    <td>
                        <div class="btn-group">
                            <button id="btn-rev-${e.estudiante_id}" onclick="Perseus.abrirCalificador(${e.id}, '${e.estudiante_nombre}', ${e.estudiante_id})" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold fs-nano me-1" ${e.id ? '' : 'disabled'}>REVISAR</button>
                            <button id="btn-del-${e.estudiante_id}" onclick="Perseus.resetearIntento(${e.id}, '${e.estudiante_nombre}')" class="btn btn-sm btn-outline-danger rounded-circle p-1" ${e.id ? '' : 'disabled'}><i class="bi bi-trash3"></i></button>
                        </div>
                    </td>
                </tr>`;
        });
        html += '</tbody></table></div>';
        contEnt.innerHTML = html;
    }

    function renderizarWorkstationQROptimizada(id, titulo, curso_nombre, entregas) {
        const contEnt = document.getElementById('contenedor-entregas');
        
        let html = `
            <div class="ares-workstation animate__animated animate__fadeIn mb-4">
                <div class="ares-scanner-sidebar text-center">
                    <div class="scanner-station shadow-lg rounded-4 overflow-hidden mb-3">
                        <div id="reader"></div>
                    </div>
                    <div id="scanner-status" class="badge bg-primary px-3 py-2 fs-micro w-100 mb-2">ESPERANDO QR...</div>
                </div>
                <div id="data-canvas" class="ares-data-canvas text-center">
                    <div class="opacity-25 mb-4"><i class="bi bi-qr-code-scan ares-icon-hero"></i></div>
                    <h4 class="fw-bold text-dark text-uppercase letter-spacing-1">Listo para Calificar</h4>
                    <p class="text-muted small">Acerque el examen del estudiante al escáner para iniciar identificación.</p>
                </div>
            </div>
            
            <!-- PANEL GENERAL DE ESTUDIANTES (Reactivo e Integrado) -->
            <div class="card border-0 shadow-sm rounded-4 bg-white mt-4">
                <div class="card-header bg-transparent border-0 pt-4 pb-0 px-4">
                    <h5 class="fw-bold text-dark mb-1 text-uppercase letter-spacing-1">
                        <i class="bi bi-list-check me-2 text-primary"></i> Control de Entregas y Calificaciones
                    </h5>
                    <p class="text-muted small mb-0">Listado reactivo en tiempo real de los estudiantes asignados a esta prueba física.</p>
                </div>
                <div class="card-body px-4 pb-4 pt-3">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle border-light">
                            <thead class="table-light text-muted small text-uppercase fs-nano">
                                <tr>
                                    <th>Estudiante</th>
                                    <th>Estado</th>
                                    <th>Máquina</th>
                                    <th>Docente</th>
                                    <th>Final</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>`;

        entregas.forEach(e => {
            let notaFinal = e.calificacion_final_calculada !== null && e.calificacion_final_calculada !== undefined
                ? parseFloat(e.calificacion_final_calculada).toFixed(1)
                : (parseFloat(e.calificacion_automatica) + parseFloat(e.calificacion_manual)).toFixed(1);
            let estadoVal = parseInt(e.estado);
            let calificado = (estadoVal === 2 || estadoVal === 3);
            let badgeHtml = "";
            if (calificado) {
                if (estadoVal === 3) {
                    badgeHtml = `<span id="badge-estado-${e.estudiante_id}" class="badge bg-warning bg-opacity-10 text-warning border border-warning fs-nano text-uppercase"><i class="bi bi-arrow-repeat me-1"></i> Re-calificado</span>`;
                } else {
                    badgeHtml = `<span id="badge-estado-${e.estudiante_id}" class="badge bg-success bg-opacity-10 text-success border border-success fs-nano text-uppercase"><i class="bi bi-check-circle-fill me-1"></i> Calificado</span>`;
                }
            } else {
                badgeHtml = `<span id="badge-estado-${e.estudiante_id}"></span>`;
            }
            
            html += `
                                <tr id="row-estudiante-${e.estudiante_id}">
                                    <td class="fw-bold text-dark fs-nano">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-2 avatar-student"><i class="bi bi-person"></i></div>
                                            ${e.estudiante_nombre}
                                        </div>
                                    </td>
                                    <td>${badgeHtml}</td>
                                    <td id="nota-auto-${e.estudiante_id}" class="text-muted fs-nano">${e.id ? parseFloat(e.calificacion_automatica).toFixed(1) : '-'}</td>
                                    <td id="nota-manual-${e.estudiante_id}" class="text-muted fs-nano">${e.id ? parseFloat(e.calificacion_manual).toFixed(1) : '-'}</td>
                                    <td id="nota-final-${e.estudiante_id}" class="fw-bold ${e.id ? (calificado ? (parseFloat(notaFinal) >= (window.AresEscala?.nota_aprobacion || 3.0) ? 'text-success' : 'text-danger') : 'text-primary') : 'text-primary'} fs-sub">${e.id ? notaFinal : '-'}</td>
                                    <td>
                                        <div class="btn-group">
                                            <button id="btn-rev-${e.estudiante_id}" onclick="Perseus.verificarEstadoYCalificar(${e.id}, '${e.estudiante_nombre}', ${e.estudiante_id}, ${e.id ? notaFinal : 0.0}, ${e.id ? e.estado : 0}, ${id})" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold fs-nano me-1" ${e.id ? '' : 'disabled'}>REVISAR</button>
                                            <button id="btn-del-${e.estudiante_id}" onclick="Perseus.resetearIntento(${e.id}, '${e.estudiante_nombre}')" class="btn btn-sm btn-outline-danger btn-circle-compact" ${e.id ? '' : 'disabled'}><i class="bi bi-trash3"></i></button>
                                        </div>
                                    </td>
                                </tr>`;
        });

        html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>`;
            
        contEnt.innerHTML = html;
        iniciarEscaneo(id, titulo, curso_nombre);
    }

    // --- ESCÁNER Y QR ---
    async function iniciarEscaneo(asig_id, titulo, curso) {
        if (typeof Html5Qrcode === 'undefined') return;
        scanner = new Html5Qrcode("reader");
        const statusEl = document.getElementById('scanner-status');

        try {
            await scanner.start({ facingMode: "environment" }, { fps: 25, aspectRatio: 1.0 }, async (decodedText) => {
                if (decodedText.startsWith("PRU_")) {
                    reproducirBeep();
                    statusEl.innerText = "IDENTIFICADO. PROCESANDO...";
                    statusEl.className = "badge bg-success px-3 py-2 fs-micro";
                    const partes = decodedText.split("_");
                    identificarEstudianteQR(partes[3], asig_id, titulo, curso);
                }
            });

            // 🛡️ Blindaje de Terceros: Intervenir el DOM inyectado por la librería para forzar adaptabilidad y purgar controles residuales
            const readerEl = document.getElementById('reader');
            if (readerEl) {
                readerEl.style.setProperty('width', '100%', 'important');
                readerEl.style.setProperty('height', '100%', 'important');
                readerEl.style.setProperty('border', 'none', 'important');
                
                // Ocultar botones y selectores inyectados (como el botón swap de cámara y controles residuales)
                const controls = readerEl.querySelectorAll('button, select, img, a, .html5-qrcode-element');
                controls.forEach(el => {
                    el.style.setProperty('display', 'none', 'important');
                    el.style.setProperty('opacity', '0', 'important');
                    el.style.setProperty('visibility', 'hidden', 'important');
                });

                const videoEl = readerEl.querySelector('video');
                if (videoEl) {
                    videoEl.style.setProperty('width', '100%', 'important');
                    videoEl.style.setProperty('height', '100%', 'important');
                    videoEl.style.setProperty('max-height', '100%', 'important');
                    videoEl.style.setProperty('object-fit', 'cover', 'important');
                }
            }
        } catch (err) {
            statusEl.innerText = "ERROR DE CÁMARA";
            statusEl.className = "badge bg-danger px-3 py-2 fs-micro";
        }
    }

    async function verificarEstadoYCalificar(id_entrega, nombre, est_id, nota_existente, estado, asig_id) {
        if (parseInt(estado) === 2 || parseInt(estado) === 3) {
            // Pausar el escáner si está activo
            if (scanner) { try { await scanner.pause(false); } catch(e){} }

            // Alerta informativa premium indicando que ya está calificado
            const r = await Swal.fire({
                title: 'EXAMEN YA CALIFICADO',
                html: `
                    <div class="text-center p-2">
                        <div class="avatar-estudiante bg-success bg-opacity-10 text-success d-inline-flex align-items-center justify-content-center mb-3 shadow-sm" style="width: 60px; height: 60px; font-size: 1.8rem; border-radius: var(--el-radius-sub);"><i class="bi bi-check-circle-fill"></i></div>
                        <h5 class="fw-bold text-dark">${nombre}</h5>
                        <p class="text-muted small">Este estudiante ya tiene una calificación publicada para esta evaluación física.</p>
                        <div class="bg-light p-3 rounded-4 mt-3 mb-2 d-inline-block" style="min-width: 150px;">
                            <span class="text-muted fs-nano text-uppercase d-block fw-bold text-secondary">Calificación Actual</span>
                            <span class="fs-2 fw-bold text-success">${parseFloat(nota_existente).toFixed(1)}</span>
                        </div>
                    </div>`,
                showCancelButton: true,
                confirmButtonText: 'CORREGIR / RE-EVALUAR',
                cancelButtonText: 'MANTENER NOTA',
                customClass: { 
                    confirmButton: 'btn-elite px-4', 
                    cancelButton: 'btn-elite btn-elite--outline px-4 ms-2' 
                },
                buttonsStyling: false
            });

            if (r.isConfirmed) {
                abrirCalificadorQR(id_entrega, nombre, est_id, asig_id);
            } else {
                resetearEstacionAres();
            }
        } else {
            abrirCalificadorQR(id_entrega, nombre, est_id, asig_id);
        }
    }

    async function identificarEstudianteQR(est_id, asig_id, titulo, curso) {
        try {
            const d = await enviarPostElite('logica/api_pruebas.php', { accion: 'identificar_estudiante_qr', estudiante_id: est_id, asignacion_id: asig_id }, true);
            if (d.status === 'success') {
                const est = d.data;
                
                // 1. Pausar escáner para evitar lecturas múltiples
                if (scanner) { try { await scanner.pause(false); } catch(e){} }
                
                // 2. Visualización reactiva e inmediata en el Canvas superior
                const canvas = document.getElementById('data-canvas');
                canvas.innerHTML = `
                    <div class="animate__animated animate__fadeInRight w-100 text-start">
                        <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                            <div class="avatar-estudiante bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center me-3 shadow-sm"><i class="bi bi-person-fill"></i></div>
                            <div>
                                <h4 class="fw-bold mb-0 text-dark">${est.nombre}</h4>
                                <span class="badge bg-success bg-opacity-10 text-success fs-nano border border-success">Ficha QR Identificada</span>
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6"><div class="p-2 bg-light rounded-3 border small">ID: <strong>${est.estudiante_id}</strong></div></div>
                            <div class="col-6"><div class="p-2 bg-light rounded-3 border small text-truncate">Grado: <strong>${curso}</strong></div></div>
                        </div>
                        <p class="text-muted small text-center mb-0 mt-3"><i class="bi bi-patch-check-fill text-success me-1"></i> Cargando calificador deductivo...</p>
                    </div>`;
                
                // 3. Lanzar intercepción de seguridad
                const notaFinal = (parseFloat(est.calificacion_automatica) + parseFloat(est.calificacion_manual)).toFixed(1);
                verificarEstadoYCalificar(est.id_entrega, est.nombre, est_id, notaFinal, est.estado, asig_id);
            } else {
                throw new Error(d ? d.message : "No se pudo identificar al estudiante.");
            }
        } catch(e) {
            resetearEstacionAres();
            Swal.fire({
                icon: 'error',
                title: 'Error de Identificación',
                text: e.message || 'Error al conectar con la bóveda de datos para identificar el código QR.',
                customClass: { popup: 'rounded-4' }
            });
        }
    }

    // --- MODALES DE CALIFICACIÓN ---
    async function abrirCalificador(id_entrega, nombre, est_id) {
        entregaActualId = id_entrega;
        document.getElementById('calificar_entrega_id').value = id_entrega;
        const modalTitle = document.getElementById('modalCalificarTitle');
        if (modalTitle) modalTitle.innerHTML = `<i class="bi bi-person-bounding-box me-2"></i> ${nombre}`;
        
        const contResp = document.getElementById('contenedor-respuestas-estudiante');
        contResp.innerHTML = '<div class="text-center py-5"><div class="loader-ball-elite"><div class="balls-1"></div><div class="balls-2"></div><div class="balls-3"></div><div class="balls-4"></div><div class="balls-5"></div><div class="balls-6"></div><div class="balls-7"></div><div class="balls-8"></div><div class="balls-9"></div></div></div>';
        
        const modalEl = document.getElementById('modalCalificar');
        if (modalEl) {
            if (modalEl.parentNode !== document.body) {
                document.body.appendChild(modalEl);
            }
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
        
        try {
            const d = await enviarPostElite('logica/api_pruebas.php', { accion: 'obtener_detalle_entrega', entrega_id: id_entrega, estudiante_id: est_id, asignacion_id: asigSeleccionadaId }, true);
            if (d.status === 'success' && d.data) {
                renderizarRespuestasEstudiante(d);
            } else {
                throw new Error(d ? d.message : "Fallo desconocido al recuperar los datos.");
            }
        } catch(e) {
            contResp.innerHTML = `<div class="alert alert-danger m-3 fs-nano"><i class="bi bi-exclamation-triangle-fill me-1"></i> Error al cargar respuestas: ${e.message || e}</div>`;
            Swal.fire({
                icon: 'error',
                title: 'Error de Bóveda',
                text: 'No se pudieron recuperar las respuestas detalladas del estudiante.',
                customClass: { popup: 'rounded-4' }
            });
        }
    }

    function renderizarRespuestasEstudiante(d) {
        const rawResp = d.data.respuestas_json;
        const respJson = rawResp ? (typeof rawResp === 'string' ? JSON.parse(rawResp) : rawResp) : {};
        
        let normalizedResp = {};
        if (Array.isArray(respJson)) {
            respJson.forEach(item => {
                if (item && item.pregunta_id !== undefined) {
                    normalizedResp[item.pregunta_id] = item.respuesta !== undefined ? item.respuesta : (item.resp !== undefined ? item.resp : '');
                } else if (item && item.qid !== undefined) {
                    normalizedResp[item.qid] = item.resp !== undefined ? item.resp : (item.respuesta !== undefined ? item.respuesta : '');
                }
            });
        } else {
            normalizedResp = respJson;
        }

        document.getElementById('nota-manual').value = parseFloat(d.data.calificacion_manual || 0).toFixed(1);
        
        const autoEl = document.getElementById('nota-automatica');
        if (autoEl) {
            autoEl.innerText = parseFloat(d.data.calificacion_automatica || 0).toFixed(1);
        }

        const recupVal = d.data.calificacion_recuperacion !== null && d.data.calificacion_recuperacion !== undefined 
            ? parseFloat(d.data.calificacion_recuperacion).toFixed(1) 
            : '';
        const recupEl = document.getElementById('nota-recuperacion-prueba');
        if (recupEl) recupEl.value = recupVal;
        
        let incHtml = d.incidentes?.length > 0 
            ? d.incidentes.map(i => `<div class="alert alert-danger p-2 small mb-2 fs-nano border-start border-4"><strong>${i.tipo_evento}</strong>: ${i.detalles}</div>`).join('')
            : '<div class="text-success fs-nano"><i class="bi bi-check-circle me-1"></i> Sin incidentes.</div>';
        document.getElementById('contenedor-incidentes').innerHTML = incHtml;
        // Recuento de reactivos manuales para autocuración defensiva
        let manualQuestionsCount = 0;
        let singleManualQuestionId = null;
        pruebaEstructura.forEach(p => {
            let auto = (p.tipo_id == 1 || p.tipo_id == 3 || p.tipo_id == 4);
            if (!auto) {
                manualQuestionsCount++;
                singleManualQuestionId = p.id;
            }
        });

        let resHtml = '';
        
        pruebaEstructura.forEach((p, idx) => {
            let display = normalizedResp[p.id] || '<span class="text-danger fst-italic">No respondida</span>';
            let auto = (p.tipo_id == 1 || p.tipo_id == 3 || p.tipo_id == 4);
            
            // Recuperar calificación individual histórica del detalle (soporte robusto para tipos cruzados string/number)
            const det = d.detalles ? d.detalles.find(item => String(item.pregunta_id) === String(p.id)) : null;
            let manualScore = det ? parseFloat(det.puntaje_obtenido || 0).toFixed(1) : '0.0';

            // Auto-curación defensiva: Si hay un solo reactivo manual y no tiene desgloses pero hay nota manual
            if (!auto && manualQuestionsCount === 1 && (!d.detalles || d.detalles.length === 0) && parseFloat(d.data.calificacion_manual) > 0) {
                manualScore = parseFloat(d.data.calificacion_manual).toFixed(1);
            }

            resHtml += `
                <div class="card border-0 border-start border-4 ${auto ? 'border-primary' : 'border-warning'} bg-white shadow-sm mb-3">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between mb-2">
                            <h6 class="fw-bold small mb-0">Pregunta ${idx+1}</h6>
                            <span class="badge bg-primary rounded-pill">${auto ? 'Auto' : 'Manual'} / ${p.peso} Pts</span>
                        </div>
                        <div class="text-dark mb-3 p-2 bg-light rounded-3 small">${p.enunciado}</div>
                        <div class="p-2 bg-primary bg-opacity-10 text-dark rounded-3 fs-sub">${display}</div>
                        ${!auto ? `<div class="mt-2"><input type="number" class="form-control form-control-sm input-manual-pregunta" data-qid="${p.id}" data-peso="${p.peso}" value="${manualScore}" step="0.1" oninput="Perseus.recalcularTotalManual()"></div>` : ''}
                    </div>
                </div>`;
        });
        document.getElementById('contenedor-respuestas-estudiante').innerHTML = resHtml;
        recalcularTotalManual();
    }

    async function abrirCalificadorQR(id_entrega, nombre, est_id, asig_id) {
        let respuestas_existentes = {};
        try {
            const res = await enviarPostElite('logica/api_pruebas.php', { 
                accion: 'obtener_detalle_entrega', 
                entrega_id: id_entrega, 
                estudiante_id: est_id, 
                asignacion_id: asig_id 
            }, true);
            if (res.status === 'success' && res.data) {
                const rawResp = res.data.respuestas_json;
                respuestas_existentes = rawResp ? (typeof rawResp === 'string' ? JSON.parse(rawResp) : rawResp) : {};
            }
        } catch (e) {
        }

        let puntajeMaximo = 0;
        let puntajeActual = 0;
        pruebaEstructura.forEach(p => {
            puntajeMaximo += parseFloat(p.peso);
            let isCorrect = true;
            if (respuestas_existentes && respuestas_existentes[p.id] === false) {
                isCorrect = false;
            }
            if (isCorrect) {
                puntajeActual += parseFloat(p.peso);
            }
        });

        const { isConfirmed, value } = await Swal.fire({
            title: `CALIFICANDO: ${nombre}`,
            width: '800px',
            html: `
                <div class="text-start p-2">
                    <div class="bg-light p-3 rounded-4 mb-4">
                        <span class="text-muted fs-nano">Puntaje Actual</span>
                        <h2 class="mb-0 fw-bold text-primary" id="nota-tiempo-real">${puntajeActual.toFixed(1)}</h2>
                    </div>
                    <div class="d-flex flex-wrap gap-2 justify-content-center" id="grid-deduccion">
                        ${pruebaEstructura.map((p, i) => {
                            let isCorrect = true;
                            if (respuestas_existentes && respuestas_existentes[p.id] === false) {
                                isCorrect = false;
                            }
                            return `
                            <div class="item-deduccion-compact ${isCorrect ? 'active' : 'incorrect'} rounded-4 d-flex flex-column align-items-center justify-content-center shadow-sm" 
                                 id="item-p-${p.id}" onclick="Perseus.togglePreguntaDeduccion(${p.id}, ${p.peso})">
                                <span class="fw-bold">${i+1}</span>
                                <span class="badge-peso fs-nano">${p.peso} PTS</span>
                            </div>`;
                        }).join('')}
                    </div>
                </div>`,
            showCancelButton: true,
            confirmButtonText: 'PUBLICAR NOTA',
            customClass: { confirmButton: 'btn-elite px-4', cancelButton: 'btn-elite btn-elite--outline px-4 ms-2' },
            buttonsStyling: false,
            preConfirm: () => {
                const nota = parseFloat(document.getElementById('nota-tiempo-real').innerText);
                const respuestas = {};
                pruebaEstructura.forEach(p => {
                    const el = document.getElementById(`item-p-${p.id}`);
                    respuestas[p.id] = el.classList.contains('active');
                });
                return { nota, respuestas };
            }
        });

        if (isConfirmed) {
            guardarNotaDeductiva(id_entrega, value.nota, est_id, value.respuestas, asig_id);
        } else {
            resetearEstacionAres();
        }
    }

    function togglePreguntaDeduccion(id, peso) {
        const el = document.getElementById(`item-p-${id}`);
        const notaEl = document.getElementById('nota-tiempo-real');
        let nota = parseFloat(notaEl.innerText);
        
        if (el.classList.contains('active')) {
            el.classList.remove('active');
            el.classList.add('incorrect');
            nota -= peso;
        } else {
            el.classList.remove('incorrect');
            el.classList.add('active');
            nota += peso;
        }
        notaEl.innerText = nota.toFixed(1);
    }

    async function guardarNotaDeductiva(id_entrega, nota, estudiante_id, respuestas, asig_id) {
        try {
            const d = await enviarPostElite('logica/api_pruebas.php', { 
                accion: 'guardar_calificacion_final', 
                entrega_id: id_entrega, 
                calificacion_manual: 0, 
                calificacion_auto: nota,
                respuestas_json: JSON.stringify(respuestas)
            }, true);
            if (d.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: '¡CALIFICACIÓN GUARDADA!',
                    text: 'La nota ha sido publicada con éxito.',
                    timer: 1500,
                    showConfirmButton: false,
                    customClass: { popup: 'rounded-4' }
                });
                
                // ⚡ Reactividad Soberana: Actualizar la grilla de alumnos en tiempo real
                const badgeEstado = document.getElementById(`badge-estado-${estudiante_id}`);
                let nuevoEstado = 2;
                if (badgeEstado) {
                    // Si ya tenía texto (ya estaba calificado), se convierte en "Re-calificado"
                    const esRecalificacion = badgeEstado.textContent.trim().length > 0;
                    if (esRecalificacion) {
                        nuevoEstado = 3;
                        badgeEstado.className = "badge bg-warning bg-opacity-10 text-warning border border-warning fs-nano text-uppercase";
                        badgeEstado.innerHTML = `<i class="bi bi-arrow-repeat me-1"></i> Re-calificado`;
                    } else {
                        badgeEstado.className = "badge bg-success bg-opacity-10 text-success border border-success fs-nano text-uppercase";
                        badgeEstado.innerHTML = `<i class="bi bi-check-circle-fill me-1"></i> Calificado`;
                    }
                }
                
                const notaAutoEl = document.getElementById(`nota-auto-${estudiante_id}`);
                if (notaAutoEl) {
                    notaAutoEl.innerText = parseFloat(nota).toFixed(1);
                }

                const notaManualEl = document.getElementById(`nota-manual-${estudiante_id}`);
                if (notaManualEl) {
                    notaManualEl.innerText = "0.0";
                }

                const notaFinalEl = document.getElementById(`nota-final-${estudiante_id}`);
                if (notaFinalEl) {
                    notaFinalEl.innerText = parseFloat(nota).toFixed(1);
                    const aprobado = parseFloat(nota) >= (window.AresEscala?.nota_aprobacion || 3.0);
                    notaFinalEl.className = `fw-bold ${aprobado ? 'text-success' : 'text-danger'} fs-sub`;
                }

                // Habilitar los botones de acción reactivamente
                const btnRev = document.getElementById(`btn-rev-${estudiante_id}`);
                if (btnRev) {
                    btnRev.removeAttribute('disabled');
                    const nombreEst = btnRev.closest('tr').querySelector('.avatar-student').nextSibling.textContent.trim();
                    btnRev.setAttribute('onclick', `Perseus.verificarEstadoYCalificar(${id_entrega}, '${nombreEst}', ${estudiante_id}, ${parseFloat(nota).toFixed(1)}, ${nuevoEstado}, ${asig_id})`);
                }

                const btnDel = document.getElementById(`btn-del-${estudiante_id}`);
                if (btnDel) {
                    btnDel.removeAttribute('disabled');
                    const nombreEst = btnDel.closest('tr').querySelector('.avatar-student').nextSibling.textContent.trim();
                    btnDel.setAttribute('onclick', `Perseus.resetearIntento(${id_entrega}, '${nombreEst}')`);
                }
                
                resetearEstacionAres();
            } else {
                throw new Error(d ? d.message : "Error al guardar calificación.");
            }
        } catch(e) {
            resetearEstacionAres();
            Swal.fire({
                icon: 'error',
                title: 'Error de Bóveda',
                text: e.message || 'Error al conectar con la bóveda para publicar la nota.',
                customClass: { popup: 'rounded-4' }
            });
        }
    }

    function reproducirBeep() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'square'; 
            osc.frequency.setValueAtTime(1850, ctx.currentTime);
            gain.gain.setValueAtTime(0, ctx.currentTime);
            gain.gain.linearRampToValueAtTime(0.08, ctx.currentTime + 0.005);
            gain.gain.linearRampToValueAtTime(0, ctx.currentTime + 0.08); 
            osc.connect(gain); gain.connect(ctx.destination);
            osc.start(); osc.stop(ctx.currentTime + 0.08);
        } catch(e) {}
    }

    function resetearEstacionAres() {
        if (scanner) {
            try { scanner.resume(); } catch(e){}
        }
        
        const statusEl = document.getElementById('scanner-status');
        if (statusEl) {
            statusEl.innerText = "ESPERANDO QR...";
            statusEl.className = "badge bg-primary px-3 py-2 fs-micro w-100";
        }
        
        const canvas = document.getElementById('data-canvas');
        if (canvas) {
            canvas.innerHTML = `
                <div class="opacity-25 mb-4"><i class="bi bi-qr-code-scan ares-icon-hero"></i></div>
                <h4 class="fw-bold text-dark text-uppercase letter-spacing-1">Listo para Calificar</h4>
                <p class="text-muted small">Acerque el examen del estudiante al escáner para iniciar identificación.</p>
            `;
        }
    }

    function resetearCanvasAres() {
        resetearEstacionAres();
    }

    async function guardarCalificacionFinal() {
        const entregaId = document.getElementById('calificar_entrega_id').value;
        const notaManualVal = parseFloat(document.getElementById('nota-manual').value) || 0;
        const notaRecuperacionVal = document.getElementById('nota-recuperacion-prueba').value;

        if (!entregaId) {
            Swal.fire('Error', 'No se ha seleccionado ninguna entrega activa.', 'error');
            return;
        }

        // Recopilar calificaciones manuales individuales
        const detalles_manuales = [];
        document.querySelectorAll('.input-manual-pregunta').forEach(inp => {
            detalles_manuales.push({
                pregunta_id: parseInt(inp.dataset.qid),
                puntaje_obtenido: parseFloat(inp.value) || 0
            });
        });

        try {
            const params = {
                accion: 'guardar_calificacion_final',
                entrega_id: entregaId,
                calificacion_manual: notaManualVal,
                calificacion_recuperacion: notaRecuperacionVal,
                detalles_manuales: JSON.stringify(detalles_manuales)
            };

            const d = await enviarPostElite('logica/api_pruebas.php', params, true);
            if (d.status === 'success') {
                if (d.es_reprobado) {
                    const stats = d.data || { total_reprobados: 1, total_estudiantes: 1, porcentaje_perdida: 0 };
                    Swal.fire({
                        title: 'Nivelación Académica Requerida',
                        html: `<div class="swal-container-elite">
                                 <p class="swal-text-elite">
                                   El estudiante ha reprobado este examen con una nota inferior a la aprobatoria.<br><br>
                                   Actualmente hay <strong>${stats.total_reprobados}</strong> estudiante(s) reprobado(s) en esta prueba (<strong>${stats.porcentaje_perdida}%</strong> de la clase).
                                 </p>
                                 <div class="d-flex justify-content-end gap-2">
                                     <button id="swal-btn-entendido-prueba" class="swal-btn-confirm-elite">Entendido</button>
                                 </div>
                               </div>`,
                        showConfirmButton: false,
                        customClass: { popup: 'swal2-popup-elite' },
                        didOpen: () => {
                            document.getElementById('swal-btn-entendido-prueba').onclick = () => {
                                Swal.close();
                                const modalEl = document.getElementById('modalCalificar');
                                if (modalEl) {
                                    bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                                }
                                seleccionarAsignacion(asigSeleccionadaId, asigTitulo, asigPruebaId, asigModalidad, asigCursoNombre);
                            };
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'success',
                        title: '¡CALIFICACIÓN PUBLICADA!',
                        text: 'Los resultados han sido guardados y consolidados.',
                        timer: 1500,
                        showConfirmButton: false,
                        customClass: { popup: 'rounded-4' }
                    });
                    
                    const modalEl = document.getElementById('modalCalificar');
                    if (modalEl) {
                        bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                    }
                    seleccionarAsignacion(asigSeleccionadaId, asigTitulo, asigPruebaId, asigModalidad, asigCursoNombre);
                }
            } else {
                Swal.fire('Error', d.message || 'Error al persistir calificación.', 'error');
            }
        } catch (e) {
            Swal.fire('Error', 'Fallo crítico de conexión con el motor de evaluación.', 'error');
        }
    }

    async function resetearIntento(id, nombre) {
        const r = await Swal.fire({
            title: '¿Resetear intento?',
            text: `Se purgarán los datos de ${nombre}.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'SÍ, RESETEAR',
            customClass: { confirmButton: 'btn-elite btn-elite--danger px-4', cancelButton: 'btn-elite btn-elite--outline px-4 ms-2' },
            buttonsStyling: false
        });

        if (r.isConfirmed) {
            const d = await enviarPostElite('logica/resetear_intento_individual.php', { entrega_id: id }, true);
            if (d.status === 'success') {
                Swal.fire('¡Reseteado!', d.message, 'success');
                seleccionarAsignacion(asigSeleccionadaId, asigTitulo, asigPruebaId, asigModalidad, asigCursoNombre);
            }
        }
    }

    // --- LÓGICA DE ACTIVIDADES Y FOCUS MODE (FASE 1 MIGRACIÓN) ---
    function cambiarTab(tab) {
        if (tab === activeTab) return;
        activeTab = tab;
        
        document.querySelectorAll('.gradebook-tab').forEach(btn => {
            btn.classList.remove('active');
            btn.setAttribute('aria-selected', 'false');
        });
        const activeBtn = document.getElementById(`tab-${tab}`);
        if (activeBtn) {
            activeBtn.classList.add('active');
            activeBtn.setAttribute('aria-selected', 'true');
        }

        asigSeleccionadaId = null;
        actividadSeleccionadaId = null;

        // Actualizar título e icono de la barra lateral dinámicamente (Fase 1 / Opción A)
        const headerEl = document.getElementById('titulo-sidebar-gradebook');
        if (headerEl) {
            if (tab === 'examenes') {
                headerEl.innerHTML = '<i class="bi bi-journal-text me-2 text-primary"></i> Evaluaciones Vigentes';
            } else {
                headerEl.innerHTML = '<i class="bi bi-magic me-2 text-primary"></i> Tareas y Talleres';
            }
        }

        const contEnt = document.getElementById('contenedor-entregas');
        if (contEnt) {
            contEnt.innerHTML = `
                <div class="text-center py-5 opacity-50">
                    <i class="bi bi-arrow-left-circle fs-1 text-muted mb-3"></i>
                    <h5 class="text-muted">Seleccione una ${tab === 'examenes' ? 'evaluación' : 'actividad'}</h5>
                    <p class="text-muted small">Haga clic en una de la izquierda para ver los estudiantes que han entregado.</p>
                </div>`;
        }

        const titleEl = document.getElementById('titulo-entregas');
        if (titleEl) {
            titleEl.innerHTML = `<i class="bi bi-people me-2 text-primary"></i> Entregas Recibidas`;
        }

        const badgeTotal = document.getElementById('badge-total-entregas');
        if (badgeTotal) badgeTotal.innerText = `0 Entregas`;

        if (scanner) { try { scanner.stop(); } catch(e){} }

        cargarAsignaciones();
    }

    function actualizarGradebook() {
        cargarAsignaciones();
        if (activeTab === 'examenes' && asigSeleccionadaId) {
            seleccionarAsignacion(asigSeleccionadaId, asigTitulo, asigPruebaId, asigModalidad, asigCursoNombre);
        } else if (activeTab === 'actividades' && actividadSeleccionadaId) {
            seleccionarActividad(actividadSeleccionadaId, actividadSeleccionadaTitulo);
        }
    }

    async function seleccionarActividad(id, titulo) {
        id = id || actividadSeleccionadaId;
        titulo = titulo || actividadSeleccionadaTitulo;

        actividadSeleccionadaId = id;
        actividadSeleccionadaTitulo = titulo;

        const titleEl = document.getElementById('titulo-entregas');
        if (titleEl) {
            titleEl.innerHTML = `<i class="bi bi-people me-2 text-primary"></i> ${titulo}`;
        }
        
        cargarAsignaciones();
        
        const contEnt = document.getElementById('contenedor-entregas');
        if (contEnt) contEnt.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';
        
        if (scanner) { try { await scanner.stop(); } catch(e){} }

        try {
            const response = await fetch(`logica/api_actividades.php?accion=listar_estudiantes_actividad&actividad_id=${id}`);
            const d = await response.json();
            if (d.status === 'success') {
                const badgeTotal = document.getElementById('badge-total-entregas');
                if (badgeTotal) badgeTotal.innerText = `${d.data.length} Estudiantes`;
                renderizarTablaActividadEstudiantes(d.data);
            } else {
                throw new Error(d.message || "Fallo en la comunicación con la API.");
            }
        } catch (e) {
            if (contEnt) {
                contEnt.innerHTML = `
                    <div class="text-center py-5 text-danger opacity-75">
                        <i class="bi bi-exclamation-triangle-fill fs-2 mb-3"></i>
                        <h6 class="fw-bold">Error de Carga de Estudiantes</h6>
                        <p class="small mb-0">No se pudieron recuperar los estudiantes de esta actividad.</p>
                    </div>`;
            }
        }
    }

    function renderizarTablaActividadEstudiantes(estudiantes) {
        const contEnt = document.getElementById('contenedor-entregas');
        if (estudiantes.length === 0) {
            contEnt.innerHTML = '<div class="text-center py-5 text-muted fs-nano">No hay estudiantes en este curso.</div>';
            return;
        }

        let html = `
            <div class="table-responsive">
                <table class="table table-hover align-middle border-light">
                    <thead class="table-light text-muted small text-uppercase fs-nano">
                        <tr>
                            <th>Estudiante</th>
                            <th>Estado</th>
                            <th>Nota Original</th>
                            <th>Recuperación</th>
                            <th>Final</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>`;
        
        estudiantes.forEach(e => {
            let notaFinal = e.nota_final_calculada !== null && e.nota_final_calculada !== undefined
                ? parseFloat(e.nota_final_calculada).toFixed(1)
                : '-';
            
            let tieneNota = (e.nota_final !== null && e.nota_final !== undefined);
            let badgeHtml = "";
            
            if (!tieneNota) {
                badgeHtml = `<span id="act-badge-estado-${e.id}" class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary fs-nano text-uppercase">Sin Entregar</span>`;
            } else {
                if (parseInt(e.fue_editado) === 1) {
                    badgeHtml = `<span id="act-badge-estado-${e.id}" class="badge bg-warning bg-opacity-10 text-warning border border-warning fs-nano text-uppercase"><i class="bi bi-arrow-repeat me-1"></i> Re-calificado</span>`;
                } else {
                    badgeHtml = `<span id="act-badge-estado-${e.id}" class="badge bg-success bg-opacity-10 text-success border border-success fs-nano text-uppercase"><i class="bi bi-check-circle-fill me-1"></i> Calificado</span>`;
                }
            }

            let passScore = window.AresEscala?.nota_aprobacion || 3.0;
            let finalClass = tieneNota
                ? (parseFloat(notaFinal) >= passScore ? 'text-success' : 'text-danger')
                : 'text-primary';

            html += `
                <tr id="row-estudiante-${e.id}">
                    <td class="fw-bold text-dark fs-nano">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-2 avatar-student"><i class="bi bi-person"></i></div>
                            ${e.nombre}
                        </div>
                    </td>
                    <td>${badgeHtml}</td>
                    <td id="act-nota-orig-${e.id}" class="text-muted fs-nano">${tieneNota ? parseFloat(e.nota_final).toFixed(1) : '-'}</td>
                    <td id="act-nota-recup-${e.id}" class="text-muted fs-nano">${(e.nota_recuperacion !== null && e.nota_recuperacion !== undefined) ? parseFloat(e.nota_recuperacion).toFixed(1) : '-'}</td>
                    <td id="act-nota-final-${e.id}" class="fw-bold ${finalClass} fs-sub">${notaFinal}</td>
                    <td>
                        <button id="btn-calif-act-${e.id}" onclick="Perseus.abrirCalificadorActividad(${actividadSeleccionadaId}, ${e.id})" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold fs-nano me-1">CALIFICAR</button>
                    </td>
                </tr>`;
        });
        html += '</tbody></table></div>';
        contEnt.innerHTML = html;
    }

    async function abrirCalificadorActividad(id, estudianteId = null) {
        Swal.fire({ title: 'Cargando Focus Mode...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        try {
            const resAct = await fetch(`logica/api_actividades.php?accion=obtener_actividad_detalles&actividad_id=${id}`);
            const dAct = await resAct.json();
            if(dAct.status !== 'success') throw new Error(dAct.message);
            focusMode.actividad = dAct.data;

            const resEst = await fetch(`logica/api_actividades.php?accion=listar_estudiantes_actividad&actividad_id=${id}`);
            const dEst = await resEst.json();
            if(dEst.status !== 'success') throw new Error(dEst.message);
            focusMode.estudiantes = dEst.data;

            if(focusMode.estudiantes.length === 0) {
                throw new Error("No hay estudiantes matriculados en este curso.");
            }

            if (estudianteId !== null) {
                const idx = focusMode.estudiantes.findIndex(e => e.id == estudianteId);
                if (idx !== -1) {
                    focusMode.currentIndex = idx;
                } else {
                    focusMode.currentIndex = 0;
                }
            } else {
                focusMode.currentIndex = 0;
            }

            renderizarEstudianteActual();
            focusMode.modal.show();
            Swal.close();
        } catch (e) {
            Swal.fire('Error', e.message, 'error');
        }
    }

    function renderizarEstudianteActual() {
        const est = focusMode.estudiantes[focusMode.currentIndex];
        const act = focusMode.actividad;

        if (est.nota_original === undefined) {
            est.nota_original = est.nota_final;
        }

        document.getElementById('focus-titulo').innerText = act.titulo;
        document.getElementById('focus-estudiante-nombre').innerText = est.nombre;
        
        const statusHtml = (parseFloat(est.nota_final) > 0 || est.desglose) 
            ? '<i class="bi bi-patch-check-fill text-success ms-2 animate__animated animate__bounceIn" title="Ya calificado"></i>' 
            : '';
        document.getElementById('focus-estudiante-status').innerHTML = statusHtml;

        const notaInicial = (est.nota_final !== null && est.nota_final !== undefined && est.nota_final !== '') ? parseFloat(est.nota_final).toFixed(1) : '0.0';
        document.getElementById('focus-nota-final').innerText = notaInicial;
        
        const badgeEdit = document.getElementById('badge-editado-ares');
        if (parseInt(est.fue_editado) === 1) {
            badgeEdit.classList.remove('d-none');
        } else {
            badgeEdit.classList.add('d-none'); 
        }

        const areaRubrica = document.getElementById('focus-area-rubrica');
        const areaDirecto = document.getElementById('focus-area-directo');
        const canvas = document.getElementById('focus-canvas');

        if (act.tipo_evaluacion === 'rubrica') {
            areaRubrica.classList.remove('d-none');
            areaDirecto.classList.add('d-none');

            const notasPrevias = {};
            if (est.desglose) {
                est.desglose.split(',').forEach(item => {
                    const [cid, nota] = item.split(':');
                    notasPrevias[cid] = parseFloat(nota).toFixed(1);
                });
            }

            canvas.innerHTML = act.criterios.map(c => {
                const valorPrevio = notasPrevias[c.id] || parseFloat(window.AresEscala.nota_minima).toFixed(1);
                return `
                    <div class="row align-items-center mb-3">
                        <div class="col-md-5">
                            <span class="fw-bold text-dark text-uppercase fs-nano">${c.titulo}</span>
                            <span class="text-muted fs-micro ms-1">(${c.peso_porcentaje}%)</span>
                        </div>
                        <div class="col-md-5">
                            <input type="range" class="form-range slider-elite custom-range-focus crit-slider" 
                                   data-id="${c.id}" data-peso="${c.peso_porcentaje}"
                                   min="${window.AresEscala.nota_minima}" max="${window.AresEscala.nota_maxima}" step="0.1" value="${valorPrevio}" 
                                   oninput="document.getElementById('badge-crit-${c.id}').innerText=parseFloat(this.value).toFixed(1); Perseus.calcularNotaFocusReal();">
                        </div>
                        <div class="col-md-2 text-end">
                            <span class="badge-elite badge-elite--info fs-6 fw-bold" id="badge-crit-${c.id}">${parseFloat(valorPrevio).toFixed(1)}</span>
                        </div>
                    </div>
                `;
            }).join('');
        } else {
            areaRubrica.classList.add('d-none');
            areaDirecto.classList.remove('d-none');
            document.getElementById('focus-input-directo').value = notaInicial;
        }

        const inputRecup = document.getElementById('focus-input-recuperacion');
        const areaRecup = document.getElementById('focus-area-recuperacion');
        const selectMetodo = document.getElementById('focus-metodo-recuperacion');
        const inputJustRecup = document.getElementById('focus-justificacion-recuperacion');
        
        if (inputRecup && areaRecup) {
            inputRecup.value = (est.nota_recuperacion !== null && est.nota_recuperacion !== undefined && est.nota_recuperacion !== '') ? parseFloat(est.nota_recuperacion).toFixed(1) : '';
            inputRecup.min = window.AresEscala.nota_minima;
            inputRecup.max = window.AresEscala.nota_maxima;
            
            if (selectMetodo) selectMetodo.value = est.metodo_recuperacion || '';
            if (inputJustRecup) inputJustRecup.value = est.justificacion_recuperacion || '';
            
            const metMap = {
                'examen_fisico': 'Examen Físico / Sustentación',
                'trabajo_clase': 'Trabajo / Taller Escrito',
                'aula_virtual': 'Actividad Aula Virtual',
                'examen_online': 'Examen en Línea'
            };
            const labelSpan = document.getElementById('label-metodo-recuperacion');
            if (labelSpan) {
                labelSpan.innerText = metMap[est.metodo_recuperacion] || '[Seleccionar...]';
            }

            if (est.metodo_recuperacion) {
                setMetodoRecuperacion(est.metodo_recuperacion, metMap[est.metodo_recuperacion], est.soporte_recuperacion_id || null);
            } else {
                const contDigital = document.getElementById('focus-contenedor-soporte-digital');
                if (contDigital) contDigital.classList.add('d-none');
                if (inputJustRecup) inputJustRecup.readOnly = false;
            }
            
            const notaOriginal = parseFloat(est.nota_final);
            const notaAprobacion = window.AresEscala?.nota_aprobacion || 3.0;
            if (!isNaN(notaOriginal) && notaOriginal < notaAprobacion) {
                areaRecup.classList.remove('d-none');
            } else {
                areaRecup.classList.add('d-none');
            }
        }

        // Reset e inicialización de modo corrección
        const chkCorreccion = document.getElementById('focus-chk-correccion');
        const areaCorreccion = document.getElementById('focus-area-correccion');
        const areaJustificacion = document.getElementById('focus-area-justificacion');
        const justificacionInput = document.getElementById('focus-justificacion');
        
        if (chkCorreccion) chkCorreccion.checked = false;
        if (areaJustificacion) areaJustificacion.classList.add('d-none');
        if (justificacionInput) justificacionInput.value = '';

        const yaCalificado = (est.nota_final !== null && est.nota_final !== undefined && est.nota_final !== '' && parseFloat(est.nota_final) > 0);
        if (yaCalificado) {
            if (areaCorreccion) areaCorreccion.classList.remove('d-none');
            toggleInputsBloqueados(true);
        } else {
            if (areaCorreccion) areaCorreccion.classList.add('d-none');
            toggleInputsBloqueados(false);
        }
    }

    function toggleInputsBloqueados(bloqueado) {
        const sliders = document.querySelectorAll('.crit-slider');
        sliders.forEach(s => s.disabled = bloqueado);
        const inputDirecto = document.getElementById('focus-input-directo');
        if (inputDirecto) inputDirecto.disabled = bloqueado;
    }

    function toggleModoCorreccion(activo) {
        const areaJustificacion = document.getElementById('focus-area-justificacion');
        if (areaJustificacion) {
            if (activo) {
                areaJustificacion.classList.remove('d-none');
            } else {
                areaJustificacion.classList.add('d-none');
            }
        }
        toggleInputsBloqueados(!activo);
    }

    async function setMetodoRecuperacion(value, label, preselectedSoporteId = null) {
        const input = document.getElementById('focus-metodo-recuperacion');
        if (input) input.value = value;
        const btnLabel = document.getElementById('label-metodo-recuperacion');
        if (btnLabel) btnLabel.innerText = label;

        const contDigital = document.getElementById('focus-contenedor-soporte-digital');
        const selectDigital = document.getElementById('focus-soporte-digital');
        const inputJustRecup = document.getElementById('focus-justificacion-recuperacion');
        
        if (!contDigital || !selectDigital) return;

        if (value === 'aula_virtual' || value === 'examen_online') {
            contDigital.classList.remove('d-none');
            if (inputJustRecup) inputJustRecup.readOnly = true;

            selectDigital.innerHTML = '<option value="">[Cargando evidencias...]</option>';
            
            const est = focusMode.estudiantes[focusMode.currentIndex];
            const act = focusMode.actividad;

            try {
                const res = await fetch(`logica/api_actividades.php?accion=obtener_evidencias_digitales&estudiante_id=${est.id}&especialidad_id=${act.especialidad_id}&tipo=${value}`);
                const d = await res.json();
                
                if (d.status === 'success') {
                    selectDigital.innerHTML = '<option value="">[Seleccione Evidencia...]</option>';
                    if (d.data.length === 0) {
                        selectDigital.innerHTML = '<option value="">[Sin evidencias disponibles]</option>';
                    } else {
                        d.data.forEach(item => {
                            const notaTxt = item.calificacion !== null ? parseFloat(item.calificacion).toFixed(1) : 'Sin calificar';
                            const opt = document.createElement('option');
                            opt.value = item.id;
                            opt.text = `${item.titulo} (Nota: ${notaTxt})`;
                            opt.dataset.titulo = item.titulo;
                            opt.dataset.nota = notaTxt;
                            if (preselectedSoporteId && parseInt(item.id) === parseInt(preselectedSoporteId)) {
                                opt.selected = true;
                            }
                            selectDigital.appendChild(opt);
                        });
                    }
                } else {
                    selectDigital.innerHTML = `<option value="">Error: ${d.message}</option>`;
                }
            } catch (err) {
                selectDigital.innerHTML = '<option value="">Error al cargar evidencias</option>';
            }
        } else {
            contDigital.classList.add('d-none');
            selectDigital.innerHTML = '<option value=""></option>';
            if (inputJustRecup) {
                inputJustRecup.readOnly = false;
                if (inputJustRecup.value.includes('Evidencia digital:')) {
                    inputJustRecup.value = '';
                }
            }
        }
    }


    function calcularNotaFocusReal() {
        const sliders = document.querySelectorAll('.crit-slider');
        let sumaPonderada = 0;
        
        sliders.forEach(s => {
            const valor = parseFloat(s.value) || 0;
            const peso = parseFloat(s.dataset.peso) || 0;
            sumaPonderada += (valor * peso);
        });
        
        const notaExacta = Math.round(((sumaPonderada / 100) + Number.EPSILON) * 10) / 10;
        const notaTxt = notaExacta.toFixed(1);
        document.getElementById('focus-nota-final').innerText = notaTxt;
        
        const est = focusMode.estudiantes[focusMode.currentIndex];
        const badgeEdit = document.getElementById('badge-editado-ares');
        
        const notaOriginal = (parseFloat(est.nota_original) || 0.0).toFixed(1);
        if (notaTxt !== notaOriginal) {
            badgeEdit.classList.remove('d-none');
        } else {
            badgeEdit.classList.add('d-none');
        }

        est.nota_final = notaTxt;
        est.desglose = Array.from(sliders).map(s => `${s.dataset.id}:${s.value}`).join(',');
    }

    async function guardarNotaFocus() {
        const est = focusMode.estudiantes[focusMode.currentIndex];
        const act = focusMode.actividad;
        const btn = document.getElementById('btn-registrar-focus');
        
        const chkCorreccion = document.getElementById('focus-chk-correccion');
        const justificacionInput = document.getElementById('focus-justificacion');
        let justificacion = '';
        
        const yaCalificado = (est.nota_original !== null && est.nota_original !== undefined && est.nota_original !== '' && parseFloat(est.nota_original) > 0);
        if (yaCalificado && chkCorreccion && chkCorreccion.checked) {
            justificacion = justificacionInput?.value.trim() || '';
            if (justificacion.length < 10) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Justificación requerida',
                    text: 'Debe ingresar una justificación detallada (mínimo 10 caracteres) para corregir la nota.'
                });
                return;
            }
        }

        const fd = new FormData();
        fd.append('accion', 'guardar_calificaciones_focus');
        fd.append('csrf_token', window.CSRF_TOKEN || '');
        fd.append('actividad_id', act.id);
        fd.append('estudiante_id', est.id);
        
        if (justificacion) {
            fd.append('justificacion_cambio', justificacion);
        }

        let notaDirectaVal = null;
        if (act.tipo_evaluacion === 'rubrica') {
            const califs = Array.from(document.querySelectorAll('.crit-slider')).map(s => ({
                criterio_id: s.dataset.id,
                calificacion: s.value
            }));
            fd.append('calificaciones_rubrica', JSON.stringify(califs));
        } else {
            notaDirectaVal = parseFloat(document.getElementById('focus-input-directo').value) || 0;
            fd.append('calificacion_directa', notaDirectaVal);
        }

        // 🏛️ INTERCEPTACIÓN DE CALIFICACIONES EXTREMAS (CENTINELA DE INTEGRIDAD - OPCIÓN B)
        const notaFinalCalculada = parseFloat(document.getElementById('focus-nota-final').innerText) || 0;
        if (notaFinalCalculada <= 1.5) {
            const confirmExtrema = await Swal.fire({
                title: '¿Confirmar Calificación Atípica?',
                text: `El estudiante obtendrá una calificación extrema de ${notaFinalCalculada.toFixed(1)}. ¿Desea confirmar que no es un error tipográfico antes de persistir?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, Registrar',
                cancelButtonText: 'No, Corregir',
                customClass: {
                    confirmButton: 'btn-elite btn-elite--danger px-3',
                    cancelButton: 'btn-elite btn-elite--outline px-3 ms-2'
                },
                buttonsStyling: false
            });
            if (!confirmExtrema.isConfirmed) {
                return;
            }
        }

        const recupVal = document.getElementById('focus-input-recuperacion')?.value || '';
        const selectMetodo = document.getElementById('focus-metodo-recuperacion');
        const inputJustRecup = document.getElementById('focus-justificacion-recuperacion');
        
        let metodoRecup = '';
        let justificacionRecup = '';
        let soporteRecupId = '';
        
        if (recupVal !== '') {
            metodoRecup = selectMetodo?.value || '';
            justificacionRecup = inputJustRecup?.value.trim() || '';
            
            if (!metodoRecup) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Método de evidencia requerido',
                    text: 'Debe seleccionar un método de evidencia para registrar la recuperación.'
                });
                return;
            }

            if (metodoRecup === 'aula_virtual' || metodoRecup === 'examen_online') {
                const selectDigital = document.getElementById('focus-soporte-digital');
                soporteRecupId = selectDigital?.value || '';
                if (!soporteRecupId) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Soporte digital requerido',
                        text: 'Debe seleccionar una evidencia digital válida.'
                    });
                    return;
                }
            }

            if (justificacionRecup.length < 5) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Justificación requerida',
                    text: 'Debe ingresar detalles o justificación de la recuperación (mínimo 5 caracteres).'
                });
                return;
            }
        }

        fd.append('nota_recuperacion', recupVal);
        fd.append('metodo_recuperacion', metodoRecup);
        fd.append('justificacion_recuperacion', justificacionRecup);
        fd.append('soporte_recuperacion_id', soporteRecupId);

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        try {
            const res = await fetch('logica/api_actividades.php', { method: 'POST', body: fd });
            const d = await res.json();
            if(d.status === 'success') {
                const fueEdicion = (parseFloat(est.nota_original) > 0);
                est.nota_final = document.getElementById('focus-nota-final').innerText;
                est.nota_original = est.nota_final;
                
                if (fueEdicion) {
                    est.fue_editado = 1;
                    document.getElementById('badge-editado-ares').classList.remove('d-none');
                }

                if (act.tipo_evaluacion === 'rubrica') {
                    const sliders = document.querySelectorAll('.crit-slider');
                    est.desglose = Array.from(sliders).map(s => `${s.dataset.id}:${s.value}`).join(',');
                }
                
                // Si la nota final/original pasó a ser aprobatoria (>= 3.0), vaciar campos en local
                if (parseFloat(est.nota_final) >= 3.0) {
                    est.nota_recuperacion = '';
                    est.metodo_recuperacion = '';
                    est.justificacion_recuperacion = '';
                    est.soporte_recuperacion_id = '';
                } else {
                    est.nota_recuperacion = recupVal;
                    est.metodo_recuperacion = metodoRecup;
                    est.justificacion_recuperacion = justificacionRecup;
                    est.soporte_recuperacion_id = soporteRecupId;
                }
                
                Toast.fire({ icon: 'success', title: 'Nota guardada para ' + est.nombre.split(' ')[0] });
                
                if (d.es_reprobado) {
                    const stats = d.data || { total_reprobados: 1, total_estudiantes: 1, porcentaje_perdida: 0 };
                    Swal.fire({
                        title: 'Nivelación Académica Requerida',
                        html: `<div class="swal-container-elite">
                                 <p class="swal-text-elite">
                                   El estudiante <strong>${est.nombre}</strong> ha reprobado esta actividad con una nota de <strong>${est.nota_final}</strong>.<br><br>
                                   Actualmente hay <strong>${stats.total_reprobados}</strong> estudiante(s) reprobado(s) en esta actividad (<strong>${stats.porcentaje_perdida}%</strong> de la clase).
                                 </p>
                                 <div class="d-flex justify-content-end gap-2">
                                     <button id="swal-btn-cancel" class="swal-btn-confirm-elite">Entendido</button>
                                 </div>
                               </div>`,
                        showConfirmButton: false,
                        customClass: {
                            popup: 'swal2-popup-elite'
                        },
                        didOpen: () => {
                            document.getElementById('swal-btn-cancel').onclick = () => {
                                Swal.close();
                                if(focusMode.currentIndex < focusMode.estudiantes.length - 1) {
                                    cambiarEstudianteFocus(1);
                                }
                            };
                        }
                    });
                } else {
                    if(focusMode.currentIndex < focusMode.estudiantes.length - 1) {
                        cambiarEstudianteFocus(1);
                    }
                }
            } else {
                throw new Error(d.message);
            }
        } catch (e) {
            Swal.fire('Error', e.message, 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-all me-2"></i> REGISTRAR NOTA <i class="bi bi-arrow-right ms-2"></i>';
        }
    }

    function cambiarEstudianteFocus(delta) {
        let newIndex = focusMode.currentIndex + delta;
        if(newIndex >= 0 && newIndex < focusMode.estudiantes.length) {
            focusMode.currentIndex = newIndex;
            renderizarEstudianteActual();
        }
    }

    // Reusable SwAl2 Toast local para Perseus
    const Toast = Swal.mixin({
      toast: true,
      position: 'bottom-end',
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true
    });

    return {
        init,
        recalcularTotalManual,
        abrirCalificador,
        abrirCalificadorQR,
        verificarEstadoYCalificar,
        togglePreguntaDeduccion,
        resetearIntento,
        resetearCanvasAres,
        actualizarAsignaciones: cargarAsignaciones,
        guardarCalificacionFinal,
        cambiarTab,
        actualizarGradebook,
        seleccionarActividad,
        abrirCalificadorActividad,
        calcularNotaFocusReal,
        guardarNotaFocus,
        cambiarEstudianteFocus,
        toggleModoCorreccion,
        setMetodoRecuperacion
    };
})();
