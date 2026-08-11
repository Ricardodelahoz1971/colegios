<?php
declare(strict_types=1);
require_once __DIR__ . '/../php/db.php';

try {
    echo "--- ALL TEST PRUEBAS ---\n";
    $pruebas = $db->query("SELECT p.id, p.titulo, p.docente_id, p.materia_id, u.nombre as docente_nombre FROM eval_pruebas p JOIN usuarios u ON p.docente_id = u.id")->fetchAll(PDO::FETCH_ASSOC);
    print_r($pruebas);

    echo "--- ALL TEST ASIGNACIONES ---\n";
    $asig = $db->query("SELECT a.id, a.prueba_id, a.curso_id, c.nombre_curso FROM eval_asignaciones a JOIN cursos c ON a.curso_id = c.id")->fetchAll(PDO::FETCH_ASSOC);
    print_r($asig);

    echo "--- ALL TEST ITEMS ---\n";
    $items = $db->query("SELECT * FROM eval_pruebas_items")->fetchAll(PDO::FETCH_ASSOC);
    print_r($items);

    echo "--- ALL PREGUNTAS ---\n";
    $preg = $db->query("SELECT id, aprendizaje_id, evidencia_id, titulo FROM eval_preguntas")->fetchAll(PDO::FETCH_ASSOC);
    print_r($preg);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
