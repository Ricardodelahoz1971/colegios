<?php
declare(strict_types=1);
require_once __DIR__ . '/../php/db.php';

try {
    echo "--- RECORDS IN view_perseus_cobertura ---\n";
    $rows = $db->query("SELECT * FROM view_perseus_cobertura")->fetchAll(PDO::FETCH_ASSOC);
    echo "Total rows in view: " . count($rows) . "\n";
    foreach ($rows as $r) {
        print_r($r);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
