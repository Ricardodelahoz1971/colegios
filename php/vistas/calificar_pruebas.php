<?php
declare(strict_types=1);
// PHP/VISTAS/CALIFICAR_PRUEBAS.PHP - CENTRO DE CALIFICACIONES ARES v1.0
if (!isset($_SESSION['usuario_id']) || !tiene_permiso('evaluacion')) {
    echo "Acceso denegado."; exit;
}

// 🏛️ INYECCIÓN DE CONFIGURACIONES ACADÉMICAS GLOBALES (VITRINA 06)
$stmt_esc = $db->prepare("SELECT * FROM eval_config_escala WHERE activo = 1 LIMIT 1");
$stmt_esc->execute();
$escala = $stmt_esc->fetch() ?: [
    'nota_minima' => 1.0,
    'nota_maxima' => 5.0,
    'nota_aprobacion' => 3.0
];

$stmt_pol = $db->prepare("SELECT valor FROM configuracion_global WHERE clave = 'politica_recuperacion' LIMIT 1");
$stmt_pol->execute();
$politica = $stmt_pol->fetchColumn() ?: 'reemplazo';
?>

<div class="container-fluid py-4 animate__animated animate__fadeIn" id="gradebook-container" data-nota-minima="<?php echo e((string)$escala['nota_minima']); ?>" data-nota-maxima="<?php echo e((string)$escala['nota_maxima']); ?>" data-nota-aprobacion="<?php echo e((string)$escala['nota_aprobacion']); ?>" data-politica="<?php echo e($politica); ?>">
    <div class="row align-items-center mb-4 g-3">
        <div class="col-md-8 header-module-elite">
            <h4 class="h3 fw-bold mb-0 text-titulo-elite hero-module-title">Centro de Calificaciones</h4>
            <p class="subtitle-elite">Auditoría y evaluación manual de entregas académicas.</p>
        </div>
        <div class="col-md-4 text-end">
            <button class="btn-elite btn-elite--primary px-4" onclick="Perseus.actualizarGradebook()">
                <i class="bi bi-arrow-clockwise me-2"></i> ACTUALIZAR
            </button>
        </div>
    </div>

    <!-- Pestañas de Navegación Elite (Vitrina 06) -->
    <div class="gradebook-tabs-container mb-4">
        <div class="gradebook-tabs" role="tablist">
            <button class="gradebook-tab active" id="tab-examenes" onclick="Perseus.cambiarTab('examenes')" role="tab" aria-selected="true">
                <i class="bi bi-journal-text me-2"></i> EXÁMENES Y PRUEBAS
            </button>
            <button class="gradebook-tab" id="tab-actividades" onclick="Perseus.cambiarTab('actividades')" role="tab" aria-selected="false">
                <i class="bi bi-magic me-2"></i> ACTIVIDADES DE CLASE
            </button>
        </div>
    </div>

    <!-- Script del Escáner (Ojo de Ares) -->
    <script src="https://unpkg.com/html5-qrcode"></script>

    <div class="row g-4">
        <!-- Panel Izquierdo: Lista de Evaluaciones -->
        <div class="col-12 col-xl-4">
            <div class="card card-elite border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom border-light p-4">
                    <h5 class="fw-bold text-dark mb-0" id="titulo-sidebar-gradebook"><i class="bi bi-journal-text me-2 text-primary"></i> Evaluaciones Vigentes</h5>
                </div>
                <div class="card-body p-0" id="lista-asignaciones-calificar">
                    <div class="text-center p-5"><div class="loader-ball-elite"><div class="balls-1"></div><div class="balls-2"></div><div class="balls-3"></div><div class="balls-4"></div><div class="balls-5"></div><div class="balls-6"></div><div class="balls-7"></div><div class="balls-8"></div><div class="balls-9"></div></div></div>
                </div>
            </div>
        </div>

        <!-- Panel Derecho: Lista de Entregas -->
        <div class="col-12 col-xl-8">
            <div class="card card-elite border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom border-light p-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold text-dark mb-0" id="titulo-entregas"><i class="bi bi-people me-2 text-primary"></i> Entregas Recibidas</h5>
                    <span class="badge-elite badge-elite--primary rounded-pill px-3 py-2" id="badge-total-entregas">0 Entregas</span>
                </div>
                <div class="card-body p-3" id="contenedor-entregas">
                    <div class="text-center py-5 opacity-50">
                        <i class="bi bi-arrow-left-circle fs-1 text-muted mb-3"></i>
                        <h5 class="text-muted">Seleccione una evaluación</h5>
                        <p class="text-muted small">Haga clic en una evaluación a la izquierda para ver los estudiantes que han entregado.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Calificar a un Estudiante -->
