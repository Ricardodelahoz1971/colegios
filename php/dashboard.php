<?php
declare(strict_types=1);
// 0. BLINDAJE DE SEGURIDAD (Evitar Cache)
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// 1. SEGURIDAD, MEMORIA Y CONEXIÓN
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
require_once 'auth.php';
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit();
}

if (!isset($_SESSION['rol_id']) || empty($_SESSION['rol_id']) || (!isset($_SESSION['estudiante_id']) && in_array($_SESSION['rol_id'] ?? 0, [5, 12]))) {
    $stmt_seg = $db->prepare("SELECT rol_id, estudiante_id FROM usuarios WHERE id = ?");
    $stmt_seg->execute([$_SESSION['usuario_id']]);
    $user_db = $stmt_seg->fetch(PDO::FETCH_ASSOC);
    
    if ($user_db) {
        $_SESSION['rol_id'] = $user_db['rol_id'];
        if ($user_db['rol_id'] == 12 || $user_db['rol_id'] == 5) {
            $_SESSION['estudiante_id'] = $user_db['estudiante_id'];
        }
    } else {
        session_destroy();
        header("Location: ../index.php?error=sesion_invalida");
        exit();
    }
}

// Obtener nombre del rol para el encabezado (PDO)
$mi_rol_id_enc = (int)($_SESSION['rol_id'] ?? 0);
$stmt_rol = $db->prepare("SELECT nombre_rol FROM roles WHERE id = ?");
$stmt_rol->execute([$mi_rol_id_enc]);
$mi_rol_nombre_enc = $stmt_rol->fetchColumn() ?: 'Sin Rol';


$pagina = $_GET['p'] ?? 'inicio';
$pagina = preg_replace('/[^a-zA-Z0-9_]/', '', $pagina); // Destruye trazas de LFI / Traversal

// 🛡️ FILTRO DE SOBERANÍA: ¿Tiene permiso real para ver este módulo?
$permisos_requeridos = [
    'matricula'      => 'matricula',
    'matriculados'   => 'estudiantes',
    'personal'       => 'personal',
    'usuarios'       => 'usuarios',
    'configuracion'  => 'configuracion',
    'pruebas_formales' => 'configuracion',
    'roles'          => 'roles',
    'auditoria'      => 'auditoria',
    'zulu'           => 'zulu',
    'khronos'        => 'zulu',
    'reporte_listas' => 'estudiantes',
    'editor_preguntas' => 'evaluacion',
    'ares_canvas_lab' => 'evaluacion',
    'constructor_pruebas' => 'evaluacion',
    'constructor_actividades' => 'evaluacion',
    'calificar_pruebas' => 'evaluacion',
    'aplicacion_pruebas' => 'evaluacion',
    'sabana_calificaciones' => 'evaluacion',
    'asistencia'     => 'asistencia',
    'areas'          => 'areas',
    'especialidades' => 'especialidades',
    'cursos'         => 'cursos',
    'formatos_matricula' => 'configuracion'
];

if (isset($permisos_requeridos[$pagina])) {
    if (!tiene_permiso($permisos_requeridos[$pagina])) {
        ejecutar_protocolo_intimidacion();
    }
}

$nombres_modulos = [
    'inicio' => 'Inicio (Olimpo)',
    'matricula' => 'Matrícula (Génesis)',
    'reporte_listas' => 'Centro de Reportes (Oráculo)',
    'configuracion' => 'Configuración (Hefesto)',
    'roles' => 'Gestión de Roles (Panteón)',
    'personal' => 'Gestión de Personal (Hoplitas)',
    'matriculados' => 'Gestión de Matriculados (Academia)',
    'agenda' => 'Agenda Académica (Kairós)',
    'mensajeria' => 'MENSAJERÍA',
    'zulu' => 'Gestión Académica (Atlas)',
    'khronos' => 'Horarios (Khronos)',
    'editor_preguntas' => 'Banco de Reactivos (Ares)',
    'constructor_pruebas' => 'Constructor de Pruebas (Ares)',
    'constructor_actividades' => 'Constructor de Actividades (Rúbricas)',
    'calificar_pruebas' => 'Calificación de Pruebas (Ares)',
    'sabana_calificaciones' => 'Sábana de Notas',
    'aula_virtual_gestion' => 'Aula Virtual',
    'aula_virtual_estudiante' => 'Aula Virtual',
    'ares_canvas_lab' => 'Laboratorio Canvas (Beta)',
    'formatos_matricula' => 'Formatos de Matrícula (Hefesto Formatos)'
];
$nombre_modulo = "MÓDULO " . strtoupper($nombres_modulos[$pagina] ?? $pagina);
$cfg_menu = 'legacy'; // Pre-definición Élite
$archivo = "vistas/" . $pagina . ".php";

// 🛡️ DETECTOR DE DISPOSITIVOS MÓVILES (Soberanía Híbrida UA + Cookie)
function esMovil(): bool {
    $ua = $_SERVER["HTTP_USER_AGENT"] ?? '';
    $is_ua_mobile = (bool)preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|iphone|ipad|ipod|mobile|pie|tablet|up\.browser|up\.link|webos|wos)/i", $ua);
    $is_cookie_mobile = (isset($_COOKIE['dispositivo_elite']) && $_COOKIE['dispositivo_elite'] === 'movil');
    return $is_ua_mobile || $is_cookie_mobile;
}

