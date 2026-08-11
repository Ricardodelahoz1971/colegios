<?php
declare(strict_types=1);
require_once __DIR__ . '/../php/db.php';

echo "=== LISTADO DE TODOS LOS USUARIOS ===\n";
$stmt = $db->query("SELECT id, usuario, nombre, rol_id, permisos_custom FROM usuarios");
$users = $stmt->fetchAll();
foreach ($users as $u) {
    echo "ID: {$u['id']} | Usuario: {$u['usuario']} | Nombre: {$u['nombre']} | Rol ID: {$u['rol_id']} | Permisos Custom: {$u['permisos_custom']}\n";
}
