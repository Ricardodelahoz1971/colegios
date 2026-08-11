<?php
declare(strict_types=1);

/**
 * 🏛️ CONFIGURADOR DE CREDENCIALES DE COORDINADOR (LFV)
 */

require_once __DIR__ . '/../php/db.php';

// 1. Definir credenciales
$usuario_coordinador = 'LFV';
$password_claro = 'coordinador';
$hash_password = password_hash($password_claro, PASSWORD_DEFAULT);

// 2. Actualizar base de datos
try {
    $stmt = $db->prepare("UPDATE usuarios SET password = ? WHERE usuario = ?");
    $stmt->execute([$hash_password, $usuario_coordinador]);
    $affected = $stmt->rowCount();
    
    echo "========================================================\n";
    echo "🔐 CONFIGURACIÓN DE CREDENCIALES DE COORDINADOR\n";
    echo "========================================================\n";
    if ($affected > 0) {
        echo "✅ Base de datos actualizada con éxito para el usuario '$usuario_coordinador'.\n";
        echo "🔹 Contraseña asignada: '$password_claro'\n";
    } else {
        echo "⚠️ El usuario '$usuario_coordinador' ya tenía esa configuración o no fue modificado.\n";
    }
} catch (PDOException $e) {
    echo "❌ Error actualizando base de datos: " . $e->getMessage() . "\n";
    exit(1);
}

// 3. Crear el archivo php/autologin_coordinador.php
$autologin_content = <<<'PHP'
<?php
declare(strict_types=1);

// 🛡️ BLINDAJE DE SEGURIDAD: Bloquear acceso remoto a la puerta de desarrollo
$ip_cliente = $_SERVER['REMOTE_ADDR'] ?? '';
if ($ip_cliente !== '127.0.0.1' && $ip_cliente !== '::1') {
    http_response_code(403);
    die('Acceso Denegado: Autologin restringido únicamente a entornos de desarrollo local.');
}

session_start();
require_once 'db.php';

// Buscar datos del coordinador LFV (ID 21)
$stmt = $db->prepare("
    SELECT u.id, u.usuario, u.nombre, u.rol_id, r.nombre_rol 
    FROM usuarios u
    JOIN roles r ON u.rol_id = r.id
    WHERE u.usuario = 'LFV'
");
$stmt->execute();
$user = $stmt->fetch();

if ($user) {
    $_SESSION['usuario_id'] = $user['id'];
    $_SESSION['nombre_usuario'] = $user['nombre'];
    $_SESSION['identificacion'] = $user['usuario']; 
    $_SESSION['rol_id'] = $user['rol_id'];
    $_SESSION['rol_nombre'] = $user['nombre_rol'];
    $_SESSION['last_activity'] = time();
    
    header("Location: dashboard.php?p=sabana_calificaciones");
    exit();
} else {
    echo "Usuario Coordinador no encontrado.";
}
PHP;

$filepath = __DIR__ . '/../php/autologin_coordinador.php';
$written = file_put_contents($filepath, $autologin_content);

if ($written !== false) {
    echo "✅ Archivo de autologin de un solo clic creado con éxito en:\n   php/autologin_coordinador.php\n";
} else {
    echo "❌ Error al escribir el archivo php/autologin_coordinador.php\n";
}
echo "========================================================\n";
