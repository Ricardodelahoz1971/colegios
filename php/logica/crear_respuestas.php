<?php
declare(strict_types=1);
require_once '../db.php';
try {
    $sql = "CREATE TABLE IF NOT EXISTS eval_respuestas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        asignacion_id INTEGER NOT NULL,
        estudiante_id INTEGER NOT NULL,
        respuestas_json TEXT,
        estado TEXT DEFAULT 'enviado',
        fecha_envio DATETIME DEFAULT CURRENT_TIMESTAMP,
        calificacion_automatica REAL DEFAULT 0,
        calificacion_manual REAL DEFAULT 0,
        retroalimentacion TEXT
    )";
    $db->exec($sql);
    echo "Tabla eval_respuestas creada exitosamente.";
} catch(Exception $e) {
    echo "ERROR: " . $e->getMessage();
}

