<?php
include_once __DIR__ . '/../php/db.php';
try {
    $stmt = $db->prepare("UPDATE ares_catalogo_aprendizajes SET enunciado = TRIM(REPLACE(enunciado, '. ley', '')) WHERE area_id = 1");
    $stmt->execute();
    echo "✅ Residuos '. ley' eliminados con éxito.\n";
    
    // Verificación de conteo
    $stmt = $db->query("SELECT COUNT(*) FROM ares_catalogo_aprendizajes WHERE area_id = 1 AND grado = 'Grado 11º'");
    $count = $stmt->fetchColumn();
    echo "📊 Total DBA en 11º Naturales: $count\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
