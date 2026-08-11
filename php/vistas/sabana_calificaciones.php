<?php

// @sast-ignore - Deuda técnica legacy controlada bajo línea base de seguridad v5.0
declare(strict_types=1);

/**
 * 🏛️ VISTA: SÁBANA DE CALIFICACIONES CONSOLIDADA ÉLITE (VITRINA 06)
 * Matriz General de Desempeño Escolar por Materias y Promedios (Lectura Pura)
 * 
 * @author Ingeniería Élite v9.5
 * @version 3.0 (Read-Only Architecture)
 */

if (!tiene_permiso('evaluacion')) {
    echo "<div class='container p-5 text-center'><h2 class='display-6 fw-bold text-muted'>Acceso denegado: Privilegios insuficientes.</h2></div>";
    return;
}

$mi_id = (int)$_SESSION['usuario_id'];
$es_admin = tiene_permiso('configuracion') || tiene_permiso('matricula') || tienen_rol(['administrador', 'coordinador']);

if ($es_admin) {
    // Admins y Coordinadores acceden a todos los cursos del plantel
    $stmt_c = $db->prepare("SELECT id, nombre_curso FROM cursos ORDER BY nombre_curso");
    $stmt_c->execute();
    $cursos = $stmt_c->fetchAll();
} else {
    // Docentes ven los cursos donde tienen carga académica o son tutores
    $stmt_c = $db->prepare("
        SELECT DISTINCT c.id, c.nombre_curso 
        FROM cursos c
        LEFT JOIN carga_academica ca ON c.id = ca.curso_id 
        WHERE c.tutor_id = ? OR ca.docente_id = ?
        ORDER BY c.nombre_curso
    ");
    $stmt_c->execute([$mi_id, $mi_id]);
    $cursos = $stmt_c->fetchAll();
}

// Calcular el periodo por defecto en base al mes actual del sistema
$mes_actual = (int)date('n');
$periodo_defecto = 1;
if ($mes_actual >= 4 && $mes_actual <= 6) {
    $periodo_defecto = 2;
} elseif ($mes_actual >= 7 && $mes_actual <= 9) {
    $periodo_defecto = 3;
} elseif ($mes_actual >= 10 && $mes_actual <= 12) {
    $periodo_defecto = 4;
}
?>

<div class="container-fluid py-4 animate__animated animate__fadeIn">
    
    <!-- 🏛️ ISLA DE FILTROS SUPERIOR (Control interactivo 44px & Radios de 12px) -->
    <div class="card card-elite card-elite--header border-0 shadow-sm mb-4 filter-isla-global">
        <div class="card-body p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="avatar-elite bg-primary text-white d-flex align-items-center justify-content-center">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                    </svg>
                </div>
                <div>
                    <h4 class="h5 fw-bold mb-0 text-dark">SÁBANA DE NOTAS</h4>
                    <p class="fs-nano text-muted mb-0">Consolidado general de curso por materias (Lectura Pura)</p>
                </div>
            </div>
            
            <!-- FILTROS DE PRESTIGIO -->
            <div class="d-flex align-items-center flex-wrap gap-2 print-hidden-element">
                <!-- Selector Maestro de Nivel de Análisis -->
                <div class="filtro-grupo">
                    <label for="filtro-analisis" class="visually-hidden">Nivel de Análisis</label>
                    <select id="filtro-analisis" class="select-elite filter-control-44" onchange="alternarNivelAnalisis()" aria-label="Nivel de Análisis" autocomplete="off">
                        <option value="curso">Curso (Grupo)</option>
                        <option value="nivel">Nivel (Grado)</option>
                        <option value="colegio">Colegio (Global)</option>
                    </select>
                </div>

                <!-- Selector de Grado (inicialmente oculto) -->
                <div id="wrapper-filtro-nivel" class="filtro-grupo d-none">
                    <label for="filtro-nivel" class="visually-hidden">Grado / Nivel</label>
                    <select id="filtro-nivel" class="select-elite filter-control-44" onchange="cargarAnalisisNivel()" aria-label="Seleccionar Grado" autocomplete="off">
                        <option value="">-- Seleccione Grado --</option>
                    </select>
                </div>

                <!-- Selector de Curso (inicialmente visible) -->
                <div class="filtro-grupo">
                    <label for="filtro-curso" class="visually-hidden">Curso</label>
                    <select id="filtro-curso" class="select-elite filter-control-44" onchange="cargarConsolidado()" aria-label="Seleccionar Curso" autocomplete="off">
                        <option value="">-- Seleccione Grupo --</option>
                        <?php foreach ($cursos as $c): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['nombre_curso']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Selector de Periodo -->
                <div class="filtro-grupo">
                    <label for="filtro-periodo" class="visually-hidden">Periodo</label>
                    <select id="filtro-periodo" class="select-elite filter-control-44" onchange="recargarVistaActual()" data-periodo-inicial="<?php echo $periodo_defecto; ?>" aria-label="Seleccionar Periodo" autocomplete="off">
                        <option value="1" <?php echo $periodo_defecto === 1 ? 'selected' : ''; ?>>Periodo 1</option>
                        <option value="2" <?php echo $periodo_defecto === 2 ? 'selected' : ''; ?>>Periodo 2</option>
                        <option value="3" <?php echo $periodo_defecto === 3 ? 'selected' : ''; ?>>Periodo 3</option>
                        <option value="4" <?php echo $periodo_defecto === 4 ? 'selected' : ''; ?>>Periodo 4</option>
                    </select>
                </div>
                
                <button id="btn-print-sabana" class="btn-elite btn-elite--primary filter-control-44 px-3 d-flex align-items-center gap-2 shadow-sm" onclick="window.print()" disabled>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                        <rect x="6" y="14" width="12" height="8"></rect>
                    </svg>
                    Imprimir Reporte
                </button>
            </div>
        </div>
    </div>

    <!-- 🏛️ MARCO DE LA MATRIZ PRINCIPAL (Panel maestro con radio de 24px) -->
    <div class="card card-elite panel-maestro-sabana border-0 shadow-sm">
        <div class="card-body p-0">
            <!-- Cargadores de Estado -->
            <div id="loading-sabana" class="text-center py-5 text-muted d-none">
                <div class="loader-ball-elite">
                    <div class="balls-1"></div>
                    <div class="balls-2"></div>
                    <div class="balls-3"></div>
                    <div class="balls-4"></div>
                    <div class="balls-5"></div>
                    <div class="balls-6"></div>
                    <div class="balls-7"></div>
                    <div class="balls-8"></div>
                    <div class="balls-9"></div>
                </div>
                <div class="mt-2">Procesando consolidado académico de alta fidelidad...</div>
            </div>

            <div id="vacio-sabana" class="text-center py-5 text-muted">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="mb-3 opacity-50">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="9" y1="3" x2="9" y2="21"></line>
                    <line x1="15" y1="3" x2="15" y2="21"></line>
                    <line x1="3" y1="9" x2="21" y2="9"></line>
                    <line x1="3" y1="15" x2="21" y2="15"></line>
                </svg>
                <h5 class="fw-bold mb-1">Ningún Grupo Seleccionado</h5>
                <p class="small text-secondary mb-0">Seleccione un curso y un periodo en los controles superiores para cargar la sábana de notas.</p>
            </div>

            <!-- 📊 VISTA 1: ANALÍTICA GLOBAL DE COLEGIO (Rectoría/Coordinación) -->
            <div id="wrapper-colegio" class="bi-view-wrapper p-4 d-none">
                <!-- Se inyecta dinámicamente -->
            </div>

            <!-- 📊 VISTA 2: COMPARATIVA DE PARALELOS POR NIVEL (Coordinación) -->
            <div id="wrapper-nivel" class="bi-view-wrapper p-4 d-none">
                <!-- Se inyecta dinámicamente -->
            </div>

            <!-- 📊 VISTA 3: SÁBANA DE NOTAS CLÁSICA (Docente/Curso) -->
            <div id="wrapper-sabana" class="sabana-grid-wrapper p-3 d-none">
                <!-- BOTONES DE RESALTADO INTERACTIVO (Vitrina 06 Seda) -->
                <div class="d-flex justify-content-between align-items-center gap-3 mb-3 flex-wrap print-hidden-element">
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn-elite btn-elite--outline filter-control-44 px-3 d-flex align-items-center gap-2" onclick="limpiarSeleccionCurso()">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="19" y1="12" x2="5" y2="12"></line>
                                <polyline points="12 19 5 12 12 5"></polyline>
                            </svg>
                            Regresar
                        </button>
                        <button id="toggle-alertas" class="btn-elite filter-control-44 px-3 d-flex align-items-center gap-2" onclick="alternarHighlightAlertas()">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                <line x1="12" y1="9" x2="12" y2="13"></line>
                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                            </svg>
                            <span>Destacar Alertas</span>
                        </button>
                        
                        <button id="toggle-excelencia" class="btn-elite filter-control-44 px-3 d-flex align-items-center gap-2" onclick="alternarHighlightExcelencia()">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                            </svg>
                            <span>Destacar Excelencia</span>
                        </button>
                    </div>
                    <div class="fs-nano text-muted">
                        * Haga clic en una celda de calificación para desplegar el panel lateral interactivo de lectura pura.
                    </div>
                </div>

                <!-- 🏛️ ENCABEZADO INSTITUCIONAL DE IMPRESIÓN PREMIUM -->
                <div class="sabana-print-header print-only-element mb-4">
                    <div class="d-flex align-items-center justify-content-between w-100 border-bottom pb-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="print-logo bg-primary text-white d-flex align-items-center justify-content-center">
                                <span class="fw-bold fs-4">P</span>
                            </div>
                            <div>
                                <h3 class="h4 fw-bold mb-0 text-dark">PLATAFORMA ESCOLAR PERSEUS</h3>
                                <p class="fs-nano text-muted mb-0">Reporte Académico Oficial de Sábana de Notas</p>
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="badge-elite badge-elite--light py-2 px-3" id="print-info-curso-periodo"></span>
                            <div class="fs-micro text-muted mt-1">Generado automáticamente el: <?php echo date('Y-m-d H:i'); ?></div>
                        </div>
                    </div>
                </div>

                <div class="sabana-scroll-container">
                    <table class="table-sabana">
                        <thead>
                            <tr id="cabecera-sabana">
                                <!-- Generado dinámicamente -->
                            </tr>
                        </thead>
                        <tbody id="cuerpo-sabana">
                            <!-- Generado dinámicamente -->
                        </tbody>
                        <tfoot>
                            <tr id="pie-sabana">
                                <!-- Generado dinámicamente -->
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- 🏛️ ESTRUCTURA DE FIRMAS DE IMPRESIÓN PREMIUM -->
                <div class="sabana-firmas print-only-element">
                    <div class="firma-box">
                        <div class="firma-linea"></div>
                        <span class="firma-label">Firma del Tutor de Grupo</span>
                    </div>
                    <div class="firma-box">
                        <div class="firma-linea"></div>
                        <span class="firma-label">Firma de Coordinación Académica</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 🏛️ SLIDE-OVER DETAILS PANEL (Panel deslizable de lectura pura - Vitrina 06) -->
<div id="slide-over-desglose" class="slide-over-panel" aria-hidden="true">
    <div class="slide-over-panel__backdrop" onclick="cerrarSlideOver()"></div>
    <div class="slide-over-panel__content shadow-lg">
        
        <!-- Cabecera del Panel -->
        <div class="slide-over-panel__header d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <div class="avatar-elite bg-primary text-white d-flex align-items-center justify-content-center">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 20h9"></path>
                        <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                    </svg>
                </div>
                <div>
                    <h5 class="fw-bold mb-0 text-dark text-uppercase fs-subtle" id="desglose-materia-title">Desglose</h5>
                    <p class="fs-nano text-muted mb-0" id="desglose-estudiante-name">Estudiante</p>
                </div>
            </div>
            <button class="btn-close-elite" onclick="cerrarSlideOver()" aria-label="Cerrar Panel">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <!-- Contenido Desglosado de Lectura Pura -->
        <div class="slide-over-panel__body">
            <div id="loading-desglose" class="text-center py-4 text-muted d-none">
                <div class="loader-ball-elite">
                    <div class="balls-1"></div>
                    <div class="balls-2"></div>
                    <div class="balls-3"></div>
                    <div class="balls-4"></div>
                    <div class="balls-5"></div>
                    <div class="balls-6"></div>
                    <div class="balls-7"></div>
                    <div class="balls-8"></div>
                    <div class="balls-9"></div>
                </div>
                <div class="mt-2">Cargando planilla de actividades...</div>
            </div>
            
            <div id="lista-desglose" class="d-flex flex-column gap-3">
                <!-- Se inyectan las actividades de forma dinámica -->
            </div>
        </div>
    </div>
</div>
<script src="../js/sabana_calificaciones.js" defer></script>
