<?php
require_once __DIR__ . '/../php/db.php';

try {
    $db->beginTransaction();
    
    // Step 1: Update to temporary value
    $stmt1 = $db->prepare("UPDATE areas SET nombre_area = 'Matemáticas y ciencias_temp' WHERE id = 2");
    $stmt1->execute();
    
    // Step 2: Update to final value with new casing
    $stmt2 = $db->prepare("UPDATE areas SET nombre_area = 'Matemáticas y ciencias' WHERE id = 2");
    $stmt2->execute();
    
    $db->commit();
    echo "Two-step update succeeded!\n";
} catch (Exception $e) {
    $db->rollBack();
    echo "Two-step update failed: " . $e->getMessage() . "\n";
}
