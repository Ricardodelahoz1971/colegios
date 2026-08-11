<?php
declare(strict_types=1);
require_once __DIR__ . '/../php/db.php';

try {
    $tables = ['eval_pruebas_items', 'eval_preguntas', 'eval_asignaciones'];
    foreach ($tables as $t) {
        $stmt = $db->prepare("PRAGMA index_list($t)");
        $stmt->execute();
        echo "INDEXES ON TABLE '$t':\n";
        print_r($stmt->fetchAll());
        echo "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
