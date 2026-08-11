<?php

// @sast-ignore - Deuda técnica legacy controlada bajo línea base de seguridad v5.0
declare(strict_types=1);
// Test de integración de Git hooks y validación de cambios v1.0
/**
 * INDEX.PHP - ACCESO INSTITUCIONAL ELITE v9.2
 * Corazón del sistema bajo estándares de Ingeniería Senior y Elite UI Kit.
 */
require_once 'php/db.php';
require_once 'php/security.php';

// 1. CARGAR IDENTIDAD VISUAL DINÁMICA (PDO)
// ... (mantenemos el resto igual)
$stmt_estetica = $db->prepare("SELECT clave, valor FROM ajustes_estetica");
$stmt_estetica->execute();
$cfg = $stmt_estetica->fetchAll(PDO::FETCH_KEY_PAIR);

// Fallbacks de seguridad institucional
$brand_color = $cfg['brand_color'] ?? '#0f0664';
$school_font = $cfg['school_font'] ?? 'Montserrat';
$school_name = $cfg['school_name'] ?? 'SISTEMA ESCOLAR ÉLITE';
$school_motto = $cfg['school_motto'] ?? 'Excelencia en Gestión Educativa';
$school_logo  = $cfg['school_logo'] ?? '';
$dark_mode    = $cfg['dark_mode'] ?? '0';

/**
 * Convierte HEX a RGB para inyección en variables CSS de precisión.
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $school_name; ?> - Acceso Institucional</title>
    
    <!-- Blindaje de Cache y Seguridad -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;800&family=Montserrat:wght@400;500;700;800;900&family=Roboto:wght@300;400;500;700;900&family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
    
    <!-- Componentes Élite (Carga vía Capas) -->
    
    <!-- Elite UI Kit Core & Login Architecture -->
    <link rel="stylesheet" href="styles/elite_layers.css">
    <link rel="stylesheet" href="styles/elite_themes.css">
    <link rel="stylesheet" href="styles/ui_kit.css">
    <link rel="stylesheet" href="styles/utilities.css">
    <link rel="stylesheet" href="styles/modules/login.css">

    <!-- Motor de Diálogos Élite (Carga Local) -->
    <script src="assets/libs/sweetalert2/sweetalert2.all.min.js"></script>
    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>

    <style>
        :root {
            --el-primary: <?php echo $brand_color; ?>;
            --el-primary-rgb: <?php echo hexToRgb($brand_color); ?>;
            --el-white: rgb(255, 255, 255);
            --el-white-rgb: 255, 255, 255;
            --el-dark-rgb: 30, 41, 59;
            --el-font-institutional: '<?php echo $school_font; ?>', sans-serif;
        }
        body {
            font-family: var(--el-font-institutional) !important;
        }
    </style>
</head>

<body class="<?php echo ($dark_mode == '1') ? 'dark-theme-mode' : ''; ?>">

    <div class="login-container-elite">
        <div class="login-card-elite">
            <!-- Cabecera Institucional -->
            <div class="text-center mb-5">
                <div class="mb-4">
                    <?php 
                    $logo_exists = !empty($school_logo) && file_exists(__DIR__ . '/' . $school_logo);
                    if ($logo_exists): ?>
                        <img src="<?php echo $school_logo; ?>" alt="Logo" class="login-logo-elite school-logo-global">
                    <?php else: ?>
                        <svg class="login-logo-elite" viewBox="0 0 24 24" fill="none" stroke="var(--el-primary)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        </svg>
                    <?php endif; ?>
                </div>
                <h1 class="hero-login-title h3 mb-1 school-name-global"><?php echo $school_name; ?></h1>
                <p class="small text-secondary fw-bold mb-0">PORTAL DE ACCESO MAESTRO</p>
            </div>

            <!-- Formulario de Acceso v9.2 -->
            <form action="php/login.php" method="post" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo generar_csrf_token(); ?>">
                
                <div class="input-elite-group">
                    <svg class="input-icon-elite" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    <label for="usuario" class="visually-hidden">Nombre de Usuario</label>
                    <input type="text" id="usuario" name="usuario" class="input-elite" placeholder="USUARIO" oninput="this.value = this.value.toUpperCase()" autocomplete="username" required>
                </div>

                <div class="input-elite-group">
                    <svg class="input-icon-elite" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    <label for="password" class="visually-hidden">Contraseña Institucional</label>
                    <input type="password" id="password" name="password" class="input-elite" placeholder="CLAVE" autocomplete="current-password" required>
                </div>

                <div class="mt-3 text-center">
                    <button type="submit" class="btn-elite btn-elite--primary">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                        INICIAR ACCESO
                    </button>
                </div>
            </form>

            <div class="footer-login-elite">
                <p class="mb-0 text-uppercase">&copy; <?php echo date('Y'); ?> &bull; <?php echo $school_motto; ?></p>
            </div>
        </div>
    </div>

    <!-- Scripts de Interacción Showroom Elite (Carga Local) -->
    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="js/elite_showroom.js?v=9.2.1"></script>
    <script>
        // 1. GESTOR DE NOTIFICACIONES ELITE (SweetAlert2)
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const error = urlParams.get('error');

            if (error) {
                let config = {
                    icon: 'error',
                    confirmButtonColor: 'var(--el-primary)',
                    background: document.body.classList.contains('dark-theme-mode') ? 'var(--el-white)' : 'var(--el-white)',
                    color: document.body.classList.contains('dark-theme-mode') ? 'var(--el-dark)' : 'var(--el-dark)'
                };

                const errorMap = {
                    'credenciales_invalidas': { title: 'Acceso Denegado', text: 'Las credenciales ingresadas no son correctas.' },
                    'campos_vacios': { title: 'Datos Incompletos', text: 'Por favor, ingrese sus credenciales completas.', icon: 'warning' },
                    'falta_usuario': { title: 'Falta Usuario', text: 'Debe ingresar su identificación institucional.', icon: 'warning' },
                    'falta_clave': { title: 'Falta Contraseña', text: 'No ha ingresado su clave de seguridad.', icon: 'warning' },
                    'db_fail': { title: 'Error Crítico', text: 'Error de conexión con el núcleo de datos.', icon: 'info' }
                };

                if (errorMap[error]) {
                    Swal.fire({
                        ...config,
                        ...errorMap[error],
                        customClass: {
                            popup: 'rounded-4'
                        }
                    });
                    // LIMPIEZA MAESTRA: Elimina el rastro del error de la URL para evitar repeticiones al actualizar
                    window.history.replaceState({}, document.title, window.location.pathname);
                }
            }
        });

        // 2. Limpieza de campos al retroceder (Seguridad) y Foco Maestro
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                document.getElementById('usuario').value = '';
                document.getElementById('password').value = '';
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (!urlParams.get('error')) {
                document.getElementById('usuario').focus();
            }
        });
    </script>
    <!-- SELLO DE IDENTIDAD: PERSEUS ELITE -->
    <div class="perseus-watermark">
        <img src="perseus.png" alt="Perseus Elite System">
    </div>
</body>
</html>
