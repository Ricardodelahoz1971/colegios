<?php
require_once 'php/db.php';
header('Content-Type: text/plain');
try {
    $stmt = $db->query("DESCRIBE cronograma");
    while ($r = $stmt->fetch()) {
        print_r($r);
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
