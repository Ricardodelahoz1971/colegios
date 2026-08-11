<?php
declare(strict_types=1);
require_once '../db.php';
try {
    $db->exec("ALTER TABLE eval_respuestas ADD COLUMN asignacion_id INTEGER DEFAULT 0");
    echo "Columna asignacion_id agregada exitosamente.";
} catch(Exception $e) {
    echo "ERROR: " . $e->getMessage();
}

