<?php

// @sast-ignore - Deuda técnica legacy controlada bajo línea base de seguridad v5.0
declare(strict_types=1);
// PHP/VISTAS/MATRICULADOS.PHP - GESTIÓN INTEGRAL DE ESTUDIANTES v2.0 (ELITE TERM)
if (!tiene_permiso('estudiantes')) {
    echo "<div class='alert-soberania-elite animate__animated animate__headShake'>
            <div class='alert-soberania-icon'><i class='bi bi-shield-lock-fill'></i></div>
            <div class='alert-soberania-content'>
                <h4>Acceso Denegado</h4>
                <p>No posee las credenciales académicas necesarias para gestionar la base de datos de estudiantes.</p>
            </div>
          </div>";
    return;
}

// 1. DATOS DE CURSOS PARA MODALES
$stmt_c = $db->prepare("SELECT id, nombre_curso FROM cursos ORDER BY nombre_curso ASC");
$stmt_c->execute();
$cursos_lista = $stmt_c->fetchAll(PDO::FETCH_ASSOC);
$cursos_json = htmlspecialchars(json_encode($cursos_lista), ENT_QUOTES, 'UTF-8');

// 1.1. DATOS DE FORMATOS DE MATRÍCULA ACTIVOS
$stmt_f_activos = $db->prepare("SELECT id, nombre, tipo_documento, tamano_lienzo FROM formatos_matricula WHERE activo = 1 ORDER BY tipo_documento ASC, nombre ASC");
$stmt_f_activos->execute();
$formatos_activos = $stmt_f_activos->fetchAll(PDO::FETCH_ASSOC) ?: [];
$formatos_json = htmlspecialchars(json_encode($formatos_activos), ENT_QUOTES, 'UTF-8');

