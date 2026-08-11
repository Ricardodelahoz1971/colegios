<?php
declare(strict_types=1);
require_once '../db.php';
try {
    $db->exec("ALTER TABLE eval_respuestas ADD COLUMN calificacion_automatica REAL DEFAULT 0");
    $db->exec("ALTER TABLE eval_respuestas ADD COLUMN calificacion_manual REAL DEFAULT 0");
    $db->exec("ALTER TABLE eval_respuestas ADD COLUMN respuestas_json TEXT");
    echo "Columnas inyectadas exitosamente.";
} catch(Exception $e) {
    echo "ERROR: " . $e->getMessage();
}

