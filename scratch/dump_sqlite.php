<?php
$dbPath = __DIR__ . '/../php/database/usuarios.db';
$db = new PDO("sqlite:" . $dbPath);
$stmt = $db->query("SELECT sql FROM sqlite_master WHERE type='table'");
$schema = "";
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $schema .= $row['sql'] . ";\n\n";
}
file_put_contents(__DIR__ . '/schema.sql', $schema);
echo "Dumped to scratch/schema.sql";
?>
