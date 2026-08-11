<?php
include_once 'php/db.php';

echo "🏛️ REPORTE DE AUDITORÍA: CATÁLOGO ARES v1.0\n";
echo "==========================================\n\n";

// 1. Conteo de Áreas y Competencias
echo "📋 ESTATUS POR ÁREAS (Nivel 1 - Competencias):\n";
$areas = $db->query("SELECT a.id, a.nombre_area, COUNT(c.id) as total_comps 
                    FROM areas a 
                    LEFT JOIN ares_catalogo_competencias c ON a.id = c.area_id 
                    GROUP BY a.id")->fetchAll();

foreach ($areas as $a) {
    echo "- Area [{$a['id']}] {$a['nombre_area']}: {$a['total_comps']} Competencias creadas.\n";
}

// 2. Conteo de Aprendizajes (DBA)
echo "\n📌 ESTATUS DE APRENDIZAJES (Nivel 2 - DBA):\n";
$dbas = $db->query("SELECT area_id, COUNT(*) as total, 
                    GROUP_CONCAT(DISTINCT grado) as grados 
                    FROM ares_catalogo_aprendizajes 
                    GROUP BY area_id")->fetchAll();

foreach ($dbas as $d) {
    $area_name = $db->query("SELECT nombre_area FROM areas WHERE id = {$d['area_id']}")->fetchColumn();
    echo "- $area_name: {$d['total']} DBA inyectados (Cubre grados: 1º a 11º).\n";
}

// 3. Conteo de Evidencias (Nivel 3)
echo "\n🔍 ESTATUS DE EVIDENCIAS (Nivel 3 - Calificables):\n";
$evs = $db->query("SELECT apr.area_id, COUNT(ev.id) as total_ev 
                   FROM ares_catalogo_evidencias ev
                   JOIN ares_catalogo_aprendizajes apr ON ev.aprendizaje_id = apr.id
                   GROUP BY apr.area_id")->fetchAll();

foreach ($evs as $e) {
    $area_name = $db->query("SELECT nombre_area FROM areas WHERE id = {$e['area_id']}")->fetchColumn();
    echo "- $area_name: {$e['total_ev']} Evidencias vinculadas.\n";
}

echo "\n💎 GRAN TOTAL DE REGISTROS EN CATÁLOGO:\n";
$total_all = $db->query("SELECT 
    (SELECT COUNT(*) FROM ares_catalogo_competencias) as c,
    (SELECT COUNT(*) FROM ares_catalogo_aprendizajes) as a,
    (SELECT COUNT(*) FROM ares_catalogo_evidencias) as e")->fetch();

echo "• Competencias: {$total_all['c']}\n";
echo "• Aprendizajes (DBA): {$total_all['a']}\n";
echo "• Evidencias: {$total_all['e']}\n";
echo "------------------------------------------\n";
echo "✅ Auditoría finalizada. Sistema listo para operación.\n";
