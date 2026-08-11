/**
 * 🏛️ GESTOR DE ESTADO Y DATOS DE LA SÁBANA (HEFESTO ENGINE)
 */
window.SabanaState = {
    escala: null,
    materias: [],
    estudiantes: [],
    nivelAnalisis: 'curso', // 'curso', 'nivel', 'colegio'
    colegioData: null,
    nivelData: {},
    nivelesCargados: false,
    highlightAlertas: false,
    highlightExcelencia: false,
    periodoInicial: document.getElementById('filtro-periodo') ? document.getElementById('filtro-periodo').getAttribute('data-periodo-inicial') || '1' : '1'
};

/**
 * Alterna dinámicamente la vista según el nivel de análisis seleccionado
 */
function alternarNivelAnalisis() {
    const selectAnalisis = document.getElementById('filtro-analisis');
    if (!selectAnalisis) return;
    const val = selectAnalisis.value;
    window.SabanaState.nivelAnalisis = val;

    const selectCurso = document.getElementById('filtro-curso');
    if (!selectCurso) return;
    const filterCurso = selectCurso.closest('.filtro-grupo');
    const filterNivel = document.getElementById('wrapper-filtro-nivel');
    if (!filterCurso || !filterNivel) return;
    
    const wrapperSabana = document.getElementById('wrapper-sabana');
    const wrapperColegio = document.getElementById('wrapper-colegio');
    const wrapperNivel = document.getElementById('wrapper-nivel');
    const vacio = document.getElementById('vacio-sabana');
    const btnPrint = document.getElementById('btn-print-sabana');

    // Restablecer estados y botones de destacado
    window.SabanaState.highlightAlertas = false;
    window.SabanaState.highlightExcelencia = false;
    const btnAlertas = document.getElementById('toggle-alertas');
    const btnExcelencia = document.getElementById('toggle-excelencia');
    if (btnAlertas) btnAlertas.classList.remove('btn-elite--alert-active');
    if (btnExcelencia) btnExcelencia.classList.remove('btn-elite--excel-active');

    // Ocultar todas las vistas
    if (wrapperSabana) wrapperSabana.classList.add('d-none');
    if (wrapperColegio) wrapperColegio.classList.add('d-none');
    if (wrapperNivel) wrapperNivel.classList.add('d-none');
    if (vacio) vacio.classList.add('d-none');

    if (btnPrint) btnPrint.disabled = true;

    if (vacio) {
        // Restablecer textos por defecto del vacío
        const vacioTitle = vacio.querySelector('h5');
        const vacioDesc = vacio.querySelector('p');
        if (vacioTitle) vacioTitle.textContent = 'Ningún Grupo Seleccionado';
        if (vacioDesc) vacioDesc.textContent = 'Seleccione un curso y un periodo en los controles superiores para cargar la sábana de notas.';

        if (val === 'curso') {
            filterCurso.classList.remove('d-none');
            filterNivel.classList.add('d-none');
            
            const cursoId = selectCurso.value;
            if (cursoId) {
                cargarConsolidado();
            } else {
                vacio.classList.remove('d-none');
            }
        } else if (val === 'nivel') {
            filterCurso.classList.add('d-none');
            filterNivel.classList.remove('d-none');

            if (!window.SabanaState.nivelesCargados) {
                cargarNivelesDropdown();
            } else {
                const selectNivel = document.getElementById('filtro-nivel');
                const nivelPrefix = selectNivel ? selectNivel.value : '';
                if (nivelPrefix) {
                    cargarAnalisisNivel();
                } else {
                    vacio.classList.remove('d-none');
                    if (vacioTitle) vacioTitle.textContent = 'Ningún Grado Seleccionado';
                    if (vacioDesc) vacioDesc.textContent = 'Seleccione un grado en los controles superiores para cargar el análisis comparativo.';
                }
            }
        } else if (val === 'colegio') {
            filterCurso.classList.add('d-none');
            filterNivel.classList.add('d-none');
            
            cargarAnalisisColegio();
        }
    }
}

/**
 * Recarga la vista activa ante un cambio de periodo u otro parámetro global
 */
function recargarVistaActual() {
    window.SabanaState.nivelesCargados = false;
    
    const selectPeriodo = document.getElementById('filtro-periodo');
    if (!selectPeriodo) return;
    const periodoId = selectPeriodo.value;
    
    const val = window.SabanaState.nivelAnalisis || 'curso';
    if (val === 'curso') {
        cargarConsolidado();
    } else if (val === 'nivel') {
        const selectNivel = document.getElementById('filtro-nivel');
        const nivelPrefix = selectNivel ? selectNivel.value : '';
        if (nivelPrefix) {
            cargarAnalisisNivel();
        } else {
            cargarNivelesDropdown();
        }
    } else if (val === 'colegio') {
        cargarAnalisisColegio();
    }
}

/**
 * Carga la lista de niveles únicos para popular el selector comparativo
 */
