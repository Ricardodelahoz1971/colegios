<?php
declare(strict_types=1);
require_once __DIR__ . '/../php/db.php';

try {
    echo "--- APRENDIZAJES AREA 9 ---\n";
    $aprs = $db->query("SELECT id, grado, num_dba, enunciado FROM ares_catalogo_aprendizajes WHERE area_id = 9")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($aprs as $a) {
        echo "Aprendizaje ID: {$a['id']} | Grado: {$a['grado']} | DBA: {$a['num_dba']} | Enunciado: " . substr($a['enunciado'], 0, 50) . "...\n";
        // Check evidences
        $stmt = $db->prepare("SELECT COUNT(*) FROM ares_catalogo_evidencias WHERE aprendizaje_id = ?");
        $stmt->execute([$a['id']]);
        $count = $stmt->fetchColumn();
        echo "  - Evidences count: $count\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
