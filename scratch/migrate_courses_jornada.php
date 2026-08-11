<?php
require 'php/db.php';
try {
    $db->exec("ALTER TABLE cursos ADD COLUMN jornada VARCHAR(50) DEFAULT 'Mañana';");
    echo "Columna jornada agregada a la tabla cursos.\n";
} catch (PDOException $e) {
    echo "Aviso: " . $e->getMessage() . "\n";
}
