<?php
declare(strict_types=1);
require_once '../db.php';

// 1. Mostrar las asignaciones
echo "=== ASIGNACIONES ===\n";
$stmt_stmt = $db->prepare("SELECT a.id, a.docente_id, p.titulo, c.nombre_curso FROM eval_asignaciones a JOIN eval_pruebas p ON a.prueba_id = p.id JOIN cursos c ON a.curso_id = c.id"); $stmt_stmt->execute(); $stmt = $stmt_stmt;
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

// 2. Mostrar las entregas
echo "\n=== RESPUESTAS ENTREGADAS ===\n";
$stmt_stmt2 = $db->prepare("SELECT id, asignacion_id, estudiante_id, estado, calificacion_automatica FROM eval_respuestas"); $stmt_stmt2->execute(); $stmt2 = $stmt_stmt2;
print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));

