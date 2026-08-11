<?php
$db = new PDO('sqlite:c:\xampp\htdocs\sistema_escolar\php\database\usuarios.db');
$stmt = $db->query("SELECT id, usuario, rol_id, nombre FROM usuarios");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
