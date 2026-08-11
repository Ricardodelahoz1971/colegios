<?php
require_once 'php/db.php';
try {
    $stmt = $db->query("SELECT id, nombre_rol FROM roles ORDER BY id ASC");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
