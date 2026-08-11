<?php
require_once 'php/db.php';
$asig_id = 18;
$stmt = $db->prepare("SELECT estado, COUNT(*) as total FROM eval_respuestas WHERE asignacion_id = ? GROUP BY estado");
$stmt->execute([$asig_id]);
echo "ANÁLISIS REAL ASIGNACIÓN #18:\n";
while($r = $stmt->fetch()) {
    echo "ESTADO: {$r['estado']} | TOTAL: {$r['total']}\n";
}
?>
