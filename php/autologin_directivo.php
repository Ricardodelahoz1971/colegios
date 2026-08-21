<?php
declare(strict_types=1);
session_start();
require_once 'db.php';

// Obtener el primer usuario administrador o docente
$stmt = $db->prepare("SELECT u.id, u.nombre, u.rol_id, r.nombre_rol FROM usuarios u JOIN roles r ON u.rol_id = r.id WHERE u.rol_id IN (1,2,3,11) LIMIT 1"); $stmt->execute();
$user = $stmt->fetch();

if ($user) {
    session_regenerate_id(true);
    $_SESSION['usuario_id'] = $user['id'];
    $_SESSION['rol_id'] = $user['rol_id'];
    $_SESSION['rol_nombre'] = $user['nombre_rol'];
    $_SESSION['nombre_usuario'] = $user['nombre'];
    header("Location: dashboard.php");
} else {
    echo "No user found with rol 1, 2, 3, or 11.";
}
exit();
