<?php
// scratch/auditoria_final_ares.php
include_once __DIR__ . '/../php/db.php';

echo "🏛️ REPORTE DE AUDITORÍA INTEGRAL ARES - " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 60) . "\n\n";

// 1. Resumen por Área
echo "📊 1. RESUMEN DE CARGA POR ÁREA:\n";
$stmt = $db->query("SELECT a.id, a.nombre_area, 
    (SELECT COUNT(*) FROM ares_catalogo_aprendizajes WHERE area_id = a.id) as total_dba,
    (SELECT COUNT(*) FROM ares_catalogo_evidencias e JOIN ares_catalogo_aprendizajes ap ON e.aprendizaje_id = ap.id WHERE ap.area_id = a.id) as total_evidencias
    FROM areas a");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    printf("[%d] %-40s | DBA: %3d | EV: %4d\n", $row['id'], substr($row['nombre_area'],0,40), $row['total_dba'], $row['total_evidencias']);
}

// 2. Control de Calidad (Huérfanos y Nulos)
echo "\n🔍 2. CONTROL DE CALIDAD:\n";
$h_dba = $db->query("SELECT COUNT(*) FROM ares_catalogo_aprendizajes WHERE competencia_id IS NULL OR area_id IS NULL")->fetchColumn();
$h_ev = $db->query("SELECT COUNT(*) FROM ares_catalogo_evidencias WHERE aprendizaje_id NOT IN (SELECT id FROM ares_catalogo_aprendizajes)")->fetchColumn();
$d_dba = $db->query("SELECT COUNT(*) FROM (SELECT enunciado FROM ares_catalogo_aprendizajes GROUP BY area_id, grado, num_dba, enunciado HAVING COUNT(*) > 1)")->fetchColumn();

echo "• DBA sin vinculación (Crítico): " . ($h_dba > 0 ? "❌ $h_dba" : "✅ 0") . "\n";
echo "• Evidencias huérfanas: " . ($h_ev > 0 ? "❌ $h_ev" : "✅ 0") . "\n";
echo "• Posibles duplicados: " . ($d_dba > 0 ? "⚠️ $d_dba" : "✅ 0") . "\n";

// 3. Cobertura de Grados
echo "\n🎓 3. COBERTURA DE GRADOS:\n";
$grados = $db->query("SELECT DISTINCT grado FROM ares_catalogo_aprendizajes ORDER BY grado")->fetchAll(PDO::FETCH_COLUMN);
echo "Grados cubiertos: " . implode(", ", $grados) . "\n";

echo "\n" . str_repeat("=", 60) . "\n";
echo "✅ AUDITORÍA DE DATOS FINALIZADA CON ÉXITO.\n";
