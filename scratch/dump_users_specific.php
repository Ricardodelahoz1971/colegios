<?php
require_once __DIR__ . '/../php/db.php';
$res = $db->query("SELECT * FROM usuarios WHERE id IN (2, 9)");
print_r($res->fetchAll(PDO::FETCH_ASSOC));
