<?php
require_once 'php/db.php';

$stmt = $db->query("SELECT * FROM eval_respuestas_detalles WHERE respuesta_id = 13");
print_r($stmt->fetchAll());
