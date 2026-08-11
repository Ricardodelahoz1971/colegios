<?php
require 'php/db.php';
$stmt = $db->query("PRAGMA table_info(eval_asignaciones)");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