// 🛡️ MATRIZ DE MOVILIDAD ÉLITE v1.0
// Define qué módulos son visibles en móvil por ROL
function moduloAutorizadoMovil(string $modulo, int $rol): bool {
    $rol_nombre = strtolower($_SESSION['rol_nombre'] ?? '');

    // 🛡️ SOBERANÍA ACADÉMICA (Docentes)
    if ($rol_nombre === 'docente') {
        $modulos_permitidos = ['inicio', 'agenda', 'calendario', 'mensajeria', 'calificar_pruebas', 'editor_preguntas', 'ares_canvas_lab', 'constructor_pruebas', 'constructor_actividades', 'aplicacion_pruebas', 'asistencia', 'cursos', 'zulu', 'khronos', 'aula_virtual_gestion', 'sabana_calificaciones'];
        return in_array($modulo, $modulos_permitidos);
    }
    
    // 🛡️ SOBERANÍA ESTUDIANTIL (Estudiantes)
    if ($rol_nombre === 'estudiante') {
        $modulos_permitidos = ['inicio', 'agenda', 'calendario', 'mensajeria', 'estudiante_examenes', 'aula_virtual_estudiante'];
        return in_array($modulo, $modulos_permitidos);
    }

    // 🛡️ SOBERANÍA ADMINISTRATIVA (Directores, Coordinadores, y nuevos cargos)
    // Para todos los demás roles, destruimos el candado falso. 
    // Si la base de datos (tiene_permiso) dice que sí, el menú renderizará.
    return true;
}

// --- CARGA MAESTRA DE ESTÉTICA (Sincronización Total) ---
$stmt_estetica = $db->prepare("SELECT clave, valor FROM ajustes_estetica");
$stmt_estetica->execute();
$estilos = $stmt_estetica->fetchAll(PDO::FETCH_KEY_PAIR);

$cfg_brand   = $estilos['brand_color'] ?? '#0f0664';
$cfg_font    = $estilos['school_font'] ?? 'Montserrat';
if ($cfg_font === 'Simplifica') $cfg_font = 'Montserrat';
$cfg_hover   = $estilos['brand_hover'] ?? '#1a1c8f';
$cfg_sidebar = $estilos['sidebar_bg']  ?? '#0f0664';
$cfg_body    = $estilos['body_bg']     ?? '#f8fafc';
$cfg_surface = $estilos['surface_bg']  ?? '#ffffff';
$cfg_text    = $estilos['text_main']   ?? '#334155';
$cfg_school  = $estilos['school_name'] ?? 'Sistema Escolar';
$cfg_logo_raw = $estilos['school_logo'] ?? '';
$cfg_menu    = $estilos['menu_style'] ?? 'legacy';
$cfg_logo = $cfg_logo_raw;
if (!empty($cfg_logo) && strpos($cfg_logo, 'http') === false) {
    $cfg_logo = '../' . $cfg_logo;
}
$raw_count   = count($estilos);

if (isset($_GET['raw'])) {
    $posible_css_modulo = "../styles/modules/" . $pagina . ".css";
    if (file_exists(__DIR__ . "/../styles/modules/" . $pagina . ".css")) {
        echo '<link rel="stylesheet" href="' . $posible_css_modulo . '?v=' . time() . '">';
    }
    echo '<span class="hero-module-title d-none">' . $nombre_modulo . '</span>';
    echo '<div class="flex-fill overflow-auto"><div class="p-0">';
    if (file_exists($archivo)) {
        include($archivo);
    } else {
        echo "<div class='container p-5 text-center'><h2 class='display-6 fw-bold text-muted'>La sección '$pagina' no fue encontrada.</h2></div>";
    }
    echo '</div></div>';
    exit();
}
?>

