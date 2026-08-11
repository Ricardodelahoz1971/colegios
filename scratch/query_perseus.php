<?php
$db = new PDO('sqlite:c:\xampp\htdocs\sistema_escolar\php\database\usuarios.db');
$stmt = $db->query("SELECT * FROM carga_academica WHERE docente_id = 1");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
