<?php
declare(strict_types=1);
require_once 'db.php';
$stmt = (function($db) { $s = $db->prepare("SHOW TABLES LIKE \'eval_%\'"); $s->execute(); return $s; })($db);
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo json_encode($tables);

