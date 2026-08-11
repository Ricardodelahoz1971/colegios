<?php
include_once 'php/db.php';

// Análisis de Integridad y Distribución
$stats = [];

// 1. Cobertura por Grados
$stmt = $db->query("SELECT area_id, grado, COUNT(*) as dba_count FROM ares_catalogo_aprendizajes GROUP BY area_id, grado ORDER BY area_id, CAST(grado AS INTEGER)");
while($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $stats['cobertura'][$r['area_id']][$r['grado']] = $r['dba_count'];
}

// 2. Relación Evidencia/DBA (Promedio de reactivos por aprendizaje)
$stmt = $db->query("SELECT area_id, CAST(COUNT(ev.id) AS FLOAT) / COUNT(DISTINCT apr.id) as ratio 
                    FROM ares_catalogo_aprendizajes apr
                    JOIN ares_catalogo_evidencias ev ON ev.aprendizaje_id = apr.id
                    GROUP BY area_id");
while($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $stats['ratios'][$r['area_id']] = round($r['ratio'], 2);
}

// 3. Huérfanos (Control de Calidad)
$huerfanos = $db->query("SELECT COUNT(*) FROM ares_catalogo_evidencias WHERE aprendizaje_id NOT IN (SELECT id FROM ares_catalogo_aprendizajes)")->fetchColumn();

// 4. Totales
$totales = $db->query("SELECT 
    (SELECT COUNT(*) FROM ares_catalogo_competencias) as c,
    (SELECT COUNT(*) FROM ares_catalogo_aprendizajes) as a,
    (SELECT COUNT(*) FROM ares_catalogo_evidencias) as e")->fetch();

echo "--- DATA ANALYSIS START ---\n";
echo json_encode(['stats' => $stats, 'huerfanos' => $huerfanos, 'totales' => $totales], JSON_PRETTY_PRINT);
echo "\n--- DATA ANALYSIS END ---";
