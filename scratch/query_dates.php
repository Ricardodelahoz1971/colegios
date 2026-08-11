<?php
require_once 'php/db.php';

echo "--- RANGO DE FECHAS EN ARES_ACTIVIDADES ---\n";
$stmt = $db->query("SELECT MIN(fecha_registro) as min_f, MAX(fecha_registro) as max_f, COUNT(*) as cant FROM ares_actividades");
print_r($stmt->fetch());

echo "\n--- RANGO DE FECHAS EN EVAL_ASIGNACIONES ---\n";
$stmt2 = $db->query("SELECT MIN(fecha_inicio) as min_f, MAX(fecha_inicio) as max_f, COUNT(*) as cant FROM eval_asignaciones");
print_r($stmt2->fetch());

echo "\n--- RANGO DE FECHAS EN CALIFICACIONES DESGLOSE ---\n";
$stmt3 = $db->query("SELECT MIN(fecha_registro) as min_f, MAX(fecha_registro) as max_f, COUNT(*) as cant 
                    FROM ares_calificaciones_desglose cd
                    JOIN ares_actividades a ON cd.actividad_id = a.id");
print_r($stmt3->fetch());
?>
