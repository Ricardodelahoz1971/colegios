<?php
declare(strict_types=1);
require_once '../db.php';
$s = (function($db) { $s = $db->prepare("PRAGMA table_info(ajustes_estetica)"); $s->execute(); return $s; })($db);

