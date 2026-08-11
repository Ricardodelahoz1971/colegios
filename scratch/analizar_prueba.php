<?php
require 'php/db.php';
try {
    // 1. Buscar asignaciones con entregas ya calificadas
    $sql = "SELECT a.id as asig_id, p.titulo, p.modalidad, c.nombre_curso
            FROM eval_asignaciones a
            JOIN eval_pruebas p ON a.prueba_id = p.id
            JOIN cursos c ON a.curso_id = c.id
            WHERE (SELECT COUNT(*) FROM eval_entregas WHERE asignacion_id = a.id AND estado = 2) > 0
            LIMIT 5";
    
    $stmt = $db->query($sql);
    $pruebas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "PRUEBAS DETECTADAS CON CALIFICACIONES:\n";
    foreach ($pruebas as $p) {
        echo "ID: {$p['asig_id']} | Título: {$p['titulo']} | Curso: {$p['nombre_curso']} | Modalidad: " . ($p['modalidad'] == 1 ? 'Digital' : 'Híbrida') . "\n";
        
        // Ver una entrega de esta prueba
        $stmt_e = $db->prepare("SELECT * FROM eval_entregas WHERE asignacion_id = ? AND estado = 2 LIMIT 1");
        $stmt_e->execute([$p['asig_id']]);
        $entrega = $stmt_e->fetch(PDO::FETCH_ASSOC);
        if ($entrega) {
            echo "--- Ejemplo Entrega ID: {$entrega['id']} | Alumno ID: {$entrega['estudiante_id']} | Nota: {$entrega['calificacion_manual']}\n";
        }
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
