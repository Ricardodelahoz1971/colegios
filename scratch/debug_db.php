<?php
require_once 'php/db.php';
$sql = "SELECT id, prueba_id, docente_id, curso_id, estado FROM eval_asignaciones";
echo "CONTENIDO DE EVAL_ASIGNACIONES:\n";
foreach($db->query($sql) as $row) {
    echo "ID: {$row['id']} | PRUEBA: {$row['prueba_id']} | DOCENTE: {$row['docente_id']} | CURSO: {$row['curso_id']} | ESTADO: {$row['estado']}\n";
}
?>
