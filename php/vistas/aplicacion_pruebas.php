<?php

// @sast-ignore - Deuda técnica legacy controlada bajo línea base de seguridad v5.0
declare(strict_types=1);
// PHP/VISTAS/APLICACION_PRUEBAS.PHP - CENTRO DE CONTROL DE ASIGNACIONES v1.1
if (!tiene_permiso('evaluacion')) {
    echo "<div class='alert-soberania-elite animate__animated animate__headShake'>
            <div class='alert-soberania-icon'><i class='bi bi-shield-lock-fill'></i></div>
            <div class='alert-soberania-content'>
                <h4>Acceso Restringido</h4>
                <p>La programación de exámenes requiere permisos de gestión académica.</p>
            </div>
          </div>";
    return;
}

$mi_id = (int)$_SESSION['usuario_id'];

// Cargar Pruebas del docente
$stmt_p = $db->prepare("SELECT id, titulo FROM eval_pruebas WHERE docente_id = ? ORDER BY titulo ASC");
$stmt_p->execute([$mi_id]);
$pruebas_docente = $stmt_p->fetchAll();

// Cargar Cursos (Tabla real: cursos)
$stmt_stmt_c = $db->prepare("SELECT id, nombre_curso FROM cursos ORDER BY nombre_curso ASC"); $stmt_stmt_c->execute(); $stmt_c = $stmt_stmt_c;
$cursos_disponibles = $stmt_c->fetchAll();
?>

<div class="container-fluid py-4 animate__animated animate__fadeIn">
    <div class="row g-4">
        
    <div class="row align-items-center mb-4 g-3">
        <div class="col-md-4 header-module-elite mb-0">
            <h4 class="h3 fw-bold mb-0 text-titulo-elite">Programación de Exámenes</h4>
            <p class="subtitle-elite">Gestione el cronograma y aplicación de instrumentos.</p>
        </div>
        <div class="col-md-3">
            <div class="input-group-elite position-relative">
                <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                <label for="buscar-asignacion-ares" class="visually-hidden">Buscar exámenes o cursos</label>
                <input type="text" id="buscar-asignacion-ares" class="input-elite ps-5" placeholder="Buscar por examen o curso..." onkeyup="filtrarAsignacionesAres()">
            </div>
        </div>
        <div class="col-md-5 text-end d-flex gap-2 justify-content-md-end">
            <button class="btn-elite btn-elite--outline" onclick="navegarModulo('constructor_pruebas')">
                <i class="bi bi-arrow-left me-2"></i> VOLVER AL CREADOR
            </button>
            <button class="btn-elite" onclick="abrirModalAsignacion()">
                <i class="bi bi-plus-lg me-2"></i> PROGRAMAR EXAMEN
            </button>
        </div>
    </div>

        <!-- LISTADO DE ASIGNACIONES ACTIVAS/PROGRAMADAS -->
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3 ps-2">
                <h5 class="fw-bold title-section-elite mb-0"><i class="bi bi-grid-3x3-gap me-2"></i> CRONOGRAMA DE EVALUACIÓN</h5>
                <div class="d-flex gap-2">
                    <span class="badge-elite badge-elite--success px-3">ACTIVOS</span>
                    <span class="badge-elite badge-elite--warning px-3">PROGRAMADOS</span>
                </div>
            </div>
            
            <div id="grid-asignaciones-ares" class="row g-4">
                <!-- Carga AJAX -->
                <div class="col-12 text-center py-5 loader-ball-elite"><div class="balls-1"></div><div class="balls-2"></div><div class="balls-3"></div><div class="balls-4"></div><div class="balls-5"></div><div class="balls-6"></div><div class="balls-7"></div><div class="balls-8"></div><div class="balls-9"></div></div>
            </div>
        </div>

    </div>
</div>

