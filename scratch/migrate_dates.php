<?php
require 'php/db.php';
try {
    // 1. Agregar columna
    $db->exec("ALTER TABLE estudiantes_datos_adicionales ADD COLUMN fecha_registro DATE NULL;");
    echo "Columna fecha_registro agregada exitosamente.\n";
} catch (PDOException $e) {
    // Si ya existe, ignoramos el error
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "La columna fecha_registro ya existe.\n";
    } else {
        echo "Error al agregar columna: " . $e->getMessage() . "\n";
    }
}

try {
    // 2. Poblar registros existentes con CURDATE()
    $rows = $db->exec("UPDATE estudiantes_datos_adicionales SET fecha_registro = CURDATE() WHERE fecha_registro IS NULL;");
    echo "Poblados $rows registros existentes con la fecha actual.\n";
} catch (PDOException $e) {
    echo "Error al poblar registros: " . $e->getMessage() . "\n";
}
