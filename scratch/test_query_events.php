<?php
require_once 'php/db.php';
header('Content-Type: text/plain');
try {
    $stmt = $db->query("SELECT * FROM cronograma");
    $rows = $stmt->fetchAll();
    echo "COUNT: " . count($rows) . "\n";
    print_r($rows);
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
