<?php
require_once 'php/db.php';
$stmt = $db->query("SELECT * FROM eval_respuestas WHERE prueba_id = 999");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "ALL EVAL_RESPUESTAS FOR PRUEBA_ID = 999:\n";
print_r($rows);
