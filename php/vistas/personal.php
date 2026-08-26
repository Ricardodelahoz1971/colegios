<?php
declare(strict_types=1);
// PHP/VISTAS/PERSONAL.PHP - GESTIÓN INTEGRAL DE TALENTO HUMANO v2.0 (ELITE)
if (!tiene_permiso('personal')) {
    echo "<div class='container py-5 text-center'><h4 class='text-danger fw-bold'>ACCESO DENEGADO</h4><p>No tienes privilegios para gestionar el personal institucional.</p></div>";
    return;
}

// 1. CONSULTA DE ROLES BLINDADA (Excluyendo Estudiantes por rol)
$stmt_roles = $db->prepare("SELECT * FROM roles WHERE nombre_rol NOT LIKE '%Estudiante%' AND nombre_rol NOT LIKE '%Alumno%' ORDER BY id ASC");
$stmt_roles->execute();
$roles_lista = $stmt_roles->fetchAll(PDO::FETCH_ASSOC);
$roles_json_raw = json_encode($roles_lista, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT);

// 2. CONSULTA DE PERMISOS BLINDADA
$stmt_m = $db->prepare("SELECT * FROM permisos ORDER BY nombre ASC");
$stmt_m->execute();
$todos_modulos = $stmt_m->fetchAll(PDO::FETCH_ASSOC);
$todos_modulos_raw = json_encode($todos_modulos, JSON_UNESCAPED_UNICODE);

// 3. CONSULTA DE ESPECIALIDADES BLINDADA
$stmt_esp = $db->prepare("SELECT * FROM especialidades ORDER BY nombre_especialidad ASC");
$stmt_esp->execute();
$especialidades_lista = $stmt_esp->fetchAll(PDO::FETCH_ASSOC);
$especialidades_json_raw = json_encode($especialidades_lista, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT);

