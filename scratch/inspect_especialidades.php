<?php
include_once __DIR__ . '/../php/db.php';
$cols = $db->query("PRAGMA table_info(especialidades)")->fetchAll(PDO::FETCH_ASSOC);
echo "📋 COLUMNAS DE especialidades:\n";
foreach($cols as $c) echo "- {$c['name']} ({$c['type']})\n";

$data = $db->query("SELECT * FROM especialidades")->fetchAll(PDO::FETCH_ASSOC);
echo "\n📊 DATOS EN especialidades:\n";
print_r($data);
