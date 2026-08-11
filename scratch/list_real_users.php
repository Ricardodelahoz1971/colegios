<?php
include_once __DIR__ . '/../php/db.php';
$users = $db->query("SELECT u.id, u.usuario, u.nombre, u.rol_id, r.nombre_rol FROM usuarios u JOIN roles r ON u.rol_id = r.id")->fetchAll(PDO::FETCH_ASSOC);
echo "📋 USUARIOS REALES EN LA BASE DE DATOS:\n";
foreach($users as $u) {
    echo "- Username: {$u['usuario']} | Nombre: {$u['nombre']} | Rol: {$u['nombre_rol']} (ID: {$u['rol_id']})\n";
}
