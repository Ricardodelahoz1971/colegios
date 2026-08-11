<?php
require 'php/db.php';
try {
    $db->exec("ALTER TABLE estudiantes_datos_adicionales ADD COLUMN padre_documento_expedicion VARCHAR(100) NULL;");
    echo "Columna padre_documento_expedicion agregada.\n";
} catch (PDOException $e) {
    echo "Aviso padre: " . $e->getMessage() . "\n";
}

try {
    $db->exec("ALTER TABLE estudiantes_datos_adicionales ADD COLUMN madre_documento_expedicion VARCHAR(100) NULL;");
    echo "Columna madre_documento_expedicion agregada.\n";
} catch (PDOException $e) {
    echo "Aviso madre: " . $e->getMessage() . "\n";
}
