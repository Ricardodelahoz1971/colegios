<?php
require_once __DIR__ . '/../php/db.php';
try {
    // Buscar preguntas con materias inexistentes
    $stmt = $db->query("SELECT id, titulo, materia_id FROM eval_preguntas WHERE materia_id NOT IN (SELECT id FROM especialidades)");
    $orphans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($orphans) . " questions with invalid materia_id:\n";
    print_r($orphans);
    
    if (count($orphans) > 0) {
        $update = $db->prepare("UPDATE eval_preguntas SET materia_id = 1 WHERE id = ?");
        foreach ($orphans as $q) {
            $update->execute([$q['id']]);
            echo "Updated question {$q['id']} ({$q['titulo']}) to materia_id = 1 (Matemáticas).\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
