<?php
require_once 'php/db.php';

// 1. Obtener la asignación activa para la prueba 999
$stmt = $db->query("SELECT id FROM eval_asignaciones WHERE prueba_id = 999 LIMIT 1");
$active_asig_id = $stmt->fetchColumn();

if (!$active_asig_id) {
    echo "ERROR: No hay asignación activa para la prueba 999.\n";
    exit;
}

echo "ASIGNACIÓN ACTIVA DETECTADA: $active_asig_id\n";

// 2. Actualizar las respuestas para que apunten a la asignación activa
$stmt_upd = $db->prepare("UPDATE eval_respuestas SET asignacion_id = ? WHERE prueba_id = 999");
$stmt_upd->execute([$active_asig_id]);
$affected_rows = $stmt_upd->rowCount();

echo "SE ACTUALIZARON $affected_rows FILAS EN EVAL_RESPUESTAS AL ASIGNACION_ID $active_asig_id.\n";

// 3. Verificar los resultados
$stmt_check = $db->prepare("SELECT e.nombre, e.apellido, r.calificacion_automatica, r.calificacion_manual, r.asignacion_id 
                            FROM eval_respuestas r
                            JOIN estudiantes e ON r.estudiante_id = e.id
                            WHERE r.prueba_id = 999");
$stmt_check->execute();
$rows = $stmt_check->fetchAll(PDO::FETCH_ASSOC);
print_r($rows);
