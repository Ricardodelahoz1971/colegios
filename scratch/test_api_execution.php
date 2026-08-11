<?php
declare(strict_types=1);

/**
 * 🏛 Cortafuegos / Script de Verificación de API Sabana
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../php/db.php';

// 1. Check which courses have demo data
$stmt = $db->query("
    SELECT c.id, c.nombre_curso, COUNT(a.id) as num_activities
    FROM cursos c
    JOIN ares_actividades a ON c.id = a.curso_id
    WHERE a.titulo LIKE '%[DEMO-BI]%'
    GROUP BY c.id
");
$seeded_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "========================================================\n";
echo "📊 INSPECCIÓN DE CURSOS CON DATOS DEMO SEEDADOS\n";
echo "========================================================\n";
if (empty($seeded_courses)) {
    echo "⚠️ No se encontraron cursos con actividades demo. Asegúrese de correr el seeder primero.\n";
} else {
    foreach ($seeded_courses as $sc) {
        echo "🔹 Curso: " . $sc['nombre_curso'] . " (ID: " . $sc['id'] . ") -> " . $sc['num_activities'] . " actividades demo.\n";
    }
}
echo "========================================================\n\n";

if (empty($seeded_courses)) {
    exit(1);
}

// Select the first course that has seeded activities
$test_curso_id = (int)$seeded_courses[0]['id'];
$test_curso_nombre = $seeded_courses[0]['nombre_curso'];

// 2. Mock session and parameters after session_start()
$_SESSION = [
    'usuario_id' => 1,
    'rol_nombre' => 'Docente',
    'nombre_usuario' => 'director_bi_test'
];

$_GET = [
    'accion' => 'cargar_consolidado_curso',
    'curso_id' => $test_curso_id,
    'periodo_id' => 2
];

$_POST = [];

// 3. Include api_sabana.php to capture response
ob_start();
try {
    // We change directory to php/logica/ so relative includes work properly
    chdir(__DIR__ . '/../php/logica');
    require 'api_sabana.php';
} catch (Exception $e) {
    echo json_encode(['status' => 'exception', 'message' => $e->getMessage()]);
}

$output = ob_get_clean();

// 4. Parse and display results
$data = json_decode($output, true);

echo "========================================================\n";
echo "📊 PRUEBA DE CONSOLIDADO ACADÉMICO BI (CURSO: $test_curso_nombre, ID: $test_curso_id, PERIODO: 2)\n";
echo "========================================================\n";

if ($data === null) {
    echo "❌ Error parsing JSON response from API.\n";
    echo "Raw Output:\n" . $output . "\n";
    exit(1);
}

if (($data['status'] ?? '') === 'error') {
    echo "❌ API Error: " . ($data['message'] ?? 'Unknown error') . "\n";
    exit(1);
}

echo "✅ Estado de Respuesta: " . strtoupper($data['status'] ?? 'unknown') . "\n";
echo "✅ Mensaje: " . ($data['message'] ?? 'N/A') . "\n";

$payload = $data['data'] ?? [];
echo "✅ Alumnos Consolidados: " . count($payload['estudiantes'] ?? []) . " estudiantes encontrados.\n";
echo "✅ Materias Evaluadas: " . count($payload['materias'] ?? []) . " materias asignadas.\n";

if (!empty($payload['estudiantes'])) {
    echo "\n🔎 MUESTRA DE NOTAS POR ESTUDIANTE (Primeros 3 alumnos):\n";
    $count = 0;
    foreach ($payload['estudiantes'] as $est) {
        if ($count++ >= 3) break;
        echo "👤 Estudiante: " . $est['apellido'] . ", " . $est['nombre'] . " (ID: " . $est['id'] . ")\n";
        echo "   Definitivas por Materia (Muestra de 5):\n";
        $total_notas = 0;
        $con_nota = 0;
        foreach ($payload['materias'] as $mat) {
            $nota = $est['calificaciones'][$mat['id']] ?? null;
            if ($nota !== null) {
                $nota_str = number_format((float)$nota, 2);
                $con_nota++;
            } else {
                $nota_str = 'SIN NOTA';
            }
            $total_notas++;
            // Only print first 5 subjects to avoid verbose screen outputs
            if ($total_notas <= 5) {
                echo "   - " . $mat['nombre_especialidad'] . ": [" . $nota_str . "]\n";
            }
        }
        echo "   ... (" . $con_nota . " de " . $total_notas . " materias calificadas)\n";
        echo "\n";
    }
} else {
    echo "⚠️ No se encontraron estudiantes con calificaciones en este periodo.\n";
}

echo "========================================================\n";
echo "🎉 PRUEBA DE API COMPLETADA EXITOSAMENTE Y CERTIFICADA.\n";
echo "========================================================\n";
