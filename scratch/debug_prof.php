<?php
declare(strict_types=1);
require_once '../db.php';

echo "=== USUARIOS (Docentes) ===\n";
$stmt_stmt = $db->prepare("SELECT id, nombre, rol_id FROM usuarios WHERE rol_id NOT IN (3)"); $stmt_stmt->execute(); $stmt = $stmt_stmt;
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n=== EVAL ASIGNACIONES ===\n";
$stmt_stmt2 = $db->prepare("SELECT a.id, a.docente_id, p.titulo, a.curso_id FROM eval_asignaciones a JOIN eval_pruebas p ON a.prueba_id = p.id"); $stmt_stmt2->execute(); $stmt2 = $stmt_stmt2;
print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));

echo "\n=== CURSOS ASIGNADOS ===\n";
$stmt_stmt3 = $db->prepare("SELECT id, nombre_curso, tutor_id FROM cursos"); $stmt_stmt3->execute(); $stmt3 = $stmt_stmt3;
print_r($stmt3->fetchAll(PDO::FETCH_ASSOC));

echo "\n=== TEST CONSULTA API PROFESOR (CON DOCENTE_ID = 2) ===\n";
$sql = "SELECT a.id, a.prueba_id, a.fecha_inicio, a.fecha_fin, p.titulo, e.nombre_especialidad as materia, c.nombre_curso as curso_nombre,
        (SELECT COUNT(*) FROM eval_respuestas WHERE asignacion_id = a.id) as total_entregas
        FROM eval_asignaciones a 
        JOIN eval_pruebas p ON a.prueba_id = p.id 
        JOIN especialidades e ON p.materia_id = e.id 
        JOIN cursos c ON a.curso_id = c.id
        WHERE a.docente_id = 2 
        ORDER BY a.id DESC";
$stmt4_prep = $db->prepare($sql);
$stmt4_prep->execute();
$stmt4 = $stmt4_prep;
print_r($stmt4->fetchAll(PDO::FETCH_ASSOC));

