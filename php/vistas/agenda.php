<?php
declare(strict_types=1);
// PHP/VISTAS/AGENDA.PHP - AGENDA ACADÉMICA POR CURSO (ESTILO ÉLITE)
if (!tiene_permiso('agenda')) {
    echo "<div class='container py-5 text-center'><h4 class='text-danger fw-bold'>ACCESO DENEGADO</h4></div>";
    return;
}

$usuario_id = $_SESSION['usuario_id'];
$rol = $_SESSION['rol_nombre'] ?? '';

$es_estudiante = ($rol === 'Estudiante');
$es_docente = ($rol === 'Profesor' || $rol === 'Docente');
$es_admin = tiene_permiso('matricula') || tiene_permiso('personal');

// Cargar Cursos y Materias en Arrays para serialización JSON
$cursos_data = [];
if ($es_admin) {
    $stmt_c_admin = $db->prepare("SELECT id, nombre_curso FROM cursos ORDER BY nombre_curso ASC");
    $stmt_c_admin->execute();
    $cursos_data = $stmt_c_admin->fetchAll(PDO::FETCH_ASSOC);
} else if ($es_docente) {
    $stmt_c = $db->prepare("SELECT DISTINCT c.id, c.nombre_curso FROM cursos c 
                       LEFT JOIN carga_academica ca ON c.id = ca.curso_id 
                       WHERE (ca.docente_id = :uid1 OR c.tutor_id = :uid2)
                       ORDER BY c.nombre_curso ASC");
    $stmt_c->execute([':uid1' => (int)$usuario_id, ':uid2' => (int)$usuario_id]);
    $cursos_data = $stmt_c->fetchAll(PDO::FETCH_ASSOC);
}

$stmt_m = $db->prepare("SELECT id, nombre_especialidad FROM especialidades ORDER BY nombre_especialidad ASC");
$stmt_m->execute();
$materias_data = $stmt_m->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Los datos se inyectan ahora directamente en el motor del módulo para evitar colisiones globales -->

<div class="container-fluid py-4" id="agenda-modulo-container" data-agenda-cfg="<?php echo htmlspecialchars(json_encode([ 'cursos' => $cursos_data, 'materias' => $materias_data, 'esEstudiante' => $es_estudiante, 'esDocente' => $es_docente ]), ENT_QUOTES, 'UTF-8'); ?>">
    <div class="row align-items-center mb-4 g-3">
        <div class="col-md-7 header-module-elite">
            <h4 class="h3 fw-bold mb-0 text-titulo-elite">Agenda Académica</h4>
            <p class="subtitle-elite">Gestión de tareas, trabajos y compromisos académicos por curso.</p>
        </div>
        <div class="col-md-5 text-end d-flex justify-content-end align-items-center gap-2">
            <?php if ($es_docente): ?>
                <button onclick="nuevaTarea()" class="btn-elite btn-elite--sm btn-elite--primary shadow-sm px-elite-1-5">
                    <i class="bi bi-plus-lg margin-inline-end-2"></i>Asignar Tarea
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filtros (Para Docentes/Admin) -->
    <?php if (!$es_estudiante): ?>
    <div class="card-elite-glass mb-4">
        <div class="card-body p-3">
            <div class="row g-3 align-items-center">
                <div class="col-md-4">
                    <label class="small fw-bold text-muted mb-1 text-uppercase ps-elite-0-5">Filtrar por Curso</label>
                    <select id="filtro-curso" class="select-elite" onchange="cargarAgenda()">
                        <option value="0">Todos mis cursos...</option>
                        <?php foreach ($cursos_data as $c): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['nombre_curso']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Contenedor de Agenda -->
    <div id="agenda-container" class="row g-4">
        <div class="col-12 text-center py-5">
            <div class="loader-elite-orbital"></div>
            <p class="mt-2 text-muted small fw-bold">Sincronizando compromisos escolares...</p>
        </div>
    </div>
</div>

<script src="../js/agenda.js" defer></script>




