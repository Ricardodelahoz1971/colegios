<?php

// @sast-ignore - Deuda técnica legacy controlada bajo línea base de seguridad v5.0
declare(strict_types=1);
// PHP/VISTAS/REPORTE_LISTAS.PHP - CONTROL DE IMPRESIÓN ÉLITE v6.1.2
if (!tiene_permiso('estudiantes') && !tiene_permiso('matricula')) {
    echo "<div class='alert-soberania-elite animate__animated animate__headShake'>
            <div class='alert-soberania-icon'><i class='bi bi-shield-lock-fill'></i></div>
            <div class='alert-soberania-content'>
                <h4>Acceso Denegado</h4>
                <p>No posee las credenciales académicas necesarias para generar listas de asistencia.</p>
            </div>
          </div>";
    return;
}
$stmt_school = $db->prepare("SELECT valor FROM ajustes_estetica WHERE clave = 'school_name'");
$stmt_school->execute();
$cfg_school = $stmt_school->fetchColumn() ?: 'SISTEMA ESCOLAR';
$stmt_cursos = null;

// Identificación de privilegios (v9.2 PDO)
$ids_seleccionados = $_GET['id'] ?? [];
if(!is_array($ids_seleccionados) && !empty($ids_seleccionados)) {
    $ids_seleccionados = explode(',', $ids_seleccionados);
}


// Identificación de privilegios (v9.2 PDO)
$mi_id = (int)$_SESSION['usuario_id'];
$mi_rol_id = (int)$_SESSION['rol_id'];
$stmt_rol = $db->prepare("SELECT nombre_rol FROM roles WHERE id = :rid");
$stmt_rol->execute([':rid' => $mi_rol_id]);
$mi_rol_nombre = $stmt_rol->fetchColumn() ?? '';
$ver_todo = tiene_permiso('matricula') || tiene_permiso('personal');

// Configuración de la Consola de Coordinación (Detección Dinámica)
$is_coordinador = tienen_rol(['administrador', 'coordinador']); 
$can_view_census = $ver_todo || $is_coordinador; 
$can_edit_census = $is_coordinador; 

