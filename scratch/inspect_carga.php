<?php
$db = new PDO('sqlite:c:\xampp\htdocs\sistema_escolar\php\database\usuarios.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

echo "=== TYPES IN eval_tipos ===\n";
$stmt = $db->query("SELECT * FROM eval_tipos");
print_r($stmt->fetchAll());
?>
