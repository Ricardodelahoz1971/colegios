<?php
require_once 'php/db.php';

$stmt = $db->prepare("UPDATE eval_config_escala SET nota_minima = 1.00, nota_maxima = 5.00, nota_aprobacion = 3.00, rango_superior_min = 4.60, rango_alto_min = 4.00, rango_basico_min = 3.00 WHERE id = 1");
$stmt->execute();
echo "Reverted scale back to 1-5 (passing score 3.0) in database!\n";