async function cargarNivelesDropdown() {
    const loading = document.getElementById('loading-sabana');
    const vacio = document.getElementById('vacio-sabana');
    const selectNivel = document.getElementById('filtro-nivel');
    const selectPeriodo = document.getElementById('filtro-periodo');
    if (!selectPeriodo || !selectNivel) return;
    const periodoId = selectPeriodo.value;

    if (loading) loading.classList.remove('d-none');
    if (vacio) vacio.classList.add('d-none');

    try {
        const response = await fetch(`logica/api_sabana.php?accion=cargar_analisis_colegio&periodo_id=${periodoId}`);
        const result = await response.json();

        if (result.status === 'success') {
            window.SabanaState.colegioData = result.data;
            window.SabanaState.escala = result.data.escala;

            // Popular select de niveles
            selectNivel.innerHTML = '<option value="">-- Seleccione Grado --</option>';
            result.data.niveles.forEach(lvl => {
                const opt = document.createElement('option');
                opt.value = lvl;
                opt.textContent = lvl;
                selectNivel.appendChild(opt);
            });

            window.SabanaState.nivelesCargados = true;
            if (loading) loading.classList.add('d-none');
            
            if (window.SabanaState.nivelAnalisis === 'nivel' && vacio) {
                vacio.classList.remove('d-none');
                const vacioTitle = vacio.querySelector('h5');
                const vacioDesc = vacio.querySelector('p');
                if (vacioTitle) vacioTitle.textContent = 'Ningún Grado Seleccionado';
                if (vacioDesc) vacioDesc.textContent = 'Seleccione un grado en los controles superiores para cargar el análisis comparativo.';
            }
        } else {
            throw new Error(result.message || 'Error al cargar niveles.');
        }
    } catch (error) {
        if (loading) loading.classList.add('d-none');
        if (vacio) vacio.classList.remove('d-none');
        Swal.fire({
            icon: 'error',
            title: 'Error de Inicialización',
            text: error.message,
            confirmButtonColor: 'var(--el-primary)'
        });
    }
}

/**
 * Carga el análisis agregativo del colegio
 */
async function cargarAnalisisColegio() {
    const selectPeriodo = document.getElementById('filtro-periodo');
    if (!selectPeriodo) return;
    const periodoId = selectPeriodo.value;
    const wrapper = document.getElementById('wrapper-colegio');
    const vacio = document.getElementById('vacio-sabana');
    const loading = document.getElementById('loading-sabana');
    const btnPrint = document.getElementById('btn-print-sabana');

    if (vacio) vacio.classList.add('d-none');
    if (wrapper) wrapper.classList.add('d-none');
    if (loading) loading.classList.remove('d-none');
    if (btnPrint) btnPrint.disabled = true;

    try {
        const response = await fetch(`logica/api_sabana.php?accion=cargar_analisis_colegio&periodo_id=${periodoId}`);
        const result = await response.json();

        if (result.status === 'success') {
            window.SabanaState.colegioData = result.data;
            window.SabanaState.escala = result.data.escala;

            renderizarAnalisisColegio();
            
            if (loading) loading.classList.add('d-none');
            if (wrapper) wrapper.classList.remove('d-none');
            if (btnPrint) btnPrint.disabled = false;
        } else {
            throw new Error(result.message || 'Error al cargar analítica global.');
        }
    } catch (error) {
        if (loading) loading.classList.add('d-none');
        if (vacio) vacio.classList.remove('d-none');
        if (btnPrint) btnPrint.disabled = true;
        
        Swal.fire({
            icon: 'error',
            title: 'Error de Analítica',
            text: error.message,
            confirmButtonColor: 'var(--el-primary)'
        });
    }
}

/**
 * Renderiza la interfaz agregada de Colegio (BI Global)
 */
