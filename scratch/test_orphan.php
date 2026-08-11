<?php
require_once __DIR__ . '/../php/db.php';

echo "--- Buscando la actividad huérfana (id=6) ---\n";
$stmt = $db->query("SELECT * FROM aula_recursos WHERE id = 6");
$recurso = $stmt->fetch(PDO::FETCH_ASSOC);
print_r($recurso);

echo "\n--- Buscando la especialidad ID = " . ($recurso['especialidad_id'] ?? 'N/A') . " ---\n";
$stmt2 = $db->prepare("SELECT * FROM especialidades WHERE id = ?");
$stmt2->execute([$recurso['especialidad_id'] ?? 0]);
$esp = $stmt2->fetch(PDO::FETCH_ASSOC);
if ($esp) {
    print_r($esp);
} else {
    echo "NO EXISTE LA ESPECIALIDAD. ES UNA REFERENCIA ROTA.\n";
}

echo "\n--- Otros recursos con referencias rotas ---\n";
$stmt3 = $db->query("
    SELECT r.id, r.titulo, r.especialidad_id 
    FROM aula_recursos r
    LEFT JOIN especialidades e ON r.especialidad_id = e.id
    WHERE e.id IS NULL
");
$rotos = $stmt3->fetchAll(PDO::FETCH_ASSOC);
print_r($rotos);
