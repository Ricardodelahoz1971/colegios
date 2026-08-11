<?php
require_once 'php/db.php';

echo "1. BUSCANDO ESTUDIANTES CON APELLIDO 'RODRIGUEZ' O SIMILAR:\n";
$stmt = $db->prepare("SELECT e.id, e.nombre, e.apellido, c.nombre_curso, e.curso_id 
                      FROM estudiantes e 
                      LEFT JOIN cursos c ON e.curso_id = c.id 
                      WHERE e.apellido LIKE '%Rodriguez%' OR e.nombre LIKE '%Rodriguez%'");
$stmt->execute();
$rodriguez = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($rodriguez);

echo "\n2. ALUNMOS QUE TIENEN NOTAS EN EVAL_RESPUESTAS:\n";
$stmt_n = $db->prepare("SELECT DISTINCT e.id, e.nombre, e.apellido, c.nombre_curso, r.calificacion_automatica, r.calificacion_manual, r.calificacion_recuperacion
                        FROM eval_respuestas r
                        JOIN estudiantes e ON r.estudiante_id = e.id
                        LEFT JOIN cursos c ON e.curso_id = c.id");
$stmt_n->execute();
$notas_eval = $stmt_n->fetchAll(PDO::FETCH_ASSOC);
print_r($notas_eval);

echo "\n3. ALUMNOS QUE TIENEN CALIFICACIONES EN CALIFICACIONES:\n";
$stmt_c = $db->prepare("SELECT DISTINCT e.id, e.nombre, e.apellido, c.nombre_curso
                        FROM ares_calificaciones_desglose ac
                        JOIN estudiantes e ON ac.estudiante_id = e.id
                        LEFT JOIN cursos c ON e.curso_id = c.id");
$stmt_c->execute();
$calif = $stmt_c->fetchAll(PDO::FETCH_ASSOC);
print_r($calif);
