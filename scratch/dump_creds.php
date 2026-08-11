<?php
$db_path = 'c:/xampp/htdocs/sistema_escolar/php/database/usuarios.db';
$db = new PDO("sqlite:$db_path");

echo "| ID | Nombre | Usuario | Rol | Contraseña |\n";
echo "|---|---|---|---|---|\n";

$stmt = $db->query("
    SELECT u.id, u.nombre, u.usuario, r.nombre_rol 
    FROM usuarios u 
    JOIN roles r ON u.rol_id = r.id 
    WHERE r.nombre_rol IN ('Administrador', 'Coordinador', 'Docente')
    ORDER BY u.rol_id ASC, u.id ASC
");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "| {$row['id']} | {$row['nombre']} | **{$row['usuario']}** | {$row['nombre_rol']} | `1234` |\n";
}
