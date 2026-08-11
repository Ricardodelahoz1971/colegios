<?php
include_once __DIR__ . '/../php/db.php';

echo "🏛️ REPORTE DE ORFANDAD CURRICULAR ARES\n";
echo str_repeat("-", 50) . "\n";

$areas = $db->query("SELECT id, nombre_area FROM areas ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

$total = 0;
$huerfanas = 0;

foreach ($areas as $area) {
    $total++;
    $count = $db->prepare("SELECT COUNT(*) FROM ares_catalogo_aprendizajes WHERE area_id = ?");
    $count->execute([$area['id']]);
    $num = $count->fetchColumn();

    if ($num > 0) {
        echo "✅ [ID: {$area['id']}] {$area['nombre_area']} ($num DBA)\n";
    } else {
        $huerfanas++;
        echo "❌ [ID: {$area['id']}] {$area['nombre_area']} (HUÉRFANA)\n";
    }
}

echo str_repeat("-", 50) . "\n";
echo "📊 RESULTADO: $huerfanas de $total materias son HUÉRFANAS.\n";
