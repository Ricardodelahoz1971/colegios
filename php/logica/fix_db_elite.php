<?php
declare(strict_types=1);
require_once '../db.php';
try {
    // 1. Añadir columna asignacion_id a eval_incidentes si no existe
    $db->exec("ALTER TABLE eval_incidentes ADD COLUMN asignacion_id INTEGER DEFAULT 0");
    echo "COLUMNA asignacion_id AÑADIDA A eval_incidentes.<br>";
} catch (Exception $e) {
    echo "INFO: " . $e->getMessage() . " (Probablemente ya existe)<br>";
}

try {
    // 2. Limpiar cualquier basura en la tabla de incidentes que no tenga estudiante_id
    $db->exec("DELETE FROM eval_incidentes WHERE estudiante_id = 0");
    echo "LIMPIEZA DE INCIDENTES COMPLETADA.<br>";
} catch (Exception $e) {
    echo "ERROR LIMPIEZA: " . $e->getMessage() . "<br>";
}

echo "<strong>SISTEMA SINCRONIZADO.</strong>";
?>

