<?php
declare(strict_types=1);
require_once __DIR__ . '/../php/db.php';

try {
    $stmt = $db->prepare("SELECT id, nombre_rol FROM roles");
    $stmt->execute();
    $roles = $stmt->fetchAll();
    echo "ROLES IN DATABASE:\n";
    print_r($roles);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
