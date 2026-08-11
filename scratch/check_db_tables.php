<?php
declare(strict_types=1);
require_once __DIR__ . '/../php/db.php';

echo "--- ROLES ---\n";
$roles = $db->query("SELECT * FROM roles")->fetchAll();
foreach ($roles as $r) {
    echo "ID: {$r['id']} | Name: {$r['nombre_rol']}\n";
}

echo "\n--- USUARIOS ---\n";
$usuarios = $db->query("SELECT id, usuario, rol_id, nombre FROM usuarios")->fetchAll();
foreach ($usuarios as $u) {
    echo "ID: {$u['id']} | User: {$u['usuario']} | Rol ID: {$u['rol_id']} | Name: {$u['nombre']}\n";
}
