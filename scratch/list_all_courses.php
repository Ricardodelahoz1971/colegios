<?php
require_once 'php/db.php';
try {
    $stmt = $db->query("SELECT id, nombre_curso FROM cursos ORDER BY nombre_curso ASC");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
