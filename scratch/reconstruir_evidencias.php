<?php
include_once __DIR__ . '/../php/db.php';

try {
    $db->exec("DROP TABLE IF EXISTS ares_catalogo_evidencias");
    $db->exec("CREATE TABLE ares_catalogo_evidencias (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        aprendizaje_id INTEGER NOT NULL,
        enunciado TEXT NOT NULL,
        FOREIGN KEY (aprendizaje_id) REFERENCES ares_catalogo_aprendizajes(id) ON DELETE CASCADE
    )");
    echo "✅ Tabla 'ares_catalogo_evidencias' reconstruida con estándar de élite.\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
