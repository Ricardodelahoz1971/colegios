<?php
include_once __DIR__ . '/../php/db.php';
$cols = $db->query("PRAGMA table_info(eval_preguntas)")->fetchAll(PDO::FETCH_ASSOC);
echo "📋 COLUMNAS DE eval_preguntas:\n";
foreach($cols as $c) echo "- {$c['name']} ({$c['type']})\n";
