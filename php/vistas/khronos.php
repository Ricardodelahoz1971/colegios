<?php

// @sast-ignore - Deuda técnica legacy controlada bajo línea base de seguridad v5.0
declare(strict_types=1);
// PHP/VISTAS/KHRONOS.PHP - CENTRO DE MANDO DE HORARIOS
// Versión 1.3 - Sincronizada con Nivel y Temas

$user_id = $_SESSION['usuario_id'] ?? 0;
$mi_rol = $_SESSION['rol_id'] ?? 0;

// 3. SELECCIÓN DE CURSO (Contexto)
$curso_id = (int)($_GET['curso_id'] ?? 0);

$jornada_curso = 'Mañana';
$nivel_id = 0;
if ($curso_id > 0) {
    $stmt_sel = $db->prepare("SELECT nivel_id, jornada FROM cursos WHERE id = :cid");
    $stmt_sel->bindValue(':cid', $curso_id, PDO::PARAM_INT);
    $stmt_sel->execute();
    $curso_sel = $stmt_sel->fetch(PDO::FETCH_ASSOC);
    $nivel_id = (int)($curso_sel['nivel_id'] ?? 0);
    $jornada_curso = $curso_sel['jornada'] ?? 'Mañana';
}

// 1. CARGAR CONFIGURACIÓN MAESTRA
$stmt_c = $db->prepare("SELECT * FROM ajustes_estetica WHERE clave LIKE 'khronos%' OR clave = 'brand_color'");
$stmt_c->execute();
$cfg = [];
while ($row = $stmt_c->fetch(PDO::FETCH_ASSOC)) { $cfg[$row['clave']] = $row['valor']; }

$obtener_cfg = function($clave, $jornada) use ($cfg) {
    $suffix = strtolower(str_replace([' ', 'á', 'é', 'í', 'ó', 'ú'], ['', 'a', 'e', 'i', 'o', 'u'], $jornada));
    $clave_jornada = $clave . '_' . $suffix;
    if (isset($cfg[$clave_jornada]) && $cfg[$clave_jornada] !== '') {
        return $cfg[$clave_jornada];
    }
    return $cfg[$clave] ?? null;
};

// 2. PARÁMETROS DE TIEMPO RESUELTOS POR JORNADA
$h_inicio = $obtener_cfg('khronos_inicio', $jornada_curso) ?? '07:00';
$h_duracion = (int)($obtener_cfg('khronos_duracion', $jornada_curso) ?? 55);
$h_max = (int)($obtener_cfg('khronos_max_horas', $jornada_curso) ?? 7);
$dias_lab = explode(',', $obtener_cfg('khronos_dias', $jornada_curso) ?? 'lun,mar,mie,jue,vie');
$desc1_h = (int)($obtener_cfg('khronos_descanso_h', $jornada_curso) ?? 0);
$desc1_m = (int)($obtener_cfg('khronos_descanso_m', $jornada_curso) ?? 0);
$desc2_h = (int)($obtener_cfg('khronos_descanso2_h', $jornada_curso) ?? 0);
$desc2_m = (int)($obtener_cfg('khronos_descanso2_m', $jornada_curso) ?? 0);

$stmt_cursos_res = $db->prepare("SELECT id, nombre_curso, nivel_id, jornada FROM cursos ORDER BY nombre_curso ASC");
$stmt_cursos_res->execute();
$cursos_res = $stmt_cursos_res;

