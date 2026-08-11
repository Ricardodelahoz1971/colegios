<?php
declare(strict_types=1);

/**
 * 🛡️ PERSEUS SQL INJECTION v1.0
 * Configuración del Motor de Cobertura Curricular
 */

require_once __DIR__ . '/db.php';
try {
    $pdo = $db;
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Eliminar vista si existe para asegurar integridad
    $pdo->exec("DROP VIEW IF EXISTS view_perseus_cobertura");

    // 2. Crear la Vista Maestra de Cobertura
    // Esta vista une las pruebas asignadas con sus preguntas y los aprendizajes del catálogo
    $sql = "
    CREATE VIEW view_perseus_cobertura AS
    SELECT 
        p.docente_id,
        p.materia_id AS especialidad_id,
        a.curso_id,
        curr.id AS aprendizaje_id,
        q.evidencia_id,
        curr.area_id,
        curr.grado AS grado_catalogo,
        c.nombre_curso
    FROM eval_pruebas p
    JOIN eval_asignaciones a ON p.id = a.prueba_id
    JOIN eval_pruebas_items pi ON p.id = pi.prueba_id
    JOIN eval_preguntas q ON pi.pregunta_id = q.id
    JOIN ares_catalogo_aprendizajes curr ON q.aprendizaje_id = curr.id
    JOIN cursos c ON a.curso_id = c.id
    ";

    $pdo->exec($sql);
    echo "✅ Motor SQL PERSEUS inyectado con éxito.\n";

} catch (Exception $e) {
    echo "❌ ERROR EN INYECCIÓN: " . $e->getMessage();
}
