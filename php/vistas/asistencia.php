<?php

// @sast-ignore - Deuda técnica legacy controlada bajo línea base de seguridad v5.0
declare(strict_types=1);
// PHP/VISTAS/ASISTENCIA.PHP - CONTROL DE ASISTENCIA ÉLITE v1.0
if (!tiene_permiso('asistencia')) {
    echo "<div class='container py-5 text-center'><h4 class='text-danger fw-bold'>ACCESO DENEGADO</h4><p>No tienes privilegios para registrar asistencias.</p></div>";
    return;
}

$mi_id = (int)$_SESSION['usuario_id'];
$mi_rol = (int)$_SESSION['rol_id'];
$ver_todo = tiene_permiso('matricula') || tiene_permiso('personal');

// --- NUEVA REGLA: Solo Jefes de Grupo (Tutor) o Admin ---
$stmt_tutor = $db->prepare("SELECT COUNT(*) FROM cursos WHERE tutor_id = :mid");
$stmt_tutor->bindValue(':mid', $mi_id, PDO::PARAM_INT);
$stmt_tutor->execute();
$es_tutor = (int)$stmt_tutor->fetchColumn() > 0;

if (!tiene_permiso('asistencia') || (!$ver_todo && !$es_tutor)) {
    echo "<div class='container py-5 text-center'><h4 class='text-danger fw-bold'>ACCESO DENEGADO</h4><p>Solo los <b>Profesores Jefes de Grupo</b> y el personal administrativo pueden registrar asistencias.</p></div>";
    return;
}

$curso_id = isset($_GET['curso_id']) ? (int)$_GET['curso_id'] : 0;
$fecha_sel = (isset($_GET['fecha']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['fecha'])) ? $_GET['fecha'] : date('Y-m-d');