// 4. CARGA ACADÉMICA DEL CURSO (Si hay seleccionado)
$carga_pendiente = [];
if ($curso_id > 0) {

    $stmt_p = $db->prepare("SELECT ca.especialidad_id, ca.docente_id, u.nombre as docente, e.nombre_especialidad 
                        FROM carga_academica ca 
                        JOIN usuarios u ON ca.docente_id = u.id 
                        JOIN especialidades e ON ca.especialidad_id = e.id 
                        WHERE ca.curso_id = :cid");
    $stmt_p->bindValue(':cid', $curso_id, PDO::PARAM_INT);
    $stmt_p->execute();

    while($cp = $stmt_p->fetch(PDO::FETCH_ASSOC)) {
        // Intensidad Real por Nivel (v9.2 PDO)
        $stmt_int = $db->prepare("SELECT intensidad_horaria FROM zulu_plan_maestro 
                                 WHERE especialidad_id = :eid 
                                 AND (nivel_nombre = :niv OR nivel_nombre = 'GLOBAL') 
                                 LIMIT 1");
        $stmt_int->bindValue(':eid', $cp['especialidad_id'], PDO::PARAM_INT);
        $stmt_int->bindValue(':niv', $nivel_id, PDO::PARAM_STR);
        $stmt_int->execute();
        $int = (int)$stmt_int->fetchColumn() ?: 4;
        
        $stmt_asig = $db->prepare("SELECT COUNT(*) FROM khronos_horarios 
                                   WHERE curso_id = :cid 
                                   AND especialidad_id = :eid");
        $stmt_asig->bindValue(':cid', $curso_id, PDO::PARAM_INT);
        $stmt_asig->bindValue(':eid', $cp['especialidad_id'], PDO::PARAM_INT);
        $stmt_asig->execute();
        $asignadas = (int)$stmt_asig->fetchColumn();

        $cp['faltantes'] = max(0, $int - $asignadas);
        $cp['intensidad'] = $int;
        if ($cp['faltantes'] > 0 || $asignadas > 0) { 
            $carga_pendiente[] = $cp;
        }
    }
    
    // ORDENAMIENTO TÁCTICO: Faltantes arriba, Completados abajo, luego Alfabético
    usort($carga_pendiente, function($a, $b) {
        if ($a['faltantes'] > 0 && $b['faltantes'] == 0) return -1;
        if ($a['faltantes'] == 0 && $b['faltantes'] > 0) return 1;
        return strcmp($a['nombre_especialidad'], $b['nombre_especialidad']);
    });
}

// 5. CARGAR HORARIO ACTUAL
$horario_actual = [];
if ($curso_id > 0) {
    $stmt_h = $db->prepare("SELECT h.*, e.nombre_especialidad, u.nombre as docente 
                        FROM khronos_horarios h
                        LEFT JOIN especialidades e ON h.especialidad_id = e.id
                        LEFT JOIN usuarios u ON h.docente_id = u.id
                        WHERE h.curso_id = :cid");
    $stmt_h->bindValue(':cid', $curso_id, PDO::PARAM_INT);
    $stmt_h->execute();
    while($h = $stmt_h->fetch(PDO::FETCH_ASSOC)) {
        $horario_actual[$h['dia_semana']][$h['hora_numero']] = $h;
    }
}
?>


<div class="khronos-container animate__animated animate__fadeIn p-3" id="khronos-container" data-curso-id="<?php echo $curso_id; ?>">
    <!-- MIGA DE PAN (BREADCRUMB ELITE) -->
    <div class="mb-3">
        <nav class="breadcrumb-elite" aria-label="Ruta de navegación">
            <a href="javascript:void(0)" onclick="navegarModulo('inicio')" class="breadcrumb-link-elite"><i class="bi bi-house-door me-1"></i>Inicio</a>
            <i class="bi bi-chevron-right breadcrumb-separator-elite"></i>
            <span class="breadcrumb-item-elite">Académico</span>
            <i class="bi bi-chevron-right breadcrumb-separator-elite"></i>
            <span class="breadcrumb-current-elite">Horario Escolar</span>
        </nav>
    </div>

    <!-- BARRA DE CONTROL SUPERIOR (Soberanía Élite Compacta) -->
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-2 gap-3">
        <div class="header-module-elite mb-0">
            <h4 class="h4 fw-bold mb-0 text-titulo-elite">Khronos Engine</h4>
            <p class="fs-nano opacity-75 mb-0">Sincronización por niveles y temas.</p>
        </div>

        <div class="d-flex flex-wrap gap-2 align-items-center">
            <div class="khronos-selector-wrapper khronos-selector-width">
                <select class="select-elite-sm fw-bold py-1" id="selector-curso-khronos" onchange="cambiarCursoKhronos(this.value)">
                    <option value="0">--- CURSO ---</option>
                    <?php 
                    $stmt_cursos = $db->prepare("SELECT id, nombre_curso, nivel_id, jornada FROM cursos ORDER BY nombre_curso ASC");
                    $stmt_cursos->execute();
                    while($c = $stmt_cursos->fetch(PDO::FETCH_ASSOC)): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo ($curso_id == $c['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['nombre_curso'] . ' (' . ($c['jornada'] ?? 'Mañana') . ')'); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <?php if ($curso_id > 0): ?>
                <button class="btn-elite btn-elite--sm btn-elite--primary py-1" onclick="generarAutomatico(<?php echo $curso_id; ?>)">
                    <i class="bi bi-magic me-1"></i>AUTO
                </button>
                <button class="btn-elite btn-elite--sm btn-elite--danger py-1" onclick="limpiarHorario(<?php echo $curso_id; ?>)">
                    <i class="bi bi-trash3 me-1"></i>LIMPIAR
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-4 khronos-main-row">
        <!-- ÁREA DE LA CUADRÍCULA -->
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden khronos-main-card">
                <div class="table-responsive-elite">
                    <table class="table-elite table-bordered mb-0 khronos-table text-center align-middle">
                        <thead>
                            <tr>
                                <th class="py-3 border-0 khronos-th-hour">HORA</th>
                                <?php foreach($dias_lab as $dia): 
                                    $sigla = substr(strtoupper($dia), 0, 2); // Tomamos solo 2 letras
                                ?>
                                    <th class="py-2 border-0"><?php echo $sigla; ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $current_h = $h_inicio;
                            for($i=1; $i<=$h_max; $i++): 
                                [$hour, $min] = explode(':', $current_h);
                                $total_m = (int)$min + $h_duracion;
                                $end_h = sprintf("%02d:%02d", $hour + floor($total_m/60), $total_m % 60);
                            ?>
                                <tr>
                                    <td class="khronos-td-hour-label">
                                        <div class="fs-nano text-uppercase opacity-50 mb-1">Bloque <?php echo $i; ?></div>
                                        <div class="text-primary-gradient"><?php echo $current_h; ?></div>
                                        <div class="fs-nano opacity-75 mt-1"><?php echo $end_h; ?></div>
                                    </td>
                                    <?php foreach($dias_lab as $dia): 
                                        $es_fin_de_semana = ($dia === 'sab' || $dia === 'dom');
                                        $slot = $horario_actual[$dia][$i] ?? null;
                                        $es_evento = !empty($slot['evento_nombre']);
                                        $nombre_actual = $es_evento ? addslashes($slot['evento_nombre']) : '';
                                    ?>
                                        <td class="khronos-slot p-0-5" 
                                            data-dia="<?php echo $dia; ?>" data-hora="<?php echo $i; ?>"
                                            data-inicio="<?php echo $current_h; ?>" data-fin="<?php echo $end_h; ?>"
                                            onclick="<?php echo $es_fin_de_semana ? "window.gestionarEventoExtracurricular('$dia', $i, '$nombre_actual')" : ""; ?>"
                                            ondragover="allowDropKhronos(event)" ondrop="dropKhronos(event)">
                                            <?php if ($slot): 
                                                $es_evento = !empty($slot['evento_nombre']);
                                            ?>
                                                <div class="khronos-item p-1-5 rounded-3 animate__animated animate__zoomIn text-start khronos-item-card <?php echo $es_evento ? 'khronos-event-card' : ''; ?>" 
                                                     draggable="true" ondragstart="dragKhronos(event)"
                                                     data-id="<?php echo $slot['id']; ?>"
                                                     data-docente-id="<?php echo $slot['docente_id'] ?? 0; ?>"
                                                     data-especialidad-id="<?php echo $slot['especialidad_id'] ?? 0; ?>"
                                                     data-ori-dia="<?php echo $dia; ?>" data-ori-hora="<?php echo $i; ?>">
                                                    
                                                    <div class="khronos-item-header">
                                                        <?php if ($es_evento): ?>
                                                            <h6 class="mb-0 fw-bold fs-nano text-uppercase text-accent"><i class="bi bi-star-fill me-1"></i> EVENTO</h6>
                                                        <?php else: ?>
                                                            <h6 class="khronos-item-title"><?php echo htmlspecialchars($slot['nombre_especialidad'] ?? ''); ?></h6>
                                                        <?php endif; ?>
                                                        
                                                        <button class="khronos-btn-delete" onclick="event.stopPropagation(); window.eliminarSlot(<?php echo $slot['id']; ?>, <?php echo $es_evento ? 'true' : 'false'; ?>)">
                                                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                                        </button>
                                                    </div>

                                                    <div class="fs-nano text-secondary khronos-item-teacher opacity-75">
                                                        <?php echo htmlspecialchars($es_evento ? $slot['evento_nombre'] : ($slot['docente'] ?? '')); ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>

                                <?php 
                                $current_h = $end_h; 
                                $inyectarDescanso = function($horaT, $minT, $label, $icon) use (&$current_h, $i, $dias_lab) {
                                    if ($i == $horaT && $minT > 0) {
                                        [$hh, $mm] = explode(':', $current_h);
                                        $total_d = (int)$mm + $minT;
                                        $desc_end = sprintf("%02d:%02d", $hh + floor($total_d/60), $total_d % 60);
                                        $prev_h = $current_h; $current_h = $desc_end;
                                        ?>
                                        <tr class="khronos-row-break">
                                            <td colspan="<?php echo count($dias_lab) + 1; ?>" class="py-2 border-0">
                                                <div class="d-flex align-items-center justify-content-center gap-3">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <?php echo $icon; ?>
                                                        <span class="receso-label text-uppercase"><?php echo $label; ?></span>
                                                    </div>
                                                    <span class="badge-elite badge-elite--primary shadow-sm">
                                                        <?php echo $prev_h; ?> - <?php echo $desc_end; ?>
                                                    </span>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                };
                                $cup_icon = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" class="text-primary" stroke="currentColor" stroke-width="2.5"><path d="M18 8h1a4 4 0 0 1 0 8h-1"></path><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path><line x1="6" y1="1" x2="6" y2="4"></line><line x1="10" y1="1" x2="10" y2="4"></line><line x1="14" y1="1" x2="10" y2="4"></line></svg>';
                                $inyectarDescanso($desc1_h, $desc1_m, "RECREO 01 ({$desc1_m} MIN)", $cup_icon);
                                $inyectarDescanso($desc2_h, $desc2_m, "RECREO 02 ({$desc2_m} MIN)", $cup_icon);
                                ?>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- SIDEBAR DE CARGA -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 sticky-top khronos-sidebar-card">
                <div class="card-header bg-transparent border-bottom p-3">
                    <h6 class="fw-bold mb-0 khronos-title">
                        <svg class="me-2 text-primary" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                        Carga Pendiente
                    </h6>
                </div>
                <div class="card-body p-3 overflow-auto custom-scroll khronos-sidebar-body">
                    <?php if (empty($carga_pendiente)): ?>
                        <div class="text-center p-4">
                            <p class="text-secondary small mb-0">No hay carga pendiente detectada.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($carga_pendiente as $cp): 
                                $asig = $cp['intensidad'] - $cp['faltantes'];
                                $pct = ($cp['intensidad'] > 0) ? ($asig / $cp['intensidad']) * 100 : 0;
                                
                                $completada = ($cp['faltantes'] == 0);
                                $clase_anim = ($pct < 40 && !$completada) ? 'pulse-critical' : '';
                                $opacidad = $completada ? 'opacity-50 grayscale' : '';
                            ?>
                            <div class="carga-pend-item p-3 border rounded-4 mb-3 shadow-sm animate__animated animate__fadeInRight khronos-pending-item <?php echo $opacidad; ?> <?php echo $completada ? 'khronos-item-completed' : 'cursor-move'; ?>" 
                                 draggable="<?php echo $completada ? 'false' : 'true'; ?>" 
                                 onmouseenter="<?php echo $completada ? "this.style.cursor='not-allowed'" : ""; ?>"
                                 ondragstart="dragCarga(event)"
                                 data-docente-id="<?php echo $cp['docente_id']; ?>"
                                 data-especialidad-id="<?php echo $cp['especialidad_id']; ?>"
                                 data-faltantes="<?php echo $cp['faltantes']; ?>">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="badge-elite fs-nano <?php echo $clase_anim; ?> <?php echo $completada ? 'badge-elite--success' : 'badge-elite--danger'; ?>">
                                        <?php echo $asig; ?> / <?php echo $cp['intensidad']; ?>h
                                    </span>
                                    <div class="flex-grow-1 mx-2 khronos-progress-bg">
                                        <div class="progress-gradient khronos-progress-inner js-khronos-progress" data-pct="<?php echo $pct; ?>"></div>
                                    </div>
                                    <?php if($completada): ?>
                                        <svg class="text-primary" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                    <?php endif; ?>
                                </div>
                                <h6 class="fw-bold mb-1 small khronos-title"><?php echo htmlspecialchars($cp['nombre_especialidad']); ?></h6>
                                <p class="mb-0 text-secondary fs-nano fw-medium opacity-75"><?php echo htmlspecialchars($cp['docente']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>


<script src="../js/khronos_engine.js?v=<?php echo time(); ?>"></script>

<script src="../js/khronos.js" defer></script>



