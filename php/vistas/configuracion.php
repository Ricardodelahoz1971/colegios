<?php
// @sast-ignore - Deuda técnica legacy controlada bajo línea base de seguridad v5.0
declare(strict_types=1);
// PHP/VISTAS/CONFIGURACION.PHP - RESTAURACIÓN TOTAL v18.0 (AUDIT CLEAN)

$stmt_res_e = $db->prepare("SELECT * FROM ajustes_estetica"); 
$stmt_res_e->execute(); 
$res_e = $stmt_res_e;
$cfg = [];
while ($e = $res_e->fetch(PDO::FETCH_ASSOC)) {
    $cfg[$e['clave']] = $e['valor'];
}

// 🏛️ CARGAR CONFIGURACIONES GLOBALES DESDE EL CORE (PDO)
$stmt_global = $db->prepare("SELECT clave, valor FROM configuracion_global");
$stmt_global->execute();
$global_cfg = $stmt_global->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
$privacidad_sabana = $global_cfg['privacidad_catedratico_sabana'] ?? 'estricto';
$politica_recup = $global_cfg['politica_recuperacion'] ?? 'reemplazo';
$dias_gracia = (int)($global_cfg['dias_gracia_recuperaciones'] ?? 5);
$limite_horas = (int)($global_cfg['limite_horas_docente'] ?? 24);

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
$stmt_dim = $db->prepare("SELECT * FROM ares_clases_nota WHERE estado = 1 ORDER BY id"); $stmt_dim->execute();
$dimensiones = $stmt_dim->fetchAll(PDO::FETCH_ASSOC) ?: [];

// 🏛️ CARGAR PERIODOS ACADÉMICOS PARA EL CALENDARIO ESCOLAR
$stmt_p_ac = $db->prepare("SELECT * FROM eval_periodos_academicos ORDER BY id"); $stmt_p_ac->execute();
$periodos_academicos = $stmt_p_ac->fetchAll(PDO::FETCH_ASSOC) ?: [];

// 🏛️ CARGAR JORNADAS ACTIVAS EN CURSOS PARA KHRONOS MULTIJORNADA DINDAMICO
$stmt_j = $db->prepare("SELECT DISTINCT jornada FROM cursos WHERE jornada IS NOT NULL AND jornada != ''"); $stmt_j->execute();
$jornadas_activas = $stmt_j->fetchAll(PDO::FETCH_COLUMN) ?: ['Mañana'];

if (!function_exists('obtener_khronos_cfg')) {
    function obtener_khronos_cfg($clave, $jornada, $cfg, $fallback = '') {
        $suffix = strtolower(str_replace([' ', 'á', 'é', 'í', 'ó', 'ú'], ['', 'a', 'e', 'i', 'o', 'u'], $jornada));
        $clave_jornada = $clave . '_' . $suffix;
        if (isset($cfg[$clave_jornada]) && $cfg[$clave_jornada] !== '') {
            return $cfg[$clave_jornada];
        }
        return $cfg[$clave] ?? $fallback;
    }
}

// Centinela de Administración de Sistemas (Profesor de Sistemas / Admin)
$es_admin_sistema = ((int)($_SESSION['rol_id'] ?? 0) === 1 || strpos(strtolower($_SESSION['nombre_usuario'] ?? ''), 'admin') !== false);

// Centinela de Autoridad Suprema Institucional (Rector o Administrador)
$es_admin_o_rector = (in_array((int)($_SESSION['rol_id'] ?? 0), [1, 3]) 
    || strpos(strtolower($_SESSION['nombre_usuario'] ?? ''), 'admin') !== false 
    || strpos(strtolower($_SESSION['nombre_usuario'] ?? ''), 'rector') !== false);

$mostrar_pestana_herramientas = (in_array((int)($_SESSION['rol_id'] ?? 0), [1, 2])
    || strpos(strtolower($_SESSION['nombre_usuario'] ?? ''), 'admin') !== false);

$tab_activa = 'identidad';
if (!$es_admin_o_rector) {
    $tab_activa = 'jornada';
}
if (isset($_GET['success']) && (strpos($_GET['success'], 'adn') !== false || strpos($_GET['success'], 'paleta') !== false)) {
    $tab_activa = 'visual';
}
?>

<link rel="stylesheet" href="../styles/modules/khronos_time_input.css?v=<?php echo time(); ?>">

