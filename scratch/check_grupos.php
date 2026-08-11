<?php
require_once 'php/db.php';
try {
    $stmt = $db->query("SELECT id, nombre, tipo, referencia_id FROM grupos");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
