<?php
require_once 'php/db.php';
header('Content-Type: text/plain');
try {
    $stmt = $db->query("SELECT * FROM log_auditoria_seguridad ORDER BY id DESC LIMIT 5");
    $rows = $stmt->fetchAll();
    print_r($rows);
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
