<?php
declare(strict_types=1);

require_once __DIR__ . '/../php/db.php';

try {
    $stmt = $db->prepare("SELECT COUNT(*) FROM eval_tipos WHERE slug = 'canvas_reactivo'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $db->prepare("INSERT INTO eval_tipos (nombre, slug, estado) VALUES ('Lienzo Gráfico (Canvas)', 'canvas_reactivo', 1)")->execute();
        echo "✅ Tipo 'canvas_reactivo' registrado con éxito en SQLite!\n";
    } else {
        echo "ℹ️ El tipo 'canvas_reactivo' ya existe en eval_tipos.\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
