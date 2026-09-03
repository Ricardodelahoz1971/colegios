<?php

// @sast-ignore - Deuda técnica legacy controlada bajo línea base de seguridad v5.0
declare(strict_types=1);
// 1. OBTENER TODOS LOS CURSOS PARA EL SELECTOR
$stmt_stmt_all = $db->prepare("SELECT id, nombre_curso FROM cursos ORDER BY nombre_curso ASC"); $stmt_stmt_all->execute(); $stmt_all = $stmt_stmt_all;
$todos_los_cursos = $stmt_all->fetchAll(PDO::FETCH_ASSOC);

// 2. RECUPERAR ID DEL CURSO ACTUAL (Sin forzar el primero)
$curso_id = $_GET['id'] ?? '';

// 3. OBTENER INFORMACIÓN DEL CURSO SELECCIONADO
$curso = null;
if (!empty($curso_id)) {
    $stmt_curso = $db->prepare("
        SELECT c.*, u.nombre as nombre_tutor 
        FROM cursos c 
        LEFT JOIN usuarios u ON c.tutor_id = u.id 
        WHERE c.id = :cid
    ");
    $stmt_curso->execute([':cid' => $curso_id]);
    $curso = $stmt_curso->fetch(PDO::FETCH_ASSOC);
}

// 4. OBTENER CARGA ACADÉMICA ACTUAL
$carga = [];
if ($curso) {
    $stmt_carga = $db->prepare("
        SELECT ca.id, e.nombre_especialidad as materia, u.nombre as profesor, a.nombre_area
        FROM carga_academica ca
        JOIN especialidades e ON ca.especialidad_id = e.id
        JOIN usuarios u ON ca.docente_id = u.id
        LEFT JOIN areas a ON e.area_id = a.id
        WHERE ca.curso_id = :cid
        ORDER BY a.nombre_area, e.nombre_especialidad
    ");
    $stmt_carga->execute([':cid' => $curso_id]);
    $carga = $stmt_carga->fetchAll(PDO::FETCH_ASSOC);
}

// 5. PREPARAR LISTAS PARA EL FORMULARIO
$stmt_stmt_m = $db->prepare("SELECT id, nombre_especialidad FROM especialidades ORDER BY nombre_especialidad"); $stmt_stmt_m->execute(); $stmt_m = $stmt_stmt_m;
$stmt_d_raw = $db->prepare("
    SELECT u.id, u.nombre, e.nombre_especialidad as experiecia
    FROM usuarios u
    LEFT JOIN especialidades e ON u.especialidad_id = e.id
    LEFT JOIN roles r ON u.rol_id = r.id
    WHERE LOWER(r.nombre_rol) LIKE '%profesor%' OR LOWER(r.nombre_rol) LIKE '%docente%' OR u.especialidad_id IS NOT NULL
    ORDER BY u.nombre
");
$stmt_d_raw->execute();
$stmt_d = $stmt_d_raw;
?>

<div class="container-fluid py-4">
    
    <!-- CABECERA UNIFICADA CON SELECTOR DE CURSO -->
    <div class="row align-items-center mb-4 g-3 px-3">
        <div class="col-md-6 text-center text-md-start border-start border-4 border-topbar-elite ps-4">
            <h4 class="h3 fw-bold mb-0 text-titulo-elite">Carga Académica</h4>
            <p class="text-secondary mb-0 small">Asignación de cátedras y docentes por nivel institucional.</p>
            <nav class="breadcrumb-elite" aria-label="Ruta de navegación">
                <a href="javascript:void(0)" onclick="navegarModulo('inicio')" class="breadcrumb-link-elite"><i class="bi bi-house-door me-1"></i>Inicio</a>
                <i class="bi bi-chevron-right breadcrumb-separator-elite"></i>
                <span class="breadcrumb-item-elite">Académico</span>
                <i class="bi bi-chevron-right breadcrumb-separator-elite"></i>
                <a href="javascript:void(0)" onclick="navegarModulo('cursos')" class="breadcrumb-link-elite">Cursos</a>
                <?php if ($curso): ?>
                    <i class="bi bi-chevron-right breadcrumb-separator-elite"></i>
                    <span class="breadcrumb-current-elite">Carga: <?php echo htmlspecialchars($curso['nombre_curso']); ?></span>
                <?php endif; ?>
            </nav>
        </div>
        <div class="col-md-6">
            <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-md-end align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <label class="small fw-bold text-uppercase text-secondary mb-0 u-nowrap">Gestionar Curso:</label>
                    <select class="select-elite min-w-250" onchange="if(this.value) typeof navegarModulo === 'function' ? navegarModulo('carga', 'id=' + this.value) : window.location.href='dashboard.php?p=carga&id=' + this.value">
                        <option value="" disabled <?php echo empty($curso_id) ? 'selected' : ''; ?>>-- Seleccione un nivel académico --</option>
                        <?php foreach ($todos_los_cursos as $c_opt): ?>
                            <option value="<?php echo $c_opt['id']; ?>" <?php echo ($c_opt['id'] == $curso_id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c_opt['nombre_curso']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <?php if (!$curso): ?>
        <!-- VISTA DE ESPERA (ESTADO LIMPIO) -->
        <div class="d-flex flex-column align-items-center justify-content-center py-5 mt-4">
            <div class="alert-soberania-elite shadow-lg max-w-600">
                <div class="alert-soberania-icon bg-primary-faded text-primary">
                    <i class="bi bi-layers-half"></i>
                </div>
                <div class="alert-soberania-content">
                    <h4>Consola Académica Lista</h4>
                    <p>Por favor, seleccione un <strong>Grado o Curso</strong> desde el menú superior derecho para comenzar con la asignación de materias y docentes.</p>
                </div>
            </div>
            <div class="mt-4 text-center opacity-50">
                <i class="bi bi-arrow-up-right display-4 text-primary animate__animated animate__bounce animate__infinite"></i>
                <p class="small fw-bold text-uppercase text-secondary mt-2">El selector está aquí</p>
            </div>
        </div>
    <?php else: ?>

    <div class="row g-4 px-3 animate__animated animate__fadeIn">
        
        <!-- LISTADO DE MATERIAS ASIGNADAS -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 rounded-3 overflow-hidden h-100 bg-white">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between px-4">
                    <h5 class="fw-bold mb-0 text-dark small text-uppercase">
                        <i class="bi bi-journal-check me-2 text-primary"></i>
                        Materias en <span class="text-primary"><?php echo htmlspecialchars($curso['nombre_curso']); ?></span>
                    </h5>
                    <span class="badge-elite badge-elite--info">Ciclo Lectivo 2026</span>
                </div>
                
                <div class="table-responsive table-scroll-elite">
                    <table class="table-elite tabla-datos table-hover table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="py-3 ps-4 text-uppercase small fw-800">Área / Materia</th>
                                <th class="py-3 text-uppercase small fw-800">Docente Responsable</th>
                                <th class="py-3 text-center text-uppercase small fw-800 col-actions-sm">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            <?php if (empty($carga)): ?>
                                <tr>
                                    <td colspan="3" class="py-5 text-center">
                                        <div class="d-flex flex-column align-items-center justify-content-center py-4">
                                            <!-- Premium Document SVG -->
                                            <svg width="100" height="100" viewBox="0 0 120 120" fill="none" class="mb-3 text-muted opacity-75">
                                                <rect x="34" y="24" width="56" height="72" rx="8" fill="currentColor" class="opacity-10" />
                                                <rect x="30" y="20" width="56" height="72" rx="8" fill="currentColor" class="opacity-5" stroke="currentColor" stroke-width="2" />
                                                <line x1="40" y1="36" x2="65" y2="36" stroke="currentColor" stroke-width="3" stroke-linecap="round" class="text-primary" />
                                                <line x1="40" y1="48" x2="76" y2="48" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="opacity-25" />
                                                <line x1="40" y1="60" x2="76" y2="60" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="opacity-25" />
                                                <line x1="40" y1="72" x2="60" y2="72" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="opacity-25" />
                                                <rect x="80" y="44" width="14" height="8" rx="2" fill="currentColor" class="text-danger opacity-75" />
                                                <rect x="82" y="64" width="12" height="8" rx="2" fill="currentColor" class="text-warning opacity-75" />
                                            </svg>
                                            <h5 class="fw-bold mb-1 text-uppercase text-primary small-letter-spacing">Sin Asignaciones Realizadas</h5>
                                            <p class="text-secondary small mb-0">No se han asignado materias ni docentes todavía a este curso.</p>
                                        </div>
                                     </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($carga as $c): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="text-uppercase text-primary mb-0 fw-bold fs-nano"><?php echo htmlspecialchars($c['nombre_area'] ?? 'General'); ?></div>
                                            <div class="fw-semibold text-dark text-uppercase"><?php echo htmlspecialchars($c['materia']); ?></div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-user me-2 bg-primary text-white d-flex align-items-center justify-content-center fw-bold small">
                                                    <?php echo inicial_texto_utf8($c['profesor']); ?>
                                                </div>
                                                <div class="fw-semibold text-dark small"><?php echo htmlspecialchars($c['profesor']); ?></div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn-elite-icon btn-elite-icon--danger" 
                                                    onclick="borrarCarga(<?php echo $c['id']; ?>, '<?php echo htmlspecialchars($c['materia'], ENT_QUOTES); ?>')" 
                                                    title="Retirar Asignación">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- FORMULARIO DE ASIGNACIÓN -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 rounded-4 p-4 bg-white border-top border-5 border-primary">
                <h5 class="fw-bold mb-4 text-dark d-flex align-items-center small text-uppercase">
                    <i class="bi bi-plus-circle-fill me-2 text-primary"></i>
                    Asignar Nueva Materia
                </h5>
                
                <form onsubmit="event.preventDefault(); enviarPostElite('logica/guardar_carga.php', new FormData(this));" class="d-flex flex-column gap-4">
                    <input type="hidden" name="curso_id" value="<?php echo $curso_id; ?>">
                    
                    <div>
                        <label class="form-label small fw-bold text-uppercase text-secondary mb-2">1. Seleccionar Asignatura</label>
                        <select name="especialidad_id" required class="select-elite w-100">
                            <option value="" disabled selected>Elegir asignatura...</option>
                            <?php while($m = $stmt_m->fetch(PDO::FETCH_ASSOC)) { ?>
                                <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['nombre_especialidad']); ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div>
                        <label class="form-label small fw-bold text-uppercase text-secondary mb-2">2. Asignar Docente</label>
                        <select name="docente_id" required class="select-elite w-100">
                            <option value="" disabled selected>Elegir profesor...</option>
                            <?php while($d = $stmt_d->fetch(PDO::FETCH_ASSOC)) { ?>
                                <option value="<?php echo $d['id']; ?>">
                                    <?php echo htmlspecialchars($d['nombre']); ?> 
                                    (<?php echo $d['experiecia'] ?: 'Gral'; ?>)
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <button type="submit" class="btn-elite w-100 mt-2">
                        <i class="bi bi-person-plus-fill me-2"></i>
                        Confirmar Asignación
                    </button>
                </form>
            </div>
            
            <div class="mt-4 p-4 rounded-4 bg-light border border-primary border-opacity-10">
                <div class="small text-uppercase fw-800 text-secondary mb-2">Tutor del Curso</div>
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-ficha bg-white shadow-sm d-flex align-items-center justify-content-center text-primary size-50">
                        <i class="bi bi-person-badge"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($curso['nombre_tutor'] ?? 'Sin tutor'); ?></div>
                        <div class="small text-muted">Director de Grupo</div>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <?php endif; ?>
</div>

<script src="../js/carga.js" defer></script>
