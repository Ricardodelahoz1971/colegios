<?php
declare(strict_types=1);
require_once '../db.php';
$stmt_s = $db->prepare("SELECT metadata_json FROM eval_preguntas WHERE tipo_id = 1 LIMIT 1"); $stmt_s->execute(); $s = $stmt_s;

