<?php
require_once __DIR__ . '/../php/db.php';

echo "--- Eliminando recursos huérfanos ---\n";
$stmt = $db->query("
    DELETE FROM aula_recursos 
    WHERE especialidad_id NOT IN (SELECT id FROM especialidades)
");
$afectados = $stmt->rowCount();
echo "Registros eliminados: $afectados\n";

echo "--- Verificando... ---\n";
$stmt2 = $db->query("
    SELECT r.id, r.titulo, r.especialidad_id 
    FROM aula_recursos r
    LEFT JOIN especialidades e ON r.especialidad_id = e.id
    WHERE e.id IS NULL
");
$rotos = $stmt2->fetchAll(PDO::FETCH_ASSOC);
print_r($rotos);
