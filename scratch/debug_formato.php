<?php
require_once __DIR__ . '/../php/db.php';

echo "=== estudiantes ===\n";
$cols = $db->query('DESCRIBE estudiantes')->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo $c['Field'] . " (" . $c['Type'] . ")\n";

echo "\n=== estudiantes_datos_adicionales ===\n";
$cols = $db->query('DESCRIBE estudiantes_datos_adicionales')->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo $c['Field'] . " (" . $c['Type'] . ")\n";

echo "\n=== ajustes_estetica (claves) ===\n";
$keys = $db->query('SELECT clave FROM ajustes_estetica')->fetchAll(PDO::FETCH_COLUMN);
foreach ($keys as $k) echo $k . "\n";

echo "\n=== cursos (columnas) ===\n";
$cols = $db->query('DESCRIBE cursos')->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo $c['Field'] . " (" . $c['Type'] . ")\n";
