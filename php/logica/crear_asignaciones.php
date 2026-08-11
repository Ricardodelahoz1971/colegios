<?php
declare(strict_types=1);
require_once '../db.php';
try {
    $sql = "CREATE TABLE IF NOT EXISTS eval_asignaciones (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        prueba_id INTEGER,
        curso_id INTEGER,
        docente_id INTEGER,
        fecha_inicio DATETIME,
        fecha_fin DATETIME,
        clave_acceso TEXT,
        estado INTEGER DEFAULT 0,
        intentos_permitidos INTEGER DEFAULT 1,
        mostrar_resultados INTEGER DEFAULT 0,
        fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    $db->exec($sql);
    echo "Tabla eval_asignaciones creada exitosamente.";
} catch(Exception $e) {
    echo "ERROR: " . $e->getMessage();
}

