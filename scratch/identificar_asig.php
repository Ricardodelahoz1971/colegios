<?php
require_once 'php/db.php';
$sql = "SELECT a.id, p.titulo, 
        (SELECT COUNT(*) FROM eval_respuestas WHERE asignacion_id = a.id) as conteo_sucio,
        (SELECT COUNT(*) FROM eval_respuestas WHERE asignacion_id = a.id AND estado = 2) as conteo_real
        FROM eval_asignaciones a
        JOIN eval_pruebas p ON a.prueba_id = p.id
        WHERE a.estado = 1"; // Solo activas
foreach($db->query($sql) as $row) {
    echo "ID: {$row['id']} | TÍTULO: {$row['titulo']} | CONTEO SUCIO: {$row['conteo_sucio']} | CONTEO REAL: {$row['conteo_real']}\n";
}
?>