<!DOCTYPE html>
<html lang="es" class="notranslate" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name="google" content="notranslate">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo $nombre_modulo; ?></title>
    
    <!-- LIBRERÍAS NUCLEARES (PRE-CARGA ÉLITE) -->
    <script src="../assets/libs/jquery/jquery.min.js"></script>
    <script src="../assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/libs/sweetalert2/sweetalert2.all.min.js"></script>
    
    <script>
        window.MODULO_ACTUAL = "<?php echo $pagina; ?>";
        window.CSRF_TOKEN = "<?php echo $_SESSION['csrf_token'] ?? ''; ?>";
        // 🛡️ CENTINELA DE RESOLUCIÓN ÉLITE
        (function() {
            const isMobileScreen = window.innerWidth <= 1024;
            const cookieName = "dispositivo_elite=";
            const currentCookie = document.cookie.split(';').find(c => c.trim().startsWith(cookieName));
            const cookieValue = currentCookie ? currentCookie.split('=')[1] : null;

            if (isMobileScreen && cookieValue !== 'movil') {
                document.cookie = "dispositivo_elite=movil; path=/; max-age=86400";
                window.location.reload();
            } else if (!isMobileScreen && cookieValue === 'movil') {
                document.cookie = "dispositivo_elite=escritorio; path=/; max-age=86400";
                window.location.reload();
            }
        })();

        // 🛡️ MOTOR INTERCEPTOR ÉLITE (Soberanía de Red)
        // Gobierna TODAS las peticiones fetch de la arquitectura para aniquilar la caché
        (function() {
            const originalFetch = window.fetch;
            window.fetch = function() {
                let [resource, config] = arguments;
                
                // Si la petición va dirigida a nuestras APIs o procesos lógicos (GET por defecto)
                if (typeof resource === 'string' && (resource.includes('logica/') || resource.includes('api_'))) {
                    // Solo interceptar peticiones GET (las POST/PUT no se cachean de la misma forma)
                    const method = (config && config.method) ? config.method.toUpperCase() : 'GET';
                    
                    if (method === 'GET') {
                        const separator = resource.includes('?') ? '&' : '?';
                        resource += `${separator}_cb=${new Date().getTime()}`;
                        
                        config = config || {};
                        config.cache = 'no-store';
                    }
                }
                
                return originalFetch.apply(this, [resource, config]);
            };
        })();
    </script>
    
    <!-- Componentes Élite (Carga Local Sello Ares) -->
    <!-- 0. MANIFIESTO DE CAPAS (Soberanía de Cascada) -->
    <link rel="stylesheet" href="../styles/elite_layers.css?v=<?php echo time(); ?>">

    <!-- 1. BASE LEGACY & VENDORS (Carga vía Capas) -->
    <link rel="stylesheet" href="../assets/libs/datatables/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../styles/utilities.css?v=<?php echo time(); ?>">

    <!-- 2. TOKENS & IDENTIDAD (Variables de Diseño) -->
    <link rel="stylesheet" href="../styles/elite_themes.css?v=<?php echo time(); ?>">

    <!-- 3. MÓDULOS ESPECÍFICOS (Estructura Local) -->
    <link rel="stylesheet" href="../styles/modules/layout.css?v=<?php echo time(); ?>">
    <!-- <link rel="stylesheet" href="../styles/modules/sidebar.css?v=<?php echo time(); ?>"> -->
    <link rel="stylesheet" href="../styles/modules/mensajeria.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../styles/modules/agenda.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../styles/modules/zulu.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../styles/modules/khronos.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../styles/modules/ares.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../styles/modules/ares_editor.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../styles/modules/dashboard_elite.css?v=<?php echo time(); ?>">

    <!-- 4. NÚCLEO ÉLITE (Autoridad Suprema) -->
    <link rel="stylesheet" href="../styles/ui_kit.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../styles/modules/elite_mutations.css?v=<?php echo time(); ?>">

    <!-- 5. CARGA DINÁMICA DE MÓDULOS (Soberanía Modular) -->
    <?php
    $posible_css_modulo = "../styles/modules/" . $pagina . ".css";
    if (file_exists(__DIR__ . "/../styles/modules/" . $pagina . ".css")): ?>
        <link rel="stylesheet" href="<?php echo $posible_css_modulo; ?>?v=<?php echo time(); ?>">
    <?php endif; ?>
    
    <!-- Librerías Ares (Evaluación) - Sincronización Local -->
    <link rel="stylesheet" href="../assets/libs/kt/katex.min.css">
    <!-- KaTeX: carga via ES module y expone window.katex para compatibilidad global -->
    <script type="module">
        import katex from '../assets/libs/kt/katex.mjs';
        window.katex = katex;
    </script>
    <script src="../assets/libs/ml/ml_engine.js?v=<?php echo time(); ?>"></script>
    <link href="../assets/libs/ql/quill.snow.min.css" rel="stylesheet">
    <script src="../assets/libs/ql/ql_engine.js"></script>
    <script src="../assets/libs/st/st_engine.js"></script>
    <script src="../js/modules/ares_select_engine_v5.js?v=<?php echo time(); ?>"></script>
    
    <!-- CARGADOR DE MODELOS SOBERANOS -->
    <?php if($cfg_menu === 'aero'): ?>
        <link rel="stylesheet" href="../styles/modules/elite_aero.css?v=<?php echo time(); ?>">
    <?php endif; ?>
    <?php if($cfg_menu === 'blade'): ?>
        <link rel="stylesheet" href="../styles/modules/elite_blade.css?v=<?php echo time(); ?>">
    <?php endif; ?>
    
    <!-- Google Fonts: ADN Textual Maestro -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;800&family=Montserrat:wght@400;500;700;800;900&family=Roboto:wght@300;400;500;700;900&family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">

    <?php
    /**
     * Motor de Conversión de Color Élite (ADN dinámico).
     */
    function hexToRgb(string $hex): string {
        $hex = str_replace("#", "", $hex);
        if(strlen($hex) == 3) {
            $r = hexdec(substr($hex,0,1).substr($hex,0,1));
            $g = hexdec(substr($hex,1,1).substr($hex,1,1));
            $b = hexdec(substr($hex,2,1).substr($hex,2,1));
        } else {
            $r = hexdec(substr($hex,0,2));
            $g = hexdec(substr($hex,2,2));
            $b = hexdec(substr($hex,4,2));
        }
        return "$r, $g, $b";
    }
    $cfg_dark_mode = $estilos['dark_mode'] ?? '0';
    ?>

    <style>
        :root {
            /* ADN ÉLITE: TOKENS DE IDENTIDAD INSTITUCIONAL */
            --el-primary: <?php echo $cfg_brand; ?>;
            --el-primary-rgb: <?php echo hexToRgb($cfg_brand); ?>;
            --el-accent: <?php echo $estilos['brand_accent'] ?? '#f5bd1f'; ?>;
            --el-accent-rgb: <?php echo hexToRgb($estilos['brand_accent'] ?? '#f5bd1f'); ?>;
            --el-info: <?php echo $estilos['brand_info'] ?? '#0ea5e9'; ?>;
            --el-info-rgb: <?php echo hexToRgb($estilos['brand_info'] ?? '#0ea5e9'); ?>;
            --el-success: <?php echo $estilos['brand_success'] ?? '#10b981'; ?>;
            --el-success-rgb: <?php echo hexToRgb($estilos['brand_success'] ?? '#10b981'); ?>;
            --el-danger: <?php echo $estilos['brand_danger'] ?? '#ef4444'; ?>;
            --el-danger-rgb: <?php echo hexToRgb($estilos['brand_danger'] ?? '#ef4444'); ?>;
            --el-white: rgb(255, 255, 255);
            --el-white-rgb: 255, 255, 255;
            --el-dark-rgb: 30, 41, 59;

            /* COMPATIBILIDAD BOOTSTRAP */
            --bs-primary: var(--el-primary);
            --bs-primary-rgb: var(--el-primary-rgb);
            --bs-btn-hover-bg: <?php echo $cfg_hover; ?>;
            --bs-success: var(--el-success);
            --bs-success-rgb: var(--el-success-rgb);
            --bs-danger: var(--el-danger);
            --bs-danger-rgb: var(--el-danger-rgb);
            --bs-warning-rgb: 255, 193, 7;
            --bs-info: var(--el-info);
            --bs-info-rgb: var(--el-info-rgb);
            --bs-btn-active-bg: <?php echo $cfg_hover; ?> !important;
            --bs-body-bg: <?php echo $cfg_body; ?> !important;
            --bs-tertiary-bg: <?php echo $cfg_surface; ?> !important;
            --bs-body-color: <?php echo $cfg_text; ?> !important;
            --bs-border-color: rgba(var(--el-primary-rgb), 0.15);
            --el-border: rgba(var(--el-primary-rgb), 0.1);
            --el-border-strong: rgba(var(--el-primary-rgb), 0.2);
            --el-font-institutional: '<?php echo $cfg_font; ?>', sans-serif;
            --sidebar-width: 280px;
        }
        body {
            font-family: var(--el-font-institutional) !important;
        }

        /* Refinamiento de Inputs Numéricos (Flechitas siempre visibles) */
        input[type="number"]::-webkit-inner-spin-button, 
        input[type="number"]::-webkit-outer-spin-button { 
            opacity: 1 !important;
            height: 30px;
        }
        input[type="number"] { -moz-appearance: textfield; appearance: textfield; }

        /* 🛡️ SOBERANÍA DEL SELECT ÉLITE (Inyección Dinámica de Flecha) */
        <?php $svg_color = str_replace('#', '%23', $cfg_brand); ?>
        select.form-select, select.select-elite, select.select-elite-reborn, select.select-elite-sm {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='<?php echo $svg_color; ?>' stroke-linecap='round' stroke-linejoin='round' stroke-width='2.5' d='m2 5 6 6 6-6'/%3e%3c/svg%3e") !important;
        }
    </style>

    <?php
    $cfg_dark_mode = $estilos['dark_mode'] ?? '0';
    ?>