function renderizarAnalisisColegio() {
    const container = document.getElementById('wrapper-colegio');
    container.innerHTML = '';

    const data = window.SabanaState.colegioData;
    const kpis = data.kpis;
    const podio = data.podio;
    const semaforo = data.semaforo;
    const criticas = data.criticas;

    let html = `
        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2 print-hidden-element">
            <button class="btn-elite btn-elite--outline filter-control-44 px-3 d-flex align-items-center gap-2" onclick="volverACursoSabana()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Regresar a la Sábana
            </button>
            <span class="fs-nano text-muted">* Análisis de alta fidelidad basado en los datos globales del plantel.</span>
        </div>

        <div class="bi-kpi-grid mb-4">
            <div class="bi-kpi-card text-center">
                <p class="fs-nano text-uppercase text-secondary fw-bold mb-1">PROMEDIO INSTITUCIONAL</p>
                <h3 class="h2 fw-extrabold text-primary mb-0">${kpis.promedio_colegio.toFixed(2)}</h3>
                <span class="fs-nano text-muted">Sobre escala de ${parseFloat(data.escala.nota_maxima).toFixed(1)}</span>
            </div>
            <div class="bi-kpi-card text-center">
                <p class="fs-nano text-uppercase text-secondary fw-bold mb-1">PORCENTAJE APROBACIÓN</p>
                <h3 class="h2 fw-extrabold text-success mb-0">${kpis.aprobacion_porcentaje.toFixed(1)}%</h3>
                <span class="fs-nano text-muted">Aprobados vs en riesgo</span>
            </div>
            <div class="bi-kpi-card text-center">
                <p class="fs-nano text-uppercase text-secondary fw-bold mb-1">ESTUDIANTES EN RIESGO</p>
                <h3 class="h2 fw-extrabold text-danger mb-0">${kpis.estudiantes_riesgo}</h3>
                <span class="fs-nano text-muted">Promedios definitivos < ${parseFloat(data.escala.nota_aprobacion).toFixed(1)}</span>
            </div>
            <div class="bi-kpi-card text-center">
                <p class="fs-nano text-uppercase text-secondary fw-bold mb-1">ESTUDIANTES EVALUADOS</p>
                <h3 class="h2 fw-extrabold text-dark mb-0">${kpis.total_evaluados}</h3>
                <span class="fs-nano text-muted">Alumnos con calificaciones</span>
            </div>
        </div>

        <div class="bi-grid-panels mb-4">
            <div class="bi-panel p-4 shadow-sm">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="avatar-elite bg-success text-white d-flex align-items-center justify-content-center">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                        </svg>
                    </div>
                    <h5 class="fw-bold mb-0 text-dark">PODIO DE EXCELENCIA (TOP 3 GRUPOS)</h5>
                </div>
                <div id="podio-list" class="d-flex flex-column gap-3"></div>
            </div>

            <div class="bi-panel p-4 shadow-sm">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="avatar-elite bg-danger text-white d-flex align-items-center justify-content-center">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                            <line x1="12" y1="9" x2="12" y2="13"></line>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                    </div>
                    <h5 class="fw-bold mb-0 text-dark">ÁREAS CRÍTICAS INSTITUCIONALES</h5>
                </div>
                <div id="criticas-list" class="d-flex flex-column gap-3"></div>
            </div>
        </div>

        <div class="bi-panel p-4 shadow-sm mb-4">
            <div class="d-flex align-items-center gap-2 mb-3">
                <div class="avatar-elite bg-primary text-white d-flex align-items-center justify-content-center">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                </div>
                <h5 class="fw-bold mb-0 text-dark">SEMÁFORO DE RENDIMIENTO CURRICULAR POR CURSO</h5>
            </div>
            <div class="table-responsive">
                <table class="table-sabana bi-table-semaforo">
                    <thead>
                        <tr>
                            <th>Curso / Grupo</th>
                            <th class="text-center">Promedio General</th>
                            <th class="text-center">Estudiantes Matriculados</th>
                            <th class="text-center">Total Pérdidas</th>
                            <th class="text-center">Tasa de Riesgo</th>
                            <th class="text-center">Estado Alerta</th>
                        </tr>
                    </thead>
                    <tbody id="semaforo-tbody"></tbody>
                </table>
            </div>
        </div>
    `;

    container.innerHTML = html;

    const podioList = document.getElementById('podio-list');
    if (podio.length === 0) {
        podioList.innerHTML = '<p class="fs-nano text-muted text-center py-3">No hay datos de podio disponibles.</p>';
    } else {
        podio.forEach((item, idx) => {
            let medalClass = 'puesto-badge';
            if (idx === 0) medalClass += ' puesto-badge--gold';
            else if (idx === 1) medalClass += ' puesto-badge--silver';
            else if (idx === 2) medalClass += ' puesto-badge--bronze';

            const itemHtml = `
                <div class="bi-podio-item d-flex align-items-center justify-content-between p-3 border rounded">
                    <div class="d-flex align-items-center gap-3">
                        <span class="${medalClass}">${idx + 1}º</span>
                        <div>
                            <h6 class="fw-bold text-dark mb-0 fs-nano">${escapeHtml(item.nombre)}</h6>
                            <span class="fs-nano text-muted">${item.total_estudiantes} estudiantes evaluados</span>
                        </div>
                    </div>
                    <div class="text-end">
                        <span class="fs-subtle fw-extrabold text-primary">${item.promedio.toFixed(2)}</span>
                    </div>
                </div>
            `;
            podioList.insertAdjacentHTML('beforeend', itemHtml);
        });
    }

    const criticasList = document.getElementById('criticas-list');
    if (criticas.length === 0) {
        criticasList.innerHTML = '<p class="fs-nano text-muted text-center py-3">No hay áreas críticas detectadas.</p>';
    } else {
        criticas.forEach((item, idx) => {
            const itemHtml = `
                <div class="bi-critica-item d-flex align-items-center justify-content-between p-3 border rounded">
                    <div class="d-flex align-items-center gap-3">
                        <span class="puesto-badge puesto-badge--bronze text-danger">${idx + 1}</span>
                        <div>
                            <h6 class="fw-bold text-dark mb-0 fs-nano text-uppercase">${escapeHtml(item.nombre)}</h6>
                            <span class="fs-nano text-muted">Definitiva más baja detectada</span>
                        </div>
                    </div>
                    <div class="text-end">
                        <span class="fs-subtle fw-extrabold text-danger">${item.promedio.toFixed(2)}</span>
                    </div>
                </div>
            `;
            criticasList.insertAdjacentHTML('beforeend', itemHtml);
        });
    }

    const semaSbody = document.getElementById('semaforo-tbody');
    if (semaforo.length === 0) {
        semaSbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">Sin datos.</td></tr>';
    } else {
        semaforo.forEach(item => {
            let badgeClass = 'badge-calif--superior';
            let alertText = 'Estable';
            const risk = item.riesgo_porcentaje;

            if (risk > 40) {
                badgeClass = 'badge-calif--reprobado';
                alertText = 'Crítico';
            } else if (risk > 15) {
                badgeClass = 'badge-calif--basico';
                alertText = 'Advertencia';
            } else if (risk > 0) {
                badgeClass = 'badge-calif--alto';
                alertText = 'Favorable';
            }

            const promedioAprobacion = parseFloat(data.escala.nota_aprobacion) || 3.00;
            const promedioClass = item.promedio < promedioAprobacion ? 'nota-reprobado' : 'text-dark';

            const rowHtml = `
                <tr class="row-sabana">
                    <td class="fw-bold text-dark">${escapeHtml(item.nombre)}</td>
                    <td class="text-center fw-bold ${promedioClass}">${item.promedio.toFixed(2)}</td>
                    <td class="text-center">${item.total_estudiantes}</td>
                    <td class="text-center text-danger fw-bold">${item.total_perdidas}</td>
                    <td class="text-center fw-bold">${risk.toFixed(1)}%</td>
                    <td class="text-center">
                        <span class="tag-tipo px-2 py-1 ${badgeClass}">${alertText}</span>
                    </td>
                </tr>
            `;
            semaSbody.insertAdjacentHTML('beforeend', rowHtml);
        });
    }
}

/**
 * Carga el análisis comparativo por nivel/grado
 */