<div class="config-container-master animate__animated animate__fadeIn">
    
    <!-- TABS DE NAVEGACIÓN COMPACTAS (ESTILO GENBETA) -->
    <div class="nav-elite-header-container">
        <ul class="nav nav-pills-elite" id="pills-tab" role="tablist">
            <?php if ($es_admin_o_rector): ?>
            <li class="nav-item-elite">
                <button class="nav-link-elite <?php echo ($tab_activa === 'identidad') ? 'active' : ''; ?>" id="pills-identidad-tab" data-bs-toggle="pill" data-bs-target="#tab-identidad">
                    <i class="bi bi-house-door me-2"></i>Identidad
                </button>
            </li>
            <li class="nav-item-elite">
                <button class="nav-link-elite <?php echo ($tab_activa === 'visual') ? 'active' : ''; ?>" id="pills-adn-tab" data-bs-toggle="pill" data-bs-target="#tab-visual">
                    <i class="bi bi-palette me-2"></i>Temas
                </button>
            </li>
            <?php endif; ?>
            <li class="nav-item-elite">
                <button class="nav-link-elite <?php echo ($tab_activa === 'jornada') ? 'active' : ''; ?>" id="pills-jornada-tab" data-bs-toggle="pill" data-bs-target="#tab-jornada">
                    <i class="bi bi-clock-history me-2"></i>Jornada
                </button>
            </li>
            <li class="nav-item-elite">
                <button class="nav-link-elite" id="pills-global-tab" data-bs-toggle="pill" data-bs-target="#tab-global">
                    <i class="bi bi-sliders me-2"></i>Académico
                </button>
            </li>
            <?php if ($mostrar_pestana_herramientas): ?>
            <li class="nav-item-elite">
                <button class="nav-link-elite" id="pills-mantenimiento-tab" data-bs-toggle="pill" data-bs-target="#tab-mantenimiento">
                    <i class="bi bi-tools me-2"></i>Herramientas
                </button>
            </li>
            <?php endif; ?>
            <li class="ms-auto d-flex align-items-center pe-3">
                <div id="sync-status-indicator" class="sync-indicator-elite">
                    <i class="bi bi-check-all me-1"></i>Sincronizado
                </div>
            </li>
        </ul>
    </div>

    <div class="tab-content" id="pills-tabContent">
        <?php if ($es_admin_o_rector): ?>
        <!-- TAB 1: IDENTIDAD -->
        <div class="tab-pane fade <?php echo ($tab_activa === 'identidad') ? 'show active' : ''; ?>" id="tab-identidad">
            <div class="row g-4 pt-2">
                <div class="col-md-7">
                    <div class="config-main-card-elite">
                        <form id="formBranding" action="logica/guardar_estetica.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo generar_csrf_token(); ?>">
                            <div class="mb-4">
                                <label for="school_name" class="form-label small fw-bold text-uppercase">Nombre de la Institución</label>
                                <input type="text" id="school_name" name="school_name" class="input-elite" value="<?php echo $cfg['school_name'] ?? ''; ?>">
                            </div>
                            <div class="mb-4">
                                <label for="school_motto" class="form-label small fw-bold text-uppercase">Lema Institucional</label>
                                <input type="text" id="school_motto" name="school_motto" class="input-elite" value="<?php echo $cfg['school_motto'] ?? ''; ?>">
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-6">
                                    <label for="colegio_nit" class="form-label small fw-bold text-uppercase">NIT del Colegio</label>
                                    <input type="text" id="colegio_nit" name="colegio_nit" class="input-elite" placeholder="Ej: 800123456-7" value="<?php echo htmlspecialchars($cfg['colegio_nit'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="col-6">
                                    <label for="colegio_resolucion" class="form-label small fw-bold text-uppercase">Resolución Oficial</label>
                                    <input type="text" id="colegio_resolucion" name="colegio_resolucion" class="input-elite" placeholder="Ej: Res. 001234 de 2024" value="<?php echo htmlspecialchars($cfg['colegio_resolucion'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="input-logo-file" class="form-label small fw-bold text-uppercase">Cargar Logo</label>
                                <div class="file-upload-elite-wrapper">
                                    <div class="file-upload-elite-btn">Seleccionar Archivo</div>
                                    <div class="file-upload-elite-text" id="logo-file-name">Sin archivos seleccionados</div>
                                    <input type="file" name="school_logo" id="input-logo-file" class="file-upload-elite-input" accept="image/*">
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="config-main-card-elite text-center h-100 d-flex flex-column justify-content-center">
                        <span class="fw-bold text-muted small text-uppercase mb-3">Vista Previa del Logo</span>
                        <div class="config-preview-card mx-auto">
                            <?php 
                            $logo_path = $cfg['school_logo'] ?? '';
                            if (!empty($logo_path) && strpos($logo_path, 'http') === false) { $logo_path = '../' . $logo_path; }
                            if (!empty($logo_path)): ?>
                                <img src="<?php echo $logo_path; ?>" class="preview-img-fit">
                            <?php else: ?>
                                <i class="bi bi-image text-muted fs-1"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: ADN -->
        <div class="tab-pane fade <?php echo ($tab_activa === 'visual') ? 'show active' : ''; ?>" id="tab-visual">
            <div class="pt-2">
                <div class="row g-4">
                    <div class="col-md-5">
                        <div class="config-main-card-elite">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span id="label-identidades" class="small fw-bold text-uppercase text-muted">Identidades Registradas</span>
                            </div>
                            <div id="paletas-container" class="palette-scroll-area-elite" aria-labelledby="label-identidades">
                                <?php 
                                $stmt_stmt_pal = $db->prepare("SELECT * FROM paletas_elite ORDER BY id DESC"); $stmt_stmt_pal->execute(); $stmt_pal = $stmt_stmt_pal;
                                $paletas = $stmt_pal->fetchAll();
                                $id_activa = $cfg['active_palette_id'] ?? '1';
                                foreach ($paletas as $p): 
                                    $is_active = ($p['id'] == $id_activa);
                                ?>
                                <div class="palette-swatch-elite-mini <?php echo $is_active ? 'active' : ''; ?>" 
                                     onclick="window.aplicarIdentidadElite('<?php echo $p['id']; ?>')">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="color-circle-elite-micro" data-bg="<?php echo $p['primary_color']; ?>"></div>
                                        <div class="color-circle-elite-micro" data-bg="<?php echo $p['accent_color']; ?>"></div>
                                        <div class="color-circle-elite-micro" data-bg="<?php echo $p['info_color']; ?>"></div>
                                    </div>
                                    <span class="ms-3 fw-bold text-uppercase fs-nano"><?php echo htmlspecialchars($p['nombre']); ?></span>
                                    <div class="ms-auto d-flex gap-1">
                                        <button class="btn-action-elite text-primary" onclick="event.stopPropagation(); window.abrirEditorPaleta({id: '<?php echo $p['id']; ?>', nombre: '<?php echo addslashes($p['nombre']); ?>', p: '<?php echo $p['primary_color']; ?>', a: '<?php echo $p['accent_color']; ?>', i: '<?php echo $p['info_color']; ?>'})">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button class="btn-action-elite text-danger" onclick="event.stopPropagation(); window.borrarPaleta('<?php echo $p['id']; ?>', '<?php echo $p['nombre']; ?>')">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <div class="config-main-card-elite">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label for="menu-style-selector" class="small fw-bold text-uppercase text-muted d-block mb-3">Arquitectura del Sistema</label>
                                    <select id="menu-style-selector" class="select-elite-sm w-100" onchange="window.actualizarArquitecturaMenu(this.value)">
                                        <option value="legacy" <?php echo ($cfg['menu_style'] ?? '') === 'legacy' ? 'selected' : ''; ?>>✦ ESTÁNDAR</option>
                                        <option value="aero" <?php echo ($cfg['menu_style'] ?? '') === 'aero' ? 'selected' : ''; ?>>✦ AERO (GLASS)</option>
                                        <option value="blade" <?php echo ($cfg['menu_style'] ?? '') === 'blade' ? 'selected' : ''; ?>>✦ BLADE (INDUSTRIAL)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="font-family-selector" class="small fw-bold text-uppercase text-muted d-block mb-3">Tipografía Maestro</label>
                                    <select id="font-family-selector" class="select-elite-sm w-100" onchange="window.cambiarTipografiaSistema(this.value)">
                                        <option value="Montserrat" <?php echo ($cfg['school_font'] ?? '') === 'Montserrat' ? 'selected' : ''; ?>>✦ MONTSERRAT</option>
                                        <option value="Roboto" <?php echo ($cfg['school_font'] ?? '') === 'Roboto' ? 'selected' : ''; ?>>✦ ROBOTO</option>
                                        <option value="Inter" <?php echo ($cfg['school_font'] ?? '') === 'Inter' ? 'selected' : ''; ?>>✦ INTER</option>
                                        <option value="Outfit" <?php echo ($cfg['school_font'] ?? '') === 'Outfit' ? 'selected' : ''; ?>>✦ OUTFIT</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- TAB 3: KHRONOS -->
        <div class="tab-pane fade <?php echo ($tab_activa === 'jornada') ? 'show active' : ''; ?>" id="tab-jornada">
            <div class="row g-4 pt-2">
                <div class="col-md-7">
                    <div class="config-main-card-elite">
                        <form id="formKhronos" action="logica/guardar_estetica.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo generar_csrf_token(); ?>">
                            
                            <?php if (count($jornadas_activas) > 1): ?>
                                <!-- Sub-tabs para múltiples jornadas -->
                                <ul class="nav nav-pills-elite mb-3 border-bottom pb-2" id="pills-tab-jornadas-khronos" role="tablist">
                                    <?php foreach ($jornadas_activas as $idx => $jor):
                                        $suffix = strtolower(str_replace([' ', 'á', 'é', 'í', 'ó', 'ú'], ['', 'a', 'e', 'i', 'o', 'u'], $jor));
                                    ?>
                                        <li class="nav-item-elite" role="presentation">
                                            <button class="nav-link-elite <?php echo $idx === 0 ? 'active' : ''; ?>" id="pills-jornada-<?php echo $suffix; ?>-tab" data-bs-toggle="pill" data-bs-target="#tab-jornada-<?php echo $suffix; ?>" type="button" data-suffix="<?php echo $suffix; ?>"><?php echo htmlspecialchars($jor); ?></button>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>

                            <div class="<?php echo count($jornadas_activas) > 1 ? 'tab-content' : ''; ?>">
                                <?php foreach ($jornadas_activas as $idx => $jor): 
                                    $suffix = strtolower(str_replace([' ', 'á', 'é', 'í', 'ó', 'ú'], ['', 'a', 'e', 'i', 'o', 'u'], $jor));
                                    // Para Mañana (o si solo hay 1 jornada), usamos el nombre base por retrocompatibilidad, de lo contrario usamos sufijo
                                    $is_default = (count($jornadas_activas) === 1 || $suffix === 'manana');
                                    $sufijo_input = $is_default ? '' : '_' . $suffix;
                                    
                                    $val_inicio = obtener_khronos_cfg('khronos_inicio', $jor, $cfg, '06:30');
                                    $val_duracion = obtener_khronos_cfg('khronos_duracion', $jor, $cfg, '60');
                                    $val_max_horas = obtener_khronos_cfg('khronos_max_horas', $jor, $cfg, '6');
                                    $val_max_diario = obtener_khronos_cfg('khronos_max_diario_materia', $jor, $cfg, '2');
                                    $val_dias = obtener_khronos_cfg('khronos_dias', $jor, $cfg, 'lun,mar,mie,jue,vie');
                                    $val_desc1_h = obtener_khronos_cfg('khronos_descanso_h', $jor, $cfg, '2');
                                    $val_desc1_m = obtener_khronos_cfg('khronos_descanso_m', $jor, $cfg, '15');
                                    $val_desc2_h = obtener_khronos_cfg('khronos_descanso2_h', $jor, $cfg, '4');
                                    $val_desc2_m = obtener_khronos_cfg('khronos_descanso2_m', $jor, $cfg, '15');
                                ?>
                                    <div class="<?php echo count($jornadas_activas) > 1 ? 'tab-pane fade ' . ($idx === 0 ? 'show active' : '') : ''; ?>" id="tab-jornada-<?php echo $suffix; ?>">
                                        <!-- GRID MAESTRO DE 2 COLUMNAS (Sincronización Binaria) -->
                                        <div class="config-flex-row-elite mb-2">
                                            <div class="config-flex-col-elite">
                                                <div class="config-input-card-elite">
                                                    <label for="khronos_inicio<?php echo $sufijo_input; ?>">Apertura (Inicio)</label>
                                                    <input type="time" id="khronos_inicio<?php echo $sufijo_input; ?>" name="khronos_inicio<?php echo $sufijo_input; ?>" class="input-time-khronos" oninput="window.actualizarVistaKhronos()" value="<?php echo $val_inicio; ?>">
                                                </div>
                                            </div>
                                            <div class="config-flex-col-elite">
                                                <div class="config-input-card-elite">
                                                    <label for="khronos_duracion<?php echo $sufijo_input; ?>">Bloque Académico (Min)</label>
                                                    <input type="number" id="khronos_duracion<?php echo $sufijo_input; ?>" name="khronos_duracion<?php echo $sufijo_input; ?>" class="input-elite" oninput="window.actualizarVistaKhronos()" value="<?php echo $val_duracion; ?>">
                                                </div>
                                            </div>
                                            <div class="config-flex-col-elite">
                                                <div class="config-input-card-elite">
                                                    <label for="khronos_max_horas<?php echo $sufijo_input; ?>">Carga Horaria</label>
                                                    <input type="number" id="khronos_max_horas<?php echo $sufijo_input; ?>" name="khronos_max_horas<?php echo $sufijo_input; ?>" class="input-elite" oninput="window.actualizarVistaKhronos()" value="<?php echo $val_max_horas; ?>">
                                                </div>
                                            </div>
                                            <div class="config-flex-col-elite">
                                                <div class="config-input-card-elite">
                                                    <label for="khronos_max_diario_materia<?php echo $sufijo_input; ?>">Límite Fatiga</label>
                                                    <input type="number" id="khronos_max_diario_materia<?php echo $sufijo_input; ?>" name="khronos_max_diario_materia<?php echo $sufijo_input; ?>" class="input-elite" value="<?php echo $val_max_diario; ?>">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mb-2 mt-2">
                                            <span class="small fw-bold text-muted text-uppercase d-block config-soberania-label-elite">Soberanía de Días Laborales</span>
                                            <div class="d-flex flex-wrap gap-2">
                                                <?php 
                                                $dias_config = explode(',', $val_dias);
                                                foreach (['lun' => 'LUN', 'mar' => 'MAR', 'mie' => 'MIE', 'jue' => 'JUE', 'vie' => 'VIE', 'sab' => 'SAB', 'dom' => 'DOM'] as $val => $label): 
                                                    $checked = in_array($val, $dias_config) ? 'checked' : '';
                                                ?>
                                                    <input type="checkbox" name="khronos_dias<?php echo $sufijo_input; ?>[]" value="<?php echo $val; ?>" id="dia_<?php echo $val; ?><?php echo $sufijo_input; ?>" class="btn-check-elite" <?php echo $checked; ?> onchange="window.actualizarVistaKhronos()">
                                                    <label class="day-coin-elite" for="dia_<?php echo $val; ?><?php echo $sufijo_input; ?>"><?php echo $label; ?></label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>

                                        <div class="config-flex-row-elite">
                                            <div class="config-flex-col-elite">
                                                <div class="config-input-card-elite">
                                                    <label class="fw-bold fs-mini text-primary text-uppercase d-block mb-2">Receso 01</label>
                                                    <div class="d-flex gap-2 align-items-center">
                                                        <div class="w-50">
                                                            <span class="fs-nano text-muted d-block mb-1 text-center">Después de (Bloque)</span>
                                                            <input type="number" id="khronos_descanso_h<?php echo $sufijo_input; ?>" name="khronos_descanso_h<?php echo $sufijo_input; ?>" class="input-elite text-center" oninput="window.actualizarVistaKhronos()" value="<?php echo $val_desc1_h; ?>">
                                                        </div>
                                                        <div class="w-50">
                                                            <span class="fs-nano text-muted d-block mb-1 text-center">Duración (Minutos)</span>
                                                            <input type="number" id="khronos_descanso_m<?php echo $sufijo_input; ?>" name="khronos_descanso_m<?php echo $sufijo_input; ?>" class="input-elite text-center" oninput="window.actualizarVistaKhronos()" value="<?php echo $val_desc1_m; ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="config-flex-col-elite">
                                                <div class="config-input-card-elite">
                                                    <label class="fw-bold fs-mini text-primary text-uppercase d-block mb-2">Receso 02</label>
                                                    <div class="d-flex gap-2 align-items-center">
                                                        <div class="w-50">
                                                            <span class="fs-nano text-muted d-block mb-1 text-center">Después de (Bloque)</span>
                                                            <input type="number" id="khronos_descanso2_h<?php echo $sufijo_input; ?>" name="khronos_descanso2_h<?php echo $sufijo_input; ?>" class="input-elite text-center" oninput="window.actualizarVistaKhronos()" value="<?php echo $val_desc2_h; ?>">
                                                        </div>
                                                        <div class="w-50">
                                                            <span class="fs-nano text-muted d-block mb-1 text-center">Duración (Minutos)</span>
                                                            <input type="number" id="khronos_descanso2_m<?php echo $sufijo_input; ?>" name="khronos_descanso2_m<?php echo $sufijo_input; ?>" class="input-elite text-center" oninput="window.actualizarVistaKhronos()" value="<?php echo $val_desc2_m; ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="config-main-card-elite h-100">
                        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
                            <h6 class="fw-bold text-muted small text-uppercase mb-0">Cronograma Dinámico Generado</h6>
                            <span class="badge-elite badge-elite--primary rounded-pill px-3 fs-micro">VISTA PREVIA</span>
                        </div>
                        <div class="khronos-timeline-elite khronos-timeline-scroll">
                             <!-- El JS inyectará el contenido aquí -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB: ACADÉMICO (GLOBAL) -->
        <div class="tab-pane fade" id="tab-global">
            <div class="row g-4 pt-2">
                <div class="col-md-7">
                    <!-- CARD 1: PRIVACIDAD Y REGLAS BÁSICAS -->
                    <div class="config-main-card-elite config-main-card-elite--primary mb-4">
                        <span class="small fw-bold text-muted text-uppercase d-block mb-3 config-main-card-elite__title"><i class="bi bi-shield-lock me-2 text-primary"></i>Regulación Académica y Privacidad</span>
                        <form id="formGlobalConfig" action="logica/guardar_configuracion_global.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo generar_csrf_token(); ?>">
                            
                            <div class="mb-4 border-bottom pb-3">
                                <label class="elite-switch">
                                    <input type="checkbox" id="switch-privacidad" class="elite-switch__input" <?php echo ($privacidad_sabana === 'estricto') ? 'checked' : ''; ?> onchange="window.cambiarPrivacidadCatedratico(this.checked)">
                                    <div class="elite-switch__track">
                                        <div class="elite-switch__thumb"></div>
                                    </div>
                                    <span class="elite-switch__label">
                                        Modo Silo de Datos (Restricción de Sábanas a Catedráticos)
                                    </span>
                                </label>
                                <div class="mt-2 text-muted fs-nano elite-switch-desc">
                                    <strong>Activo (Modo Silo)</strong>: El catedrático ve únicamente las notas de las asignaturas a su cargo.<br>
                                    <strong>Inactivo (Modo Abierto)</strong>: El catedrático tiene acceso a ver la sábana completa del curso (todas las materias).
                                </div>
                            </div>

                            <div class="mb-2">
                                <label for="select-politica-recuperacion" class="form-label fs-nano text-muted fw-bold text-uppercase d-block mb-2">Política Institucional de Recuperaciones (Retakes)</label>
                                <select id="select-politica-recuperacion" class="select-elite w-100" onchange="window.cambiarPoliticaRecuperacion(this.value)">
                                    <option value="reemplazo" <?php echo ($politica_recup === 'reemplazo') ? 'selected' : ''; ?>>✦ REEMPLAZO DIRECTO (Sustituye la nota original si es mayor)</option>
                                    <option value="promedio" <?php echo ($politica_recup === 'promedio') ? 'selected' : ''; ?>>✦ PROMEDIO SIMPLE (Promedia original y recuperación)</option>
                                    <option value="tope_aprobacion" <?php echo ($politica_recup === 'tope_aprobacion') ? 'selected' : ''; ?>>✦ LÍMITE DE APROBACIÓN (Reemplaza y limita al mínimo aprobatorio)</option>
                                </select>
                                <div class="mt-2 text-muted fs-nano mb-3">
                                    Determina el cálculo aritmético automático que aplica el motor **Perseus** cuando el profesor inyecta una nota de recuperación para un criterio o examen.
                                </div>
                            </div>

                            <div class="mb-2">
                                <label for="input-limite-horas" class="form-label fs-nano text-muted fw-bold text-uppercase d-block mb-2">Límite de Horas Semanales Docente</label>
                                <input type="number" id="input-limite-horas" class="input-elite text-center w-fixed-100" min="1" max="60" value="<?php echo $limite_horas; ?>" onchange="window.cambiarLimiteHorasDocente(this.value)">
                                <div class="mt-2 text-muted fs-nano">
                                    Define el tope máximo de horas semanales permitidas por docente antes de disparar la alerta de sobrecarga laboral en el Protocolo Atlas.
                                </div>
                            </div>

                        </form>
                    </div>

                    <!-- CARD 1.5: FORMATOS DE MATRÍCULA -->
                    <div class="config-main-card-elite mb-4">
                        <span class="small fw-bold text-muted text-uppercase d-block mb-3 config-main-card-elite__title"><i class="bi bi-file-earmark-pdf me-2 text-primary"></i>Formatos de Matrícula</span>
                        <div class="p-2">
                            <p class="text-muted fs-nano mb-3">
                                Administre las plantillas oficiales y los contratos de matrícula que el sistema utiliza para generar las actas de admisión de los estudiantes.
                            </p>
                            <button type="button" class="btn-elite btn-elite--primary w-100" onclick="navegarModulo('formatos_matricula')">
                                <i class="bi bi-gear-fill me-2"></i>Gestionar Formatos de Matrícula
                            </button>
                        </div>
                    </div>

                    <!-- CARD 2: CONFIGURADOR DE ESCALAS Y LIMITES DE NOTA -->
                    <div class="config-main-card-elite mb-4">
                        <span class="small fw-bold text-muted text-uppercase d-block mb-3 config-main-card-elite__title"><i class="bi bi-percent me-2 text-primary"></i>Escala de Calificación Institucional</span>
                        <form id="formEscalaNotas" onsubmit="window.guardarEscalaNotas(event)" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo generar_csrf_token(); ?>">
                            
                            <div class="row g-3 mb-4">
                                <div class="col-4">
                                    <label for="nota_minima" class="form-label fs-nano text-muted fw-bold text-uppercase">Nota Mínima</label>
                                    <input type="number" id="nota_minima" name="nota_minima" class="input-elite text-center" step="0.1" value="<?php echo number_format((float)$escala['nota_minima'], 2); ?>" required>
                                </div>
                                <div class="col-4">
                                    <label for="nota_maxima" class="form-label fs-nano text-muted fw-bold text-uppercase">Nota Máxima</label>
                                    <input type="number" id="nota_maxima" name="nota_maxima" class="input-elite text-center" step="0.1" value="<?php echo number_format((float)$escala['nota_maxima'], 2); ?>" required>
                                </div>
                                <div class="col-4">
                                    <label for="nota_aprobacion" class="form-label fs-nano text-muted fw-bold text-uppercase text-primary">Aprobación</label>
                                    <input type="number" id="nota_aprobacion" name="nota_aprobacion" class="input-elite text-center border-primary text-primary" step="0.1" value="<?php echo number_format((float)$escala['nota_aprobacion'], 2); ?>" oninput="document.getElementById('rango_basico_min').value = this.value" required>
                                </div>
                            </div>

                            <span class="small fw-bold text-muted text-uppercase d-block mb-3 fs-micro border-top pt-3"><i class="bi bi-bar-chart-steps me-2"></i>Límites Inferiores de Desempeño Oficial</span>
                            <div class="row g-3 mb-4">
                                <div class="col-4">
                                    <label for="rango_superior_min" class="form-label fs-nano text-muted fw-bold text-uppercase text-success">Mín. Superior</label>
                                    <input type="number" id="rango_superior_min" name="rango_superior_min" class="input-elite text-center border-success text-success" step="0.1" value="<?php echo number_format((float)$escala['rango_superior_min'], 2); ?>" required>
                                </div>
                                <div class="col-4">
                                    <label for="rango_alto_min" class="form-label fs-nano text-muted fw-bold text-uppercase text-info">Mín. Alto</label>
                                    <input type="number" id="rango_alto_min" name="rango_alto_min" class="input-elite text-center border-info text-info" step="0.1" value="<?php echo number_format((float)$escala['rango_alto_min'], 2); ?>" required>
                                </div>
                                <div class="col-4">
                                    <label for="rango_basico_min" class="form-label fs-nano text-muted fw-bold text-uppercase text-warning">Mín. Básico</label>
                                    <input type="number" id="rango_basico_min" name="rango_basico_min" class="input-elite text-center border-warning text-warning bg-light" step="0.1" value="<?php echo number_format((float)$escala['rango_basico_min'], 2); ?>" readonly required>
                                </div>
                            </div>

                            <!-- CARD 3: PLAN DE NOTAS MINIMAS POR DIMENSION -->
                            <span class="small fw-bold text-muted text-uppercase d-block mb-3 fs-micro border-top pt-3"><i class="bi bi-grid-3x3-gap me-2"></i>Ponderaciones y Plan de Notas Mínimas</span>
                            <div class="bg-light p-3 rounded-4 mb-4 border border-opacity-10">
                                <div class="row g-2 mb-2 pb-2 border-bottom text-muted fw-bold fs-nano text-uppercase text-center">
                                    <div class="col-4 text-start">Dimensión</div>
                                    <div class="col-4">Peso (%)</div>
                                    <div class="col-4">Notas Mínimas</div>
                                </div>
                                <?php foreach($dimensiones as $dim): ?>
                                <div class="row align-items-center mb-3 text-center">
                                    <div class="col-4 text-start">
                                        <span class="fw-bold text-dark text-uppercase fs-nano"><?php echo htmlspecialchars($dim['nombre']); ?></span>
                                    </div>
                                    <div class="col-4">
                                        <input type="number" name="peso_dim[<?php echo $dim['id']; ?>]" class="input-elite text-center py-2 m-auto w-fixed-70 input-dim-peso fw-bold" value="<?php echo $dim['peso_global']; ?>" min="1" max="100" required>
                                    </div>
                                    <div class="col-4">
                                        <input type="number" name="min_eval_dim[<?php echo $dim['id']; ?>]" class="input-elite text-center py-2 m-auto w-fixed-70 input-dim-min fw-bold" value="<?php echo $dim['min_evaluaciones']; ?>" min="1" max="10" required>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <button type="submit" class="btn-elite btn-elite--primary w-100 py-3 shadow-sm">
                                <i class="bi bi-save2 me-2"></i> GUARDAR REGULACIÓN ACADÉMICA
                            </button>
                        </form>
                    </div>

                    <!-- CARD 4: CRONOGRAMA DE PERIODOS ACADÉMICOS -->
                    <div class="config-main-card-elite mb-4">
                        <span class="small fw-bold text-muted text-uppercase d-block mb-3 config-main-card-elite__title"><i class="bi bi-calendar-event me-2 text-primary"></i>Calendario de Periodos Académicos</span>
                        <form id="formCalendarioPeriodos" onsubmit="window.guardarFechasPeriodos(event)" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo generar_csrf_token(); ?>">
                            
                            <div class="bg-light p-3 rounded-4 mb-4 border border-opacity-10">
                                <div class="row g-2 mb-2 pb-2 border-bottom text-muted fw-bold fs-nano text-uppercase text-center align-items-center">
                                    <div class="col-3 text-start">Periodo</div>
                                    <div class="col-1">Activo</div>
                                    <div class="col-4">Fecha Inicio</div>
                                    <div class="col-4">Fecha Terminación</div>
                                </div>
                                <?php foreach($periodos_academicos as $p): 
                                    // Formatear fechas para input date (solo dia, mes y año)
                                    $val_inicio = date('Y-m-d', strtotime($p['fecha_inicio']));
                                    $val_fin = date('Y-m-d', strtotime($p['fecha_fin']));
                                ?>
                                <div class="row align-items-center mb-3 text-center">
                                    <div class="col-3 text-start">
                                        <span class="fw-bold text-dark text-uppercase fs-nano"><?php echo htmlspecialchars($p['nombre']); ?></span>
                                    </div>
                                    <div class="col-1 d-flex justify-content-center align-items-center">
                                        <input type="radio" name="periodo_activo" value="<?php echo $p['id']; ?>" class="form-check-input" <?php echo ($p['activo'] == 1) ? 'checked' : ''; ?> required>
                                    </div>
                                    <div class="col-4">
                                        <input type="date" name="periodo[<?php echo $p['id']; ?>][inicio]" class="input-elite py-1 px-2 fs-nano text-center" value="<?php echo $val_inicio; ?>" required>
                                    </div>
                                    <div class="col-4">
                                        <input type="date" name="periodo[<?php echo $p['id']; ?>][fin]" class="input-elite py-1 px-2 fs-nano text-center" value="<?php echo $val_fin; ?>" required>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="mb-4 mt-3 pt-3 border-top">
                                <label for="input-dias-gracia" class="form-label fs-nano text-muted fw-bold text-uppercase d-block mb-2"><i class="bi bi-clock me-1 text-primary"></i>Días de Holgura para Nivelaciones (Grace Period)</label>
                                <input type="number" id="input-dias-gracia" class="input-elite w-100 text-center fw-bold" min="0" max="30" value="<?php echo $dias_gracia; ?>" onchange="window.cambiarDiasGraciaRecuperacion(this.value)">
                                <div class="mt-2 text-muted fs-nano">
                                    Número de días que se mantiene activo el periodo académico anterior para subir recuperaciones tras su cierre de calendario.
                                </div>
                            </div>

                            <button type="submit" class="btn-elite btn-elite--primary w-100 py-3 shadow-sm">
                                <i class="bi bi-calendar-check me-2"></i> GUARDAR CRONOGRAMA ESCOLAR
                            </button>
                        </form>
                    </div>

                    <!-- CARD 5: GESTIÓN DE RECESOS Y VACACIONES -->
                    <div class="config-main-card-elite mb-4">
                        <span class="small fw-bold text-muted text-uppercase d-block mb-3 config-main-card-elite__title"><i class="bi bi-calendar-range me-2 text-primary"></i>Recesos y Vacaciones Escolares</span>
                        
                        <div class="bg-light p-3 rounded-4 mb-4 border border-opacity-10">
                            <div class="row g-2 mb-2 pb-2 border-bottom text-muted fw-bold fs-nano text-uppercase text-center align-items-center">
                                <div class="col-4 text-start">Receso / Vacación</div>
                                <div class="col-3">Fecha Inicio</div>
                                <div class="col-3">Fecha Fin</div>
                                <div class="col-2">Acción</div>
                            </div>
                            <div id="lista-recesos-container">
                                <?php 
                                require_once __DIR__ . '/../logica/helpers_recesos.php';
                                $recesos = obtener_todos_los_recesos($db);
                                if (empty($recesos)):
                                ?>
                                    <div class="text-muted fs-nano text-center py-3">No hay recesos registrados.</div>
                                <?php else: 
                                    foreach ($recesos as $r):
                                ?>
                                    <div class="row align-items-center mb-3 text-center" id="receso-row-<?php echo $r['id']; ?>">
                                         <div class="col-4 text-start">
                                             <span class="fw-bold text-dark fs-nano receso-display-nombre"><?php echo htmlspecialchars($r['nombre']); ?></span>
                                         </div>
                                         <div class="col-3 fs-nano text-muted receso-display-inicio" data-val="<?php echo $r['fecha_inicio']; ?>">
                                             <?php echo date('d/m/Y', strtotime($r['fecha_inicio'])); ?>
                                         </div>
                                         <div class="col-3 fs-nano text-muted receso-display-fin" data-val="<?php echo $r['fecha_fin']; ?>">
                                             <?php echo date('d/m/Y', strtotime($r['fecha_fin'])); ?>
                                         </div>
                                         <div class="col-2 d-flex justify-content-center gap-2">
                                             <button type="button" class="btn-action-elite text-primary" title="Editar Receso" onclick="window.editarRecesoEscolar(<?php echo $r['id']; ?>)">
                                                 <i class="bi bi-pencil-square"></i>
                                             </button>
                                             <button type="button" class="btn-action-elite text-danger" title="Eliminar Receso" onclick="window.eliminarRecesoEscolar(<?php echo $r['id']; ?>)">
                                                 <i class="bi bi-trash3-fill"></i>
                                             </button>
                                         </div>
                                     </div>
                                <?php 
                                    endforeach;
                                endif; 
                                ?>
                            </div>
                        </div>

                        <!-- Formulario para agregar receso -->
                        <form id="formAgregarReceso" onsubmit="window.agregarRecesoEscolar(event)" novalidate class="border-top pt-3">
                            <div class="row g-2">
                                <div class="col-12 col-sm-4">
                                    <label class="form-label fs-nano text-muted fw-bold text-uppercase d-block mb-1">Nombre</label>
                                    <input type="text" id="receso-nombre" class="input-elite w-100" placeholder="Ej: Semana Santa" required>
                                </div>
                                <div class="col-6 col-sm-4">
                                    <label class="form-label fs-nano text-muted fw-bold text-uppercase d-block mb-1">Fecha Inicio</label>
                                    <input type="date" id="receso-inicio" class="input-elite w-100" required>
                                </div>
                                <div class="col-6 col-sm-4">
                                    <label class="form-label fs-nano text-muted fw-bold text-uppercase d-block mb-1">Fecha Fin</label>
                                    <input type="date" id="receso-fin" class="input-elite w-100" required>
                                </div>
                            </div>
                            <button type="submit" class="btn-elite btn-elite--primary w-100 py-2 mt-3 shadow-sm">
                                <i class="bi bi-plus-circle me-2"></i> REGISTRAR RECESO / VACACIONES
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="col-md-5">
                    <div class="config-main-card-elite config-main-card-elite--padded config-main-card-elite--accented mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-shield-check text-primary fs-3 me-3"></i>
                            <div>
                                <h6 class="fw-bold text-primary small text-uppercase mb-0">Soberanía de Datos Académicos</h6>
                                <p class="text-muted fs-nano mb-0">Regulación curricular avanzada</p>
                            </div>
                        </div>
                        <p class="text-muted fs-nano mb-3">
                            Esta sección regula los límites del motor **Perseus API**. La escala institucional que configure aquí re-parametrizará de inmediato:
                        </p>
                        <ul class="text-muted fs-nano ps-3 mb-0">
                            <li class="mb-2">El rango de los sliders de Focus Mode en Rúbricas.</li>
                            <li class="mb-2">Las alertas de cumplimiento de notas obligatorias del docente.</li>
                            <li class="mb-2">La nota mínima de aprobación en exámenes del banco de reactivos.</li>
                            <li class="mb-2">Las fórmulas aritméticas del cierre del boletín y sábanas de notas.</li>
                        </ul>
                    </div>

                    <div class="config-main-card-elite config-main-card-elite--padded config-main-card-elite--accented">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-calculator-fill text-primary fs-3 me-3"></i>
                            <div>
                                <h6 class="fw-bold text-primary small text-uppercase mb-0">Política de Recuperación</h6>
                                <p class="text-muted fs-nano mb-0">Flexibilidad evaluativa transparente</p>
                            </div>
                        </div>
                        <p class="text-muted fs-nano mb-0">
                            La política institucional se aplica de manera automática en el backend del oráculo. El profesor ingresa la nota de recuperación del alumno y el sistema recalcula los definitivos ponderados de forma inmediata sin alterar las notas históricas de cada actividad.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($mostrar_pestana_herramientas): ?>
        <!-- TAB 4: MANTENIMIENTO / HERRAMIENTAS -->
        <div class="tab-pane fade" id="tab-mantenimiento">
            <div class="row g-4 pt-2">
                <!-- CARD: TEST RUNNER DE CALIFICACIONES (PRUEBAS FORMALES) -->
                <div class="col-md-6">
                    <div class="config-main-card-elite border-start border-primary border-4">
                        <div class="d-flex align-items-center mb-4">
                            <i class="bi bi-shield-check text-primary fs-4 me-3"></i>
                            <div>
                                <h5 class="text-primary small fw-bold text-uppercase mb-0">Test Runner de Calificaciones</h5>
                                <p class="text-muted fs-nano mb-0">Pruebas formales del motor matemático de notas</p>
                            </div>
                        </div>
                        <a href="dashboard.php?p=pruebas_formales" class="btn-elite btn-elite--primary w-100 py-3 text-center d-flex align-items-center justify-content-center text-decoration-none">
                            <i class="bi bi-play-circle me-2"></i> EJECUTAR PRUEBAS UNITARIAS
                        </a>
                    </div>
                </div>

                <!-- CARD: ZONA CRÍTICA (SOLO ADMINISTRADOR) -->
                <?php if ($es_admin_sistema): ?>
                <div class="col-md-6">
                    <div class="config-main-card-elite border-start border-danger border-4">
                        <div class="d-flex align-items-center mb-4">
                            <i class="bi bi-exclamation-triangle-fill text-danger fs-4 me-3"></i>
                            <div>
                                <h5 class="text-danger small fw-bold text-uppercase mb-0">Zona Crítica Ares</h5>
                                <p class="text-muted fs-nano mb-0">Acciones de purga y restauración soberana</p>
                            </div>
                        </div>
                        <button class="btn-elite btn-elite--danger w-100 py-3" onclick="window.ejecutarLimpiezaIntegral()">
                            <i class="bi bi-shield-shaded me-2"></i> INICIAR LIMPIEZA INTEGRAL
                        </button>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="../js/modules/configuracion.js?v=<?php echo time(); ?>"></script>
