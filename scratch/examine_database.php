<?php
declare(strict_types=1);

require_once __DIR__ . '/../php/db.php';

echo "=== USERS WITH permisos_custom ===\n";
$stmt = $db->prepare("SELECT id, usuario, nombre, rol_id, permisos_custom FROM usuarios");
$stmt->execute();
$users = $stmt->fetchAll();
foreach ($users as $u) {
    echo "ID: {$u['id']} | User: {$u['usuario']} | Name: {$u['nombre']} | Rol ID: {$u['rol_id']} | Custom Perms: {$u['permisos_custom']}\n";
}
