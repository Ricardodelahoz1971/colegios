<?php
include_once __DIR__ . '/../php/db.php';
try {
    $db->exec("ALTER TABLE eval_preguntas ADD COLUMN aprendizaje_id INTEGER DEFAULT NULL");
    echo "✅ Columna 'aprendizaje_id' añadida con éxito.\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'duplicate column') !== false) {
        echo "⚠️ La columna ya existe.\n";
    } else {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}
