<?php
$db = new PDO('sqlite:c:\xampp\htdocs\sistema_escolar\php\database\usuarios.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

echo "=== LJP USER ===\n";
$stmt = $db->query("SELECT * FROM usuarios WHERE id = 3 OR usuario = 'LJP'");
print_r($stmt->fetchAll());
?>
