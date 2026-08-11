<?php
require_once __DIR__ . '/../php/db.php';
$stmt = $db->query('DESCRIBE estudiantes');
$estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt2 = $db->query('SHOW TABLES');
$tables = $stmt2->fetchAll(PDO::FETCH_COLUMN);

echo "--- TABLA ESTUDIANTES ---\n";
print_r($estudiantes);
echo "\n--- TODAS LAS TABLAS ---\n";
print_r($tables);
