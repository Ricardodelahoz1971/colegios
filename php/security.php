<?php
declare(strict_types=1);
// PHP/SECURITY.PHP - EL ESCUDO DE SEGURIDAD ÉLITE v1.0
// Este archivo centraliza la protección contra CSRF, XSS y accesos no autorizados.

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => false,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}



/**
 * Genera un token CSRF único si no existe.
 * @return string El token generado.
 */
function generar_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validar_csrf(?string $token = null): bool {
    if ($token === null) {
        $token = filter_input(INPUT_POST, 'csrf_token') ?? '';
    }
    if (empty($token)) {
        // Soporte para cabeceras HTTP (AJAX)
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            $token = $headers['X-CSRF-Token'] ?? $headers['x-csrf-token'] ?? '';
        }
    }
    if (empty($token)) {
        // Soporte para cuerpos de solicitud JSON
        $raw_input = file_get_contents('php://input');
        if (!empty($raw_input)) {
            $decoded = json_decode($raw_input, true);
            if (is_array($decoded)) {
                $token = $decoded['csrf_token'] ?? '';
            }
        }
    }
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/**
 * 🛡️ PROTOCOLO DE INTIMIDACIÓN ÉLITE v2.0 (MODO ESCARMIENTO)
 * Despliega castigo visual y psicológico. 
 * Strike 1-2: Rojo/Serio. Strike 3+: Verde/Burlón/Bailando.
 */
function ejecutar_protocolo_intimidacion(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    
    $_SESSION['security_strikes'] = ($_SESSION['security_strikes'] ?? 0) + 1;
    $strikes = $_SESSION['security_strikes'];
    
    $ip = $_SERVER['REMOTE_ADDR'];
    $uri = $_SERVER['REQUEST_URI'];
    
    // Registrar violación de seguridad de forma persistente en auditoría
    registrar_violacion_seguridad('SISTEMA_GLOBAL', "Intento de Intrusión (Strike: $strikes)", "URI Objetivo: $uri");
    
    if (ob_get_length()) ob_clean();
    header("HTTP/1.1 403 Forbidden");

    $color_base = ($strikes >= 3) ? "var(--el-success)" : "var(--el-danger)";
    $color_rgb = ($strikes >= 3) ? "5, 150, 105" : "220, 38, 38";
    $clase_troll = ($strikes >= 3) ? "troll-dancing" : "glitch";
    $mensaje_header = ($strikes >= 3) ? "¡TE LO DIJIMOS, REINCIDENTE!" : "ACCESO NO AUTORIZADO";
    $mensaje_cuerpo = ($strikes >= 3) ? "FELICIDADES: Has ignorado 2 advertencias. Ahora tu navegador nos pertenece. Baila con nosotros mientras enviamos tu historial al Ingeniero Jefe de Seguridad." : "Se ha detectado un intento de intrusión. Su actividad ha sido registrada para análisis forense.";
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>SISTEMA DE CASTIGO ÉLITE</title>
        <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="../styles/ui_kit.css">
        <link rel="stylesheet" href="../styles/modules/security.css">
        
    </head>
    <body>
        <div class="lockdown-container <?php echo $clase_troll; ?>" id="lockdown-container" data-lockdown-color="<?php echo $color_base; ?>" data-lockdown-color-rgb="<?php echo $color_rgb; ?>">
            <div class="strike-count">STRIKE: <?php echo $strikes; ?></div>
            <h1><?php echo $mensaje_header; ?></h1>
            <p><?php echo $mensaje_cuerpo; ?></p>
            
            <div class="data-box">
                > ORIGEN: <?php echo e($ip); ?><br>
                > OBJETIVO: <?php echo e($uri); ?><br>
                > CASTIGO: <?php echo ($strikes >= 3) ? "HUMILLACIÓN PÚBLICA Y RASTREO TOTAL" : "ADVERTENCIA NIVEL 1"; ?>
            </div>

            <?php if($strikes >= 3): ?>
                <div class="ascii-troll">
                 _   _  _  _  _   _ 
                ( ) ( )( )( )( ) ( )
                | |_| || || || |_| |
                |  _  || || ||  _  |
                | | | || || || | | |
                (_) (_)(_)(_)(_) (_)
                DANCING LOCKDOWN ACTIVE
                </div>
            <?php endif; ?>

            <div class="timer-box" id="timer">10</div>
        </div>
        <script src="../js/security.js" defer></script>
    </body>
    </html>
    <?php
    exit;
}

/**
 * Registra un evento de seguridad de forma de bitácora digital en la base de datos.
 * @param string $modulo El módulo afectado.
 * @param string $intento La violación o acción sospechosa.
 * @param string|null $detalles Detalles adicionales de la violación.
 */
function registrar_violacion_seguridad(string $modulo, string $intento, ?string $detalles = null): void {
    global $db;
    try {
        if (!isset($db)) {
            $host = 'localhost';
            $dbname = 'sistema_escolar';
            $user = 'root';
            $pass = '';
            $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $usuario_id = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : null;
        $rol_nombre = $_SESSION['rol_nombre'] ?? 'Invitado';
        
        $stmt = $db->prepare("
            INSERT INTO log_auditoria_seguridad (ip, usuario_id, rol_nombre, violacion_intento, modulo_afectado, detalles)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$ip, $usuario_id, $rol_nombre, $intento, $modulo, $detalles]);
    } catch (Exception $e) {
        error_log("Error al registrar logs de seguridad: " . $e->getMessage());
    }
}

/**
 * Alias ultra-corto para htmlspecialchars (Protección XSS).
 * @param string $string El texto a escapar.
 * @return string
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Bloqueo de seguridad para peticiones que requieren POST y Token.
 */
function proteccion_extrema(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('HTTP/1.1 405 Method Not Allowed');
        die(json_encode(['status' => 'error', 'message' => 'Solo se permiten peticiones POST']));
    }
    
    if (!validar_csrf()) {
        registrar_violacion_seguridad($_SERVER['REQUEST_URI'] ?? 'API_UNKNOWN', 'VIOLACIÓN_CSRF', 'Petición POST rechazada por falta o invalidez de token CSRF');
        header('HTTP/1.1 403 Forbidden');
        die(json_encode(['status' => 'error', 'message' => 'Violación de Seguridad CSRF detectada']));
    }
}

/**
 * Verifica que exista una sesión activa con usuario autenticado.
 * Responde con 401 JSON y termina si no hay sesión válida.
 * Invocar al inicio de cada endpoint de lógica.
 */
function guardia_sesion(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => false,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }
    if (!isset($_SESSION['usuario_id'])) {
        http_response_code(401);
        die(json_encode(['status' => 'error', 'message' => 'Sesión no autorizada']));
    }
}

/**
 * Sanitiza texto eliminando caracteres de control invisibles sin alterar
 * caracteres UTF-8 nativos (tildes, eñes, diéresis, símbolos) ni convertirlos a entidades HTML.
 *
 * @param string|null $texto
 * @return string
 */
function limpiar_texto_utf8(?string $texto): string {
    if ($texto === null) return '';
    $t = trim((string)$texto);
    $limpio = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $t);
    return $limpio !== null ? $limpio : $t;
}
?>
