<?php
declare(strict_types=1);
require_once '../db.php';
try {
    $db->exec("ALTER TABLE eval_asignaciones ADD COLUMN tipo_navegacion TEXT DEFAULT 'libre'");
    echo "Columna tipo_navegacion añadida exitosamente.";
} catch(Exception $e) {
    if (strpos($e->getMessage(), 'duplicate column name') !== false) {
        echo "La columna ya existe.";
    } else {
        echo "ERROR: " . $e->getMessage();
    }
}

