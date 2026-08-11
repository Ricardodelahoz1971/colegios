(function() {
    try {
        let modalRecurso = null;

        // 1. REGISTRO SOBERANO DE FUNCIONES (Visibilidad Inmediata)
        window.abrirModalRecurso = function(param = null) {
            try {
                let data = null;
                if (param !== null) {
                    if (typeof param === 'object') {
                        data = param;
                    } else if (typeof param === 'number' || typeof param === 'string') {
                        const idNum = parseInt(param);
                        if (window.RecursosAula) {
                            data = window.RecursosAula.find(x => parseInt(x.id) === idNum) || null;
                        }
                    }
                }

                if (!modalRecurso) {
                    const el = document.getElementById('modalRecurso');
                    if (el && typeof bootstrap !== 'undefined') {
                        document.body.appendChild(el);
                        modalRecurso = new bootstrap.Modal(el);
                    }
                }

                const formAula = document.getElementById('form-recurso-aula');
                if (formAula) formAula.reset();

                const idInput = document.getElementById('recurso-id');
                if (idInput) idInput.value = data ? data.id : 0;
                
                document.getElementById('titulo-modal-recurso').innerText = data ? 'EDITAR RECURSO' : 'NUEVO RECURSO DIDÁCTICO';
                
                if (data) {
                    document.getElementById('titulo').value = data.titulo;
                    document.getElementById('tipo_recurso').value = data.tipo_recurso;
                    document.getElementById('url_recurso').value = data.url_recurso;
                    document.getElementById('especialidad_id').value = data.especialidad_id;
                    document.getElementById('curso_id').value = data.curso_id;
                    document.getElementById('descripcion').value = data.descripcion;
                    
                    if (data.aula_ambito === 'recuperacion') {
                        document.getElementById('aula-ambito-recuperacion').checked = true;
                        if (data.recupera_actividad_id) {
                            cargarAulaActividadesOrigen(data.recupera_actividad_id);
                        }
                    } else {
                        document.getElementById('aula-ambito-estandar').checked = true;
                    }
                } else {
                    document.getElementById('titulo').value = '';
                    document.getElementById('tipo_recurso').value = 'PDF';
                    document.getElementById('url_recurso').value = '';
                    document.getElementById('especialidad_id').value = '';
                    document.getElementById('curso_id').value = '';
                    document.getElementById('descripcion').value = '';
                    document.getElementById('aula-ambito-estandar').checked = true;
                    document.getElementById('aula-act-origen').value = '';
                }

                // Sincronizar todos los selectores visuales de AresSelectEngine
                document.getElementById('tipo_recurso').dispatchEvent(new Event('change', { bubbles: true }));
                document.getElementById('especialidad_id').dispatchEvent(new Event('change', { bubbles: true }));
                document.getElementById('curso_id').dispatchEvent(new Event('change', { bubbles: true }));
                document.getElementById('aula-act-origen').dispatchEvent(new Event('change', { bubbles: true }));

                const esEvalInput = document.getElementById('es_evaluativo');
                if (esEvalInput) {
                    esEvalInput.checked = data ? (parseInt(data.es_evaluativo) === 1) : false;
                }

                const formatDateTimeForInput = function(dtStr) {
                    if (!dtStr) return '';
                    let formatted = dtStr.replace(' ', 'T');
                    if (formatted.length > 16) {
                        formatted = formatted.substring(0, 16);
                    }
                    return formatted;
                };

                document.getElementById('fecha_inicio').value = data && data.fecha_inicio ? formatDateTimeForInput(data.fecha_inicio) : '';
                document.getElementById('fecha_fin').value = data && data.fecha_fin ? formatDateTimeForInput(data.fecha_fin) : '';
                
                if (typeof window.toggleInputsRecurso === 'function') window.toggleInputsRecurso();
                
                // VINCULACIÓN FÍSICA DIRECTA DEL EVENTO
                const form = document.getElementById('form-recurso-aula');
                form.onsubmit = function(e) {
                    e.preventDefault();
                    const btn = document.getElementById('btn-guardar-aula');
                    if (btn) {
                        btn.disabled = true;
                        btn.innerHTML = '<div class="loader-ball-elite--mini"><div class="balls-1"></div><div class="balls-2"></div><div class="balls-3"></div><div class="balls-4"></div><div class="balls-5"></div><div class="balls-6"></div><div class="balls-7"></div><div class="balls-8"></div><div class="balls-9"></div></div> GUARDANDO...';
                    }

                    const fd = new FormData(this);
                    fetch('logica/api_aula.php?action=guardar', { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(res => {
                        if (btn) {
                            btn.disabled = false;
                            btn.innerHTML = 'GUARDAR RECURSO';
                        }
                        if (res.status === 'success') {
                            const inst = bootstrap.Modal.getInstance(document.getElementById('modalRecurso'));
                            if (inst) inst.hide();
                            Swal.fire({ icon: 'success', title: 'Actualización Exitosa', text: res.message, timer: 1500, showConfirmButton: false });
                            cargarRecursos();
                        } else {
                            Swal.fire('Falla de Bóveda', res.message, 'error');
                        }
                    })
                    .catch(err => {
                        if (btn) {
                            btn.disabled = false;
                            btn.innerHTML = 'GUARDAR RECURSO';
                        }
                        Swal.fire('Error de Comunicación', 'No se pudo conectar con la bóveda.', 'error');
                    });
                };

                if (modalRecurso) modalRecurso.show();
            } catch (err) {
            }
        };

        window.toggleInputsRecurso = function() {
            const tipo = document.getElementById('tipo_recurso').value;
            const wUrl = document.getElementById('wrapper-url');
            const wFile = document.getElementById('wrapper-archivo');
            const wEval = document.getElementById('wrapper-evaluativo');
            const inpUrl = document.getElementById('url_recurso');
            const inpFile = document.getElementById('archivo_recurso');
            const inpEval = document.getElementById('es_evaluativo');

            if (tipo === 'PDF' || tipo === 'DOC' || tipo === 'DOCX') {
                wUrl.classList.add('d-none');
                wFile.classList.remove('d-none');
                wEval.classList.remove('d-none');
                inpUrl.required = false;
                inpFile.required = (document.getElementById('recurso-id').value == 0);
            } else {
                wUrl.classList.remove('d-none');
                wFile.classList.add('d-none');
                wEval.classList.add('d-none');
                inpUrl.required = true;
                inpFile.required = false;
                if (inpEval) inpEval.checked = false;
            }
            toggleAulaAmbitoSelector();
        };

        window.toggleAulaAmbitoSelector = function() {
            const isEval = document.getElementById('es_evaluativo').checked;
            const container = document.getElementById('aula-ambito-container');
            if (isEval) {
                container.classList.remove('d-none');
                toggleAulaAmbitoRecuperacion();
            } else {
                container.classList.add('d-none');
                document.getElementById('aula-ambito-estandar').checked = true;
                toggleAulaAmbitoRecuperacion();
            }
        };

        window.toggleAulaAmbitoRecuperacion = function() {
            const radioVal = document.querySelector('input[name="aula_ambito"]:checked')?.value || 'estandar';
            const cont = document.getElementById('aula-contenedor-actividad-origen');
            if (radioVal === 'recuperacion') {
                cont.classList.remove('d-none');
                cargarAulaActividadesOrigen();
            } else {
                cont.classList.add('d-none');
                document.getElementById('aula-act-origen').value = '';
            }
        };

        window.cargarAulaActividadesOrigen = async function(preselectId = null) {
            const curso_id = document.getElementById('curso_id').value;
            const especialidad_id = document.getElementById('especialidad_id').value;
            const selectOrigen = document.getElementById('aula-act-origen');
            
            if (!curso_id || !especialidad_id) {
                selectOrigen.innerHTML = '<option value="">[Seleccione Curso y Materia primero...]</option>';
                return;
            }

            try {
                const response = await fetch(`logica/api_actividades.php?accion=listar_actividades_filtro&curso_id=${curso_id}&especialidad_id=${especialidad_id}`);
                const res = await response.json();
                
                if (res.status === 'success') {
                    const filtradas = res.data.filter(a => a.ambito === 'estandar');
                    if (filtradas.length === 0) {
                        selectOrigen.innerHTML = '<option value="">No hay actividades estándar para recuperar</option>';
                        return;
                    }
                    
                    selectOrigen.innerHTML = '<option value="">[Seleccionar actividad...]</option>' + 
                        filtradas.map(a => `<option value="${a.id}" ${preselectId && parseInt(a.id) === parseInt(preselectId) ? 'selected' : ''}>${escapeHtml(a.titulo)}</option>`).join('');
                    
                    // Forzar actualización visual de AresSelectEngine
                    selectOrigen.dispatchEvent(new Event('change', { bubbles: true }));
                } else {
                    selectOrigen.innerHTML = '<option value="">Error al cargar actividades</option>';
                    selectOrigen.dispatchEvent(new Event('change', { bubbles: true }));
                }
            } catch (e) {
                selectOrigen.innerHTML = '<option value="">Error de conexión</option>';
                selectOrigen.dispatchEvent(new Event('change', { bubbles: true }));
            }
        };

        function escapeHtml(text) {
            if (!text) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&apos;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }

        window.obtenerUrlValida = function(url) {
            if (!url) return '#';
            if (url.startsWith('http://') || url.startsWith('https://') || url.startsWith('../') || url.startsWith('/')) {
                return url;
            }
            if (url.startsWith('uploads/')) {
                return '../' + url;
            }
            return 'https://' + url;
        };

        window.cargarRecursos = function() {
            const grid = document.getElementById('grid-recursos');
            const estadoVacio = document.getElementById('estado-vacio-aula');
            if (!grid) return;

            const mat = document.getElementById('filtro-materia').value;
            const cur = document.getElementById('filtro-curso').value;
            
            grid.innerHTML = '<div class="col-12 text-center py-5"><div class="loader-ball-elite"><div class="balls-1"></div><div class="balls-2"></div><div class="balls-3"></div><div class="balls-4"></div><div class="balls-5"></div><div class="balls-6"></div><div class="balls-7"></div><div class="balls-8"></div><div class="balls-9"></div></div></div>';
            
            fetch(`logica/api_aula.php?action=listar&materia_id=${mat}&curso_id=${cur}`)
            .then(r => r.json())
            .then(data => {
                grid.innerHTML = '';
                if (data.length === 0) {
                    estadoVacio.classList.remove('d-none');
                    return;
                }
                estadoVacio.classList.add('d-none');
                
                // Guardar los recursos globalmente
                window.RecursosAula = data;
                
                data.forEach(r => {
                    const card = document.createElement('div');
                    card.className = 'col-md-6 col-xl-4 animate__animated animate__fadeInUp';
                    
                    let icon = 'bi-link-45deg';
                    let color = 'info';
                    if (r.tipo_recurso === 'PDF') { icon = 'bi-file-earmark-pdf-fill'; color = 'danger'; }
                    if (r.tipo_recurso === 'VIDEO') { icon = 'bi-play-btn-fill'; color = 'primary'; }
                    if (r.tipo_recurso === 'DOC') { icon = 'bi-file-earmark-word-fill'; color = 'info'; }

                    let evaluativoBadge = '';
                    if (parseInt(r.es_evaluativo) === 1) {
                        evaluativoBadge = `<span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 small fs-nano"><i class="bi bi-award-fill me-1"></i>CALIFICABLE</span>`;
                    }

                    card.innerHTML = `
                        <div class="card card-elite h-100 shadow-sm">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="icon-box-elite bg-${color} bg-opacity-10 text-${color} rounded-3 p-3">
                                        <i class="bi ${icon} fs-3"></i>
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn btn-link text-muted p-0" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                            <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="abrirModalRecurso(${r.id})"><i class="bi bi-pencil me-2"></i> Editar</a></li>
                                            <li><a class="dropdown-item py-2 text-danger" href="javascript:void(0)" onclick="eliminarRecurso(${r.id})"><i class="bi bi-trash me-2"></i> Eliminar</a></li>
                                        </ul>
                                    </div>
                                </div>
                                <h5 class="fw-bold text-dark mb-1">${r.titulo}</h5>
                                <p class="text-secondary small mb-3 text-truncate-2">${r.descripcion || 'Sin descripción.'}</p>
                                
                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    <span class="badge bg-light text-dark border small fs-nano">${r.nombre_curso}</span>
                                    <span class="badge bg-light text-primary border border-primary border-opacity-25 small fs-nano">${r.nombre_especialidad}</span>
                                    ${evaluativoBadge}
                                </div>

                                <div class="d-flex justify-content-between align-items-center mt-auto pt-3 border-top gap-2">
                                    <div class="card-switch-container">
                                        <div class="card-switch-item">
                                            <div class="form-check form-switch m-0 d-flex align-items-center gap-2">
                                                <input class="form-check-input pointer-event" type="checkbox" id="vis-${r.id}" ${r.visibilidad == 1 ? 'checked' : ''} onchange="toggleVisibilidad(${r.id})">
                                                <label class="card-switch-label" for="vis-${r.id}">Visible</label>
                                            </div>
                                        </div>
                                        ${(r.tipo_recurso === 'PDF' || r.tipo_recurso === 'DOC') ? `
                                        <div class="card-switch-item">
                                            <div class="form-check form-switch m-0 d-flex align-items-center gap-2">
                                                <input class="form-check-input pointer-event" type="checkbox" id="eval-${r.id}" ${r.es_evaluativo == 1 ? 'checked' : ''} onchange="toggleEvaluativo(${r.id})">
                                                <label class="card-switch-label" for="eval-${r.id}">Calificable</label>
                                            </div>
                                        </div>
                                        ` : ''}
                                    </div>
                                    <a href="${obtenerUrlValida(r.url_recurso)}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold">
                                        <i class="bi bi-box-arrow-up-right me-1"></i> VER
                                    </a>
                                </div>
                            </div>
                        </div>
                    `;
                    grid.appendChild(card);
                });
            })
        };

        window.toggleVisibilidad = function(id) {
            const fd = new FormData();
            fd.append('id', id);
            fetch('logica/api_aula.php?action=toggle_visibilidad', { method: 'POST', body: fd });
        };

        window.toggleEvaluativo = function(id) {
            const fd = new FormData();
            fd.append('id', id);
            fetch('logica/api_aula.php?action=toggle_evaluativo', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') {
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true
                    });
                    Toast.fire({
                        icon: 'success',
                        title: res.message
                    });
                } else {
                    Swal.fire('Falla de Bóveda', res.message, 'error');
                }
                cargarRecursos();
            })
            .catch(err => {
                Swal.fire('Error', 'No se pudo sincronizar el estado evaluativo.', 'error');
                cargarRecursos();
            });
        };

        window.eliminarRecurso = function(id) {
            Swal.fire({
                title: '¿Eliminar recurso?',
                text: "Esta acción no se puede deshacer.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'SÍ, ELIMINAR',
                confirmButtonColor: 'var(--el-danger)'
            }).then(res => {
                if (res.isConfirmed) {
                    const fd = new FormData();
                    fd.append('id', id);
                    fetch('logica/api_aula.php?action=eliminar', { method: 'POST', body: fd })
                    .then(() => cargarRecursos());
                }
            });
        };

        window.resetFiltros = function() {
            document.getElementById('filtro-materia').value = 0;
            document.getElementById('filtro-curso').value = 0;
            cargarRecursos();
        };

        window.aulaVirtualSubmitListenerActive = true; 

        // 3. CARGA INICIAL
        cargarRecursos();

    } catch (criticalError) {
    }
})();