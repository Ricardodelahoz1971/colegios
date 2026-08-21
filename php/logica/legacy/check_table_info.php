<?php
declare(strict_types=1);
require_once __DIR__ . '/../db.php';

$stmt = $db->prepare("SELECT * FROM ares_actividades LIMIT 10"); $stmt->execute();
$acts = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "--- SAMPLE ACTIVITIES ---\n";
foreach ($acts as $a) {
}

$stmt_n = $db->prepare("SELECT COUNT(*) FROM ares_actividades"); $stmt_n->execute();
echo "Total activities: " . $stmt_n->fetchColumn() . "\n";

