<?php
include_once __DIR__ . '/../php/db.php';
$dbas = $db->query("SELECT id, num_dba, enunciado FROM ares_catalogo_aprendizajes WHERE area_id = 1 AND grado = 'Grado 10º'")->fetchAll(PDO::FETCH_ASSOC);
echo "🔬 DBA ENCONTRADOS PARA 10º (CIENCIAS NATURALES):\n";
foreach($dbas as $d) {
    echo "ID: {$d['id']} | DBA {$d['num_dba']}: {$d['enunciado']}\n\n";
}