</head>
<body class="bg-light notranslate <?php echo ($cfg_dark_mode == '1') ? 'dark-theme-mode' : ''; ?> <?php 
    if($cfg_menu == 'blade') echo 'mode-blade-elite';
    if($cfg_menu == 'aero') echo 'mode-aero-elite';
?>">

<div class="container-fluid overflow-hidden p-0">
    <div class="row vh-100 g-0">
        
        <!-- SIDEBAR (Navegación Élite) -->
        <nav class="sidebar-container d-flex flex-column p-0 shadow-lg" id="sidebarMaestro">
            <div class="sidebar-header-elite p-4 border-bottom">
                <div class="logo-container-elite mb-3">
                    <?php $logo_exists = !empty($cfg_logo_raw) && file_exists(__DIR__ . '/../' . $cfg_logo_raw); ?>
                    <img src="<?php echo $logo_exists ? ('../' . $cfg_logo_raw) : ''; ?>" alt="Logo" class="logo-img-elite school-logo-global <?php echo $logo_exists ? '' : 'd-none'; ?>">
                    <div class="logo-fallback-elite <?php echo $logo_exists ? 'd-none' : ''; ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-white-50">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        </svg>
                    </div>
                </div>
                <div class="school-name-container-elite">
                    <h2 class="school-name-elite school-name-global"><?php echo $cfg_school; ?></h2>
                </div>
            </div>
            
            <div class="flex-fill py-3 sidebar-scroll-elite">
                <ul class="menu-elite px-3">
                    
                    <!-- INICIO -->
                    <li class="menu-item-elite">
                        <a href="dashboard.php?p=inicio" onclick="event.preventDefault(); navegarModulo('inicio')" class="menu-link-elite <?php echo ($pagina == 'inicio') ? 'active' : ''; ?>">
                            <div class="d-flex align-items-center">
                                <svg class="me-3" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                                <span>Inicio</span>
                            </div>
                        </a>
                    </li>

                    <!-- EVALUACIÓN (ESTUDIANTES) -->
                    <?php if ($_SESSION['rol_id'] == 3 || $_SESSION['rol_nombre'] == 'Estudiante'): ?>
                    <li class="menu-item-elite">
                        <a href="dashboard.php?p=estudiante_examenes" onclick="event.preventDefault(); navegarModulo('estudiante_examenes')" class="menu-link-elite <?php echo ($pagina == 'estudiante_examenes') ? 'active' : ''; ?>">
                            <div class="d-flex align-items-center">
                                <svg class="me-3" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                <span>Mis Exámenes</span>
                            </div>
                        </a>
                    </li>
                    <li class="menu-item-elite">
                        <a href="dashboard.php?p=aula_virtual_estudiante" onclick="event.preventDefault(); navegarModulo('aula_virtual_estudiante')" class="menu-link-elite <?php echo ($pagina == 'aula_virtual_estudiante') ? 'active' : ''; ?>">
                            <div class="d-flex align-items-center">
                                <svg class="me-3" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                                <span>Aula Virtual</span>
                            </div>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if ((tiene_permiso('matricula') || tiene_permiso('estudiantes')) && (moduloAutorizadoMovil('matricula', $mi_rol_id_enc) || moduloAutorizadoMovil('matriculados', $mi_rol_id_enc))): ?>
                    <li class="menu-item-elite">
                        <a href="javascript:void(0)" class="menu-link-elite <?php echo (in_array($pagina, ['matricula', 'matriculados'])) ? 'active' : ''; ?>">
                            <div class="d-flex align-items-center">
                                <svg class="me-3" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                <span>Estudiantes</span>
                            </div>
                            <i class="bi bi-chevron-right small arrow-icon"></i>
                        </a>
                        <ul class="submenu-elite">
                            <?php if (tiene_permiso('matricula') && moduloAutorizadoMovil('matricula', $mi_rol_id_enc)): ?>
                                <li><a class="submenu-link-elite <?php echo ($pagina == 'matricula') ? 'active' : ''; ?>" href="dashboard.php?p=matricula" onclick="event.preventDefault(); navegarModulo('matricula')">Nueva Matrícula</a></li>
                            <?php endif; ?>
                            <?php if (tiene_permiso('estudiantes') && moduloAutorizadoMovil('matriculados', $mi_rol_id_enc)): ?>
                                <li><a class="submenu-link-elite <?php echo ($pagina == 'matriculados') ? 'active' : ''; ?>" href="dashboard.php?p=matriculados" onclick="event.preventDefault(); navegarModulo('matriculados')">Gestión de Matriculados</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <!-- CATEGORÍA: ACADÉMICO -->
                    <?php if ((tiene_permiso('areas') || tiene_permiso('especialidades') || tiene_permiso('cursos')) && (moduloAutorizadoMovil('areas', $mi_rol_id_enc) || moduloAutorizadoMovil('especialidades', $mi_rol_id_enc) || moduloAutorizadoMovil('cursos', $mi_rol_id_enc) || moduloAutorizadoMovil('khronos', $mi_rol_id_enc) || moduloAutorizadoMovil('reporte_listas', $mi_rol_id_enc))): ?>
                    <li class="menu-item-elite">
                        <a href="#!" onclick="event.preventDefault()" class="menu-link-elite <?php echo (in_array($pagina, ['areas', 'especialidades', 'cursos', 'zulu', 'khronos', 'reporte_listas'])) ? 'active' : ''; ?>">
                            <div class="d-flex align-items-center">
                                <svg class="me-3" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 19.5A2.5 2.5 0 0 1 17 17H20"></path><path d="M6.5 20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                                <span>Académico</span>
                            </div>
                            <i class="bi bi-chevron-right small arrow-icon"></i>
                        </a>
                        <ul class="submenu-elite">
                            <?php if (tiene_permiso('areas') && moduloAutorizadoMovil('areas', $mi_rol_id_enc)): ?>
                                <li><a class="submenu-link-elite <?php echo ($pagina == 'areas') ? 'active' : ''; ?>" href="dashboard.php?p=areas" onclick="event.preventDefault(); navegarModulo('areas')">Áreas Curriculares</a></li>
                            <?php endif; ?>
                            <?php if (tiene_permiso('especialidades') && moduloAutorizadoMovil('especialidades', $mi_rol_id_enc)): ?>
                                <li><a class="submenu-link-elite <?php echo ($pagina == 'especialidades') ? 'active' : ''; ?>" href="dashboard.php?p=especialidades" onclick="event.preventDefault(); navegarModulo('especialidades')">Especialidades</a></li>
                            <?php endif; ?>
                            <?php if (tiene_permiso('cursos') && moduloAutorizadoMovil('cursos', $mi_rol_id_enc)): ?>
                                <?php 
                                    $es_admin_r = in_array($_SESSION['rol_id'], [1, 2, 10, 20]);
                                    $label_cursos = $es_admin_r ? 'Cursos / Grupos' : 'Mis Grupos';
                                ?>
                                <li><a class="submenu-link-elite <?php echo ($pagina == 'cursos') ? 'active' : ''; ?>" href="dashboard.php?p=cursos" onclick="event.preventDefault(); navegarModulo('cursos')"><?php echo $label_cursos; ?></a></li>
                            <?php endif; ?>
                            <?php if (tiene_permiso('zulu') && moduloAutorizadoMovil('khronos', $mi_rol_id_enc)): ?>
                                <li><a class="submenu-link-elite <?php echo ($pagina == 'zulu') ? 'active' : ''; ?>" href="dashboard.php?p=zulu" onclick="event.preventDefault(); navegarModulo('zulu')">Carga Académica</a></li>
                                <li><a class="submenu-link-elite <?php echo ($pagina == 'khronos') ? 'active' : ''; ?>" href="dashboard.php?p=khronos" onclick="event.preventDefault(); navegarModulo('khronos')">Horario Escolar</a></li>
                            <?php endif; ?>
                            <?php if (tiene_permiso('estudiantes') && moduloAutorizadoMovil('reporte_listas', $mi_rol_id_enc)): ?>
                                <li><a class="submenu-link-elite <?php echo ($pagina == 'reporte_listas') ? 'active' : ''; ?>" href="dashboard.php?p=reporte_listas" onclick="event.preventDefault(); navegarModulo('reporte_listas')">Centro de Reportes</a></li>
                            <?php endif; ?>
                            <?php if (tiene_permiso('aula_virtual') && moduloAutorizadoMovil('aula_virtual_gestion', $mi_rol_id_enc)): ?>
                                <li><a class="submenu-link-elite <?php echo ($pagina == 'aula_virtual_gestion') ? 'active' : ''; ?>" href="dashboard.php?p=aula_virtual_gestion" onclick="event.preventDefault(); navegarModulo('aula_virtual_gestion')">Aula Virtual</a></li>
                            <?php endif; ?>
                            <?php if (tiene_permiso('evaluacion') && moduloAutorizadoMovil('editor_preguntas', $mi_rol_id_enc)): ?>
                                <li><a class="submenu-link-elite <?php echo ($pagina == 'editor_preguntas') ? 'active' : ''; ?>" href="dashboard.php?p=editor_preguntas" onclick="event.preventDefault(); navegarModulo('editor_preguntas')">Banco de Reactivos</a></li>
                                <li><a class="submenu-link-elite <?php echo ($pagina == 'constructor_pruebas') ? 'active' : ''; ?>" href="dashboard.php?p=constructor_pruebas" onclick="event.preventDefault(); navegarModulo('constructor_pruebas')">Creador de exámenes</a></li>
                                <li><a class="submenu-link-elite <?php echo ($pagina == 'constructor_actividades') ? 'active' : ''; ?>" href="dashboard.php?p=constructor_actividades" onclick="event.preventDefault(); navegarModulo('constructor_actividades')">Gestor de Actividades</a></li>
                                <li><a class="submenu-link-elite <?php echo ($pagina == 'calificar_pruebas') ? 'active' : ''; ?>" href="dashboard.php?p=calificar_pruebas" onclick="event.preventDefault(); navegarModulo('calificar_pruebas')">Calificación de pruebas</a></li>
                                <?php if (moduloAutorizadoMovil('sabana_calificaciones', $mi_rol_id_enc)): ?>
                                    <li><a class="submenu-link-elite <?php echo ($pagina == 'sabana_calificaciones') ? 'active' : ''; ?>" href="dashboard.php?p=sabana_calificaciones" onclick="event.preventDefault(); navegarModulo('sabana_calificaciones')">Sábana de Notas</a></li>
                                <?php endif; ?>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <!-- CATEGORÍA: AGENDA -->
                    <?php if ((tiene_permiso('asistencia') || tiene_permiso('cronograma')) && (moduloAutorizadoMovil('asistencia', $mi_rol_id_enc) || moduloAutorizadoMovil('agenda', $mi_rol_id_enc) || moduloAutorizadoMovil('calendario', $mi_rol_id_enc))): ?>
                    <li class="menu-item-elite">
                        <a href="#!" onclick="event.preventDefault()" class="menu-link-elite <?php echo (in_array($pagina, ['asistencia', 'agenda', 'calendario'])) ? 'active' : ''; ?>">
                            <div class="d-flex align-items-center">
                                <svg class="me-3" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                <span>Agenda</span>
                            </div>
                            <i class="bi bi-chevron-right small arrow-icon"></i>
                        </a>
                        <ul class="submenu-elite">
                            <?php 
                            $stmt_tutor = $db->prepare("SELECT COUNT(*) FROM cursos WHERE tutor_id = ?");
                            $stmt_tutor->execute([(int)$_SESSION['usuario_id']]);
                            $es_tutor = $stmt_tutor->fetchColumn() > 0;
                            $es_dir = tiene_permiso('matricula') || tiene_permiso('personal');
                            
                            if (tiene_permiso('asistencia') && ($es_tutor || $es_dir) && moduloAutorizadoMovil('asistencia', $mi_rol_id_enc)): ?>
                                <li><a class="submenu-link-elite <?php echo ($pagina == 'asistencia') ? 'active' : ''; ?>" href="dashboard.php?p=asistencia" onclick="event.preventDefault(); navegarModulo('asistencia')">Control de Asistencia</a></li>
                            <?php endif; ?>
                            
                            <?php if (tiene_permiso('agenda') && moduloAutorizadoMovil('agenda', $mi_rol_id_enc)): ?>
                                <li><a class="submenu-link-elite <?php echo ($pagina == 'agenda') ? 'active' : ''; ?>" href="dashboard.php?p=agenda" onclick="event.preventDefault(); navegarModulo('agenda')">Agenda Académica</a></li>
                            <?php endif; ?>

                            <?php if (tiene_permiso('cronograma') && moduloAutorizadoMovil('calendario', $mi_rol_id_enc)): ?>
                                <li><a class="submenu-link-elite <?php echo ($pagina == 'calendario') ? 'active' : ''; ?>" href="dashboard.php?p=calendario" onclick="event.preventDefault(); navegarModulo('calendario')">Cronograma Escolar</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <!-- MENSAJERÍA -->
                    <?php if (tiene_permiso('mensajeria') && moduloAutorizadoMovil('mensajeria', $mi_rol_id_enc)): ?>
                    <li class="menu-item-elite">
                        <a href="dashboard.php?p=mensajeria" onclick="event.preventDefault(); navegarModulo('mensajeria')" class="menu-link-elite <?php echo ($pagina == 'mensajeria') ? 'active' : ''; ?>">
                            <div class="d-flex align-items-center">
                                <svg class="me-3" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                                <span>Mensajería</span>
                            </div>
                        </a>
                    </li>
                    <?php endif; ?>

                    <!-- ADMINISTRACIÓN -->
                    <?php if ((tiene_permiso('configuracion') || tiene_permiso('usuarios') || tiene_permiso('personal') || tiene_permiso('roles') || tiene_permiso('auditoria')) && (moduloAutorizadoMovil('configuracion', $mi_rol_id_enc) || moduloAutorizadoMovil('personal', $mi_rol_id_enc) || moduloAutorizadoMovil('auditoria', $mi_rol_id_enc))): ?>
                    <li class="menu-item-elite">
                        <a href="#!" onclick="event.preventDefault()" class="menu-link-elite <?php echo (in_array($pagina, ['configuracion', 'usuarios', 'mi_perfil', 'personal', 'roles', 'auditoria'])) ? 'active' : ''; ?>">
                            <div class="d-flex align-items-center">
                                <svg class="me-3" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                                <span>Administración</span>
                            </div>
                            <i class="bi bi-chevron-right small arrow-icon"></i>
                        </a>
                        <ul class="submenu-elite">
                            <?php if (tiene_permiso('configuracion') && moduloAutorizadoMovil('configuracion', $mi_rol_id_enc)): ?>
                                <li><a class="submenu-link-elite <?php echo ($pagina == 'configuracion') ? 'active' : ''; ?>" href="dashboard.php?p=configuracion" onclick="event.preventDefault(); navegarModulo('configuracion')">Configuración</a></li>
                            <?php endif; ?>
                            <?php if (tiene_permiso('personal') && moduloAutorizadoMovil('personal', $mi_rol_id_enc)): ?>
                                <li><a class="submenu-link-elite <?php echo ($pagina == 'personal') ? 'active' : ''; ?>" href="dashboard.php?p=personal" onclick="event.preventDefault(); navegarModulo('personal')">Talento Humano</a></li>
                            <?php endif; ?>
                            <?php if (tiene_permiso('auditoria') && moduloAutorizadoMovil('auditoria', $mi_rol_id_enc)): ?>
                                <li><a class="submenu-link-elite <?php echo ($pagina == 'auditoria') ? 'active' : ''; ?>" href="dashboard.php?p=auditoria" onclick="event.preventDefault(); navegarModulo('auditoria')">Bitácora de Sistema</a></li>
                            <?php endif; ?>
                            
                            <?php if ($_SESSION['rol_id'] == 1): ?>
                                <li class="py-2"><hr class="dropdown-divider opacity-25"></li>
                                <li class="px-4 pb-1"><span class="text-uppercase fw-bold opacity-50 fs-nano tracking-widest">Mantenimiento / Resets</span></li>
                                <li>
                                    <a class="submenu-link-elite text-danger fw-bold" href="#!" onclick="purgarIntentosAres()">
                                        <i class="bi bi-fire me-2"></i> Resetear Intentos Ares
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
                <div class="px-3 py-2 text-center border-top mt-2 border-white-10">
                    <span class="badge-soberania badge-soberania--context">
                        CONTEXTO: <?php echo esMovil() ? 'MÓVIL' : 'ESCRITORIO'; ?>
                    </span>
                </div>
            </div>
            
            <div class="p-3 mt-auto">
                <!-- Espacio para expansión futura -->
            </div>
        </nav>

        <!-- MAIN CONTENT AREA -->
        <main class="d-flex flex-column p-0 main-content-fixed">
            
            <!-- HEADER -->
            <header class="navbar navbar-white sticky-top bg-white p-2 shadow-sm border-bottom">
                <div class="container-fluid d-flex flex-nowrap align-items-center justify-content-between">
                    <div class="d-flex align-items-center overflow-hidden">
                        <button class="btn-toggle-sidebar me-2" type="button" onclick="toggleSidebarElite()">
                            <i class="bi bi-list fs-2"></i>
                        </button>
                        <h1 class="h3 fw-bold mb-0 hero-module-title text-topbar-elite text-truncate header-title-truncate"><?php echo $nombre_modulo; ?></h1>
                    </div>
                    
                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                        <!-- TOGGLE MODO OSCURO -->
                        <button onclick="toggleDarkMode()" class="btn btn-link text-dark no-print p-1 shadow-none" id="btnDarkMode">
                            <svg id="icon-moon" class="<?php echo ($cfg_dark_mode == '1') ? 'd-none' : ''; ?>" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                            <svg id="icon-sun" class="<?php echo ($cfg_dark_mode == '1') ? '' : 'd-none'; ?>" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
                        </button>

                        <!-- CAMPANA DE NOTIFICACIONES -->
                        <div class="dropdown no-print" id="campanaNotificaciones">
                            <a href="#" class="text-dark position-relative p-1 d-block" data-bs-toggle="dropdown" aria-expanded="false">
                                <svg id="icono-campana" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0 p-2 w-fixed-280" id="lista-notificaciones-mini">
                                <li class="dropdown-header fw-bold text-uppercase small pb-2">Notificaciones</li>
                                <li><hr class="dropdown-divider"></li>
                                <li class="text-center py-3 text-muted small">Cargando...</li>
                            </ul>
                        </div>

                        <!-- PERFIL DE USUARIO -->
                        <div class="dropdown no-print ms-2">
                            <a href="#" class="d-flex align-items-center text-decoration-none" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="d-none d-md-flex flex-column text-end me-3">
                                    <span class="text-dark fw-bold fs-xs lh-1"><?php echo htmlspecialchars($_SESSION['nombre_usuario'] ?? 'Usuario'); ?></span>
                                    <span class="text-muted text-uppercase fs-nano fw-semibold"><?php echo htmlspecialchars($_SESSION['rol_nombre'] ?? 'Personal'); ?></span>
                                </div>
                                <div class="avatar-user-header">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="8" r="4" />
                                        <path d="M 6 20 C 6 16.7 8.7 14 12 14 C 15.3 14 18 16.7 18 20" />
                                    </svg>
                                </div>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 animate__animated animate__fadeIn fs-7">
                                <li class="px-3 py-2 border-bottom mb-1">
                                    <div class="fw-bold text-dark"><?php echo $_SESSION['nombre'] ?? 'Usuario'; ?></div>
                                    <small class="text-muted text-uppercase fs-nano"><?php echo $_SESSION['rol_nombre'] ?? 'Personal'; ?></small>
                                </li>
                                <li><a class="dropdown-item d-flex align-items-center py-2" href="dashboard.php?p=mi_perfil" onclick="event.preventDefault(); navegarModulo('mi_perfil')">
                                    <i class="bi bi-person me-2"></i> Mi Perfil</a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger d-flex align-items-center py-2" href="logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </header>

            <!-- DINAMIC VIEW CONTENT -->
            <div class="flex-fill overflow-auto">
                <div class="p-0">
                    <?php
                        if (file_exists($archivo)) {
                            include($archivo);
                        } else {
                            echo "<div class='container p-5 text-center'><h2 class='display-6 fw-bold text-muted'>La sección '$pagina' no fue encontrada.</h2></div>";
                        }
                    ?>
                </div>
            </div>
        </main>

    </div>
