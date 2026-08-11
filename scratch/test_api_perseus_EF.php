<?php
declare(strict_types=1);
require_once __DIR__ . '/../php/db.php';
require_once __DIR__ . '/../php/helpers_elite.php';

try {
    $docente_id = 8; // Carlos Ramírez
    $materia_id = 6; // Educación Física
    $curso_id = 1; // 1A

    $stmtInfo = $db->prepare("
        SELECT e.area_id, c.nombre_curso, e.nombre_especialidad 
        FROM especialidades e
        CROSS JOIN cursos c 
        WHERE e.id = :materia_id AND c.id = :curso_id
    ");
    $stmtInfo->execute([':materia_id' => $materia_id, ':curso_id' => $curso_id]);
    $info = $stmtInfo->fetch(PDO::FETCH_ASSOC);

    echo "INFO:\n";
    print_r($info);

    $gradoNormalizado = el_resolve_catalogue_grade($db, (int)$info['area_id'], $info['nombre_curso']);
    echo "RESOLVED GRADE: $gradoNormalizado\n";

    // 3. Cálculo de DBA Totales (Catálogo MEN)
    $stmtTotales = $db->prepare("
        SELECT COUNT(*) as total 
        FROM ares_catalogo_aprendizajes 
        WHERE area_id = :area_id AND grado = :grado
    ");
    $stmtTotales->execute([':area_id' => $info['area_id'], ':grado' => $gradoNormalizado]);
    $totalDBA = (int)$stmtTotales->fetch(PDO::FETCH_ASSOC)['total'];
    echo "TOTAL DBA: $totalDBA\n";

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
    echo "EVALUADOS DBA: $evaluadosDBA\n";

    // Cálculo de Evidencias Totales (Catálogo MEN)
    $stmtEvidenciasTotales = $db->prepare("
        SELECT COUNT(e.id) as total_evidencias
        FROM ares_catalogo_evidencias e
        JOIN ares_catalogo_aprendizajes a ON e.aprendizaje_id = a.id
        WHERE a.area_id = :area_id AND a.grado = :grado
    ");
    $stmtEvidenciasTotales->execute([':area_id' => $info['area_id'], ':grado' => $gradoNormalizado]);
    $totalEvidencias = (int)$stmtEvidenciasTotales->fetch(PDO::FETCH_ASSOC)['total_evidencias'];
    echo "TOTAL EVIDENCIAS: $totalEvidencias\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
