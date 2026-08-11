<?php
declare(strict_types=1);

require_once __DIR__ . '/../php/db.php';

$stmt = $db->query("
    SELECT u.id, u.usuario, u.nombre, r.nombre_rol 
    FROM usuarios u 
    LEFT JOIN roles r ON u.rol_id = r.id
");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "========================================================\n";
echo "👥 LISTA DE USUARIOS REGISTRADOS EN EL SISTEMA\n";
echo "========================================================\n";
foreach ($users as $u) {
    echo "ID: " . $u['id'] . " | Usuario: " . $u['usuario'] . " | Nombre: " . $u['nombre'] . " | Rol: " . ($u['nombre_rol'] ?? 'Sin Rol') . "\n";
}
echo "========================================================\n";
