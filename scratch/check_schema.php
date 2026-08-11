<?php
require_once __DIR__ . '/../php/db.php';
$stmt = $db->query("SELECT * FROM roles WHERE id = 11");
echo "--- ROLE WITH ID 11 ---\n";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
