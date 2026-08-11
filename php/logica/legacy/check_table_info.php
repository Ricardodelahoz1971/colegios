<?php
declare(strict_types=1);
require_once __DIR__ . '/../db.php';

$stmt = $db->query("SELECT * FROM ares_actividades LIMIT 10");
$acts = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "--- SAMPLE ACTIVITIES ---\n";
foreach ($acts as $a) {
}

$stmt_n = $db->query("SELECT COUNT(*) FROM ares_actividades");
echo "Total activities: " . $stmt_n->fetchColumn() . "\n";

