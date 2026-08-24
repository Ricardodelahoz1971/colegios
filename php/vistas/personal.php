<?php
declare(strict_types=1);
// PHP/VISTAS/PERSONAL.PHP - GESTIÓN DE PERSONAL
if (!tiene_permiso('personal')) {
    echo "<div class='container py-5 text-center'><h4 class='text-danger fw-bold'>ACCESO DENEGADO</h4><p>No tienes privilegios para gestionar el personal institucional.</p></div>";
    return;
}
// --- INFRAESTRUCTURA DE SEGURIDAD (Migración Completa) ---
// La estructura de tablas usuarios y usuario_permisos ya ha sido validada.
// --- FIN INFRAESTRUCTURA ---

// 1. CONSULTA DE ROLES BLINDADA (Excluyendo Estudiantes por rol)
$stmt_roles = $db->prepare("SELECT * FROM roles WHERE nombre_rol NOT LIKE '%Estudiante%' AND nombre_rol NOT LIKE '%Alumno%' ORDER BY id ASC");
$stmt_roles->execute();
$roles_lista = $stmt_roles->fetchAll(PDO::FETCH_ASSOC);

// 2. CONSULTA DE PERMISOS BLINDADA
$stmt_m = $db->prepare("SELECT * FROM permisos ORDER BY nombre ASC");
$stmt_m->execute();
$todos_modulos = $stmt_m->fetchAll(PDO::FETCH_ASSOC);

// 3. CONSULTA DE ESPECIALIDADES BLINDADA
$stmt_esp = $db->prepare("SELECT * FROM especialidades ORDER BY nombre_especialidad ASC");
$stmt_esp->execute();
$especialidades_lista = $stmt_esp->fetchAll(PDO::FETCH_ASSOC);
$especialidades_json = htmlspecialchars(json_encode($especialidades_lista), ENT_QUOTES, 'UTF-8');