</div>

<!-- 🛡️ ESCUDO DE ORIENTACIÓN ÉLITE (Soberanía Horizontal) -->
<div id="escudo-orientacion-elite">
    <div class="escudo-contenido">
        <div class="escudo-icon-box">
            <i class="bi bi-phone-landscape-fill"></i>
        </div>
        <h2 class="escudo-titulo">GIRE SU DISPOSITIVO</h2>
        <p class="escudo-texto">Para una visualización institucional óptima y legible, el sistema requiere el uso en modo horizontal.</p>
        <div class="loader-ball-elite">
            <div class="balls-1"></div>
            <div class="balls-2"></div>
            <div class="balls-3"></div>
            <div class="balls-4"></div>
            <div class="balls-5"></div>
            <div class="balls-6"></div>
            <div class="balls-7"></div>
            <div class="balls-8"></div>
            <div class="balls-9"></div>
        </div>
    </div>
</div>

<!-- 🛡️ ESCUDO DE NAVEGACIÓN (OVERLAY) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebarElite()"></div>

<!-- Scripts Nucleares (Carga Local) -->
<script src="../assets/libs/datatables/dataTables.min.js"></script>
<script src="../assets/libs/datatables/dataTables.bootstrap5.min.js"></script>
<script src="../js/dashboard.js" defer></script>
<script src="../js/sidebar_gravity.js?v=<?php echo time(); ?>"></script>
<script src="../js/elite_showroom.js?v=<?php echo time(); ?>"></script>
<!-- 🛠️ MOTOR DE SEGURIDAD ÉLITE (Inyección de Tokens) -->
<script>
    window.CSRF_TOKEN = '<?php echo generar_csrf_token(); ?>';
