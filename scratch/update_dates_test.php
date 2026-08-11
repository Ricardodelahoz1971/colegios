<?php
require 'php/db.php';
try {
    $rows = $db->exec("UPDATE estudiantes_datos_adicionales SET fecha_registro = DATE_SUB(CURDATE(), INTERVAL 3 DAY)");
    echo "Actualizados $rows registros de estudiantes a 3 días antes de la fecha actual.\n";
    
    // Consultar para verificar
    $stmt = $db->query("SELECT e.nombre, e.apellido, ed.fecha_registro 
                        FROM estudiantes e 
                        JOIN estudiantes_datos_adicionales ed ON e.id = ed.estudiante_id");
    while($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Estudiante: " . $r['nombre'] . " " . $r['apellido'] . " | Fecha Registro: " . $r['fecha_registro'] . "\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
