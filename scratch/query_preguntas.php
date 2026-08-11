<?php
$db = new PDO('sqlite:c:\xampp\htdocs\sistema_escolar\php\database\usuarios.db');
$stmt = $db->query("SELECT name, sql FROM sqlite_master WHERE type='table'");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    if (strpos($row['name'], 'pregunta') !== false || strpos($row['name'], 'banco') !== false) {
        echo "TABLE: " . $row['name'] . "\n";
        echo $row['sql'] . "\n\n";
    }
}
?>