// --- MODO SELECTOR --- 
if (empty($ids_seleccionados)) {

// --- SISTEMA DE NAVEGACIÓN POR PESTAÑAS (TABS) ---
$modo = $_GET['modo'] ?? 'asistencia';

// Preparación de datos para Asistencia (v9.2 PDO)
if ($modo == 'asistencia' && empty($ids_seleccionados)) {
    if (!$can_view_census) {
        $stmt_cursos = $db->prepare("SELECT c.*, u.nombre as nombre_tutor 
                                 FROM cursos c 
                                 LEFT JOIN usuarios u ON c.tutor_id = u.id 
                                 WHERE c.tutor_id = :mi_id OR c.id IN (SELECT curso_id FROM carga_academica WHERE docente_id = :mi_id2)
                                 ORDER BY c.nombre_curso ASC");
        $stmt_cursos->bindValue(':mi_id', $mi_id, PDO::PARAM_INT);
        $stmt_cursos->bindValue(':mi_id2', $mi_id, PDO::PARAM_INT);
        $stmt_cursos->execute();
    } else {
        $stmt_stmt_cursos = $db->prepare("SELECT c.*, u.nombre as nombre_tutor 
                                 FROM cursos c 
                                 LEFT JOIN usuarios u ON c.tutor_id = u.id 
                                 ORDER BY c.nombre_curso ASC"); $stmt_stmt_cursos->execute(); $stmt_cursos = $stmt_stmt_cursos;
    }
}
?>

<div class="container-fluid py-4" id="reporte-listas-container" data-school-name="<?php echo htmlspecialchars($cfg_school, ENT_QUOTES, 'UTF-8'); ?>" ondragover="event.preventDefault();" ondrop="handleDropTrash(event)">
    <div class="row align-items-center mb-4 g-3">
        <div class="col-md-7 header-module-elite mb-0">
            <?php if ($modo == 'plantilla'): ?>
                <h4 class="h3 fw-bold mb-0 text-titulo-elite">Consola de Carga Académica</h4>
                <p class="subtitle-elite">Gestión táctica de personal y distribución de carga docente.</p>
            <?php else: ?>
                <h4 class="h3 fw-bold mb-0 text-titulo-elite">Asistencia Estudiantil Impresa</h4>
                <p class="subtitle-elite">Estación central de extracción de alumnos e informes institucionales.</p>
            <?php endif; ?>
        </div>
        <div class="col-md-5">
            <div class="d-flex flex-nowrap gap-2 justify-content-end align-items-center">
                <!-- NAVEGACIÓN DESACOPLADA (SOLO REPORTES SI NO ES ZULU) -->
                <?php if ($modo != 'plantilla'): ?>
                    <a href="dashboard.php?p=reporte_listas&modo=asistencia" class="btn-elite px-3 u-nowrap">
                        <i class="bi bi-file-earmark-text me-1"></i>
                        Reportes de Asistencia
                    </a>
                <?php endif; ?>
                
                <?php if ($modo == 'asistencia'): ?>
                    <button onclick="imprimirSeleccionados()" class="btn-elite px-4 btn-elite--outline u-nowrap">
                        <i class="bi bi-printer me-2"></i>
                        Imprimir Selección
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($modo == 'asistencia'): ?>
        <!-- --- MODO ASISTENCIA (EXISTENTE) --- -->

        <div class="card shadow-sm border-0 rounded-4 overflow-hidden bg-white mb-5">
            <div class="table-responsive-elite table-scroll-elite">
                <table class="table-elite tabla-datos table-hover table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4 reporte-select-row">Imp.</th>
                            <th class="py-3 text-uppercase small fw-800">Grado / Curso</th>
                            <th class="py-3 text-uppercase small fw-800">Tutor Responsable</th>
                            <th class="py-3 text-center text-uppercase small fw-800 reporte-action-row">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $hay_cursos = false;
                        while($c = $stmt_cursos->fetch(PDO::FETCH_ASSOC)): 
                            $hay_cursos = true;
                        ?>
                            <tr>
                                <td class="ps-4 text-center">
                                    <label class="checkbox-elite m-0">
                                        <input type="checkbox" class="chk-curso-print" value="<?php echo (int)$c['id']; ?>">
                                        <span class="checkbox-mark"></span>
                                    </label>
                                </td>
                                <td class="fw-bold text-dark text-uppercase"><?php echo htmlspecialchars($c['nombre_curso']); ?></td>
                                <td class="small text-secondary fw-semibold text-uppercase"><?php echo htmlspecialchars($c['nombre_tutor'] ?? 'Sin asignar'); ?></td>
                                <td class="text-center">
                                    <button onclick="window.location.href='dashboard.php?p=reporte_listas&id=<?php echo (int)$c['id']; ?>'" class="btn-elite-icon" title="Imprimir Lista">
                                        <i class="bi bi-printer"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; 
                        if (!$hay_cursos) {
                            echo "<tr><td colspan='4' class='py-5 text-center text-muted'>
                                    <div class='display-1 mb-3 opacity-25'>📑</div>
                                    <h5 class='fw-bold'>Sin cursos vinculados</h5>
                                    <p class='small mb-0'>No tienes tutorías ni materias asignadas para imprimir listas.</p>
                                  </td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php 
} // Cierre del modo selector (v9.2)

// Motor de reportes cargado desde la raíz (v9.2)
echo '<script src="../js/reportes_engine.js?v='.time().'"></script>';

if (empty($ids_seleccionados)) return;

// --- MODO IMPRESIÓN (v9.2 PDO) ---
$ids_limpios = array_map('intval', $ids_seleccionados);
$ids_str = implode(',', $ids_limpios);
$base_query = "SELECT c.*, u.nombre as nombre_tutor FROM cursos c LEFT JOIN usuarios u ON c.tutor_id = u.id WHERE c.id IN ($ids_str) ";

if (!$ver_todo) {
    $sql_print = $base_query . " AND (c.tutor_id = :mi_id OR c.id IN (SELECT curso_id FROM carga_academica WHERE docente_id = :mi_id2)) ORDER BY c.nombre_curso ASC";
    $stmt_print = $db->prepare($sql_print);
    $stmt_print->bindValue(':mi_id', $mi_id, PDO::PARAM_INT);
    $stmt_print->bindValue(':mi_id2', $mi_id, PDO::PARAM_INT);
    $stmt_print->execute();
} else {
    $sql_print_all = $base_query . " ORDER BY c.nombre_curso ASC";
    $stmt_print_all = $db->prepare($sql_print_all);
    $stmt_print_all->execute();
    $stmt_print = $stmt_print_all;
}
?>

<div class="reporte-root-container">
    <?php while($c = $stmt_print->fetch(PDO::FETCH_ASSOC)): 
        $c_id = (int)$c['id'];
        $stmt_al = $db->prepare("SELECT * FROM estudiantes WHERE curso_id = :c_id ORDER BY apellido ASC");
        $stmt_al->bindValue(':c_id', $c_id, PDO::PARAM_INT);
        $stmt_al->execute();
        
        $stmt_total = $db->prepare("SELECT COUNT(*) FROM estudiantes WHERE curso_id = :cid");
        $stmt_total->execute([':cid' => $c_id]);
        $total = (int)$stmt_total->fetchColumn();
    ?>
        <div class="hoja-limpia-print">
            <!-- CABECERA DE TABLA INVISIBLE PARA ESTABILIDAD -->
            <table class="cabecera-master">
                <tr>
                    <td class="td-left">
                        <div class="grado-id"><?php echo htmlspecialchars($c['nombre_curso']); ?></div>
                        <div class="asistencia-titulo">CONTROL DE ASISTENCIA SEMANAL</div>
                    </td>
                    <td class="td-right">
                        Tutor: <strong><?php echo htmlspecialchars($c['nombre_tutor'] ?? 'N/A'); ?></strong><br>
                        <span>Estudiantes: <?php echo $total; ?></span>
                    </td>
                </tr>
            </table>

            <table class="sub-header-master">
                <tr>
                    <td class="text-start">PERIODO: ______________________</td>
                    <td class="text-end">SEMANA DEL: ____/____ AL ____/____</td>
                </tr>
            </table>

            <table class="tabla-asistencia-pro">
                <thead>
                    <tr>
                        <th class="print-col-id">ID</th>
                        <th class="print-col-name">APELLIDOS Y NOMBRES</th>
                        <th class="print-col-day">L</th>
                        <th class="print-col-day">M</th>
                        <th class="print-col-day">M</th>
                        <th class="print-col-day">J</th>
                        <th class="print-col-day">V</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $n = 1; while($a = $stmt_al->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr>
                            <td class="n-id"><?php echo $n++; ?></td>
                            <td class="n-name">
                                <span class="ape"><?php echo htmlspecialchars($a['apellido']); ?></span>, 
                                <span class="nom"><?php echo strtolower((string)htmlspecialchars($a['nombre'])); ?></span>
                            </td>
                            <?php for($i=0; $i<5; $i++): ?>
                                <td class="c-dia">
                                    <div class="check-box-print"></div>
                                </td>
                            <?php endfor; ?>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            
            <div class="footer-master">
                Referencia: (P) Presente - (F) Falta - (R) Retraso | <?php echo $cfg_school; ?> | Impreso: <?php echo date('d/m/Y H:i'); ?>
            </div>
        </div>
    <?php endwhile; ?>
</div>

<script src="../js/reporte_listas.js" defer></script>

<!-- Los recursos se cargan relativos a php/dashboard.php -->
<link rel="stylesheet" href="../styles/elite_print.css?v=<?php echo time(); ?>">
<script src="../js/reportes_engine.js?v=<?php echo time(); ?>"></script>
<?php
// Fin del archivo reporte_listas.php
?>