// 4. CONSULTA MAESTRA DE PERSONAL (Bóveda de Identidad - Solo Empleados)
$stmt_u = $db->prepare("
    SELECT u.id, u.nombre, u.usuario, u.rol_id, u.especialidad_id, u.permisos_custom, r.nombre_rol, e.nombre_especialidad 
    FROM usuarios u
    LEFT JOIN roles r ON u.rol_id = r.id
    LEFT JOIN especialidades e ON u.especialidad_id = e.id
    WHERE u.rol_id != 5 OR u.rol_id IS NULL
    ORDER BY u.nombre ASC
");
$stmt_u->execute();
?>

<div class="container-fluid py-4">
    
    <!-- CABECERA UNIFICADA BOOTSTRAP 5 -->
    <div class="row align-items-center mb-4 g-3">
        <div class="col-md-7 text-center text-md-start border-start border-4 border-primary ps-4">
            <h4 class="h3 fw-bold mb-0 text-titulo-elite">Gestión de Personal Escolar</h4>
            <p class="text-secondary mb-0 small">Administración de docentes, directivos y permisos especiales.</p>
            <nav class="breadcrumb-elite" aria-label="Ruta de navegación">
                <a href="javascript:void(0)" onclick="navegarModulo('inicio')" class="breadcrumb-link-elite"><i class="bi bi-house-door me-1"></i>Inicio</a>
                <i class="bi bi-chevron-right breadcrumb-separator-elite"></i>
                <span class="breadcrumb-item-elite">Administración</span>
                <i class="bi bi-chevron-right breadcrumb-separator-elite"></i>
                <span class="breadcrumb-current-elite">Talento Humano</span>
            </nav>
        </div>
        <div class="col-md-5">
            <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-md-end">
                <div class="search-wrapper-elite w-100 u-max-w-300">
                    <span class="search-icon-elite"><i class="bi bi-search"></i></span>
                    <input type="text" id="buscar-personal" class="search-elite--wrapped ares-h-44 buscador-dinamico" placeholder="Filtrar por nombre o cargo...">
                </div>
                <?php if (tiene_permiso('roles')): ?>
                    <button onclick="navegarModulo('roles')" class="btn-elite btn-elite--outline btn-elite--sm">
                        <i class="bi bi-shield-lock me-2"></i>
                        Registrar Rol
                    </button>
                <?php endif; ?>
                <button onclick='nuevoPersonal(<?php echo htmlspecialchars(json_encode($roles_lista), ENT_QUOTES, "UTF-8"); ?>, <?php echo $especialidades_json; ?>)' class="btn-elite btn-elite--sm">
                    <i class="bi bi-person-plus me-2"></i>
                    Registrar Personal
                </button>
            </div>
        </div>
    </div>

    <!-- TABLA UNIFICADA -->
    <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
        <div class="table-responsive-elite table-scroll-elite">
            <table class="table-elite tabla-datos table-hover table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th class="py-3 ps-4 text-uppercase small fw-800 col-id">ID</th>
                        <th class="py-3 text-uppercase small fw-800">Nombre Completo</th>
                        <th class="py-3 text-uppercase small fw-800 text-center">Usuario</th>
                        <th class="py-3 text-uppercase small fw-800">Rol / Cargo Especialidad</th>
                        <th class="py-3 text-center text-uppercase small fw-800 col-actions-md">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white">
                    <?php
                    $hay_registros = false;
                    while ($row = $stmt_u->fetch(PDO::FETCH_ASSOC)) {
                        $hay_registros = true;
                        $u_id = $row['id'];
                        $rol_id_actual = $row['rol_id'] ?? 0;
                        $especialidad_id_actual = $row['especialidad_id'] ?? 0;
                        $rol_mostrado = $row['nombre_rol'] ?? '⚠️ Sin Rol';
                        $esp_mostrada = $row['nombre_especialidad'] ? ' <span class="text-especialidad-mini">[' . htmlspecialchars($row['nombre_especialidad']) . ']</span>' : '';
                        
                        // Determinar si tiene Permisos Custom (Permisos Individuales)
                        $insignia_soberania = ($row['permisos_custom'] == 1) 
                            ? ' <span class="badge-soberania" title="Este usuario tiene una matriz de permisos personalizada (Permisos Custom)">
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                                Permisos Custom
                               </span>' 
                            : '';

                        // Consultas de permisos optimizadas con fetchAll(PDO::FETCH_COLUMN)
                        $stmt_p_ind = $db->prepare("SELECT permiso_id FROM usuario_permisos WHERE usuario_id = :uid");
                        $stmt_p_ind->bindValue(':uid', $u_id, PDO::PARAM_INT);
                        $stmt_p_ind->execute();
                        $permisos_individuales = $stmt_p_ind->fetchAll(PDO::FETCH_COLUMN);

                        $stmt_p_rol = $db->prepare("SELECT permiso_id FROM rol_permisos WHERE rol_id = :rid");
                        $stmt_p_rol->bindValue(':rid', $rol_id_actual, PDO::PARAM_INT);
                        $stmt_p_rol->execute();
                        $permisos_base = $stmt_p_rol->fetchAll(PDO::FETCH_COLUMN);

                        echo "<tr>";
                            echo "<td class='ps-4 text-muted fw-bold small'>#" . $u_id . "</td>";
                            echo "<td>
                                    <div class='d-flex align-items-center gap-2'>
                                        <div class='fw-semibold text-dark text-uppercase small'>" . htmlspecialchars($row['nombre']) . "</div>
                                        " . $insignia_soberania . "
                                    </div>
                                  </td>";
                            echo "<td class='text-center'><span class='table-badge-elite text-primary'>" . htmlspecialchars($row['usuario']) . "</span></td>";
                            echo "<td><div class='fw-normal small text-secondary'>" . $rol_mostrado . $esp_mostrada . "</div></td>";

                            
                            $roles_json_safe  = htmlspecialchars(json_encode($roles_lista), ENT_QUOTES, 'UTF-8');
                            $permisos_ind_js  = htmlspecialchars(json_encode($permisos_individuales), ENT_QUOTES, 'UTF-8');
                            $todos_modulos_js = htmlspecialchars(json_encode($todos_modulos), ENT_QUOTES, 'UTF-8');
                            $permisos_base_js = htmlspecialchars(json_encode($permisos_base), ENT_QUOTES, 'UTF-8');
                            
                            echo "<td class='text-center py-2'>
                                    <div class='d-flex justify-content-center gap-2'>
                                        <button class='btn-elite-icon' 
                                                onclick='gestionarPermisos(" . $u_id . ", \"" . htmlspecialchars($row['nombre'], ENT_QUOTES) . "\", " . $permisos_ind_js . ", " . $todos_modulos_js . ", \"usuario\", " . $permisos_base_js . ", \"" . htmlspecialchars($rol_mostrado, ENT_QUOTES) . "\", " . ($row['permisos_custom'] ?? 0) . ")' 
                                                title='Permisos'>
                                            <i class='bi bi-shield-lock'></i>
                                        </button>
                                        <button class='btn-elite-icon' onclick='editarPersonal(" . $u_id . ", \"" . htmlspecialchars($row['nombre'], ENT_QUOTES) . "\", \"" . htmlspecialchars($row['usuario'], ENT_QUOTES) . "\", " . $rol_id_actual . ", " . $roles_json_safe . ", " . $especialidad_id_actual . ", " . $especialidades_json . ")' title='Editar'>
                                            <i class='bi bi-pencil-square'></i>
                                        </button>
                                        <button class='btn-elite-icon btn-elite-icon--danger' onclick='borrarPersonal(" . $u_id . ", \"" . htmlspecialchars($row['nombre'], ENT_QUOTES) . "\")' title='Baja'>
                                            <i class='bi bi-trash3'></i>
                                        </button>
                                    </div>
                                  </td>";
                        echo "</tr>";
                    }
                    if (!$hay_registros) {
                        echo "<tr>
                                <td colspan='5' class='py-5 text-center'>
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
                                        <h5 class='fw-bold mb-1 text-uppercase text-primary small-letter-spacing'>Sin Personal Registrado</h5>
                                        <p class='text-secondary small mb-0'>No se detectó personal docente o administrativo registrado aparte del administrador.</p>
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
