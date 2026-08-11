<?php
require_once 'php/db.php';
header('Content-Type: text/plain');
try {
    $stmt = $db->query("SHOW CREATE TABLE usuarios");
    print_r($stmt->fetch());
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
