<?php
include_once __DIR__ . '/../php/db.php';
$esp = $db->query("SELECT id, nombre_especialidad FROM especialidades")->fetchAll(PDO::FETCH_ASSOC);
echo "📋 ESPECIALIDADES (MATERIAS):\n";
foreach($esp as $e) echo "- ID: {$e['id']} | Nombre: {$e['nombre_especialidad']}\n";

$areas = $db->query("SELECT id, nombre_area FROM areas")->fetchAll(PDO::FETCH_ASSOC);
echo "\n🏛️ ÁREAS MEN:\n";
foreach($areas as $a) echo "- ID: {$a['id']} | Nombre: {$a['nombre_area']}\n";