async function cargarAnalisisNivel() {
    const selectPeriodo = document.getElementById('filtro-periodo');
    const selectNivel = document.getElementById('filtro-nivel');
    if (!selectPeriodo || !selectNivel) return;
    const periodoId = selectPeriodo.value;
    const nivelPrefix = selectNivel.value;
    
    const wrapper = document.getElementById('wrapper-nivel');
    const vacio = document.getElementById('vacio-sabana');
    const loading = document.getElementById('loading-sabana');
    const btnPrint = document.getElementById('btn-print-sabana');

    if (!nivelPrefix) {
        if (wrapper) wrapper.classList.add('d-none');
        if (vacio) vacio.classList.remove('d-none');
        if (btnPrint) btnPrint.disabled = true;
        return;
    }

    if (vacio) vacio.classList.add('d-none');
    if (wrapper) wrapper.classList.add('d-none');
    if (loading) loading.classList.remove('d-none');
    if (btnPrint) btnPrint.disabled = true;

    try {
        const response = await fetch(`logica/api_sabana.php?accion=cargar_analisis_nivel&nivel_prefix=${encodeURIComponent(nivelPrefix)}&periodo_id=${periodoId}`);
        const result = await response.json();

        if (result.status === 'success') {
            window.SabanaState.nivelData[nivelPrefix] = result.data;
            window.SabanaState.escala = result.data.escala;

            renderizarAnalisisNivel(nivelPrefix);
            
            if (loading) loading.classList.add('d-none');
            if (wrapper) wrapper.classList.remove('d-none');
            if (btnPrint) btnPrint.disabled = false;
        } else {
            throw new Error(result.message || 'Error al cargar comparativa de nivel.');
        }
    } catch (error) {
        if (loading) loading.classList.add('d-none');
        if (vacio) vacio.classList.remove('d-none');
        if (btnPrint) btnPrint.disabled = true;
        
        Swal.fire({
            icon: 'error',
            title: 'Error de Comparación',
            text: error.message,
            confirmButtonColor: 'var(--el-primary)'
        });
    }
}

/**
 * Renderiza la interfaz comparativa de Nivel (BI Grado)
 */
function renderizarAnalisisNivel(nivelPrefix) {
    const container = document.getElementById('wrapper-nivel');
    container.innerHTML = '';

    const data = window.SabanaState.nivelData[nivelPrefix];
    const cursos = data.cursos;
    const comparativa = data.comparativa;

    if (comparativa.length === 0) {
        container.innerHTML = `
            <div class="d-flex flex-column align-items-center justify-content-center py-5 gap-3">
                <div class="text-secondary">No se han registrado notas consolidadas en este nivel académico.</div>
                <button class="btn-elite btn-elite--outline filter-control-44 px-3 d-flex align-items-center gap-2" onclick="volverACursoSabana()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    Regresar a la Sábana
                </button>
            </div>
        `;
        return;
    }

    let html = `
        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2 print-hidden-element">
            <button class="btn-elite btn-elite--outline filter-control-44 px-3 d-flex align-items-center gap-2" onclick="volverACursoSabana()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Regresar a la Sábana
            </button>
            <span class="fs-nano text-muted">* Análisis comparativo por nivel/grado.</span>
        </div>

        <div class="bi-panel p-4 shadow-sm mb-4">
            <div class="d-flex align-items-center gap-2 mb-3">
                <div class="avatar-elite bg-primary text-white d-flex align-items-center justify-content-center">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="9" y1="3" x2="9" y2="21"></line>
                        <line x1="15" y1="3" x2="15" y2="21"></line>
                        <line x1="3" y1="9" x2="21" y2="9"></line>
                        <line x1="3" y1="15" x2="21" y2="15"></line>
                    </svg>
                </div>
                <h5 class="fw-bold mb-0 text-dark">ANÁLISIS COMPARATIVO DE PARALELOS: GRADO ${escapeHtml(nivelPrefix)}</h5>
            </div>
            <p class="fs-nano text-muted">
                Comparativa de promedios académicos definitivos por materia entre los diferentes grupos del nivel. 
                Las barras representan el promedio en la escala de calificación institucional.
            </p>
            
            <div class="bi-comparativa-grid"></div>
        </div>
    `;

    container.innerHTML = html;
    const grid = container.querySelector('.bi-comparativa-grid');

    const maxNota = parseFloat(data.escala.nota_maxima) || 5.0;
    const notaAprobacion = parseFloat(data.escala.nota_aprobacion) || 3.0;

    comparativa.forEach(item => {
        const card = document.createElement('div');
        card.className = 'bi-subject-card p-3 border rounded shadow-sm';
        
        let cardHtml = `
            <h6 class="fw-extrabold text-uppercase text-primary mb-3 border-bottom pb-2 fs-nano text-truncate" title="${escapeHtml(item.materia)}">
                ${escapeHtml(item.materia)}
            </h6>
            <div class="d-flex flex-column gap-3">
        `;

        cursos.forEach(curso => {
            const val = item.valores[curso.id];
            const valText = val !== null ? val.toFixed(2) : 'Sin Notas';
            const pct = val !== null ? Math.min(100, Math.max(0, (val / maxNota) * 100)) : 0;
            
            let barColorClass = 'badge-calif--basico';
            if (val !== null) {
                if (val < notaAprobacion) {
                    barColorClass = 'badge-calif--reprobado';
                } else if (val >= parseFloat(data.escala.rango_superior_min)) {
                    barColorClass = 'badge-calif--superior';
                } else if (val >= parseFloat(data.escala.rango_alto_min)) {
                    barColorClass = 'badge-calif--alto';
                }
            }

            cardHtml += `
                <div class="bi-bar-container d-flex align-items-center gap-3">
                    <span class="bi-bar-label fw-bold text-dark fs-nano">${escapeHtml(curso.nombre_curso)}</span>
                    <div class="bi-bar-track flex-grow-1 position-relative">
                        ${val !== null ? `
                            <div class="bi-bar-fill d-flex align-items-center justify-content-end px-2 ${barColorClass}" data-pct="${pct}">
                                <span class="fw-extrabold fs-nano text-dark">${valText}</span>
                            </div>
                        ` : `
                            <div class="d-flex align-items-center justify-content-start w-100 h-100 ps-3 opacity-50">
                                <span class="fw-bold fs-nano text-muted">Sin Notas</span>
                            </div>
                        `}
                    </div>
                </div>
            `;
        });

        cardHtml += `</div>`;
        card.innerHTML = cardHtml;
        grid.appendChild(card);
    });
    grid.querySelectorAll('.bi-bar-fill').forEach(bar => {
        const pct = bar.getAttribute('data-pct');
        bar.style.width = pct + '%'; // layout ok
    });
}

