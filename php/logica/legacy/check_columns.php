<?php
declare(strict_types=1);
require_once '../db.php';
$s = (function($db) { $s = $db->prepare("PRAGMA table_info(eval_incidentes)"); $s->execute(); return $s; })($db);
?>

