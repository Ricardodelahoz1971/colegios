<?php
declare(strict_types=1);
require_once __DIR__ . '/../php/db.php';

try {
    $stmt = $db->prepare("
        SELECT ca.curso_id, ca.especialidad_id, ca.docente_id, c.nombre_curso, e.nombre_especialidad, u.nombre as docente_nombre 
        FROM carga_academica ca
        JOIN cursos c ON ca.curso_id = c.id
        JOIN especialidades e ON ca.especialidad_id = e.id
        JOIN usuarios u ON ca.docente_id = u.id
        ORDER BY u.nombre ASC, e.nombre_especialidad ASC
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll();
    echo "TOTAL ROWS: " . count($rows) . "\n";
    foreach ($rows as $r) {
        echo "Docente: {$r['docente_nombre']} | Especialidad: {$r['nombre_especialidad']} | Curso: {$r['nombre_curso']} | DocenteID: {$r['docente_id']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
