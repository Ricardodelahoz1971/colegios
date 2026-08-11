<?php
require_once 'php/db.php';

echo "--- CURSOS Y CANTIDAD DE ESTUDIANTES ---\n";
$stmt = $db->query("SELECT c.id, c.nombre_curso, COUNT(e.id) as total_estudiantes 
                    FROM cursos c
                    LEFT JOIN estudiantes e ON c.id = e.curso_id
                    GROUP BY c.id");
print_r($stmt->fetchAll());
?>
