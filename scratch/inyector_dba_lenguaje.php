<?php
// scratch/inyector_dba_lenguaje.php - MOTOR DE CARGA CURRICULAR (LENGUAJE)
include_once __DIR__ . '/../php/db.php';

$source = "c:\\xampp\\htdocs\\sistema_escolar\\lineamientos\\DBA_Lenguaje_Limpio.md";
if (!file_exists($source)) die("❌ Error: No se encuentra el archivo de datos limpios.");

echo "🚀 Iniciando inyección de Lenguaje (Área ID 4)...\n";

$content = file_get_contents($source);
$lines = explode("\n", $content);

$current_grade = "";
$current_dba_id = null;
$area_id = 4; // Lenguaje / Humanidades

// Competencias base para Lenguaje
$stmt_comp = $db->prepare("SELECT id FROM ares_catalogo_competencias WHERE area_id = ? LIMIT 1");
$stmt_comp->execute([$area_id]);
$default_comp_id = $stmt_comp->fetchColumn();

if (!$default_comp_id) {
    $comps = [
        [4, 'Comprensión e interpretación textual', 'Capacidad de entender y analizar textos.'],
        [4, 'Producción textual', 'Capacidad de crear textos coherentes.'],
        [4, 'Literatura', 'Conocimiento y disfrute de obras literarias.'],
        [4, 'Medios de comunicación y otros sistemas simbólicos', 'Análisis de medios y lenguajes no verbales.']
    ];
    $ins_c = $db->prepare("INSERT INTO ares_catalogo_competencias (area_id, nombre, descripcion) VALUES (?, ?, ?)");
    foreach ($comps as $c) $ins_c->execute($c);
    $default_comp_id = $db->lastInsertId();
}

$db->beginTransaction();

try {
    // Evitar duplicados
    $db->prepare("DELETE FROM ares_catalogo_aprendizajes WHERE area_id = ?")->execute([$area_id]);
    $db->prepare("DELETE FROM ares_catalogo_evidencias WHERE aprendizaje_id NOT IN (SELECT id FROM ares_catalogo_aprendizajes)")->execute();

    foreach ($lines as $line) {
        $line = trim($line);
        
        if (preg_match('/## 🎓 (.*)/', $line, $m)) {
            $current_grade = trim($m[1]);
            continue;
        }

        if (preg_match('/### 📌 DBA #(\d+)/', $line, $m)) {
            $num_dba = $m[1];
            continue;
        }

        if (preg_match('/\*\*Enunciado:\*\* (.*)/', $line, $m)) {
            $enunciado = trim($m[1]);
            $ins_dba = $db->prepare("INSERT INTO ares_catalogo_aprendizajes (competencia_id, area_id, grado, num_dba, enunciado) VALUES (?, ?, ?, ?, ?)");
            $ins_dba->execute([$default_comp_id, $area_id, $current_grade, $num_dba, $enunciado]);
            $current_dba_id = $db->lastInsertId();
            continue;
        }

        if (preg_match('/^- (.*)/', $line, $m)) {
            $evidencia = trim($m[1]);
            if ($current_dba_id) {
                $ins_ev = $db->prepare("INSERT INTO ares_catalogo_evidencias (aprendizaje_id, texto) VALUES (?, ?)");
                $ins_ev->execute([$current_dba_id, $evidencia]);
            }
        }
    }

    $db->commit();
    echo "✅ Inyección de Lenguaje completada exitosamente.\n";
    
    $count_dba = $db->query("SELECT COUNT(*) FROM ares_catalogo_aprendizajes WHERE area_id = 4")->fetchColumn();
    $count_ev = $db->query("SELECT COUNT(*) FROM ares_catalogo_evidencias WHERE aprendizaje_id IN (SELECT id FROM ares_catalogo_aprendizajes WHERE area_id = 4)")->fetchColumn();
    echo "📊 Resumen Final Lenguaje: $count_dba DBA y $count_ev Evidencias inyectadas.\n";

} catch (Exception $e) {
    $db->rollBack();
    die("❌ Error: " . $e->getMessage());
}
