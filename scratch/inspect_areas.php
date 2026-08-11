<?php
require_once __DIR__ . '/../php/db.php';
$stmt = $db->query("SELECT * FROM areas");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "=== AREAS IN DATABASE ===\n";
print_r($rows);
