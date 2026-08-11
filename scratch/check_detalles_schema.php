<?php
require_once 'php/db.php';
$stmt = $db->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='eval_respuestas_detalles'");
$row = $stmt->fetch();
echo "SCHEMA:\n";
print_r($row);