/**
 * Toggles dinámicos de resaltado estético (Pure CSS triggers)
 */
function alternarHighlightAlertas() {
    const table = document.querySelector('.table-sabana');
    const btn = document.getElementById('toggle-alertas');
    if (!table || !btn) return;

    window.SabanaState.highlightAlertas = !window.SabanaState.highlightAlertas;
    if (window.SabanaState.highlightAlertas) {
        table.classList.add('highlight-alertas');
        btn.classList.add('btn-elite--alert-active');
    } else {
        table.classList.remove('highlight-alertas');
        btn.classList.remove('btn-elite--alert-active');
    }
}

function alternarHighlightExcelencia() {
    const table = document.querySelector('.table-sabana');
    const btn = document.getElementById('toggle-excelencia');
    if (!table || !btn) return;

    window.SabanaState.highlightExcelencia = !window.SabanaState.highlightExcelencia;
    if (window.SabanaState.highlightExcelencia) {
        table.classList.add('highlight-excelencia');
        btn.classList.add('btn-elite--excel-active');
    } else {
        table.classList.remove('highlight-excelencia');
        btn.classList.remove('btn-elite--excel-active');
    }
}

/**
 * Carga el consolidado desde la API asíncrona (Vista Curso)
 */
async function cargarConsolidado() {
    const selectCurso = document.getElementById('filtro-curso');
    const selectPeriodo = document.getElementById('filtro-periodo');
    if (!selectCurso || !selectPeriodo) return;
    const cursoId = selectCurso.value;
    const periodoId = selectPeriodo.value;
    
    const wrapper = document.getElementById('wrapper-sabana');
    const vacio = document.getElementById('vacio-sabana');
    const loading = document.getElementById('loading-sabana');
    const btnPrint = document.getElementById('btn-print-sabana');

    if (!cursoId) {
        if (wrapper) wrapper.classList.add('d-none');
        if (vacio) vacio.classList.remove('d-none');
        if (btnPrint) btnPrint.disabled = true;
        return;
    }

    if (vacio) vacio.classList.add('d-none');
    if (wrapper) wrapper.classList.add('d-none');
    if (loading) loading.classList.remove('d-none');
    if (btnPrint) btnPrint.disabled = true;

    try {
        const response = await fetch(`logica/api_sabana.php?accion=cargar_consolidado_curso&curso_id=${cursoId}&periodo_id=${periodoId}`);
        const result = await response.json();

        if (result.status === 'success') {
            window.SabanaState.escala = result.data.escala;
            window.SabanaState.materias = result.data.materias;
            window.SabanaState.estudiantes = result.data.estudiantes;

            renderizarConsolidado();

            const printInfo = document.getElementById('print-info-curso-periodo');
            if (printInfo) {
                const cursoNombre = selectCurso.options[selectCurso.selectedIndex].text;
                const periodoNombre = selectPeriodo.options[selectPeriodo.selectedIndex].text;
                printInfo.textContent = `${cursoNombre} - ${periodoNombre}`;
            }
            
            // Restablecer clases de resaltado al cargar
            const table = document.querySelector('.table-sabana');
            const btnAlertas = document.getElementById('toggle-alertas');
            const btnExcelencia = document.getElementById('toggle-excelencia');
            if (table) {
                table.classList.remove('highlight-alertas', 'highlight-excelencia');
            }
            if (btnAlertas) btnAlertas.classList.remove('btn-elite--alert-active');
            if (btnExcelencia) btnExcelencia.classList.remove('btn-elite--excel-active');
            window.SabanaState.highlightAlertas = false;
            window.SabanaState.highlightExcelencia = false;

            if (loading) loading.classList.add('d-none');
            if (wrapper) wrapper.classList.remove('d-none');
            if (btnPrint) btnPrint.disabled = false;
        } else {
            throw new Error(result.message || 'Error desconocido al cargar consolidado.');
        }
    } catch (error) {
        if (loading) loading.classList.add('d-none');
        if (vacio) vacio.classList.remove('d-none');
        if (btnPrint) btnPrint.disabled = true;
        
        Swal.fire({
            icon: 'error',
            title: 'Error de Carga',
            text: error.message,
            confirmButtonColor: 'var(--el-primary)'
        });
    }
}

/**
 * Renderiza la matriz consolidada del curso en base al estado de SabanaState
 */