<div class="modal fade" id="modalCalificar" tabindex="-1" data-bs-backdrop="static" aria-labelledby="modalCalificarTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white border-0 py-3 d-flex justify-content-between align-items-center">
                <h5 class="modal-title fw-bold" id="modalCalificarTitle"><i class="bi bi-person-bounding-box me-2"></i> Calificando a...</h5>
                <button type="button" class="btn-close-elite text-white" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body bg-light p-0">
                <div class="row g-0 h-100">
                    <!-- Respuestas -->
                    <div class="col-md-8 p-4 border-end" id="contenedor-respuestas-estudiante">
                        <div class="text-center py-5"><div class="loader-ball-elite"><div class="balls-1"></div><div class="balls-2"></div><div class="balls-3"></div><div class="balls-4"></div><div class="balls-5"></div><div class="balls-6"></div><div class="balls-7"></div><div class="balls-8"></div><div class="balls-9"></div></div></div>
                    </div>
                    <!-- Auditoría y Notas -->
                    <div class="col-md-4 p-4 bg-white d-flex flex-column">
                        <h6 class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-shield-exclamation text-danger me-2"></i> Motor Centinela</h6>
                        <div id="contenedor-incidentes" class="mb-4 flex-grow-1 overflow-auto">
                            <!-- Incidentes -->
                        </div>
                        
                        <div class="mt-auto border-top pt-3">
                            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-calculator text-primary me-2"></i> Cálculo de Nota</h6>
                            <div class="bg-light p-3 rounded-4 mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small">Auto-Calificación (Máquina):</span>
                                    <span id="nota-automatica" class="fw-bold text-dark h5 mb-0">0.0</span>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label for="nota-manual" class="text-muted small">Calificación Manual (Docente):</label>
                                <input type="number" id="nota-manual" class="input-elite text-center fw-bold bg-light w-fixed-110"
                                    value="0.0" step="0.1" readonly>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3 d-none" id="area-recuperacion-prueba">
                                <label for="nota-recuperacion-prueba" class="text-muted small">Recuperación (Opcional):</label>
                                <input type="number" id="nota-recuperacion-prueba" class="input-elite text-center fw-bold bg-light w-fixed-110"
                                    value="" step="0.1" placeholder="N/A">
                            </div>
                            <div class="alert alert-warning p-3 small mb-3 border-start border-4 border-warning d-flex align-items-start gap-2" role="alert">
                                <i class="bi bi-shield-exclamation text-warning fs-5"></i>
                                <div>
                                    <strong class="d-block text-dark">Política de Inmutabilidad</strong>
                                    <span class="text-muted fs-micro">Las notas de exámenes individuales son inmutables de forma directa. La recuperación se gestiona a nivel de materia o dimensión mediante actividades de recuperación.</span>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-primary bg-opacity-10 rounded-3">
                                <span class="fw-bold text-primary">NOTA FINAL:</span>
                                <span class="fw-bold fs-4 text-primary" id="nota-final-calc">0.0</span>
                            </div>
                            
                            <button class="btn-elite w-100 py-3" onclick="Perseus.guardarCalificacionFinal()"><i class="bi bi-check-all me-2"></i> PUBLICAR NOTA FINAL</button>
                            <input type="hidden" id="calificar_entrega_id" value="">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 🛡️ FOCUS MODE MODAL (ARES ENGINE) -->
