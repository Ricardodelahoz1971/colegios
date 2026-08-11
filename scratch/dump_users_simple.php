<?php
require_once __DIR__ . '/../php/db.php';
$res = $db->query("SELECT u.id AS usuario_id, u.usuario, u.nombre, e.id AS estudiante_id, e.apellido FROM usuarios u JOIN estudiantes e ON u.estudiante_id = e.id LIMIT 5");
print_r($res->fetchAll(PDO::FETCH_ASSOC));