</script>
<!-- JS EXTERNO -->
<script src="../js/modules/hermes_global.js?v=<?php echo time(); ?>"></script>
<script src="../js/modules/ui_navigator.js?v=<?php echo time(); ?>"></script>
<script src="../js/modules/security_core.js?v=<?php echo time(); ?>"></script>
<script src="../js/modules/academic_manager.js?v=<?php echo time(); ?>"></script>
<script src="../js/modules/perseus_engine.js?v=<?php echo time(); ?>"></script>
<script src="../js/script.js?v=<?php echo time(); ?>"></script>

<?php
$accesos_rapidos = [
    ['perm' => 'matricula', 'label' => 'Matrícula', 'icon' => 'bi-clipboard-check', 'p' => 'matricula', 'color' => 'bg-primary'],
    ['perm' => 'cursos', 'label' => 'Cursos', 'icon' => 'bi-mortarboard', 'p' => 'cursos', 'color' => 'bg-info'],
    ['perm' => 'estudiantes', 'label' => 'Alumnos', 'icon' => 'bi-people', 'p' => 'matriculados', 'color' => 'bg-success'],
    ['perm' => 'personal', 'label' => 'Talento Humano', 'icon' => 'bi-person-badge', 'p' => 'personal', 'color' => 'bg-warning'],
    ['perm' => 'asistencia', 'label' => 'Asistencia', 'icon' => 'bi-calendar-check', 'p' => 'asistencia', 'color' => 'bg-danger'],
    ['perm' => 'agenda', 'label' => 'Agenda', 'icon' => 'bi-journal-bookmark', 'p' => 'agenda', 'color' => 'bg-dark'],
    ['perm' => 'mensajeria', 'label' => 'Mensajería', 'icon' => 'bi-chat-dots', 'p' => 'mensajeria', 'color' => 'bg-info'],
    ['perm' => 'configuracion', 'label' => 'Ajustes', 'icon' => 'bi-gear', 'p' => 'configuracion', 'color' => 'bg-secondary']
];

