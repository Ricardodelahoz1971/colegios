<?php
require_once 'php/db.php';

$action = $_GET['action'] ?? $argv[1] ?? 'seed';

if ($action === 'seed') {
    echo "Iniciando sembrado de notas de prueba para 1A...\n";
    try {
        $db->beginTransaction();

        // 1. Insertar Carga Académica para 1A (id 3) si no existe
        // Biología (id 2) con Martha Vergara (id 111)
        // Matemáticas (id 3) con Juan José Andrade (id 112)
        $cargas = [
            ['curso_id' => 3, 'especialidad_id' => 2, 'docente_id' => 111],
            ['curso_id' => 3, 'especialidad_id' => 3, 'docente_id' => 112]
        ];

        $cargaIds = [];
        foreach ($cargas as $c) {
            $stmt = $db->prepare("SELECT id FROM carga_academica WHERE curso_id = ? AND especialidad_id = ? AND docente_id = ?");
            $stmt->execute([$c['curso_id'], $c['especialidad_id'], $c['docente_id']]);
            $id = $stmt->fetchColumn();
            if (!$id) {
                $stmtIns = $db->prepare("INSERT INTO carga_academica (curso_id, especialidad_id, docente_id) VALUES (?, ?, ?)");
                $stmtIns->execute([$c['curso_id'], $c['especialidad_id'], $c['docente_id']]);
                $id = $db->lastInsertId();
                echo "Carga Académica creada id: $id\n";
            } else {
                echo "Carga Académica ya existía id: $id\n";
            }
            $cargaIds[] = $id;
        }

        // 2. Crear Actividades de prueba (Saber, Hacer, Ser)
        // Saber (clase_nota_id 1), Hacer (clase_nota_id 2), Ser (clase_nota_id 3)
        $actividades = [
            // Biología (especialidad 2, docente 111)
            ['titulo' => 'Evaluación de Células (Prueba)', 'tipo' => 'Taller', 'especialidad_id' => 2, 'docente_id' => 111, 'clase_nota_id' => 1],
            ['titulo' => 'Maqueta de Célula (Prueba)', 'tipo' => 'Proyecto', 'especialidad_id' => 2, 'docente_id' => 111, 'clase_nota_id' => 2],
            ['titulo' => 'Autoevaluación Biología (Prueba)', 'tipo' => 'Ser', 'especialidad_id' => 2, 'docente_id' => 111, 'clase_nota_id' => 3],
            // Matemáticas (especialidad 3, docente 112)
            ['titulo' => 'Prueba de Sumas (Prueba)', 'tipo' => 'Evaluación', 'especialidad_id' => 3, 'docente_id' => 112, 'clase_nota_id' => 1],
            ['titulo' => 'Taller en Parejas (Prueba)', 'tipo' => 'Taller', 'especialidad_id' => 3, 'docente_id' => 112, 'clase_nota_id' => 2],
            ['titulo' => 'Participación Matemáticas (Prueba)', 'tipo' => 'Ser', 'especialidad_id' => 3, 'docente_id' => 112, 'clase_nota_id' => 3]
        ];

        $actividadIds = [];
        foreach ($actividades as $act) {
            $stmt = $db->prepare("SELECT id FROM ares_actividades WHERE titulo = ? AND curso_id = 3");
            $stmt->execute([$act['titulo']]);
            $id = $stmt->fetchColumn();
            if (!$id) {
                $stmtIns = $db->prepare("INSERT INTO ares_actividades (docente_id, curso_id, especialidad_id, clase_nota_id, titulo, tipo_evaluacion, ambito, fecha_registro) VALUES (?, 3, ?, ?, ?, ?, 'estandar', '2026-02-15 10:00:00')");
                $stmtIns->execute([$act['docente_id'], $act['especialidad_id'], $act['clase_nota_id'], $act['titulo'], $act['tipo']]);
                $id = $db->lastInsertId();
                echo "Actividad creada: {$act['titulo']} (ID: $id)\n";
            }
            $actividadIds[] = $id;
        }

        // 3. Insertar Calificaciones para los estudiantes 8 y 9
        $estudiantes = [8, 9];
        // Asignaremos calificaciones aleatorias para probar
        foreach ($estudiantes as $estId) {
            foreach ($actividadIds as $actId) {
                // Verificar si ya tiene nota
                $stmt = $db->prepare("SELECT id FROM ares_calificaciones_desglose WHERE actividad_id = ? AND estudiante_id = ?");
                $stmt->execute([$actId, $estId]);
                if (!$stmt->fetchColumn()) {
                    if ($estId == 8) {
                        // Estudiante 8 tendra notas bajas (reprobadas) para activar alertas
                        $notasPrueba = [1.5, 2.2, 2.8];
                        $nota = $notasPrueba[array_rand($notasPrueba)];
                    } else {
                        // Estudiante 9 tendra notas excelentes para activar excelencia
                        $notasPrueba = [4.8, 4.9, 5.0];
                        $nota = $notasPrueba[array_rand($notasPrueba)];
                    }
                    $stmtIns = $db->prepare("INSERT INTO ares_calificaciones_desglose (actividad_id, estudiante_id, calificacion, fecha_registro) VALUES (?, ?, ?, '2026-02-15 10:00:00')");
                    $stmtIns->execute([$actId, $estId, $nota]);
                }
            }
        }

        $db->commit();
        echo "✅ Sembrado completado con éxito. Ahora puede revisar el Curso 1A en la Sábana de Notas.\n";
    } catch (Exception $e) {
        $db->rollBack();
        echo "❌ Error en sembrado: " . $e->getMessage() . "\n";
    }
} elseif ($action === 'clean') {
    echo "Iniciando desmontaje de datos de prueba...\n";
    try {
        $db->beginTransaction();

        // 1. Eliminar calificaciones de las actividades marcadas como 'estandar' y con titulo '%(Prueba)'
        $stmtGetActs = $db->prepare("SELECT id FROM ares_actividades WHERE ambito = :ambito AND titulo LIKE :titulo");
        $stmtGetActs->execute([':ambito' => 'estandar', ':titulo' => '%Prueba%']);
        $actIds = $stmtGetActs->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($actIds)) {
            $inClause = implode(',', array_map('intval', $actIds));
            $db->exec("DELETE FROM ares_calificaciones_desglose WHERE actividad_id IN ($inClause)");
            $db->exec("DELETE FROM ares_actividades WHERE id IN ($inClause)");
            echo "Eliminadas actividades y calificaciones de prueba.\n";
        }

        $db->commit();
        echo "✅ Desmontaje completado con éxito. Datos de prueba purgados.\n";
    } catch (Exception $e) {
        $db->rollBack();
        echo "❌ Error en desmontaje: " . $e->getMessage() . "\n";
    }
} else {
    echo "Acción no reconocida. Use 'seed' o 'clean'.\n";
}
?>
