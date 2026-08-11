<?php
declare(strict_types=1);
require_once '../db.php';
$s = (function($db) { $s = $db->prepare("PRAGMA table_info(eval_respuestas)"); $s->execute(); return $s; })($db);
$cols = $s->fetchAll(PDO::FETCH_ASSOC);
if(empty($cols)) {
    echo "La tabla NO EXISTE.";
} else {
}