$accesos_validos = [];
foreach ($accesos_rapidos as $acc) {
    if (tiene_permiso($acc['perm']) && moduloAutorizadoMovil($acc['p'], $mi_rol_id_enc)) {
        $accesos_validos[] = $acc;
    }
}

if (!empty($accesos_validos)):
?>
<!-- 🛠️ PESTAÑA FLOTANTE Y OFFCANVAS DE ACCESOS RÁPIDOS -->
<button class="btn-floating-tab-elite no-print" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasAccesosRapidos" aria-controls="offcanvasAccesosRapidos" title="Accesos Rápidos">
    <i class="bi bi-grid-fill fs-4"></i>
</button>

<div class="offcanvas offcanvas-end card-elite no-print" tabindex="-1" id="offcanvasAccesosRapidos" aria-labelledby="offcanvasAccesosRapidosLabel">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title text-primary fw-bold" id="offcanvasAccesosRapidosLabel">
            <i class="bi bi-grid-fill me-2"></i>ACCESOS RÁPIDOS
        </h5>
    </div>
    <div class="offcanvas-body">
        <div class="offcanvas-grid-elite">
            <?php foreach ($accesos_validos as $acc): ?>
                <button onclick="navegarModulo('<?php echo $acc['p']; ?>')" class="btn-action-pill-elite" data-bs-dismiss="offcanvas">
                    <div class="btn-action-icon <?php echo $acc['color']; ?> bg-opacity-10 text-<?php echo str_replace('bg-', '', $acc['color']); ?>">
                        <i class="bi <?php echo $acc['icon']; ?>"></i>
                    </div>
                    <span class="btn-action-label mt-1 text-center"><?php echo $acc['label']; ?></span>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>
</body>
</html>

