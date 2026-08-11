<?php
declare(strict_types=1);
require_once '../db.php';
try {
    $sql = "CREATE TABLE IF NOT EXISTS eval_incidentes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        asignacion_id INTEGER NOT NULL,
        estudiante_id INTEGER NOT NULL,
        tipo_incidente TEXT NOT NULL,
        detalles TEXT,
        nivel_gravedad INTEGER DEFAULT 1,
        fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    $db->exec($sql);
    echo "El Escribano (eval_incidentes) ha sido creado exitosamente.";
} catch(Exception $e) {
    echo "ERROR: " . $e->getMessage();
}

