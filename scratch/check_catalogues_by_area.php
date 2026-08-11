<?php
declare(strict_types=1);
require_once __DIR__ . '/../php/db.php';

try {
    echo "--- APRENDIZAJES POR AREA Y GRADO ---\n";
    $areas = $db->query("SELECT * FROM areas")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($areas as $area) {
        echo "Area: {$area['nombre_area']} (ID: {$area['id']})\n";
        $grados = $db->query("SELECT DISTINCT grado FROM ares_catalogo_aprendizajes WHERE area_id = {$area['id']}")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($grados as $g) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM ares_catalogo_aprendizajes WHERE area_id = ? AND grado = ?");
            $stmt->execute([$area['id'], $g]);
            $count = $stmt->fetchColumn();
            echo "  - Grado: $g | Aprendizajes: $count\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
