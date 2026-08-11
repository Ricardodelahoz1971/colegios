<?php
require_once 'php/db.php';

$ids = [1, 3, 6, 7, 13, 14];
$in = implode(',', $ids);
$stmt = $db->query("SELECT id, nombre, apellido FROM estudiantes WHERE id IN ($in)");
print_r($stmt->fetchAll());
