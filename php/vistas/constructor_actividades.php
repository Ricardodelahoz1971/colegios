<?php
declare(strict_types=1);
// PHP/VISTAS/CONSTRUCTOR_ACTIVIDADES.PHP - GESTOR DE ACTIVIDADES Y RÚBRICAS v2.0
if (!tiene_permiso('evaluacion')) {
    echo "Acceso denegado."; return;
}

$mi_id = (int)$_SESSION['usuario_id'];

// Carga de Cursos
$stmt_c = $db->prepare("SELECT id, nombre_curso FROM cursos WHERE tutor_id = ? OR id IN (SELECT curso_id FROM carga_academica WHERE docente_id = ?) ORDER BY nombre_curso");
$stmt_c->execute([$mi_id, $mi_id]);
$cursos = $stmt_c->fetchAll();

// Carga de Materias (Especialidades)
$stmt_m = $db->prepare("SELECT DISTINCT e.id, e.nombre_especialidad 
                        FROM especialidades e 
                        JOIN carga_academica ca ON e.id = ca.especialidad_id 
                        WHERE ca.docente_id = ? ORDER BY e.nombre_especialidad");
$stmt_m->execute([$mi_id]);
$materias = $stmt_m->fetchAll();

// 🏛️ CARGAR ESCALA INSTITUCIONAL ACTIVA
$stmt_esc = $db->prepare("SELECT * FROM eval_config_escala WHERE activo = 1 LIMIT 1"); $stmt_esc->execute();
$escala = $stmt_esc->fetch(PDO::FETCH_ASSOC) ?: [
    'nota_minima' => 1.0,
    'nota_maxima' => 5.0,
    'nota_aprobacion' => 3.0,
    'rango_superior_min' => 4.60,
    'rango_alto_min' => 4.00,
    'rango_basico_min' => 3.00
];

// 🏛️ CARGAR DIMENSIONES ACADÉMICAS (ARES CLASES NOTA)
$stmt_dims = $db->prepare("SELECT id, nombre, peso_global FROM ares_clases_nota WHERE estado = 1 ORDER BY id ASC");
$stmt_dims->execute();
$dimensiones = $stmt_dims->fetchAll(PDO::FETCH_ASSOC);

// Captura de Parámetros de Recuperación
$ambito = isset($_GET['ambito']) ? trim($_GET['ambito']) : 'estandar';
$origen_id = isset($_GET['origen_id']) ? (int)$_GET['origen_id'] : 0;
$curso_id_param = isset($_GET['curso_id']) ? (int)$_GET['curso_id'] : 0;
$especialidad_id_param = isset($_GET['especialidad_id']) ? (int)$_GET['especialidad_id'] : 0;

$nombre_actividad_origen = '';
if ($origen_id > 0) {
    $stmt_orig = $db->prepare("SELECT titulo FROM ares_actividades WHERE id = ?");
    $stmt_orig->execute([$origen_id]);
    $nombre_actividad_origen = $stmt_orig->fetchColumn() ?: '';
}
?>

<div class="container-fluid py-4 animate__animated animate__fadeIn">
    <!-- CABECERA -->
    <div class="row align-items-center mb-4 g-3">
        <div class="col-md-7 header-module-elite mb-0">
            <h4 class="h3 fw-bold mb-0 text-titulo-elite">GESTOR DE ACTIVIDADES</h4>
            <p class="subtitle-elite">Construya rúbricas y criterios de evaluación dinámicos para sus asignaturas.</p>
        
            <nav class="breadcrumb-elite" aria-label="Ruta de navegación">
                <a href="javascript:void(0)" onclick="navegarModulo('inicio')" class="breadcrumb-link-elite"><i class="bi bi-house-door me-1"></i>Inicio</a>
                <i class="bi bi-chevron-right breadcrumb-separator-elite"></i>
                <span class="breadcrumb-item-elite">Evaluación</span>
                <i class="bi bi-chevron-right breadcrumb-separator-elite"></i>
                <span class="breadcrumb-current-elite">Gestor de Actividades</span>
            </nav></div>
    </div>

    <div class="row g-4">
        <!-- FORMULARIO CREACIÓN -->
        <div class="col-xl-4">
            <div class="card card-elite border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom p-4">
                    <h5 class="fw-bold mb-0 text-dark fs-nano text-uppercase" id="titulo-panel-actividad"><i class="bi bi-magic text-primary me-2"></i> Crear Actividad</h5>
                </div>
                <div class="card-body p-4">
                    <form id="form-actividad" onsubmit="guardarActividad(event)" novalidate>
                        <!-- ID DE ACTIVIDAD PARA EDICIÓN -->
                        <input type="hidden" id="act-id" name="id" value="">
                        <!-- PARÁMETROS DE AMBITO Y RECUPERACIÓN -->
                        <input type="hidden" id="act-ambito" name="ambito" value="<?php echo e($ambito); ?>">
                        <input type="hidden" id="act-recupera-actividad-id" name="recupera_actividad_id" value="<?php echo e((string)$origen_id); ?>">

                        <?php if ($ambito === 'recuperacion' && $origen_id > 0): ?>
                            <div class="alert-recuperacion-elite p-3 mb-3 animate__animated animate__fadeIn">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <div class="avatar-icono-recuperacion">
                                        <i class="bi bi-arrow-up"></i>
                                    </div>
                                    <strong class="text-uppercase letter-spacing-1 fs-micro">Actividad de Recuperación</strong>
                                </div>
                                <span class="fs-micro d-block lh-sm opacity-90">
                                    Esta actividad está destinada exclusivamente a la nivelación de los estudiantes que reprobaron la actividad: 
                                    <strong><?php echo htmlspecialchars($nombre_actividad_origen); ?></strong>.
                                </span>
                            </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="act-titulo" class="form-label fs-nano text-muted fw-bold text-uppercase">Título de la Actividad</label>
                            <input type="text" id="act-titulo" name="titulo" class="input-elite" placeholder="Ej: Ensayo sobre la Revolución" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fs-nano text-muted fw-bold text-uppercase mb-2 d-block">PROPÓSITO DE LA ACTIVIDAD</label>
                            <div class="mb-3">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="ambito" id="ambito-estandar" value="estandar" checked onchange="toggleAmbitoRecuperacion()">
                                    <label class="form-check-label text-dark fs-nano fw-bold cursor-pointer" for="ambito-estandar">
                                        ESTÁNDAR
                                    </label>
                                </div>
                                <div class="form-check form-check-inline ms-4">
                                    <input class="form-check-input" type="radio" name="ambito" id="ambito-recuperacion" value="recuperacion" onchange="toggleAmbitoRecuperacion()">
                                    <label class="form-check-label text-dark fs-nano fw-bold cursor-pointer" for="ambito-recuperacion">
                                        RECUPERACIÓN
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Selector dinámico de actividad origen a recuperar (oculto por defecto) -->
                        <div class="mb-3 d-none animate__animated animate__fadeIn" id="contenedor-actividad-origen">
                            <label for="act-origen" class="form-label fs-nano text-muted fw-bold text-uppercase">Actividad de Origen a Recuperar</label>
                            <select id="act-origen" class="select-elite" onchange="actualizarRecuperaId(this.value)">
                                <option value="">[Cargando actividades...]</option>
                            </select>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label for="act-curso" class="form-label fs-nano text-muted fw-bold text-uppercase">Grupo / Curso</label>
                                <select id="act-curso" name="curso_id" class="select-elite" onchange="cargarActividadesOrigen()" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach($cursos as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['nombre_curso']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label for="act-materia" class="form-label fs-nano text-muted fw-bold text-uppercase">Materia</label>
                                <select id="act-materia" name="especialidad_id" class="select-elite" onchange="cargarActividadesOrigen()" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach($materias as $m): ?>
                                    <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['nombre_especialidad']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="act-clase-nota" class="form-label fs-nano text-muted fw-bold text-uppercase">Dimensión de Aprendizaje (Clase de Nota)</label>
                            <select id="act-clase-nota" name="clase_nota_id" class="select-elite" required>
                                <option value="">Seleccione...</option>
                                <?php foreach($dimensiones as $d): ?>
                                <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['nombre']); ?> (<?php echo $d['peso_global']; ?>%)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fs-nano text-muted fw-bold text-uppercase mb-2 d-block">Modo de Calificación</label>
                            <div class="d-flex flex-column gap-2">
                                <div class="border rounded-4 p-3 mb-2 bg-white transition-all shadow-sm cursor-pointer" id="container-directo">
                                    <label for="modo_directo" class="form-check d-flex align-items-start gap-3 mb-0 cursor-pointer">
                                        <input class="form-check-input flex-shrink-0" type="radio" name="modo_eval" id="modo_directo" value="directo" checked onchange="toggleRubricaMode()">
                                        <span class="flex-grow-1">
                                            <span class="fw-bold d-block text-dark">Nota Directa Única</span>
                                            <span class="text-muted small">El docente ingresa una sola calificación numérica final.</span>
                                        </span>
                                    </label>
                                </div>
                                <div class="border rounded-4 p-3 mb-2 bg-white transition-all shadow-sm cursor-pointer" id="container-rubrica">
                                    <label for="modo_rubrica" class="form-check d-flex align-items-start gap-3 mb-0 cursor-pointer">
                                        <input class="form-check-input flex-shrink-0" type="radio" name="modo_eval" id="modo_rubrica" value="rubrica" onchange="toggleRubricaMode()">
                                        <span class="flex-grow-1">
                                            <span class="fw-bold d-block text-primary">Rúbrica Multicriterio</span>
                                            <span class="text-primary small opacity-75">El docente califica parámetros específicos (ej: Redacción, Diseño).</span>
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- CONSTRUCTOR DE CRITERIOS (Oculto en Directo) -->
                        <div id="panel-criterios" class="d-none bg-white p-3 rounded-4 mb-4 border border-primary border-opacity-25 shadow-sm">
                            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                                <h6 class="fw-bold mb-0 text-primary fs-nano text-uppercase"><i class="bi bi-list-stars me-2"></i>Criterios de Evaluación</h6>
                                <button type="button" class="btn-elite-icon btn-elite-icon--sm btn-elite-icon--primary" onclick="agregarCriterio()"><i class="bi bi-plus-lg"></i></button>
                            </div>
                            <div id="lista-criterios" class="d-flex flex-column gap-2">
                                <!-- Criterios inyectados por JS -->
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" id="btn-registrar-actividad" class="btn-elite btn-elite--primary flex-grow-1 py-3 shadow-sm">
                                <i class="bi bi-save2 me-2"></i> REGISTRAR ACTIVIDAD
                            </button>
                            <button type="button" id="btn-cancelar-edicion" class="btn-elite btn-elite--outline d-none" onclick="cancelarEdicion()">
                                CANCELAR
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- LISTA DE ACTIVIDADES -->
        <div class="col-xl-8">
            <div class="card card-elite border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom p-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark fs-nano text-uppercase"><i class="bi bi-list-task text-primary me-2"></i> Mis Actividades Creadas</h5>
                    <button type="button" class="btn-elite btn-elite--primary px-3 fs-nano" onclick="cancelarEdicion(); document.getElementById('act-titulo').focus();" title="Nueva Actividad"><i class="bi bi-plus-lg me-2"></i> NUEVA ACTIVIDAD</button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table-elite table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4 py-3 fs-nano text-uppercase fw-800">Actividad Académica</th>
                                    <th class="py-3 fs-nano text-uppercase fw-800 text-center">Dimensión</th>
                                    <th class="py-3 fs-nano text-uppercase fw-800 text-center">Modo de Evaluación</th>
                                    <th class="pe-4 py-3 fs-nano text-uppercase fw-800 text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tabla-actividades-body">
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        Cargando actividades...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../js/constructor_actividades.js?v=1787418374" defer></script>