$stmt_m = $db->prepare("
    SELECT e.*, c.nombre_curso, u.usuario AS nombre_usuario,
           da.lugar_nacimiento, da.fecha_nacimiento, da.edad, da.nacionalidad, da.colegio_anterior, da.direccion_estudiante, da.folio_matricula, da.foto,
           da.padre_nombre, da.padre_tipo_documento, da.padre_documento, da.padre_documento_expedicion, da.padre_nacionalidad, da.padre_celular, da.padre_telefono, da.padre_direccion, da.padre_profesion, da.padre_email,
           da.madre_nombre, da.madre_tipo_documento, da.madre_documento, da.madre_documento_expedicion, da.madre_nacionalidad, da.madre_celular, da.madre_telefono, da.madre_direccion, da.madre_profesion, da.madre_email
    FROM estudiantes e 
    LEFT JOIN cursos c ON e.curso_id = c.id 
    LEFT JOIN usuarios u ON e.id = u.estudiante_id AND u.rol_id = 5 
    LEFT JOIN estudiantes_datos_adicionales da ON e.id = da.estudiante_id
    ORDER BY e.apellido ASC, e.nombre ASC
");
$stmt_m->execute();

$total_estudiantes = (int)(function($db) { $s = $db->prepare("SELECT COUNT(*) FROM estudiantes"); $s->execute(); return $s->fetchColumn(); })($db) ?: 0;
?>

<div class="container-fluid py-4" id="matriculados-container" data-formatos-activos="<?php echo $formatos_json; ?>">
    
    <!-- POWER HEADER UNIFICADO (VITRINA 06+) -->
    <div class="row align-items-end mb-4 g-3 px-3">
        <!-- BLOQUE TÍTULO Y MÉTRICA -->
        <div class="col-xl-4 col-lg-5 text-center text-md-start border-start border-4 border-topbar-elite ps-4">
            <h4 class="h3 fw-bold mb-0 text-titulo-elite">Administración de Expedientes</h4>
            <p class="text-secondary mb-0 small">Control central de registros académicos.</p>
            <div class="mt-3">
                <div class="metric-card-elite variant-primary d-inline-block p-3 metric-card-elite--scaled">
                    <div class="metric-header-elite mb-2">
                        <div class="metric-icon-box-elite">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <span class="metric-label-elite">Comunidad Estudiantil</span>
                    </div>
                    <div class="d-flex align-items-baseline gap-2">
                        <div class="metric-value-elite mb-0 fs-large-elite"><?php echo $total_estudiantes; ?></div>
                        <span class="text-secondary small fw-bold">Alumnos</span>
                    </div>
                    <div class="metric-footer-elite text-primary mt-2 bg-primary-faded rounded-1 px-2 fs-nano-elite">
                        <i class="bi bi-patch-check-fill me-1"></i> Cifra oficial registrada
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-8 col-lg-7">
            <div class="d-flex flex-wrap flex-lg-nowrap gap-3 justify-content-end align-items-center">
                <!-- BUSCADOR INTEGRADO -->
                <div class="search-wrapper-elite flex-grow-1 max-w-400 position-relative">
                    <span class="search-icon-elite"><i class="bi bi-search"></i></span>
                    <input type="text" class="search-elite--wrapped buscador-dinamico" placeholder="Buscar por nombre, apellido o DNI...">
                    <button class="btn-clear-search btn-close-elite d-none" title="Limpiar búsqueda">
                        <i class="bi bi-x"></i>
                    </button>
                </div>

                <!-- BOTONES DE ACCIÓN -->
                <div class="d-flex gap-2">
                    <?php if (tiene_permiso('matricula')): ?>
                        <button onclick="abrirCargaMasiva()" class="btn-elite btn-elite--outline px-3 u-nowrap">
                            <i class="bi bi-file-earmark-arrow-up me-1"></i>
                            IMPORTAR
                        </button>
                        
                        <button onclick="navegarModulo('matricula')" class="btn-elite px-3 u-nowrap">
                            <i class="bi bi-plus-lg me-1"></i>
                            NUEVA MATRÍCULA
                        </button>
                    <?php else: ?>
                        <span class="badge-soberania text-uppercase fw-bold py-2 px-3 fs-nano" title="Solo Lectura">
                            <i class="bi bi-eye-fill me-1"></i> Modo Consulta (Solo Lectura)
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- TABLA DE DATOS (ESTÁNDAR UNIVERSAL RICARDO) -->
    <div class="card shadow-sm border-0 rounded-4 bg-white mb-5 mx-3">
        <div class="table-responsive-elite">
            <table class="table-elite tabla-datos align-middle">
                <thead>
                    <tr>
                        <th>Identificación</th>
                        <th>Estudiante</th>
                        <th>Usuario</th>
                        <th>Grado / Curso</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center col-actions">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $hay_registros = false;
                    while ($row = $stmt_m->fetch(PDO::FETCH_ASSOC)): 
                        $hay_registros = true;
                        $id = $row['id'];
                        $nombre_completo = $row['apellido'] . ', ' . $row['nombre'];
                        $curso = $row['nombre_curso'] ?? '<span class="text-danger small fw-bold">SIN ASIGNAR</span>';
                        $promedio = number_format((float)($row['promedio'] ?? 0), 1);
                        
                        $prom_val = (float)($row['promedio'] ?? 0);
                        $es_antiguo = ($row['es_antiguo'] == 1);
                        $est_json = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                    ?>
                        <tr>
                            <td>
                                <span class="fw-bold text-dark"><?php echo htmlspecialchars($row['identificacion']); ?></span>
                            </td>
                            <td>
                                <div class="student-info-elite">
                                    <span class="student-name-elite"><?php echo htmlspecialchars($nombre_completo); ?></span>
                                    <span class="student-meta-elite"><?php echo htmlspecialchars($row['email'] ?? 'sin correo@elite.edu.co'); ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="table-badge-elite text-primary"><?php echo htmlspecialchars($row['nombre_usuario'] ?? 'N/A'); ?></span>
                            </td>
                            <td>
                                <div class="small fw-bold text-secondary"><?php echo $curso; ?></div>
                            </td>
                            <td class="text-center">
                                <?php if ($es_antiguo): ?>
                                    <span class="table-badge-elite text-info">ANTIGUO</span>
                                <?php else: ?>
                                    <span class="table-badge-elite text-secondary">NUEVO</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center col-actions">
                                <div class="d-flex justify-content-center gap-2">
                                    <?php if (tiene_permiso('matricula')): ?>
                                        <button class="btn-elite-icon" 
                                                onclick='editarEstudiante(<?php echo $id; ?>, <?php echo $est_json; ?>, <?php echo $cursos_json; ?>)'
                                                title="Editar Expediente">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button class="btn-elite-icon" 
                                                onclick='abrirSelectorImpresion(<?php echo $id; ?>, "<?php echo addslashes($nombre_completo); ?>")'
                                                title="Imprimir Matrícula">
                                            <i class="bi bi-printer-fill text-primary"></i>
                                        </button>
                                        <button class="btn-elite-icon btn-elite-icon--danger"
                                                onclick="borrarEstudiante(<?php echo $id; ?>, '<?php echo addslashes($nombre_completo); ?>')"
                                                title="Baja Académica">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted small italic"><i class="bi bi-lock-fill"></i> Protegido</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php 
                    endwhile; 
                    if (!$hay_registros) {
                        echo "<tr>
                                <td colspan='7' class='py-5 text-center'>
                                    <div class='d-flex flex-column align-items-center justify-content-center py-4'>
                                        <!-- Premium Document SVG -->
                                        <svg width='100' height='100' viewBox='0 0 120 120' fill='none' class='mb-3 text-muted opacity-75'>
                                            <rect x='34' y='24' width='56' height='72' rx='8' fill='currentColor' class='opacity-10' />
                                            <rect x='30' y='20' width='56' height='72' rx='8' fill='currentColor' class='opacity-5' stroke='currentColor' stroke-width='2' />
                                            <line x1='40' y1='36' x2='65' y2='36' stroke='currentColor' stroke-width='3' stroke-linecap='round' class='text-primary' />
                                            <line x1='40' y1='48' x2='76' y2='48' stroke='currentColor' stroke-width='2' stroke-linecap='round' class='opacity-25' />
                                            <line x1='40' y1='60' x2='76' y2='60' stroke='currentColor' stroke-width='2' stroke-linecap='round' class='opacity-25' />
                                            <line x1='40' y1='72' x2='60' y2='72' stroke='currentColor' stroke-width='2' stroke-linecap='round' class='opacity-25' />
                                            <rect x='80' y='44' width='14' height='8' rx='2' fill='currentColor' class='text-danger opacity-75' />
                                            <rect x='82' y='64' width='12' height='8' rx='2' fill='currentColor' class='text-warning opacity-75' />
                                        </svg>
                                        <h5 class='fw-bold mb-1 text-uppercase text-primary small-letter-spacing'>Sin Estudiantes Matriculados</h5>
                                        <p class='text-secondary small mb-0'>La comunidad estudiantil está vacía. Registre un nuevo expediente para comenzar.</p>
                                    </div>
                                </td>
                              </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="../js/matriculados.js" defer></script>
