<?php
require_once 'C:\\xampp\\htdocs\\sistema_escolar\\php\\db.php';

$stmt = $db->query("SELECT id, usuario, password, rol_id FROM usuarios WHERE usuario LIKE '%admin%'");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $u) {
    echo "ID: " . $u['id'] . " | Usuario: '" . $u['usuario'] . "' | Rol: " . $u['rol_id'] . "\n";
}
