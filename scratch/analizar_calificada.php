<?php
require 'php/db.php';
try {
    $sql = "SELECT a.id as asig_id, p.titulo, r.id as resp_id, r.estudiante_id, r.calificacion_automatica, r.calificacion_manual, r.estado,
                   u.nombre as estudiante_nombre, p.modalidad
            FROM eval_asignaciones a
            JOIN eval_pruebas p ON a.prueba_id = p.id
            JOIN eval_respuestas r ON a.id = r.asignacion_id
            JOIN usuarios u ON u.estudiante_id = r.estudiante_id
            WHERE r.estado = 2 OR r.calificacion_automatica IS NOT NULL
            LIMIT 5";
    
    $stmt = $db->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "ANÁLISIS DE PRUEBAS CALIFICADAS POR SISTEMA:\n";
    foreach ($data as $d) {
        echo "Asignación: {$d['asig_id']} | Título: {$d['titulo']} | Alumno: {$d['estudiante_nombre']} | Nota Auto: {$d['calificacion_automatica']} | Nota Manual: {$d['calificacion_manual']}\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
