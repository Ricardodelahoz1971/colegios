<?php
include_once __DIR__ . '/../php/db.php';

try {
    $db->beginTransaction();

    // 1. Crear tabla de evidencias
    $db->exec("CREATE TABLE IF NOT EXISTS ares_catalogo_evidencias (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        aprendizaje_id INTEGER NOT NULL,
        enunciado TEXT NOT NULL,
        FOREIGN KEY (aprendizaje_id) REFERENCES ares_catalogo_aprendizajes(id) ON DELETE CASCADE
    )");

    // 2. Añadir columna a preguntas para vinculación atómica
    // Primero verificamos si ya existe por si acaso
    $check = $db->query("PRAGMA table_info(eval_preguntas)");
    $exists = false;
    while($row = $check->fetch(PDO::FETCH_ASSOC)) {
        if ($row['name'] == 'evidencia_id') { $exists = true; break; }
    }
    
    if (!$exists) {
        $db->exec("ALTER TABLE eval_preguntas ADD COLUMN evidencia_id INTEGER DEFAULT NULL");
    }

    $db->commit();
    echo "✅ Estructura de Evidencias creada con éxito.\n";
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo "❌ Error: " . $e->getMessage();
}
