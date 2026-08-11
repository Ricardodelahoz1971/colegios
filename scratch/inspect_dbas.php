<?php
$db = new PDO('sqlite:c:\xampp\htdocs\sistema_escolar\php\database\usuarios.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

echo "=== MATEMATICAS DBAS ===\n";
$stmt = $db->query("SELECT id, grado, num_dba, enunciado FROM ares_catalogo_aprendizajes WHERE area_id = 3 ORDER BY CAST(grado AS INTEGER) ASC, num_dba ASC LIMIT 10");
print_r($stmt->fetchAll());
?>
