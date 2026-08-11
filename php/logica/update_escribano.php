<?php
declare(strict_types=1);
require_once '../db.php';
try {
    $db->exec("DROP TABLE IF EXISTS eval_incidentes");
    $sql = "CREATE TABLE eval_incidentes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        asignacion_id INTEGER NOT NULL,
        estudiante_id INTEGER NOT NULL,
        tipo_incidente TEXT NOT NULL,
        detalles TEXT,
        nivel_gravedad INTEGER DEFAULT 1,
        fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    $db->exec($sql);
    echo "Tabla eval_incidentes actualizada correctamente al nuevo esquema.";
} catch(Exception $e) {
    echo "ERROR: " . $e->getMessage();
}

