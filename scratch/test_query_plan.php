<?php
include_once __DIR__ . '/../php/db.php';

echo "🧪 VERIFICACIÓN DEL PLAN DE CONSULTAS (EXPLAIN QUERY PLAN):\n";

$sql = "
SELECT COUNT(DISTINCT curr.id) 
FROM eval_pruebas p
JOIN eval_asignaciones a ON p.id = a.prueba_id
JOIN eval_pruebas_items pi ON p.id = pi.prueba_id
JOIN eval_preguntas q ON pi.pregunta_id = q.id
JOIN ares_catalogo_aprendizajes curr ON q.aprendizaje_id = curr.id
WHERE p.docente_id = 3 AND p.materia_id = 1 AND a.curso_id = 1
";

$explain = $db->query("EXPLAIN QUERY PLAN " . $sql)->fetchAll(PDO::FETCH_ASSOC);

foreach ($explain as $step) {
    echo "- " . $step['detail'] . "\n";
}
