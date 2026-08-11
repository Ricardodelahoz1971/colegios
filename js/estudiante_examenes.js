(function() {
    let dataExamenes = [];

    function init() {
        // Inicializar CSRF TOKEN desde el contenedor
        const container = document.getElementById('estudiante-examenes-container');
        if (container) {
            window.CSRF_TOKEN = container.getAttribute('data-csrf-token') || '';
        }
        cargarExamenesEstudiante();
    }

    window.filtrarExamenesAres = function() {
        const busqueda = document.getElementById('buscar-examen-ares').value.toLowerCase();
        const tarjetas = document.querySelectorAll('#contenedor-examenes-estudiante > .col-12');
        
        tarjetas.forEach(t => {
            const texto = t.innerText.toLowerCase();
            t.classList.toggle('u-block', texto.includes(busqueda));
            t.classList.toggle('u-hidden', !texto.includes(busqueda));
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    async function cargarExamenesEstudiante() {
        try {
            const res = await fetch('logica/api_pruebas_estudiante.php?accion=listar_mis_examenes');
            const d = await res.json();
            
            const contenedor = document.getElementById('contenedor-examenes-estudiante');
            
            if (d.status === 'success') {
                if (d.data.length === 0) {
                    contenedor.innerHTML = `
                        <div class="col-12">
                            <div class="card card-elite border-0 shadow-sm text-center py-5">
                                <div class="card-body">
                                    <i class="bi bi-cup-hot fs-1 text-muted mb-3 opacity-50"></i>
                                    <h5 class="text-muted fw-bold">No hay evaluaciones activas</h5>
                                    <p class="text-muted small">Su docente no ha programado ningún examen para su grupo en este momento.</p>
                                </div>
                            </div>
                        </div>
                    `;
                    return;
                }

                contenedor.innerHTML = d.data.map(e => {
                    let btnHtml = '';
                    let variantClass = '';
                    let semaphoreClass = '';
                    let statusLabel = '';
                    
                    if (e.estado_eval === 'EN_CURSO') {
                        semaphoreClass = 'bg-semaphore-green';
                        statusLabel = 'DISPONIBLE';
                        btnHtml = `<button class="btn-elite w-100 py-2 mt-auto" onclick="solicitarAcceso(${e.asignacion_id}, ${e.requiere_clave})">INICIAR PRUEBA</button>`;
                    } else if (e.estado_eval === 'FINALIZADO') {
                        semaphoreClass = 'bg-semaphore-red';
                        statusLabel = 'PLAZO VENCIDO';
                        btnHtml = `<button class="btn-elite btn-elite--outline w-100 py-2 mt-auto text-muted" disabled><i class="bi bi-x-circle me-1"></i> CERRADO</button>`;
                    } else if (e.estado_eval === 'PROGRAMADO') {
                        semaphoreClass = 'bg-semaphore-blue';
                        statusLabel = 'PROGRAMADO';
                        btnHtml = `<button class="btn-elite btn-elite--outline w-100 py-2 mt-auto text-muted" disabled>PRÓXIMAMENTE</button>`;
                    } else if (e.estado_eval === 'ENTREGADO') {
                        semaphoreClass = 'bg-semaphore-yellow';
                        statusLabel = 'ENTREGADO';
                        btnHtml = `<button class="btn-elite btn-elite--outline w-100 py-2 mt-auto text-warning fw-bold" disabled><i class="bi bi-clock-history me-2"></i> EN REVISIÓN</button>`;
                    } else if (e.estado_eval === 'CALIFICADO') {
                        const umbral = parseFloat(e.puntaje_maximo) * 0.6;
                        const esAprobado = parseFloat(e.nota_final) >= umbral;
                        const colorSemaforo = esAprobado ? 'success' : 'danger';
                        const iconoSemaforo = esAprobado ? 'bi-patch-check-fill' : 'bi-patch-exclamation-fill';
                        const textoSemaforo = esAprobado ? 'APROBADO' : 'REPROBADO';

                        semaphoreClass = 'bg-semaphore-' + (esAprobado ? 'green' : 'red');
                        statusLabel = 'RESULTADO DISPONIBLE';
                        
                        btnHtml = `
                            <div class="p-3 bg-${colorSemaforo} bg-opacity-10 rounded-4 mt-auto text-center border border-${colorSemaforo} border-opacity-25 shadow-sm">
                                <div class="d-flex align-items-center justify-content-center gap-2 mb-1">
                                    <i class="bi ${iconoSemaforo} text-${colorSemaforo} fs-4"></i>
                                    <span class="fw-black text-${colorSemaforo} fs-nano tracking-wider uppercase">${textoSemaforo}</span>
                                </div>
                                <div class="h3 fw-black text-${colorSemaforo} mb-0">${e.nota_final} <span class="fs-6 opacity-75 fw-normal">/ ${e.puntaje_maximo}</span></div>
                            </div>`;
                    }

                    return `
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="metric-card-elite variant-accent animate__animated animate__fadeInUp">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="semaphore-light ${semaphoreClass}"></div>
                                    <span class="small fw-bold text-secondary text-uppercase">${statusLabel}</span>
                                </div>
                                
                                <h5 class="text-titulo-elite mb-1">${e.titulo}</h5>
                                <div class="text-muted small mb-3"><i class="bi bi-journal-bookmark me-1"></i> ${e.materia}</div>

                                <div class="d-flex gap-2 mb-4">
                                    <div class="flex-fill p-2 bg-light bg-opacity-50 rounded-2 text-center border">
                                        <div class="fs-nano text-muted text-uppercase">Tiempo</div>
                                        <div class="fw-bold text-dark">${e.duracion_minutos}'</div>
                                    </div>
                                    <div class="flex-fill p-2 bg-light bg-opacity-50 rounded-2 text-center border">
                                        <div class="fs-nano text-muted text-uppercase">Items</div>
                                        <div class="fw-bold text-dark">${e.total_preguntas}</div>
                                    </div>
                                </div>

                                ${e.requiere_clave && e.estado_eval === 'EN_CURSO' ? '<div class="text-danger x-small mb-3 text-center fw-bold"><i class="bi bi-key-fill me-1"></i> REQUIERE CLAVE</div>' : ''}
                                
                                <div class="mt-auto">
                                    ${btnHtml}
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');
            } else {
                contenedor.innerHTML = `<div class="alert alert-danger shadow-sm border-0"><i class="bi bi-exclamation-triangle-fill me-2"></i> ${d.message || 'Error desconocido.'}</div>`;
            }
        } catch (e) {
            document.getElementById('contenedor-examenes-estudiante').innerHTML = '<div class="alert alert-danger shadow-sm border-0"><i class="bi bi-x-circle-fill me-2"></i> Error de conexión con la Bóveda Estudiantil.</div>';
        }
    }

    window.solicitarAcceso = async function(asignacion_id, requiere_clave) {
        if (requiere_clave) {
            const { value: clave } = await Swal.fire({
                title: 'Control de Acceso',
                input: 'password',
                inputLabel: 'Ingrese la clave proporcionada por su docente',
                inputPlaceholder: 'Clave de acceso',
                showCancelButton: true,
                confirmButtonText: 'Verificar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: 'var(--el-primary)',
            });
            if (!clave) return;
            window.verificarEntrada(asignacion_id, clave);
        } else {
            window.verificarEntrada(asignacion_id, '');
        }
    };

    window.verificarEntrada = async function(asignacion_id, clave) {
        try {
            const fd = new FormData();
            fd.append('accion', 'verificar_acceso');
            fd.append('csrf_token', window.CSRF_TOKEN || '');
            fd.append('asignacion_id', asignacion_id);
            fd.append('clave', clave);

            const res = await fetch('logica/api_pruebas_estudiante.php', { method: 'POST', body: fd });
            const d = await res.json();

            if (d.status === 'success') {
                window.location.href = `vistas/presentar_examen.php?token=${d.token}`;
            } else {
                Swal.fire('Error', d.message, 'error');
            }
        } catch (e) { 
            Swal.fire('Error', 'Fallo en la comunicación con la bóveda.', 'error');
        }
    };
})();