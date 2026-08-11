<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
guardia_sesion();
    session_write_close();
proteccion_extrema();
require_once '../db.php';

// Parámetros de reinicio (Jorge De La Hoz en la Asignación 1)
$estudiante_id = 22; 
$asignacion_id = 1;

try {
    // 1. Borrar respuestas previas
    $stmt1 = $db->prepare("DELETE FROM eval_respuestas WHERE estudiante_id = ? AND asignacion_id = ?");
    $stmt1->execute([$estudiante_id, $asignacion_id]);
    
    // 2. Borrar incidentes previos
    $stmt2 = $db->prepare("DELETE FROM eval_incidentes WHERE estudiante_id = ? AND prueba_id = (SELECT prueba_id FROM eval_asignaciones WHERE id = ?)");
    $stmt2->execute([$estudiante_id, $asignacion_id]);

    echo "Protocolo de Reinicio Ares Completado para el Estudiante ID $estudiante_id en la Asignación $asignacion_id.\n";
    echo "Ya puede volver a entrar como estudiante y presentar la prueba.";
} catch (Exception $e) {
    echo "Error en el reinicio: " . $e->getMessage();
}