function renderizarConsolidado() {
    const cabecera = document.getElementById('cabecera-sabana');
    const cuerpo = document.getElementById('cuerpo-sabana');
    const pie = document.getElementById('pie-sabana');

    cabecera.innerHTML = '';
    cuerpo.innerHTML = '';
    pie.innerHTML = '';

    const escala = window.SabanaState.escala;
    const materias = window.SabanaState.materias;
    const estudiantes = window.SabanaState.estudiantes;

    if (estudiantes.length === 0) {
        cuerpo.innerHTML = `<tr><td colspan="${materias.length + 5}" class="text-center py-5 text-secondary">No hay estudiantes matriculados en este grupo.</td></tr>`;
        return;
    }

    let htmlCabecera = `
        <th class="col-nro print-hidden-column">#</th>
        <th class="col-estudiante">Estudiante</th>
        <th class="col-inasistencias text-center">Fallas</th>
    `;

    materias.forEach(mat => {
        let warningHtml = "";
        let tooltipText = escapeHtml(mat.nombre_especialidad);
        
        if (mat.cumple_plan_evaluacion === false) {
            const alertasStr = mat.alertas_plan ? mat.alertas_plan.join(' | ') : 'Faltan evaluaciones del plan';
            warningHtml = ` <span class="text-warning text-alerta-plan" title="${escapeHtml(alertasStr)}">⚠️</span>`;
            tooltipText = `${escapeHtml(mat.nombre_especialidad)} (Plan Incompleto: ${alertasStr})`;
        }
        
        htmlCabecera += `<th class="col-materia-header text-center" title="${tooltipText}">${escapeHtml(mat.nombre_especialidad)}${warningHtml}</th>`;
    });

    htmlCabecera += `
        <th class="col-promedio text-center">Prom</th>
        <th class="col-perdidas text-center">Perdidas</th>
        <th class="col-puesto text-center">Puesto</th>
    `;
    cabecera.innerHTML = htmlCabecera;

    const listadoEstudiantesCalculados = estudiantes.map(est => {
        let sumaCalificaciones = 0.0;
        let materiasConNota = 0;
        let perdidas = 0;

        materias.forEach(mat => {
            const nota = est.calificaciones[mat.id];
            if (nota !== null && nota !== undefined) {
                sumaCalificaciones += nota;
                materiasConNota++;
                if (nota < (parseFloat(escala.nota_aprobacion) || 3.00)) {
                    perdidas++;
                }
            }
        });

        const promedioGeneral = materiasConNota > 0 ? (sumaCalificaciones / materiasConNota) : 0.0;

        return {
            ...est,
            promedioGeneral: parseFloat(promedioGeneral.toFixed(2)),
            perdidas: perdidas
        };
    });

    const listadoOrdenado = [...listadoEstudiantesCalculados].sort((a, b) => b.promedioGeneral - a.promedioGeneral);
    
    const puestosMap = {};
    let puestoActual = 1;
    for (let i = 0; i < listadoOrdenado.length; i++) {
        const est = listadoOrdenado[i];
        if (i > 0 && est.promedioGeneral < listadoOrdenado[i - 1].promedioGeneral) {
            puestoActual = i + 1;
        }
        puestosMap[est.id] = puestoActual;
    }

    listadoEstudiantesCalculados.forEach((est, idx) => {
        const puesto = puestosMap[est.id];
        
        let htmlFila = `
            <td class="col-nro text-secondary print-hidden-column">${idx + 1}</td>
            <td class="col-estudiante fw-bold text-dark">${escapeHtml(est.apellido)}, ${escapeHtml(est.nombre)}</td>
            <td class="col-inasistencias text-center text-secondary">${est.fallas}</td>
        `;

        materias.forEach(mat => {
            const nota = est.calificaciones[mat.id];
            let cellClass = '';
            let notaTexto = '-';

            if (nota !== null && nota !== undefined) {
                notaTexto = nota.toFixed(2);
                
                const aprobacion = parseFloat(escala.nota_aprobacion) || 3.00;
                if (nota < aprobacion) {
                    cellClass = 'nota-reprobado';
                } else if (nota >= parseFloat(escala.rango_superior_min)) {
                    cellClass = 'nota-superior';
                } else if (nota >= parseFloat(escala.rango_alto_min)) {
                    cellClass = 'nota-alto';
                } else {
                    cellClass = 'nota-basico';
                }
            }

            htmlFila += `
                <td class="col-calif text-center cursor-pointer ${cellClass}" onclick="abrirDesglose(${est.id}, ${mat.id})">
                    <span>${notaTexto}</span>
                </td>
            `;
        });

        const promTexto = est.promedioGeneral > 0 ? est.promedioGeneral.toFixed(2) : '-';
        const perdidasClass = est.perdidas > 0 ? 'text-danger fw-bold' : 'text-secondary';
        
        const aprobacion = parseFloat(escala.nota_aprobacion) || 3.00;
        let promCellClass = 'text-dark';
        if (est.promedioGeneral > 0 && est.promedioGeneral < aprobacion) {
            promCellClass = 'nota-reprobado';
        }
        
        let puestoBadgeClass = 'puesto-badge';
        if (puesto === 1) puestoBadgeClass += ' puesto-badge--gold';
        else if (puesto === 2) puestoBadgeClass += ' puesto-badge--silver';
        else if (puesto === 3) puestoBadgeClass += ' puesto-badge--bronze';

        htmlFila += `
            <td class="col-promedio text-center fw-bold ${promCellClass}">${promTexto}</td>
            <td class="col-perdidas text-center ${perdidasClass}">${est.perdidas}</td>
            <td class="col-puesto text-center">
                <span class="${puestoBadgeClass}">${puesto}º</span>
            </td>
        `;

        const row = document.createElement('tr');
        row.className = 'row-sabana';
        row.innerHTML = htmlFila;
        cuerpo.appendChild(row);
    });

    let htmlPie = `
        <td class="col-nro print-hidden-column">--</td>
        <td class="col-estudiante text-uppercase fw-bold text-secondary">Promedio General</td>
        <td class="col-inasistencias text-center">--</td>
    `;

    materias.forEach(mat => {
        let sumaMateria = 0.0;
        let countMateria = 0;

        estudiantes.forEach(est => {
            const nota = est.calificaciones[mat.id];
            if (nota !== null && nota !== undefined) {
                sumaMateria += nota;
                countMateria++;
            }
        });

        const promMateria = countMateria > 0 ? (sumaMateria / countMateria) : 0.0;
        const promTexto = promMateria > 0 ? promMateria.toFixed(2) : '-';
        
        htmlPie += `<td class="col-calif text-center fw-bold text-dark">${promTexto}</td>`;
    });

    let sumaGlobal = 0.0;
    let countGlobal = 0;
    let totalPerdidas = 0;

    listadoEstudiantesCalculados.forEach(est => {
        if (est.promedioGeneral > 0) {
            sumaGlobal += est.promedioGeneral;
            countGlobal++;
        }
        totalPerdidas += est.perdidas;
    });

    const promGlobal = countGlobal > 0 ? (sumaGlobal / countGlobal) : 0.0;
    const promGlobalTexto = promGlobal > 0 ? promGlobal.toFixed(2) : '-';
    const promPerdidas = estudiantes.length > 0 ? (totalPerdidas / estudiantes.length).toFixed(1) : '-';

    let promGlobalCellClass = 'text-primary';
    if (promGlobal > 0 && promGlobal < (parseFloat(escala.nota_aprobacion) || 3.00)) {
        promGlobalCellClass = 'nota-reprobado';
    }

    htmlPie += `
        <td class="col-promedio text-center fw-bold ${promGlobalCellClass}">${promGlobalTexto}</td>
        <td class="col-perdidas text-center text-secondary">${promPerdidas}</td>
        <td class="col-puesto text-center">--</td>
    `;
    pie.innerHTML = htmlPie;
}