<div class="modal fade" id="modalFocusMode" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg bg-light modal-content-elite">
            <div class="modal-header border-0 pb-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold text-primary mb-0 fs-nano text-uppercase letter-spacing-1" id="focus-titulo"><i class="bi bi-bullseye me-2"></i> Focus Mode</h5>
                <button type="button" class="btn-close-elite shadow-none" data-bs-dismiss="modal" aria-label="Close" onclick="Perseus.actualizarGradebook()"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body p-4 pt-2 text-center">
                <div class="d-flex align-items-center justify-content-start gap-3 mb-4 bg-white p-3 rounded-4 border border-primary border-opacity-10 shadow-sm">
                    <div class="avatar-estudiante bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center avatar-estudiante-elite">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <div class="text-start">
                        <div class="d-flex align-items-center">
                            <h4 class="fw-bold text-dark mb-0 letter-spacing-1" id="focus-estudiante-nombre">Cargando...</h4>
                            <div id="focus-estudiante-status"></div>
                        </div>
                        <p class="text-muted fs-micro text-uppercase fw-bold mb-0 opacity-75">Evaluación Multicriterio</p>
                    </div>
                </div>
                
                <div id="focus-area-rubrica" class="bg-white p-3 rounded-4 shadow-sm text-start mb-3 border d-none">
                    <div id="focus-canvas" class="p-2"></div>
                    <div class="mt-2 pt-3 border-top d-flex justify-content-between align-items-end">
                        <div class="text-start">
                            <span class="text-muted fw-bold text-uppercase fs-micro d-block mb-1">Resultado del Ares Engine:</span>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge-elite badge-elite--primary fs-nano">PROCESO MULTICRITERIO ACTIVO</span>
                                <span id="badge-editado-ares" class="badge-elite badge-elite--warning fs-nano fw-bold d-none animate__animated animate__flash">EDITADO</span>
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="display-1 fw-900 text-primary lh-1 focus-nota-final-elite" id="focus-nota-final">0.0</span>
                        </div>
                    </div>
                </div>

                
                <!-- ÁREA DE CORRECCIÓN DE ERROR (Trazabilidad y Derechos del Docente) -->
                <div id="focus-area-correccion" class="bg-white p-3 rounded-4 shadow-sm text-start mb-3 border border-danger border-opacity-25 d-none animate__animated animate__fadeIn">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="focus-chk-correccion" onchange="Perseus.toggleModoCorreccion(this.checked)">
                        <label class="form-check-label text-danger fw-bold text-uppercase fs-nano" for="focus-chk-correccion">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>Corregir Error de Digitador
                        </label>
                    </div>
                    <div id="focus-area-justificacion" class="d-none mt-2">
                        <label for="focus-justificacion" class="text-muted fw-bold text-uppercase fs-micro mb-1 d-block">Justificación del Cambio (Mínimo 10 caracteres):</label>
                        <textarea id="focus-justificacion" class="input-elite border-danger border-opacity-50 textarea-justificacion-elite" rows="2" placeholder="Escriba la razón de la corrección..."></textarea>
                    </div>
                </div>

                <!-- ÁREA DE RECUPERACIÓN (Soberanía Académica) -->
                <div id="focus-area-recuperacion" class="bg-white p-3 rounded-4 shadow-sm text-start mb-4 border border-warning border-opacity-25 d-none animate__animated animate__fadeIn">
                    <label for="focus-input-recuperacion" class="text-warning fw-bold text-uppercase fs-nano mb-2 d-block"><i class="bi bi-arrow-up-circle-fill me-2"></i>Nota de Recuperación / Retake</label>
                    <div class="d-flex align-items-center gap-3">
                        <input type="number" id="focus-input-recuperacion" class="input-elite text-center fw-bold border-0 bg-light text-warning w-fixed-100 py-2 rounded-3 fs-5"
                               step="0.1" min="<?php echo e((string)$escala['nota_minima']); ?>" max="<?php echo e((string)$escala['nota_maxima']); ?>" placeholder="-">
                        <span class="text-muted fs-micro lh-sm">
                            Esta nota se computará de forma automática según la **Política de Recuperaciones** instituida.
                        </span>
                    </div>
                    
                    <div class="mt-3">
                        <label for="focus-metodo-recuperacion" class="text-muted fw-bold text-uppercase fs-micro mb-1 d-block">Método de Evidencia:</label>
                        <div class="dropdown dropup w-100">
                            <button class="btn dropdown-toggle w-100 select-metodo-recuperacion-elite text-start d-flex align-items-center justify-content-between px-3" type="button" id="dropdownMetodoRecuperacion" data-bs-toggle="dropdown" aria-expanded="false">
                                <span id="label-metodo-recuperacion" class="fs-nano text-uppercase fw-bold opacity-75">[Seleccionar...]</span>
                            </button>
                            <ul class="dropdown-menu shadow-lg border-0 w-100 dropdown-menu-recuperacion-elite" aria-labelledby="dropdownMetodoRecuperacion">
                                <li><a class="dropdown-item py-2 fs-nano text-uppercase fw-bold" href="javascript:void(0)" onclick="Perseus.setMetodoRecuperacion('examen_fisico', 'Examen Físico / Sustentación')">Examen Físico / Sustentación</a></li>
                                <li><a class="dropdown-item py-2 fs-nano text-uppercase fw-bold" href="javascript:void(0)" onclick="Perseus.setMetodoRecuperacion('trabajo_clase', 'Trabajo / Taller Escrito')">Trabajo / Taller Escrito</a></li>
                                <li><a class="dropdown-item py-2 fs-nano text-uppercase fw-bold" href="javascript:void(0)" onclick="Perseus.setMetodoRecuperacion('aula_virtual', 'Actividad Aula Virtual')">Actividad Aula Virtual</a></li>
                                <li><a class="dropdown-item py-2 fs-nano text-uppercase fw-bold" href="javascript:void(0)" onclick="Perseus.setMetodoRecuperacion('examen_online', 'Examen en Línea')">Examen en Línea</a></li>
                            </ul>
                            <input type="hidden" id="focus-metodo-recuperacion" value="">
                        </div>
                    </div>
                    <div class="mt-3 d-none" id="focus-contenedor-soporte-digital">
                        <label for="focus-soporte-digital" class="text-muted fw-bold text-uppercase fs-micro mb-1 d-block">Seleccionar Evidencia Digital:</label>
                        <select id="focus-soporte-digital" class="input-elite select-soporte-digital-elite">
                            <option value="">[Cargando evidencias...]</option>
                        </select>
                    </div>
                    <div class="mt-3">
                        <label for="focus-justificacion-recuperacion" class="text-muted fw-bold text-uppercase fs-micro mb-1 d-block">Detalles / Justificación:</label>
                        <input type="text" id="focus-justificacion-recuperacion" class="input-elite input-justificacion-recuperacion-elite" placeholder="Ej: Sustentó taller remedial">
                    </div>
                </div>
                
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <div class="d-flex w-100 align-items-center gap-2">
                    <!-- Navegadores de Estudiante -->
                    <button type="button" class="btn-elite btn-elite--secondary px-3" onclick="Perseus.cambiarEstudianteFocus(-1)" title="Estudiante Anterior">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <button type="button" class="btn-elite btn-elite--secondary px-3" onclick="Perseus.cambiarEstudianteFocus(1)" title="Siguiente Estudiante">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                    
                    <!-- Input Directo Compacto -->
                    <div id="focus-area-directo" class="d-none">
                        <input type="number" id="focus-input-directo" class="input-elite text-center fw-bold bg-light text-primary focus-input-directo-elite"
                               step="0.1" min="0" max="<?php echo e((string)$escala['nota_maxima']); ?>" value="0"
                               oninput="document.getElementById('focus-nota-final').innerText = this.value">
                    </div>

                    <!-- Botón de Registro Principal -->
                    <button type="button" id="btn-registrar-focus" class="btn-elite btn-elite--primary flex-grow-1 fw-bold py-3" onclick="Perseus.guardarNotaFocus()">
                        <i class="bi bi-check-all me-2"></i> REGISTRAR NOTA
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../js/calificar_pruebas.js" defer></script>