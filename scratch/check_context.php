<?php
include 'php/db.php';
echo "Contenido de la tabla areas:\n";
$res = $db->query("SELECT * FROM areas")->fetchAll();
foreach ($res as $r) echo "- ID: {$r['id']}, Nombre: {$r['nombre_area']}\n";

echo "\nEstructura de la tabla cursos:\n";
$cols = $db->query("PRAGMA table_info(cursos)")->fetchAll();
foreach ($cols as $c) echo "- {$c['name']} ({$c['type']})\n";
