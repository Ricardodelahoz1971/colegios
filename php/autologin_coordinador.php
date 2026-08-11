<?php
declare(strict_types=1);

// 🛡️ BLINDAJE DE SEGURIDAD: Bloquear acceso remoto a la puerta de desarrollo
$ip_cliente = $_SERVER['REMOTE_ADDR'] ?? '';
if ($ip_cliente !== '127.0.0.1' && $ip_cliente !== '::1') {
    http_response_code(403);
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
    session_regenerate_id(true);
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