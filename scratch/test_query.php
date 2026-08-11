<?php
declare(strict_types=1);
$_SESSION['usuario_id'] = 1; // Forzar id de docente LJP para la prueba

require_once __DIR__ . '/../php/db.php';

$mi_id = 1;

try {
    $sql_asignaciones = "SELECT a.*, p.titulo as prueba_titulo, e.nombre_especialidad as materia_nombre, 
                        g.nombre_curso as curso_nombre
                        FROM eval_asignaciones a
                        JOIN eval_pruebas p ON a.prueba_id = p.id
                        JOIN especialidades e ON p.materia_id = e.id
                        JOIN cursos g ON a.curso_id = g.id
                        WHERE a.docente_id = ?
                        ORDER BY a.fecha_inicio DESC";
    $stmt = $db->prepare($sql_asignaciones);
    $stmt->execute([$mi_id]);
    $res = $stmt->fetchAll();
    echo "SUCCESS: " . count($res) . " asignaciones encontradas.\n";
    print_r($res);
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
