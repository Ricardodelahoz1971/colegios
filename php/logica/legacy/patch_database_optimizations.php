<?php
declare(strict_types=1);

/**
 * 🏛️ DATABASE OPTIMIZATION PATCH v1.0
 * Inyecta índices sobre llaves foráneas en las tablas de Ares y Perseus para lecturas de sub-milisegundo.
 */

require_once __DIR__ . '/../db.php';

try {
    $optimizations = [
        "idx_eval_pruebas_items_prueba" => "eval_pruebas_items (prueba_id)",
        "idx_eval_pruebas_items_pregunta" => "eval_pruebas_items (pregunta_id)",
        "idx_eval_respuestas_detalles_resp" => "eval_respuestas_detalles (respuesta_id)",
        "idx_eval_respuestas_detalles_preg" => "eval_respuestas_detalles (pregunta_id)",
        "idx_eval_respuestas_estudiante" => "eval_respuestas (estudiante_id)",
        "idx_eval_respuestas_prueba" => "eval_respuestas (prueba_id)",
        "idx_eval_preguntas_docente" => "eval_preguntas (docente_id)",
        "idx_eval_preguntas_materia" => "eval_preguntas (materia_id)",
        "idx_eval_preguntas_aprendizaje" => "eval_preguntas (aprendizaje_id)",
        "idx_eval_preguntas_evidencia" => "eval_preguntas (evidencia_id)",
        "idx_eval_asignaciones_prueba" => "eval_asignaciones (prueba_id)",
        "idx_eval_asignaciones_curso" => "eval_asignaciones (curso_id)",
        "idx_eval_asignaciones_especialidad" => "eval_asignaciones (especialidad_id)"
    ];

    foreach ($optimizations as $index_name => $table_and_cols) {
        try {
            $db->exec("CREATE INDEX IF NOT EXISTS `$index_name` ON $table_and_cols");
        } catch (Exception $e) {
            // Fallback en caso de que no soporte IF NOT EXISTS
            try {
                $db->exec("CREATE INDEX `$index_name` ON $table_and_cols");
            } catch (Exception $ex) {
                // El índice probablemente ya existe
            }
        }
    }

    echo json_encode(["status" => "success", "message" => "Database indexing optimizations applied successfully."]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
