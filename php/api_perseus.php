<?php
declare(strict_types=1);

/**
 * 🛡️ PERSEUS API ENDPOINT v1.0
 * Auditor Académico en Tiempo Real - Vitrina 06 Standard
 */

header('Content-Type: application/json');

require_once __DIR__ . '/db.php'; // Instancia de PDO en $db
require_once __DIR__ . '/helpers_elite.php'; // Helpers institucionales

try {
    // 1. Recepción y Saneamiento (Higiene de Entrada)
    if (session_status() === PHP_SESSION_NONE) session_start();
    
    $mi_id = (int)($_SESSION['usuario_id'] ?? 0);
    $es_directivo = (isset($_SESSION['rol_id']) && in_array((int)$_SESSION['rol_id'], [1, 2, 3]));

    if (!$mi_id) {
        throw new Exception("Sesión inválida o expirada. Acceso denegado.");
    }

    $docente_id_solicitado = filter_input(INPUT_GET, 'docente_id', FILTER_VALIDATE_INT);
    $materia_id = filter_input(INPUT_GET, 'materia_id', FILTER_VALIDATE_INT);
    $curso_id   = filter_input(INPUT_GET, 'curso_id', FILTER_VALIDATE_INT);

    // BLINDAJE IDOR PERSEUS: Si no es directivo (Admin, Coordinador, Rector), se fuerza su propio ID de sesión.
    $docente_id = ($es_directivo && $docente_id_solicitado) ? $docente_id_solicitado : $mi_id;

    if (!$materia_id || !$curso_id) {
        throw new Exception("Parámetros insuficientes para la auditoría Perseus.");
    }

    // 2. Obtener Área y Grado Institucional (Sintaxis SQL-92)
    $stmtInfo = $db->prepare("
        SELECT e.area_id, c.nombre_curso, e.nombre_especialidad 
        FROM especialidades e
        CROSS JOIN cursos c 
        WHERE e.id = :materia_id AND c.id = :curso_id
    ");
    $stmtInfo->execute([':materia_id' => $materia_id, ':curso_id' => $curso_id]);
    $info = $stmtInfo->fetch(PDO::FETCH_ASSOC);

    if (!$info) {
        throw new Exception("No se encontró la configuración académica para los parámetros dados.");
    }

    // Normalización de Grado de forma robusta a través de los helpers institucionales
    $gradoNormalizado = el_resolve_catalogue_grade($db, (int)$info['area_id'], $info['nombre_curso']);

    // 3. Cálculo de DBA Totales (Catálogo MEN)
    $stmtTotales = $db->prepare("
        SELECT COUNT(*) as total 
        FROM ares_catalogo_aprendizajes 
        WHERE area_id = :area_id AND grado = :grado
    ");
    $stmtTotales->execute([':area_id' => $info['area_id'], ':grado' => $gradoNormalizado]);
    $totalDBA = (int)$stmtTotales->fetch(PDO::FETCH_ASSOC)['total'];

    // 4. Cálculo de DBA Evaluados (Producción ARES via View Perseus)
    $stmtEvaluados = $db->prepare("
        SELECT COUNT(DISTINCT aprendizaje_id) as evaluados 
        FROM view_perseus_cobertura 
        WHERE docente_id = :docente_id 
          AND especialidad_id = :materia_id 
          AND curso_id = :curso_id
    ");
    $stmtEvaluados->execute([
        ':docente_id' => $docente_id,
        ':materia_id' => $materia_id,
        ':curso_id'   => $curso_id
    ]);
    $evaluadosDBA = (int)$stmtEvaluados->fetch(PDO::FETCH_ASSOC)['evaluados'];

    // Cálculo de Evidencias Totales (Catálogo MEN)
    $stmtEvidenciasTotales = $db->prepare("
        SELECT COUNT(e.id) as total_evidencias
        FROM ares_catalogo_evidencias e
        JOIN ares_catalogo_aprendizajes a ON e.aprendizaje_id = a.id
        WHERE a.area_id = :area_id AND a.grado = :grado
    ");
    $stmtEvidenciasTotales->execute([':area_id' => $info['area_id'], ':grado' => $gradoNormalizado]);
    $totalEvidencias = (int)$stmtEvidenciasTotales->fetch(PDO::FETCH_ASSOC)['total_evidencias'];

    // Cálculo de Evidencias Evaluadas (Producción ARES via View Perseus)
    $stmtEvidenciasEvaluadas = $db->prepare("
        SELECT COUNT(DISTINCT evidencia_id) as evaluadas_evidencias
        FROM view_perseus_cobertura
        WHERE docente_id = :docente_id
          AND especialidad_id = :materia_id
          AND curso_id = :curso_id
          AND evidencia_id IS NOT NULL
    ");
    $stmtEvidenciasEvaluadas->execute([
        ':docente_id' => $docente_id,
        ':materia_id' => $materia_id,
        ':curso_id'   => $curso_id
    ]);
    $evaluadasEvidencias = (int)$stmtEvidenciasEvaluadas->fetch(PDO::FETCH_ASSOC)['evaluadas_evidencias'];

    // 5. Matriz de Cálculo y Semáforo
    $porcentaje = ($totalDBA > 0) ? round(($evaluadosDBA / $totalDBA) * 100) : 0;
    $porcentajeEvidencias = ($totalEvidencias > 0) ? round(($evaluadasEvidencias / $totalEvidencias) * 100) : 0;
    
    $estado = "rojo";
    if ($porcentaje >= 80) {
        $estado = "verde";
    } elseif ($porcentaje >= 50) {
        $estado = "amarillo";
    }

    // 6. Higiene de Respuesta (JSON Estructurado)
    echo json_encode([
        "status" => "success",
        "data" => [
            "materia" => $info['nombre_especialidad'],
            "grado" => $info['nombre_curso'],
            "dba_totales" => $totalDBA,
            "dba_evaluados" => $evaluadosDBA,
            "cobertura_porcentaje" => $porcentaje,
            "evidencias_totales" => $totalEvidencias,
            "evidencias_evaluadas" => $evaluadasEvidencias,
            "cobertura_evidencias_porcentaje" => $porcentajeEvidencias,
            "estado_semaforo" => $estado,
            "metadata" => [
                "area_id" => $info['area_id'],
                "grado_busqueda" => $gradoNormalizado
            ]
        ],
        "message" => "Auditoría Perseus completada con éxito."
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "data" => null,
        "message" => $e->getMessage()
    ]);
}
