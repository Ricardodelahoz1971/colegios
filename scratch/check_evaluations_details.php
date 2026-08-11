<?php
declare(strict_types=1);
require_once __DIR__ . '/../php/db.php';

try {
    $tables = ['eval_pruebas', 'eval_asignaciones', 'eval_pruebas_items', 'eval_preguntas', 'view_perseus_cobertura', 'carga_academica'];
    foreach ($tables as $t) {
        $stmt = $db->query("SELECT COUNT(*) FROM $t");
        echo "Table: $t | Count: " . $stmt->fetchColumn() . "\n";
    }
    
    echo "\n--- eval_pruebas content ---\n";
    $pruebas = $db->query("SELECT * FROM eval_pruebas")->fetchAll(PDO::FETCH_ASSOC);
    print_r($pruebas);

    echo "\n--- eval_asignaciones content ---\n";
    $asignaciones = $db->query("SELECT * FROM eval_asignaciones")->fetchAll(PDO::FETCH_ASSOC);
    print_r($asignaciones);

    echo "\n--- eval_preguntas content ---\n";
    $preguntas = $db->query("SELECT * FROM eval_preguntas")->fetchAll(PDO::FETCH_ASSOC);
    print_r($preguntas);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
