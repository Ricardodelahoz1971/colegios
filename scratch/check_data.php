<?php
require_once 'php/db.php';
$total = $db->query("SELECT COUNT(*) FROM estudiantes WHERE curso_id = 3")->fetchColumn();
echo "ESTUDIANTES EN 3A: $total\n";
?>