/**
 * Abre el panel lateral deslizable con el desglose de calificaciones de lectura pura
 */
async function abrirDesglose(estudianteId, materiaId) {
    const selectPeriodo = document.getElementById('filtro-periodo');
    if (!selectPeriodo) return;
    const periodoId = selectPeriodo.value;
    const slideOver = document.getElementById('slide-over-desglose');
    const loading = document.getElementById('loading-desglose');
    const lista = document.getElementById('lista-desglose');

    const title = document.getElementById('desglose-materia-title');
    const subtitle = document.getElementById('desglose-estudiante-name');

    if (title) title.innerText = 'Desglose';
    if (subtitle) subtitle.innerText = 'Estudiante';
    if (lista) lista.innerHTML = '';
    
    if (slideOver) {
        slideOver.classList.add('slide-over-panel--active');
        slideOver.setAttribute('aria-hidden', 'false');
    }
    if (loading) loading.classList.remove('d-none');

    try {
        const response = await fetch(`logica/api_sabana.php?accion=obtener_desglose_materia&estudiante_id=${estudianteId}&materia_id=${materiaId}&periodo_id=${periodoId}`);
        const result = await response.json();

        if (loading) loading.classList.add('d-none');

        if (result.status === 'success') {
            if (title) title.innerText = result.data.materia;
            if (subtitle) subtitle.innerText = result.data.estudiante;

            const desglose = result.data.desglose;

            if (desglose.length === 0) {
                if (lista) lista.innerHTML = `<div class="text-center py-4 text-secondary fs-nano">No se han registrado actividades ni exámenes para esta materia en el periodo actual.</div>`;
                return;
            }

            if (lista) {
                desglose.forEach(item => {
                    const calif = parseFloat(item.calificacion_final_calculada);
                    const califOriginal = parseFloat(item.calificacion);
                    const recup = item.nota_recuperacion_final !== null ? parseFloat(item.nota_recuperacion_final) : null;
                    const aprobacion = parseFloat(window.SabanaState.escala.nota_aprobacion) || 3.00;
                    
                    let valClass = 'badge-calif--basico';
                    if (calif < aprobacion) {
                        valClass = 'badge-calif--reprobado';
                    } else if (calif >= parseFloat(window.SabanaState.escala.rango_superior_min)) {
                        valClass = 'badge-calif--superior';
                    } else if (calif >= parseFloat(window.SabanaState.escala.rango_alto_min)) {
                        valClass = 'badge-calif--alto';
                    }

                    const fechaRaw = new Date(item.fecha);
                    const fechaFormateada = isNaN(fechaRaw.getTime()) ? item.fecha : fechaRaw.toLocaleDateString('es-ES', { day: '2-digit', month: 'short' });

                    const badgeTipo = item.tipo === 'examen' 
                        ? `<span class="tag-tipo tag-tipo--examen">Examen</span>`
                        : `<span class="tag-tipo tag-tipo--actividad">Actividad</span>`;

                    let recupHtml = "";
                    if (recup !== null) {
                        recupHtml = `<div class="fs-micro text-secondary text-truncate">Orig: <strong>${califOriginal.toFixed(1)}</strong> | Recup: <strong>${recup.toFixed(1)}</strong></div>`;
                    }

                    const itemHtml = `
                        <div class="card-desglose shadow-sm border-0 p-3 d-flex align-items-center justify-content-between gap-3">
                            <div class="d-flex flex-column gap-1 overflow-hidden">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    ${badgeTipo}
                                    <span class="tag-dimension text-uppercase fs-nano">${escapeHtml(item.dimension)}</span>
                                </div>
                                <h6 class="fw-bold text-dark mb-0 fs-nano text-truncate" title="${escapeHtml(item.titulo)}">${escapeHtml(item.titulo)}</h6>
                                ${recupHtml}
                                <span class="fs-nano text-muted"><i class="bi bi-calendar3 me-1"></i>${fechaFormateada}</span>
                            </div>
                            <div class="badge-calif ${valClass}">
                                <span>${calif.toFixed(2)}</span>
                            </div>
                        </div>
                    `;
                    lista.insertAdjacentHTML('beforeend', itemHtml);
                });
            }

        } else {
            throw new Error(result.message || 'Error al obtener desglose.');
        }

    } catch (error) {
        if (loading) loading.classList.add('d-none');
        if (lista) lista.innerHTML = `<div class="alert alert-danger fs-nano" role="alert">${escapeHtml(error.message)}</div>`;
    }
}

