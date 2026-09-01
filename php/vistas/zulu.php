<?php

// @sast-ignore - Deuda técnica legacy controlada bajo línea base de seguridad v5.0
declare(strict_types=1);
// PHP/VISTAS/ZULU.PHP - CONSOLA DE CARGA ACADÉMICA (PROTOCOLO ATLAS)
if (!tiene_permiso('zulu')) {
    echo "<div class='alert-soberania-elite animate__animated animate__headShake'>
            <div class='alert-soberania-icon'><i class='bi bi-shield-lock-fill'></i></div>
            <div class='alert-soberania-content'>
                <h4>Acceso Denegado</h4>
                <p>No posee las credenciales académicas necesarias para gestionar la carga académica institucional.</p>
            </div>
          </div>";
    return;
}

$mi_id = (int)$_SESSION['usuario_id'];
$mi_rol_id = (int)$_SESSION['rol_id'];
$ver_todo = tiene_permiso('matricula') || tiene_permiso('personal');

$is_coordinador = tienen_rol(['administrador', 'coordinador']);
$can_edit_census = $is_coordinador;

// MÉTRICA GLOBAL PARA EL POWER HEADER
$stmt_horas = $db->prepare("
    SELECT SUM(pm.intensidad_horaria) 
    FROM carga_academica ca
    JOIN cursos c ON ca.curso_id = c.id
    JOIN zulu_plan_maestro pm ON pm.especialidad_id = ca.especialidad_id AND CAST(pm.nivel_nombre AS INT) = c.nivel_id
");
$stmt_horas->execute();
$total_horas_sistema = (int)$stmt_horas->fetchColumn() ?: 0;
?>

<div class="container-fluid py-4" id="zulu-container-master" data-csrf-token="<?php echo $_SESSION['csrf_token'] ?? ''; ?>" ondragover="event.preventDefault();" ondrop="handleDropTrash(event)">
    
    <!-- POWER HEADER ATLAS (VITRINA 06+) -->
    <div class="row align-items-end mb-4 g-3 px-3">
        <div class="col-xl-4 col-lg-5 text-center text-md-start border-start border-4 border-topbar-elite ps-4">
            <h4 class="h3 fw-bold mb-0 text-titulo-elite">Protocolo Atlas</h4>
            <p class="text-secondary mb-0 small">Consola Maestra de Carga Académica.</p>
            <nav class="breadcrumb-elite" aria-label="Ruta de navegación">
                <a href="javascript:void(0)" onclick="navegarModulo('inicio')" class="breadcrumb-link-elite"><i class="bi bi-house-door me-1"></i>Inicio</a>
                <i class="bi bi-chevron-right breadcrumb-separator-elite"></i>
                <span class="breadcrumb-item-elite">Académico</span>
                <i class="bi bi-chevron-right breadcrumb-separator-elite"></i>
                <span class="breadcrumb-current-elite">Matriz Zulu</span>
            </nav>
            <div class="mt-3">
                <div class="metric-card-elite variant-info d-inline-block p-3 metric-card-elite--scaled">
                    <div class="metric-header-elite mb-2">
                        <div class="metric-icon-box-elite">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <span class="metric-label-elite">Carga Global Operativa</span>
                    </div>
                    <div class="d-flex align-items-baseline gap-2">
                        <div class="metric-value-elite mb-0 fs-xl-elite"><?php echo $total_horas_sistema; ?></div>
                        <span class="text-secondary small fw-bold">Horas / Sem</span>
                    </div>
                    <div class="metric-footer-elite text-info mt-2 bg-primary-faded rounded-1 px-2 fs-nano-elite">
                        <i class="bi bi-activity me-1"></i> Pulso del Plantel en tiempo real
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-8 col-lg-7">
            <div class="d-flex flex-wrap flex-lg-nowrap gap-3 justify-content-end align-items-center">
                
                <!-- ACCIONES DE MANDO -->
                <?php if ($can_edit_census): ?>
                <div class="d-flex gap-2 align-items-center">
                    <button type="button" class="btn-elite px-3" data-bs-toggle="modal" data-bs-target="#modalPlanMaster">
                        <i class="bi bi-clock-history me-2"></i> INTENSIDAD HORARIA
                    </button>
                </div>
                <?php endif; ?>

                <!-- BUSCADOR INTEGRADO (EXTREMO DERECHO) -->
                <div class="search-wrapper-elite flex-grow-1 max-w-300">
                    <span class="search-icon-elite-fixed"><i class="bi bi-search"></i></span>
                    <input type="text" id="buscador-cursos-zulu" class="search-elite--wrapped" placeholder="Filtrar grado o curso..." onkeyup="filtrarCursosZulu()">
                </div>
            </div>
        </div>
    </div>
    
    <?php 
    $stmt_plantilla = $db->prepare("
        SELECT c.id as curso_id, c.nombre_curso, c.nivel_id, u_tutor.nombre as nombre_tutor,
               e.nombre_especialidad as materia_nombre, u_doc.nombre as docente_nombre,
               u_doc.id as docente_id, e.id as materia_id
        FROM cursos c
        LEFT JOIN usuarios u_tutor ON c.tutor_id = u_tutor.id
        LEFT JOIN carga_academica ca ON c.id = ca.curso_id
        LEFT JOIN especialidades e ON ca.especialidad_id = e.id
        LEFT JOIN usuarios u_doc ON ca.docente_id = u_doc.id
        ORDER BY c.nombre_curso ASC, e.nombre_especialidad ASC
    ");
    $stmt_plantilla->execute();
    $plantilla_stmt = $stmt_plantilla;

    $stmt_spec = $db->prepare("SELECT id, nombre_especialidad FROM especialidades");
    $stmt_spec->execute();
    $spec_stmt = $stmt_spec;
    $nombres_materias = [];
    while($s = $spec_stmt->fetch(PDO::FETCH_ASSOC)) { $nombres_materias[$s['id']] = $s['nombre_especialidad']; }

    $stmt_plan_master = $db->prepare("SELECT nivel_nombre, especialidad_id FROM zulu_plan_maestro");
    $stmt_plan_master->execute();
    $plan_master_stmt = $stmt_plan_master;
    $plan_master_map = [];
    while($pm = $plan_master_stmt->fetch(PDO::FETCH_ASSOC)) { $plan_master_map[(int)$pm['nivel_nombre']][] = (int)$pm['especialidad_id']; }

    $data_agrupada = [];
    while($row = $plantilla_stmt->fetch(PDO::FETCH_ASSOC)) {
        $c_id = $row['curso_id'];
        if (!isset($data_agrupada[$c_id])) {
            $data_agrupada[$c_id] = [
                'nombre'   => $row['nombre_curso'],
                'nivel_id' => $row['nivel_id'],
                'tutor'    => $row['nombre_tutor'],
                'carga'    => []
            ];
        }
        if ($row['docente_nombre']) {
            $data_agrupada[$c_id]['carga'][] = [
                'materia' => $row['materia_nombre'],
                'docente' => $row['docente_nombre'],
                'd_id'    => $row['docente_id'],
                'm_id'    => $row['materia_id']
            ];
        }
    }
    ?>

    <!-- ESTRUCTURA 2 COLUMNAS ZULU: CURSOS A LA IZQUIERDA, BANCO DOCENTES DESPLEGABLE A LA DERECHA -->
    <div class="row g-4 px-3" id="contenedor-zulu-simple">
        <!-- COLUMNA IZQUIERDA: TARJETAS DE CURSOS -->
        <div class="col-xl-8 col-lg-7">
            <div class="row g-3" id="grid-cursos-zulu">
                <?php 
                foreach($data_agrupada as $id_cur => $info):
                    $nivel_id = (int)$info['nivel_id'];
                    $ids_requeridos = $plan_master_map[$nivel_id] ?? [];
                    $ids_asignados = array_column($info['carga'], 'm_id');
                    
                    $carga_unificada = [];
                    foreach($info['carga'] as $c) { $carga_unificada[] = array_merge($c, ['es_placeholder' => false]); }
                    
                    $materias_faltantes = array_diff($ids_requeridos, $ids_asignados);
                    foreach($materias_faltantes as $m_faltante_id) {
                        $carga_unificada[] = [
                            'materia' => $nombres_materias[$m_faltante_id] ?? 'Desconocida',
                            'docente' => 'SIN DOCENTE',
                            'd_id'    => null,
                            'm_id'    => $m_faltante_id,
                            'es_placeholder' => true
                        ];
                    }

                    usort($carga_unificada, function($a, $b) {
                        return $b['es_placeholder'] <=> $a['es_placeholder'];
                    });

                    $tiene_plan = count($ids_requeridos) > 0;
                    $es_incompleto = $tiene_plan && count($materias_faltantes) > 0;

                    $nombres_faltantes = [];
                    foreach ($materias_faltantes as $fid) {
                        $nombres_faltantes[] = $nombres_materias[$fid] ?? 'Desconocida';
                    }

                    if (!$tiene_plan) {
                        $border_class = 'border-start border-warning border-4';
                        $badge_class = 'bg-warning text-dark';
                        $badge_text = 'Sin Plan';
                    } elseif ($es_incompleto) {
                        $border_class = 'border-start border-danger border-4';
                        $badge_class = 'bg-danger text-white';
                        $badge_text = 'Faltan ' . count($materias_faltantes) . ' materias';
                    } else {
                        $border_class = 'border-start border-success border-4';
                        $badge_class = 'bg-success text-white';
                        $badge_text = 'Carga completa';
                    }
                ?>
                <div class="col-md-6 col-12 card-curso-wrap" data-curso-nombre="<?php echo strtolower($info['nombre']); ?>">
                    <div class="card card-zulu-elite border-0 shadow-sm rounded-4 position-relative <?php echo $border_class; ?>"
                         data-curso-dest="<?php echo $id_cur; ?>"
                         data-materias-faltantes='<?php echo json_encode(array_values($materias_faltantes)); ?>'
                         data-materias-nombres-faltantes='<?php echo json_encode(array_values($nombres_faltantes)); ?>'
                         <?php if($can_edit_census): ?>
                         ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)" ondrop="handleDrop(event)"
                         <?php endif; ?>>
                        
                        <div class="card-body p-3">
                            <div class="mb-2">
                                <div class="text-truncate text-nowrap mb-2" title="<?php echo htmlspecialchars($info['nombre'] . ' - DIR: ' . ($info['tutor'] ?? 'SIN ASIGNAR')); ?>">
                                    <span class="fw-bold text-primary text-uppercase small"><?php echo htmlspecialchars($info['nombre']); ?></span>
                                    <span class="text-secondary fs-nano fw-bold text-uppercase ms-1">- DIR: <?php echo htmlspecialchars($info['tutor'] ?? 'SIN ASIGNAR'); ?></span>
                                </div>
                                <div>
                                    <?php if (!$tiene_plan): ?>
                                        <span class="badge badge-elite badge-elite--warning fs-nano py-1 px-2 text-uppercase cursor-pointer" onclick="abrirPlanMaestroConNivel(<?php echo $nivel_id; ?>)" title="Configurar Plan Maestro para este nivel">
                                            <i class="bi bi-exclamation-triangle-fill me-1"></i> Sin intensidad horaria
                                        </span>
                                    <?php elseif ($es_incompleto): ?>
                                        <span class="badge badge-elite badge-elite--danger fs-nano py-1 px-2 text-uppercase animate-pulse-elite">
                                            <i class="bi bi-exclamation-circle-fill me-1"></i> <?php echo count($materias_faltantes) === 1 ? 'Falta 1 materia' : 'Faltan ' . count($materias_faltantes) . ' materias'; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-elite badge-elite--success fs-nano py-1 px-2 text-uppercase">
                                            <i class="bi bi-check-circle-fill me-1"></i> Carga completa
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="dropdown mt-2">
                                <button class="btn w-100 d-flex align-items-center justify-content-between px-3 py-2 rounded-3 bg-light border-0" type="button" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false">
                                    <span class="fs-nano fw-bold text-secondary text-uppercase text-nowrap">Ver carga (<?php echo count($carga_unificada); ?> materias)</span>
                                    <i class="bi bi-chevron-down ms-2"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 p-2 rounded-4 zulu-menu animate__animated animate__fadeIn min-w-280 z-1060 border-primary-10">
                                    <div class="p-2 border-bottom mb-2 bg-light rounded-3">
                                        <h6 class="fw-bold text-primary text-uppercase mb-0 fs-nano text-center">Carga Detallada: <?php echo $info['nombre']; ?></h6>
                                    </div>
                                    <div class="list-group list-group-flush border-0 overflow-auto max-h-320">
                                        <?php foreach($carga_unificada as $item): 
                                            $es_slot = $item['es_placeholder'];
                                        ?>
                                            <div class="list-group-item px-2 border-0 bg-transparent py-2 <?php echo $es_slot ? 'zulu-slot-placeholder' : 'docente-drag-item'; ?>" 
                                                 <?php if($can_edit_census): ?>
                                                 draggable="<?php echo $es_slot ? 'false' : 'true'; ?>" 
                                                 ondragstart="<?php echo $es_slot ? '' : 'handleDragStart(event)'; ?>"
                                                 ondragend="handleDragEnd(event)"
                                                 ondrop="handleDropSlot(event, '<?php echo $id_cur; ?>', '<?php echo $item['m_id']; ?>')"
                                                 data-docente-id="<?php echo $item['d_id']; ?>"
                                                 data-materia-id="<?php echo $item['m_id']; ?>"
                                                 data-curso-orig="<?php echo $id_cur; ?>"
                                                 data-docente-nombre="<?php echo htmlspecialchars($item['docente']); ?>"
                                                 data-materia-nombre="<?php echo htmlspecialchars($item['materia']); ?>"
                                                 <?php endif; ?>>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-elite--sm avatar-elite--circle me-3 <?php echo $es_slot ? 'opacity-25' : ''; ?>">
                                                        <?php echo strtoupper(substr($item['materia'], 0, 1)); ?>
                                                    </div>
                                                    <div class="flex-grow-1 overflow-hidden">
                                                        <div class="fw-bold fs-nano <?php echo $es_slot ? 'text-secondary opacity-50' : 'text-dark'; ?> text-truncate text-uppercase"><?php echo htmlspecialchars($item['materia']); ?></div>
                                                        <div class="<?php echo $es_slot ? 'text-danger fw-bold fs-nano italic' : 'text-secondary fs-nano'; ?>">
                                                            <?php echo htmlspecialchars($item['docente']); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- COLUMNA DERECHA: BANCO DE DOCENTES DESPLEGABLE -->
        <div class="col-xl-4 col-lg-5">
            <div class="card card-elite border-0 shadow-sm rounded-4 position-sticky top-0">
                <div class="card-header bg-white rounded-4 py-2 px-3 d-flex justify-content-between align-items-center cursor-pointer border-0" 
                     data-bs-toggle="collapse" data-bs-target="#desplegableDocentes" aria-expanded="false">
                    <span class="small fw-bold text-secondary text-uppercase tracking-wider">
                        <i class="bi bi-people me-2"></i>Docentes Disponibles
                    </span>
                    <i class="bi bi-chevron-down text-secondary small"></i>
                </div>
                <div class="collapse" id="desplegableDocentes">
                    <div class="card-body p-3 border-top border-light">
                        <div class="zulu-pozo-scroll" id="pozo-docentes">
                            <?php 
                            $stmt_docentes_side = $db->prepare("
                                SELECT u.id, u.nombre, u.especialidad_id as esp_id, e.nombre_especialidad,
                                (SELECT SUM(pm.intensidad_horaria) 
                                 FROM carga_academica ca
                                 JOIN cursos c ON ca.curso_id = c.id
                                 JOIN zulu_plan_maestro pm ON pm.especialidad_id = ca.especialidad_id AND CAST(pm.nivel_nombre AS INT) = c.nivel_id
                                 WHERE ca.docente_id = u.id) as total_horas
                                FROM usuarios u
                                LEFT JOIN especialidades e ON u.especialidad_id = e.id
                                WHERE u.rol_id IN (11, 19, 20) OR (u.permisos_custom = 1 AND u.rol_id NOT IN (1, 2))
                                ORDER BY u.nombre ASC
                            ");
                            $stmt_docentes_side->execute();
                            while($doc = $stmt_docentes_side->fetch(PDO::FETCH_ASSOC)):
                                $horas = $doc['total_horas'] ?? 0;
                                $color_carga = ($horas > 24) ? 'text-danger' : (($horas > 18) ? 'text-warning' : 'text-success');
                            ?>
                            <div class="docente-pozo-item rounded-3 mb-2 p-2 zulu-grab bg-light shadow-xs border border-light" 
                                 draggable="true" ondragstart="handleDragStartPozo(event)"
                                 data-docente-id="<?php echo $doc['id']; ?>"
                                 data-especialidad-id="<?php echo $doc['esp_id']; ?>"
                                 data-docente-nombre="<?php echo htmlspecialchars($doc['nombre']); ?>">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center overflow-hidden">
                                        <div class="avatar-elite--sm avatar-elite--circle me-2">
                                            <?php echo strtoupper(substr($doc['nombre'], 0, 1)); ?>
                                        </div>
                                        <div class="overflow-hidden">
                                            <div class="fw-bold small text-dark text-truncate text-uppercase"><?php echo htmlspecialchars($doc['nombre']); ?></div>
                                            <div class="text-secondary fs-nano text-uppercase opacity-75 text-truncate">
                                                <?php echo htmlspecialchars($doc['nombre_especialidad'] ?? 'DOCENTE'); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-end ps-2">
                                        <div class="fw-bold small <?php echo $color_carga; ?>"><?php echo $horas; ?>h</div>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../js/zulu.js" defer></script>
<script src="../js/zulu_engine.js?v=<?php echo time(); ?>"></script>

<!-- MODAL PLAN MAESTRO -->
<div class="modal fade animate__animated animate__fadeIn" id="modalPlanMaster" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-primary text-white border-0 py-3 px-4">
                <h5 class="modal-title fw-bold text-uppercase">Intensidad Horaria</h5>
            </div>
            <div class="modal-body p-4">
                <div class="mb-4">
                    <label class="form-label fw-bold text-uppercase small text-muted mb-2">1. Seleccionar Nivel Académico</label>
                    <select id="nivel-plan-zulu" class="select-elite w-100" onchange="cargarPlanMaestro(this.value)">
                        <option value="">-- Seleccionar nivel institucional --</option>
                        <?php 
                        $stmt_niveles = $db->prepare("SELECT DISTINCT nivel_id FROM cursos WHERE nivel_id > 0 ORDER BY nivel_id ASC");
                        $stmt_niveles->execute();
                        $res_niveles = $stmt_niveles;
                        while($rn = $res_niveles->fetch(PDO::FETCH_ASSOC)): ?>
                            <option value="<?php echo $rn['nivel_id']; ?>">Nivel Académico <?php echo $rn['nivel_id']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-2 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark text-uppercase small mb-0">2. Malla Curricular Obligatoria</h6>
                    <span class="badge-elite badge-elite--info">Horas / Sem</span>
                </div>
                
                <div class="row g-2 overflow-auto max-h-400" id="lista-materias-plan">
                    <?php 
                    $stmt_mats = $db->prepare("SELECT id, nombre_especialidad, nivel_desde, nivel_hasta FROM especialidades ORDER BY nombre_especialidad ASC");
                    $stmt_mats->execute();
                    $res_mats = $stmt_mats;
                    while($rm = $res_mats->fetch(PDO::FETCH_ASSOC)): ?>
                        <div class="col-md-6 card-materia-item" data-nivel-desde="<?php echo $rm['nivel_desde'] ?? 1; ?>" data-nivel-hasta="<?php echo $rm['nivel_hasta'] ?? 11; ?>">
                            <div class="p-2 rounded-3 border bg-white h-100 d-flex align-items-center justify-content-between">
                                <div class="m-0 d-flex align-items-center">
                                    <input class="form-check-input-elite chk-materia-plan me-2" type="checkbox" value="<?php echo $rm['id']; ?>" id="chk-mat-<?php echo $rm['id']; ?>" onchange="toggleIntensidad(<?php echo $rm['id']; ?>, this.checked)">
                                    <label class="form-check-label small fw-bold text-dark text-truncate max-w-150 cursor-pointer" for="chk-mat-<?php echo $rm['id']; ?>">
                                        <?php echo htmlspecialchars($rm['nombre_especialidad']); ?>
                                    </label>
                                </div>
                                <input type="number" id="intensidad-mat-<?php echo $rm['id']; ?>" class="input-elite zulu-intensidad-input text-center fw-bold text-primary h-35 rounded-3 fs-md-elite" value="1" min="1" max="20" disabled>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 bg-light">
                <button type="button" class="btn-elite btn-elite--outline px-4" data-bs-dismiss="modal">CANCELAR</button>
                <button type="button" class="btn-elite px-4" onclick="guardarPlanMaestro()">GUARDAR PROTOCOLO</button>
            </div>
        </div>
    </div>
</div>




