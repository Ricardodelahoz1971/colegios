<?php
require_once __DIR__ . '/../php/db.php';

try {
    $db->beginTransaction();

    // 1. Purgar demos anteriores si existen para evitar colisiones
    $db->exec("DELETE FROM eval_respuestas WHERE asignacion_id = 999 OR prueba_id = 999");
    $db->exec("DELETE FROM eval_asignaciones WHERE id = 999 OR prueba_id = 999");
    $db->exec("DELETE FROM eval_pruebas_items WHERE prueba_id = 999");
    $db->exec("DELETE FROM eval_pruebas WHERE id = 999");

    // 2. Crear una prueba demo (ID 999) para el Docente LJP (ID 3) en Matemáticas (ej. especialidad_id = 1)
    $stmt_pru = $db->prepare("
        INSERT INTO eval_pruebas (id, docente_id, materia_id, titulo, instrucciones, tiempo_limite, modalidad, fecha_creacion)
        VALUES (999, 3, 1, 'Examen de Matemáticas de Precisión', 'Resuelva con cuidado. Se permite el uso de calculadora.', 45, 1, CURRENT_TIMESTAMP)
    ");
    $stmt_pru->execute();

    // 3. Crear 3 preguntas demo en eval_preguntas si no existen, y vincularlas en eval_pruebas_items
    // Pregunta 1
    $db->exec("INSERT OR IGNORE INTO eval_preguntas (id, docente_id, materia_id, tipo_id, enunciado, metadata_json, aprendizaje_id) VALUES (991, 3, 1, 1, '¿Cuánto es 5 + 5?', '[]', 56)");
    $db->exec("INSERT INTO eval_pruebas_items (prueba_id, pregunta_id, peso, orden) VALUES (999, 991, 1.5, 1)");

    // Pregunta 2
    $db->exec("INSERT OR IGNORE INTO eval_preguntas (id, docente_id, materia_id, tipo_id, enunciado, metadata_json, aprendizaje_id) VALUES (992, 3, 1, 1, '¿Cuánto es 10 * 3?', '[]', 66)");
    $db->exec("INSERT INTO eval_pruebas_items (prueba_id, pregunta_id, peso, orden) VALUES (999, 992, 2.0, 2)");

    // Pregunta 3 (Abierta / Calificación Manual)
    $db->exec("INSERT OR IGNORE INTO eval_preguntas (id, docente_id, materia_id, tipo_id, enunciado, metadata_json, aprendizaje_id) VALUES (993, 3, 1, 2, 'Explique qué es la aritmética básica.', '[]', 56)");
    $db->exec("INSERT INTO eval_pruebas_items (prueba_id, pregunta_id, peso, orden) VALUES (999, 993, 1.5, 3)");

    // 4. Crear Asignación (ID 999) para el Curso 1A (ID 1)
    $stmt_asig = $db->prepare("
        INSERT INTO eval_asignaciones (id, prueba_id, curso_id, docente_id, fecha_inicio, fecha_fin, clave_acceso, intentos_permitidos, mostrar_resultados, tipo_navegacion)
        VALUES (999, 999, 1, 3, '2026-05-01 08:00:00', '2026-06-30 23:59:00', '', 1, 1, 'libre')
    ");
    $stmt_asig->execute();

    // 5. Inyectar 5 entregas de estudiantes del Curso 1A (estudiante_ids: 13, 3, 14, 6, 7)
    // Con nota automática baja (ej. 1.5) y listas para ser evaluadas manualmente y recibir recuperaciones
    $estudiantes_demo = [13, 3, 14, 6, 7];
    $respuestas_mock_json = json_encode([
        ["pregunta_id" => 991, "respuesta" => "10", "correcta" => true, "puntaje" => 1.5],
        ["pregunta_id" => 992, "respuesta" => "30", "correcta" => true, "puntaje" => 2.0],
        ["pregunta_id" => 993, "respuesta" => "Es la parte de las matemáticas que estudia los números y las operaciones hechas con ellos.", "correcta" => null, "puntaje" => 0.0]
    ]);

    $stmt_resp = $db->prepare("
        INSERT INTO eval_respuestas (estudiante_id, prueba_id, pregunta_id, respuesta_alumno, puntaje_obtenido, comentario_docente, estado, fecha_entrega, asignacion_id, calificacion_automatica, calificacion_manual, respuestas_json, calificacion_recuperacion)
        VALUES (?, 999, 0, 'Entregado en tiempo récord', 3.5, 'Falta calificar la pregunta abierta.', 1, CURRENT_TIMESTAMP, 999, 1.5, 0.0, ?, NULL)
    ");

    foreach ($estudiantes_demo as $est_id) {
        $stmt_resp->execute([$est_id, $respuestas_mock_json]);
    }

    $db->commit();
    echo "=== SIEMBRA DE EVALUACIÓN DEMO EXITOSA ===\n";
    echo "- Prueba Creada [ID: 999]: 'Examen de Matemáticas de Precisión'\n";
    echo "- Curso Asignado [ID: 1]: 1A\n";
    echo "- Total Entregas en Revisión Sembradas: 5 estudiantes de 1A\n";
    echo "Ya puede ingresar como Docente 'LEL' y calificar!\n";

} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