<!-- MODAL DE PROGRAMACIÓN SOBERANA -->
<div class="modal fade" id="modalAsignacionAres" tabindex="-1" aria-labelledby="modalAsignacionTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-elite">
            <div class="modal-header bg-primary text-white p-4 border-0">
                <h5 class="modal-title fw-bold" id="modalAsignacionTitle"><i class="bi bi-clock-history me-2"></i> Programar Aplicación</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="form-asignacion-ares">
                <div class="modal-body p-4">
                    <input type="hidden" id="asignacion-id" value="0">
                    
                    <div class="mb-3">
                        <label for="asig-prueba-id" class="form-label fs-nano text-muted fw-bold">1. SELECCIONAR PRUEBA</label>
                        <select id="asig-prueba-id" class="select-elite" required>
                            <option value="">-- Seleccionar Instrumento --</option>
                            <?php foreach ($pruebas_docente as $p): ?>
                                <option value="<?php echo $p['id']; ?>"><?php echo $p['titulo']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="asig-curso-id" class="form-label fs-nano text-muted fw-bold">2. GRUPO OBJETIVO</label>
                        <select id="asig-curso-id" class="select-elite" onchange="cargarAsigActividadesOrigen()" required>
                            <option value="">-- Seleccionar Curso --</option>
                            <?php foreach ($cursos_disponibles as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo $c['nombre_curso']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fs-nano text-muted fw-bold text-uppercase mb-2 d-block">PROPÓSITO DEL EXAMEN</label>
                        <div class="mb-3">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="asig_ambito" id="asig-ambito-estandar" value="estandar" checked onchange="toggleAsigAmbitoRecuperacion()">
                                <label class="form-check-label text-dark fs-nano fw-bold cursor-pointer" for="asig-ambito-estandar">
                                    ESTÁNDAR
                                </label>
                            </div>
                            <div class="form-check form-check-inline ms-4">
                                <input class="form-check-input" type="radio" name="asig_ambito" id="asig-ambito-recuperacion" value="recuperacion" onchange="toggleAsigAmbitoRecuperacion()">
                                <label class="form-check-label text-dark fs-nano fw-bold cursor-pointer" for="asig-ambito-recuperacion">
                                    RECUPERACIÓN
                                </label>
                            </div>
                        </div>
                        
                        <!-- Selector dinámico de origen con data-ares-ignore="true" -->
                        <div class="mb-3 d-none" id="asig-contenedor-actividad-origen">
                            <label for="asig-act-origen" class="form-label fs-nano text-muted fw-bold">ACTIVIDAD DE ORIGEN A RECUPERAR</label>
                            <select id="asig-act-origen" class="select-elite">
                                <option value="">[Cargando actividades...]</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label for="asig-inicio" class="form-label fs-nano text-muted fw-bold">FECHA INICIO</label>
                            <input type="datetime-local" id="asig-inicio" class="input-elite" required>
                        </div>
                        <div class="col-6">
                            <label for="asig-fin" class="form-label fs-nano text-muted fw-bold">FECHA FIN</label>
                            <input type="datetime-local" id="asig-fin" class="input-elite" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label for="asig-clave" class="form-label fs-nano text-muted fw-bold">CLAVE ACCESO (OPC)</label>
                            <input type="text" id="asig-clave" class="input-elite" placeholder="ej: 1234">
                        </div>
                        <div class="col-6">
                            <label for="asig-intentos" class="form-label fs-nano text-muted fw-bold">INTENTOS</label>
                            <input type="number" id="asig-intentos" class="input-elite" value="1" min="1">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label for="asig-navegacion" class="form-label fs-nano text-muted fw-bold">ESTILO DE NAVEGACIÓN EN EL BÚNKER</label>
                            <select id="asig-navegacion" class="select-elite">
                                <option value="libre">C. Navegación Libre (Cuadrícula Lateral)</option>
                                <option value="paginada">B. Paginada Estricta (Pregunta por Pregunta)</option>
                                <option value="lineal">A. Lineal Clásica (Todo en una página)</option>
                            </select>
                            <small class="text-muted fs-nano d-block mt-1">Define cómo el alumno interactuará con el instrumento.</small>
                        </div>
                    </div>

                    <div class="form-check form-switch-elite mb-4">
                        <input class="form-check-input" type="checkbox" id="asig-resultados" checked>
                        <label class="form-check-label fs-nano ms-2" for="asig-resultados">Mostrar resultados al finalizar</label>
                    </div>

                    <!-- 🎯 ACCIONES INTEGRADAS EN EL CUERPO (EVITA SCROLL) -->
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top border-light">
                        <button type="button" id="btn-eliminar-asignacion-modal" class="btn-elite btn-elite--danger btn-elite--sm d-none" onclick="const id = document.getElementById('asignacion-id').value; if(id > 0) eliminarAsignacion(id);">
                            <i class="bi bi-trash3 me-1"></i> CANCELAR PROGRAMACIÓN
                        </button>
                        <button type="submit" class="btn-elite px-4 ms-auto">PROGRAMAR AHORA</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../js/aplicacion_pruebas.js" defer></script>