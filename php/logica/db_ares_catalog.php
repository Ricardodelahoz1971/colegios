<?php
declare(strict_types=1);
// php/logica/db_ares_catalog.php - INFRAESTRUCTURA CURRICULAR v1.0
include_once __DIR__ . '/../db.php';

echo "🔧 Iniciando cimentación del Catálogo Ares...\n";

try {
    // 1. Competencias (Nivel Superior)
    $db->exec("CREATE TABLE IF NOT EXISTS ares_catalogo_competencias (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        area_id INTEGER,
        nombre TEXT NOT NULL,
        descripcion TEXT,
        fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 2. Aprendizajes / DBA (Nivel Operativo)
    $db->exec("CREATE TABLE IF NOT EXISTS ares_catalogo_aprendizajes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        competencia_id INTEGER,
        area_id INTEGER,
        grado TEXT NOT NULL,
        num_dba INTEGER,
        enunciado TEXT NOT NULL,
        version TEXT DEFAULT 'V.1',
        fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 3. Evidencias (Nivel de Evaluación)
    $db->exec("CREATE TABLE IF NOT EXISTS ares_catalogo_evidencias (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        aprendizaje_id INTEGER,
        texto TEXT NOT NULL,
        fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    echo "✅ Tablas de catálogo creadas/verificadas con éxito.\n";

    // Inyectar Competencias Base para Ciencias Naturales (ID 1)
    $competencias_base = [
        [1, 'Uso comprensivo del conocimiento científico', 'Capacidad para comprender y usar conceptos y teorías.'],
        [1, 'Explicación de fenómenos', 'Capacidad para dar razón de los fenómenos.'],
        [1, 'Indagación', 'Capacidad para plantear preguntas y diseñar experimentos.']
    ];

    $stmt = $db->prepare("SELECT COUNT(*) FROM ares_catalogo_competencias WHERE area_id = 1");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $ins = $db->prepare("INSERT INTO ares_catalogo_competencias (area_id, nombre, descripcion) VALUES (?, ?, ?)");
        foreach ($competencias_base as $c) $ins->execute($c);
        echo "✅ Competencias base de Ciencias Naturales inyectadas.\n";
    }

} catch (Exception $e) {
}
