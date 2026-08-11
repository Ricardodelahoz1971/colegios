<?php
require 'php/db.php';
try {
    $sql = "SELECT r.*, p.titulo as prueba_titulo, u.nombre as estudiante_nombre
            FROM eval_respuestas r
            JOIN eval_asignaciones a ON r.asignacion_id = a.id
            JOIN eval_pruebas p ON a.prueba_id = p.id
            JOIN usuarios u ON r.estudiante_id = u.estudiante_id
            LIMIT 10";
    
    $stmt = $db->query($sql);
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
