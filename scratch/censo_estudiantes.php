<?php
require_once __DIR__ . '/../php/db.php';

// 1. Total count
$total_est = $db->query("SELECT COUNT(*) FROM estudiantes")->fetchColumn();

// 2. Count by course
$stmt_cursos = $db->query("
    SELECT c.id, c.nombre_curso, COUNT(e.id) as total_estudiantes
    FROM cursos c
    LEFT JOIN estudiantes e ON c.id = e.curso_id
    GROUP BY c.id, c.nombre_curso
    ORDER BY c.nombre_curso ASC
");
$cursos_censo = $stmt_cursos->fetchAll(PDO::FETCH_ASSOC);

// 3. Preview first 15 students
$stmt_est = $db->query("
    SELECT e.id, e.nombre, e.apellido, e.identificacion, c.nombre_curso
    FROM estudiantes e
    JOIN cursos c ON e.curso_id = c.id
    ORDER BY c.nombre_curso ASC, e.nombre ASC
    LIMIT 20
");
$estudiantes_preview = $stmt_est->fetchAll(PDO::FETCH_ASSOC);

echo "=== CENSO GLOBAL DE ESTUDIANTES ===\n";
echo "Total Estudiantes Registrados: $total_est\n\n";

echo "=== DISTRIBUCIÓN POR CURSOS ===\n";
foreach ($cursos_censo as $cc) {
    echo "- Curso [ID: {$cc['id']}]: {$cc['nombre_curso']} ({$cc['total_estudiantes']} estudiantes)\n";
}
echo "\n";

echo "=== VISTA PREVIA DE ALUMNOS (PRIMEROS 20) ===\n";
foreach ($estudiantes_preview as $index => $ep) {
    $num = $index + 1;
    echo "$num. [ID: {$ep['id']}] {$ep['nombre']} {$ep['apellido']} (ID_Doc: {$ep['identificacion']}) - Curso: {$ep['nombre_curso']}\n";
}
?>