// 4. CONSULTA MAESTRA DE PERSONAL CON FICHA DE TALENTO HUMANO (LEFT JOIN)
$stmt_u = $db->prepare("
    SELECT u.id, u.nombre, u.usuario, u.email as email_usuario, u.rol_id, u.especialidad_id, u.permisos_custom,
           r.nombre_rol, e.nombre_especialidad,
           da.tipo_documento, da.documento, da.documento_expedicion, da.fecha_nacimiento, da.edad, da.genero, da.rh, da.foto,
           da.celular, da.telefono_fijo, da.email_personal, da.direccion, da.ciudad_residencia, da.barrio,
           da.titulo_profesional, da.nivel_formacion, da.escalafon_docente, da.fecha_ingreso, da.tipo_contrato, da.estado_laboral,
           da.eps, da.fondo_pensiones, da.arl, da.contacto_emergencia_nombre, da.contacto_emergencia_telefono, da.contacto_emergencia_parentesco
    FROM usuarios u
    LEFT JOIN roles r ON u.rol_id = r.id
    LEFT JOIN especialidades e ON u.especialidad_id = e.id
    LEFT JOIN personal_datos_adicionales da ON u.id = da.usuario_id
    WHERE u.rol_id != 5 OR u.rol_id IS NULL
    ORDER BY u.nombre ASC
");
$stmt_u->execute();

$total_personal = (int)(function($db) { 
    $s = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE rol_id != 5 OR rol_id IS NULL"); 
    $s->execute(); 
    return $s->fetchColumn(); 
})($db) ?: 0;
?>

<div class="container-fluid py-4" id="personal-container">
    
    <!-- MIGA DE PAN (BREADCRUMB ELITE) -->
    <div class="px-3 mb-3">
        <nav class="breadcrumb-elite" aria-label="Ruta de navegación">
            <a href="javascript:void(0)" onclick="navegarModulo('inicio')" class="breadcrumb-link-elite"><i class="bi bi-house-door me-1"></i>Inicio</a>
            <i class="bi bi-chevron-right breadcrumb-separator-elite"></i>
            <span class="breadcrumb-item-elite">Administración</span>
            <i class="bi bi-chevron-right breadcrumb-separator-elite"></i>
            <span class="breadcrumb-current-elite">Talento Humano</span>
        </nav>
    </div>

    <!-- CABECERA UNIFICADA POWER HEADER (VITRINA 06+) -->
    <div class="row align-items-end mb-4 g-3 px-3">
        <div class="col-xl-5 col-lg-5 text-center text-md-start border-start border-4 border-topbar-elite ps-4">
            <h4 class="h3 fw-bold mb-0 text-titulo-elite">Talento Humano y Nómina</h4>
            <p class="text-secondary mb-0 small">Expedientes laborales, planta docente y directiva institucional.</p>
            <div class="mt-3">
                <div class="metric-card-elite variant-primary d-inline-block p-3 metric-card-elite--scaled">
                    <div class="metric-header-elite mb-2">
                        <div class="metric-icon-box-elite">
                            <i class="bi bi-person-badge-fill"></i>
                        </div>
                        <span class="metric-label-elite">Planta Institucional</span>
                    </div>
                    <div class="d-flex align-items-baseline gap-2">
                        <div class="metric-value-elite mb-0 fs-large-elite"><?php echo intval($total_personal); ?></div>
                        <span class="text-secondary small fw-bold">Colaboradores</span>
                    </div>
                    <div class="metric-footer-elite text-primary mt-2 bg-primary-faded rounded-1 px-2 fs-nano-elite">
                        <i class="bi bi-shield-check me-1"></i> Expedientes activos en nómina
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-7 col-lg-7">
            <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-md-end align-items-center">
                <div class="search-wrapper-elite flex-grow-1 max-w-400 position-relative">
                    <span class="search-icon-elite"><i class="bi bi-search"></i></span>
                    <input type="text" id="buscar-personal" class="search-elite--wrapped buscador-dinamico" placeholder="Buscar por nombre, documento o cargo...">
                    <button class="btn-clear-search btn-close-elite d-none" title="Limpiar búsqueda">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
                <?php if (tiene_permiso('roles')): ?>
                    <button onclick="navegarModulo('roles')" class="btn-elite btn-elite--outline px-3 u-nowrap">
                        <i class="bi bi-shield-lock me-1"></i>
                        MATRIZ ROLES
                    </button>
                <?php endif; ?>
                <button id="btn-nuevo-personal" 
                        class="btn-elite px-3 u-nowrap" 
                        data-roles="<?php echo htmlspecialchars($roles_json_raw, ENT_QUOTES, 'UTF-8'); ?>" 
                        data-especialidades="<?php echo htmlspecialchars($especialidades_json_raw, ENT_QUOTES, 'UTF-8'); ?>" 
                        onclick="nuevoPersonal(this)">
                    <i class="bi bi-person-plus-fill me-1"></i>
                    VINCULAR PERSONAL
                </button>
            </div>
        </div>
    </div>

    <!-- TABLA UNIFICADA DE TALENTO HUMANO -->
    <div class="card shadow-sm border-0 rounded-4 bg-white mb-5 mx-3">
        <div class="table-responsive-elite table-scroll-elite">
            <table class="table-elite tabla-datos align-middle mb-0">
                <thead>
                    <tr>
                        <th class="text-center col-pers-doc">Documento</th>
                        <th class="text-center col-pers-colab">Colaborador</th>
                        <th class="text-center col-pers-user">Usuario</th>
                        <th class="text-center col-pers-cargo">Cargo</th>
                        <th class="text-center col-pers-contacto">Contacto</th>
                        <th class="text-center col-pers-actions">Acciones</th>
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
                        
                        // Separar Nombres y Apellidos limpiamente para 2 líneas
                        $nombre_completo = trim((string)($row['nombre'] ?? ''));
                        $partes_nom = explode(' ', $nombre_completo);
                        $cant_p = count($partes_nom);
                        if ($cant_p >= 4) {
                            $nombres_str = $partes_nom[0] . ' ' . $partes_nom[1];
                            $apellidos_str = implode(' ', array_slice($partes_nom, 2));
                        } elseif ($cant_p === 3) {
                            $nombres_str = $partes_nom[0];
                            $apellidos_str = $partes_nom[1] . ' ' . $partes_nom[2];
                        } elseif ($cant_p === 2) {
                            $nombres_str = $partes_nom[0];
                            $apellidos_str = $partes_nom[1];
                        } else {
                            $nombres_str = $nombre_completo;
                            $apellidos_str = '';
                        }
                        
                        $doc_str = !empty($row['documento']) 
                            ? htmlspecialchars(($row['tipo_documento'] ?? 'CC') . ' ' . $row['documento'])
                            : '<span class="text-muted small italic">S/N</span>';
                        
                        $cel_str = !empty($row['celular']) 
                            ? htmlspecialchars($row['celular'])
                            : (!empty($row['email_personal']) ? htmlspecialchars($row['email_personal']) : '<span class="text-muted small italic">Sin Contacto</span>');
                        
                        $ciudad_str = !empty($row['ciudad_residencia']) ? htmlspecialchars($row['ciudad_residencia']) : '';

                        $insignia_soberania = ($row['permisos_custom'] == 1) 
                            ? ' <span class="badge-soberania" title="Este usuario tiene una matriz de permisos personalizada">
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                                Custom
                               </span>' 
                            : '';

                        // Consultas de permisos
                        $stmt_p_ind = $db->prepare("SELECT permiso_id FROM usuario_permisos WHERE usuario_id = :uid");
                        $stmt_p_ind->bindValue(':uid', $u_id, PDO::PARAM_INT);
                        $stmt_p_ind->execute();
                        $permisos_individuales = $stmt_p_ind->fetchAll(PDO::FETCH_COLUMN);

                        $stmt_p_rol = $db->prepare("SELECT permiso_id FROM rol_permisos WHERE rol_id = :rid");
                        $stmt_p_rol->bindValue(':rid', $rol_id_actual, PDO::PARAM_INT);
                        $stmt_p_rol->execute();
                        $permisos_base = $stmt_p_rol->fetchAll(PDO::FETCH_COLUMN);

                        $permisos_ind_js  = htmlspecialchars(json_encode($permisos_individuales, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                        $permisos_base_js = htmlspecialchars(json_encode($permisos_base, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                        $row_json = htmlspecialchars(json_encode($row, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');

                        echo "<tr>";
                            echo "<td class='text-start ps-3 col-pers-doc'><span class='fw-bold text-dark small u-nowrap'>" . $doc_str . "</span></td>";
                            echo "<td class='text-start ps-3 col-pers-colab'>
                                    <div class='student-info-elite'>
                                        <div class='d-flex align-items-center gap-2'>
                                            <span class='student-name-elite text-uppercase fw-bold text-dark u-nowrap'>" . htmlspecialchars($nombres_str) . "</span>
                                            " . $insignia_soberania . "
                                        </div>
                                        " . (!empty($apellidos_str) ? "<div class='student-surname-elite text-secondary small fw-semibold text-uppercase u-nowrap'>" . htmlspecialchars($apellidos_str) . "</div>" : "") . "
                                        <span class='student-meta-elite'>" . htmlspecialchars($row['email_usuario'] ?? 'sin_correo@elite.edu.co') . "</span>
                                    </div>
                                  </td>";
                            echo "<td class='text-start ps-3 col-pers-user'><span class='table-badge-elite text-primary font-monospace fw-bold'>" . htmlspecialchars($row['usuario']) . "</span></td>";
                            echo "<td class='text-start ps-3 col-pers-cargo'>
                                    <div class='d-flex align-items-center justify-content-start gap-2'>
                                        <span class='badge-elite badge-elite--info fw-bold fs-nano u-nowrap'>" . htmlspecialchars($rol_mostrado) . "</span>
                                        " . (!empty($row['nombre_especialidad']) ? "<span class='text-primary small fw-bold u-nowrap'>" . htmlspecialchars($row['nombre_especialidad']) . "</span>" : "<span class='text-muted small u-nowrap'>Institucional</span>") . "
                                    </div>
                                  </td>";
                            echo "<td class='text-start ps-3 col-pers-contacto'>
                                    <div class='d-flex flex-column align-items-start'>
                                        <span class='small fw-semibold text-dark u-nowrap'><i class='bi bi-telephone text-primary me-1'></i>" . $cel_str . "</span>
                                        " . (!empty($ciudad_str) ? "<span class='text-muted fs-nano u-nowrap'><i class='bi bi-geo-alt me-1'></i>" . $ciudad_str . "</span>" : "") . "
                                    </div>
                                  </td>";
                            
                            echo "<td class='text-center py-2 col-pers-actions'>
                                    <div class='d-flex justify-content-center gap-2'>
                                        <button class='btn-elite-icon' 
                                                onclick='gestionarPermisos(" . $u_id . ", \"" . htmlspecialchars($row['nombre'], ENT_QUOTES) . "\", " . $permisos_ind_js . ", " . htmlspecialchars($todos_modulos_raw, ENT_QUOTES, "UTF-8") . ", \"usuario\", " . $permisos_base_js . ", \"" . htmlspecialchars($rol_mostrado, ENT_QUOTES) . "\", " . ($row['permisos_custom'] ?? 0) . ")' 
                                                title='Permisos y Módulos'>
                                            <i class='bi bi-shield-lock'></i>
                                        </button>
                                        <button class='btn-elite-icon' 
                                                data-personal='" . $row_json . "' 
                                                data-roles='" . htmlspecialchars($roles_json_raw, ENT_QUOTES, 'UTF-8') . "' 
                                                data-especialidades='" . htmlspecialchars($especialidades_json_raw, ENT_QUOTES, 'UTF-8') . "' 
                                                onclick='editarPersonal(" . $u_id . ", this)' 
                                                title='Editar Expediente Completo'>
                                            <i class='bi bi-pencil-square'></i>
                                        </button>
                                        <button class='btn-elite-icon btn-elite-icon--danger' onclick='borrarPersonal(" . $u_id . ", \"" . htmlspecialchars($row['nombre'], ENT_QUOTES) . "\")' title='Baja Institucional'>
                                            <i class='bi bi-trash3'></i>
                                        </button>
                                    </div>
                                  </td>";
                        echo "</tr>";
                    }
                    if (!$hay_registros) {
                        echo "<tr>
                                <td colspan='6' class='py-5 text-center'>
                                    <div class='d-flex flex-column align-items-center justify-content-center py-4'>
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