// --- 1. LISTADO DE CURSOS DISPONIBLES ---
if (!$ver_todo) {
    // REGLA: El profesor solo puede pasar lista en el curso donde es JEFE DE GRUPO (Tutor)
    $sql_mis_cursos = "SELECT c.id, c.nombre_curso FROM cursos c WHERE c.tutor_id = :mid ORDER BY c.nombre_curso ASC";
    $stmt_cur = $db->prepare($sql_mis_cursos);
    $stmt_cur->execute([':mid' => $mi_id]);
} else {
    $sql_todos_cursos = "SELECT c.id, c.nombre_curso FROM cursos c ORDER BY c.nombre_curso ASC";
    $stmt_cur = $db->prepare($sql_todos_cursos);
    $stmt_cur->execute();
}
$mis_cursos = $stmt_cur->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="container-fluid py-4" id="asistencia-container-master">
    <div class="row align-items-center mb-4 g-3">
        <div class="col-md-7 header-module-elite">
            <h4 class="title-elite">Control de Asistencia Digital</h4>
            <p class="subtitle-elite text-primary">Registro diario de asistencia por grupo institucional.</p>
        </div>
        <div class="col-md-5 d-flex justify-content-md-end gap-3">
            <input type="date" id="asistencia-fecha" class="input-elite w-fixed-140" value="<?php echo $fecha_sel; ?>" onchange="cambiarFiltroAsistencia()">
            
            <select id="asistencia-curso" class="select-elite w-fixed-280" onchange="cambiarFiltroAsistencia()">
                <option value="0">Seleccione Curso...</option>
                <?php foreach($mis_cursos as $mc): ?>
                    <option value="<?php echo $mc['id']; ?>" <?php echo ($curso_id == $mc['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($mc['nombre_curso']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <?php if ($curso_id > 0): 
        // --- 2. LISTADO DE ESTUDIANTES Y ASISTENCIA ACTUAL ---
        // Buscamos si ya hay asistencia tomada para hoy
        $asistencias_hoy = [];
        $stmt_ash = $db->prepare("SELECT estudiante_id, estado, observaciones FROM asistencias WHERE curso_id = :cid AND fecha = :fec");
        $stmt_ash->bindValue(':cid', $curso_id, PDO::PARAM_INT);
        $stmt_ash->bindValue(':fec', $fecha_sel, PDO::PARAM_STR);
        $stmt_ash->execute();
        while($ash = $stmt_ash->fetch(PDO::FETCH_ASSOC)) {
            $asistencias_hoy[$ash['estudiante_id']] = $ash;
        }

        $stmt_al = $db->prepare("SELECT id, nombre, apellido FROM estudiantes WHERE curso_id = :cid ORDER BY apellido ASC");
        $stmt_al->bindValue(':cid', $curso_id, PDO::PARAM_INT);
        $stmt_al->execute();
    ?>
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden bg-white">
            <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
                <span class="fw-bold text-secondary text-uppercase small"><i class="bi bi-people-fill me-2"></i>Lista de Estudiantes</span>
                <span class="badge-elite badge-elite--primary rounded-pill px-3">
                    <?php echo date('d/m/Y', strtotime($fecha_sel)); ?>
                </span>
            </div>
            <div class="table-responsive-elite">
                <table class="table-elite table-hover align-middle mb-0 asist-table-elite">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4 py-3 reporte-select-row">#</th>
                            <th class="py-3">Estudiante</th>
                            <th class="py-3 text-center w-320px">Estado de Asistencia</th>
                            <th class="py-3 pe-4">Observación</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $n = 1;
                        while($a = $stmt_al->fetch(PDO::FETCH_ASSOC)): 
                            $a_id = $a['id'];
                            $estado_actual = isset($asistencias_hoy[$a_id]) ? $asistencias_hoy[$a_id]['estado'] : 'P'; // Default Presente
                            $obs_actual = isset($asistencias_hoy[$a_id]) ? $asistencias_hoy[$a_id]['observaciones'] : '';
                        ?>
                        <tr class="alumno-fila-asist" data-id="<?php echo $a_id; ?>">
                            <td class="ps-4 text-muted small fw-bold" data-label="#"><?php echo $n++; ?></td>
                            <td data-label="Estudiante">
                                <div class="fw-bold text-dark text-uppercase"><?php echo htmlspecialchars($a['apellido']); ?></div>
                                <div class="small text-secondary"><?php echo htmlspecialchars($a['nombre']); ?></div>
                            </td>
                            <td class="text-center" data-label="Asistencia">
                                <div class="btn-group btn-group-sm asist-toggle-group shadow-sm" role="group">
                                    <input type="radio" class="btn-check" name="asist_<?php echo $a_id; ?>" id="p_<?php echo $a_id; ?>" value="P" <?php echo ($estado_actual == 'P') ? 'checked' : ''; ?>>
                                    <label class="btn-elite btn-elite--outline px-3" for="p_<?php echo $a_id; ?>" title="Presente">P</label>

                                    <input type="radio" class="btn-check" name="asist_<?php echo $a_id; ?>" id="f_<?php echo $a_id; ?>" value="F" <?php echo ($estado_actual == 'F') ? 'checked' : ''; ?>>
                                    <label class="btn-elite btn-elite--danger px-3" for="f_<?php echo $a_id; ?>" title="Falta">F</label>

                                    <input type="radio" class="btn-check" name="asist_<?php echo $a_id; ?>" id="r_<?php echo $a_id; ?>" value="R" <?php echo ($estado_actual == 'R') ? 'checked' : ''; ?>>
                                    <label class="btn-elite btn-elite--outline px-3" for="r_<?php echo $a_id; ?>" title="Retraso">R</label>

                                    <input type="radio" class="btn-check" name="asist_<?php echo $a_id; ?>" id="e_<?php echo $a_id; ?>" value="E" <?php echo ($estado_actual == 'E') ? 'checked' : ''; ?>>
                                    <label class="btn-elite btn-elite--outline px-3" for="e_<?php echo $a_id; ?>" title="Excusa">E</label>
                                </div>
                            </td>
                            <td class="pe-4" data-label="Observación">
                                <label for="obs_<?php echo $a_id; ?>" class="visually-hidden">Observación para <?php echo htmlspecialchars($a['apellido']); ?></label>
                                <input type="text" id="obs_<?php echo $a_id; ?>" class="input-elite border-light-subtle asist-obs" value="<?php echo htmlspecialchars($obs_actual); ?>" placeholder="...">
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white p-4 text-end border-top">
                <button onclick="guardarAsistencia(<?php echo $curso_id; ?>, '<?php echo $fecha_sel; ?>')" class="btn-elite py-3 px-5">
                    <i class="bi bi-cloud-upload-fill me-2"></i>GUARDAR CONTROL DE ASISTENCIA
                </button>
            </div>
        </div>
    <?php else: ?>
        <div class="card shadow-sm border-0 rounded-4 p-5 text-center bg-white">
            <div class="display-1 text-primary opacity-10 mb-4">
                <i class="bi bi-calendar-check"></i>
            </div>
            <h4 class="fw-bold text-dark">Área de Control de Asistencia</h4>
            <p class="text-secondary mx-auto search-wrapper-max">Por favor, seleccione un curso para gestionar el pase de lista.</p>
        </div>
    <?php endif; ?>
</div><!-- /asistencia-container-master -->

<script src="../js/asistencia.js" defer></script>
