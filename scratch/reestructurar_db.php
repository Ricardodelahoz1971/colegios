<?php
require 'php/db.php';
try {
    // 1. Purga de datos
    $db->exec("DELETE FROM eval_respuestas");
    $db->exec("DELETE FROM eval_incidentes");
    echo "Paso 1: Datos de calificaciones purgados.\n";

    // 2. Evolución de Esquema
    // Verificamos si la columna existe primero
    $check = $db->query("PRAGMA table_info(eval_pruebas)");
    $cols = $check->fetchAll(PDO::FETCH_COLUMN, 1);
    
    if (!in_array('modalidad', $cols)) {
        $db->exec("ALTER TABLE eval_pruebas ADD COLUMN modalidad INTEGER DEFAULT 1");
        echo "Paso 2: Columna 'modalidad' añadida a eval_pruebas.\n";
    } else {
        echo "Paso 2: La columna 'modalidad' ya existe.\n";
    }

    echo "OPERACIÓN COMPLETADA CON ÉXITO.";

} catch (Exception $e) {
    echo "ERROR CRÍTICO: " . $e->getMessage();
}
