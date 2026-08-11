<?php
include 'php/db.php';
try {
    $stmt = $db->query("DESCRIBE recesos_escolares");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
