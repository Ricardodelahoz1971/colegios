<?php
declare(strict_types=1);
$db = new PDO('sqlite:../php/database/usuarios.db');

echo "--- TABLES ---\n";
$tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
print_r($tables);

echo "\n--- ESPECIALIDADES SCHEMA ---\n";
$schema = $db->query("PRAGMA table_info(especialidades)")->fetchAll(PDO::FETCH_ASSOC);
print_r($schema);

if (in_array('areas', $tables)) {
    echo "\n--- AREAS SCHEMA ---\n";
    $areas_schema = $db->query("PRAGMA table_info(areas)")->fetchAll(PDO::FETCH_ASSOC);
    print_r($areas_schema);
} else {
    echo "\n--- AREAS TABLE DOES NOT EXIST ---\n";
}
