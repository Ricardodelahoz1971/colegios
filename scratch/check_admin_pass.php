<?php
require_once 'php/db.php';
header('Content-Type: text/plain');
try {
    $stmt = $db->query("SELECT u.id, u.usuario, u.password, r.nombre_rol FROM usuarios u JOIN roles r ON u.rol_id = r.id WHERE u.usuario = 'ADMIN'");
    $admin = $stmt->fetch();
    if ($admin) {
        echo "Usuario: " . $admin['usuario'] . "\n";
        echo "Hash: " . $admin['password'] . "\n";
        echo "Rol: " . $admin['nombre_rol'] . "\n";
        echo "Verificación de '1234': " . (password_verify('1234', $admin['password']) ? 'VÁLIDA' : 'INVÁLIDA') . "\n";
    } else {
        echo "El usuario ADMIN no existe en la base de datos.\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
