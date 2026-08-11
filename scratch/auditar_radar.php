<?php
require_once 'php/db.php';
$asig_id = 8; // Basado en su captura PARCIAL 1 (PF)
$stmt = $db->prepare("SELECT estado, COUNT(*) as total FROM eval_respuestas WHERE asignacion_id = ? GROUP BY estado");
$stmt->execute([$asig_id]);
echo "ESTADO DE RESPUESTAS PARA ASIGNACIÓN #$asig_id:\n";
while($r = $stmt->fetch()) {
    $status = ($r['estado'] == 1) ? "BORRADOR/ESCANEADO" : (($r['estado'] == 2) ? "PUBLICADO/CALIFICADO" : "OTRO");
    echo "- $status: " . $r['total'] . "\n";
}
?>