/**
 * Cierra el panel lateral
 */
function cerrarSlideOver() {
    const slideOver = document.getElementById('slide-over-desglose');
    slideOver.classList.remove('slide-over-panel--active');
    slideOver.setAttribute('aria-hidden', 'true');
}

/**
 * Sanitiza valores en HTML
 */
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&apos;'
    };
    return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
}

/**
 * Restablece por completo todos los selectores superiores a su estado inicial de carga y limpia localStorage
 */
function resetearFiltrosCompletamente() {
    const selectorAnalisis = document.getElementById('filtro-analisis');
    const selectCurso = document.getElementById('filtro-curso');
    const selectNivel = document.getElementById('filtro-nivel');
    const selectPeriodo = document.getElementById('filtro-periodo');
    
    // Función de micro-sincronización visual para ARES SELECT ENGINE
    const actualizarAresUI = (select) => {
        if (!select || !select.parentElement) return;
        const btnText = select.parentElement.querySelector('.ares-select-text');
        const menu = select.parentElement.querySelector('.dropdown-menu-elite');
        if (btnText) {
            const selectedOpt = select.options[select.selectedIndex];
            btnText.innerText = selectedOpt ? selectedOpt.text : 'Seleccione...';
        }
        if (menu) {
            const items = menu.querySelectorAll('.dropdown-item');
            items.forEach((item, idx) => {
                if (idx === select.selectedIndex) item.classList.add('active');
                else item.classList.remove('active');
            });
        }
    };

    // 1. Forzar la deselección nativa opción por opción (Garantía total en el DOM)
    if (selectorAnalisis) {
        Array.from(selectorAnalisis.options).forEach(opt => opt.selected = false);
        if(selectorAnalisis.options[0]) selectorAnalisis.options[0].selected = true;
        selectorAnalisis.value = 'curso';
        actualizarAresUI(selectorAnalisis);
    }
    if (selectCurso) {
        Array.from(selectCurso.options).forEach(opt => opt.selected = false);
        if(selectCurso.options[0]) selectCurso.options[0].selected = true;
        selectCurso.value = '';
        actualizarAresUI(selectCurso);
    }
    if (selectNivel) {
        Array.from(selectNivel.options).forEach(opt => opt.selected = false);
        if(selectNivel.options[0]) selectNivel.options[0].selected = true;
        selectNivel.value = '';
        actualizarAresUI(selectNivel);
    }
    if (selectPeriodo) {
        Array.from(selectPeriodo.options).forEach(opt => opt.selected = false);
        const pVal = window.SabanaState.periodoInicial;
        const pOpt = Array.from(selectPeriodo.options).find(o => o.value == pVal);
        if (pOpt) pOpt.selected = true;
        selectPeriodo.value = pVal;
        actualizarAresUI(selectPeriodo);
    }
    
    // 2. Limpiar de raíz cualquier rastro de persistencia en localStorage
    localStorage.removeItem('sabana_filtro_analisis');
    localStorage.removeItem('sabana_filtro_curso');
    localStorage.removeItem('sabana_filtro_nivel');
    localStorage.removeItem('sabana_filtro_periodo');
    
    // 3. Sincronizar el motor visual (ocultar vistas de datos y mostrar estado vacío)
    alternarNivelAnalisis();
}

/**
 * Retorna al análisis de Curso (Grupo) y muestra la pantalla inicial de selección
 */
function volverACursoSabana() {
    resetearFiltrosCompletamente();
}

/**
 * Limpia la selección actual del curso y regresa al estado vacío de la sábana
 */
function limpiarSeleccionCurso() {
    resetearFiltrosCompletamente();
}

// Inicializar estado limpio de filtros al cargar la vista por primera vez
(function inicializarFiltrosPersistidos() {
    try {
        const selectorAnalisis = document.getElementById('filtro-analisis');
        const selectorCurso = document.getElementById('filtro-curso');
        const selectorPeriodo = document.getElementById('filtro-periodo');
        const selectorNivel = document.getElementById('filtro-nivel');

        if (!selectorAnalisis && !selectorCurso && !selectorPeriodo && !selectorNivel) {
            return;
        }

        // 1. Limpiar de raíz persistencia antigua para evitar cualquier restauración no deseada
        localStorage.removeItem('sabana_filtro_analisis');
        localStorage.removeItem('sabana_filtro_curso');
        localStorage.removeItem('sabana_filtro_nivel');
        localStorage.removeItem('sabana_filtro_periodo');

        // 2. Establecer valores iniciales por defecto puros y forzar al navegador a olvidar el historial de inputs
        const forzarLimpieza = () => {
            if (selectorAnalisis) { selectorAnalisis.value = 'curso'; selectorAnalisis.selectedIndex = 0; }
            if (selectorCurso) { selectorCurso.value = ''; selectorCurso.selectedIndex = 0; }
            if (selectorNivel) { selectorNivel.value = ''; selectorNivel.selectedIndex = 0; }
            if (selectorPeriodo) { selectorPeriodo.value = window.SabanaState.periodoInicial; }
            alternarNivelAnalisis();
        };

        // Ejecutar inmediatamente
        forzarLimpieza();
        
        // Ejecutar nuevamente tras un micro-retardo para vencer la restauración nativa tardía (BFCache/Form State) del navegador
        setTimeout(forzarLimpieza, 50);
    } catch (e) {
    }
})();
