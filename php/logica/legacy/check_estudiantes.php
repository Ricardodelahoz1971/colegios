<?php
declare(strict_types=1);
require_once '../db.php';
$s = (function($db) { $s = $db->prepare("PRAGMA table_info(estudiantes)"); $s->execute(); return $s; })($db);
while($r = $s->fetch(PDO::FETCH_ASSOC)) {
}

