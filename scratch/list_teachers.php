<?php
require_once 'php/db.php';
header('Content-Type: text/plain');
try {
    $stmt = $db->query("SELECT u.id, u.usuario, u.nombre, r.nombre_rol FROM usuarios u JOIN roles r ON u.rol_id = r.id WHERE r.nombre_rol LIKE '%Docente%' OR r.nombre_rol LIKE '%Profesor%' OR r.nombre_rol LIKE '%Tutor%' OR r.nombre_rol LIKE '%Docente%' LIMIT 10");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
