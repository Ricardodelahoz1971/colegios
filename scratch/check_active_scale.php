<?php
require_once 'php/db.php';

$stmt = $db->query("SELECT * FROM eval_config_escala WHERE activo = 1 LIMIT 1");
$escala = $stmt->fetch(PDO::FETCH_ASSOC);
echo "ACTIVE SCALE:\n";
print_r($escala);
