<?php
declare(strict_types=1);

/**
 * 🏛️ MIGRACIÓN ELITE V3 - SISTEMA SOBERANO DE SEGURIDAD Y DESEMPEÑO
 * Solución quirúrgica de Falencias 1 y 3 (Vitrina 06 Standard)
 */

require_once __DIR__ . '/../db.php';

try {
    $pdo = $db;
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    echo "🚀 Iniciando Migración Estructural Élite v3...\n";

    $pdo->beginTransaction();

    // 1. Inyección de Índices de Desempeño (Falencia 3)
    echo "⚡ Creando índices de optimización en SQLite...\n";
    
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_eval_pruebas_items_union ON eval_pruebas_items (prueba_id, pregunta_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_eval_preguntas_aprendizaje ON eval_preguntas (aprendizaje_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_eval_asignaciones_curso ON eval_asignaciones (prueba_id, curso_id)");
    
    echo "✅ Índices de desempeño inyectados correctamente.\n";

    // 2. Inyección de Columna disciplina_men en especialidades (Falencia 1)
    echo "⚡ Agregando columna disciplina_men a especialidades...\n";
    
    $hasColumn = false;
    $columns = $pdo->query("PRAGMA table_info(especialidades)")->fetchAll();
    foreach ($columns as $c) {
        if ($c['name'] === 'disciplina_men') {
            $hasColumn = true;
            break;
        }
    }

    if (!$hasColumn) {
        $pdo->exec("ALTER TABLE especialidades ADD COLUMN disciplina_men TEXT DEFAULT 'general'");
        echo "✅ Columna disciplina_men añadida con éxito.\n";
    } else {
        echo "ℹ️ La columna disciplina_men ya existía en especialidades.\n";
    }

    // 3. Sincronización Real de Relaciones Curriculares (Especialidades -> Áreas MEN)
    echo "⚡ Actualizando correspondencias curriculares oficiales del MEN...\n";
    
    $mapeos = [
        ['materia' => 'Matemáticas', 'area' => 3, 'disc' => 'general'],
        ['materia' => 'Español', 'area' => 4, 'disc' => 'general'],
        ['materia' => 'Historia', 'area' => 2, 'disc' => 'general'],
        ['materia' => 'Ciencias', 'area' => 1, 'disc' => 'general'],
        ['materia' => 'Inglés', 'area' => 4, 'disc' => 'general'],
        ['materia' => 'Educación Física', 'area' => 9, 'disc' => 'general']
    ];

    $stmtUpdate = $pdo->prepare("UPDATE especialidades SET area_id = :area_id, disciplina_men = :disc WHERE nombre_especialidad = :materia");
    
    foreach ($mapeos as $m) {
        $stmtUpdate->execute([
            ':area_id' => $m['area'],
            ':disc'    => $m['disc'],
            ':materia' => $m['materia']
        ]);
        echo "   👉 '{$m['materia']}' vinculada a Área MEN ID {$m['area']} y disciplina '{$m['disc']}'.\n";
    }

    $pdo->commit();
    echo "🎉 MIGRACIÓN COMPLETADA CON ÉXITO ABSOLUTO.\n";

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "❌ ERROR EN LA MIGRACIÓN: " . $e->getMessage() . "\n";
}
