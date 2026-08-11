<?php
// scratch/inyector_dba_naturales.php - MOTOR DE CARGA CURRICULAR
include_once __DIR__ . '/../php/db.php';

$source = "c:\\xampp\\htdocs\\sistema_escolar\\lineamientos\\DBA_Ciencias_Naturales_Limpio.md";
if (!file_exists($source)) die("❌ Error: No se encuentra el archivo de datos limpios.");

echo "🚀 Iniciando inyección de Ciencias Naturales...\n";

$content = file_get_contents($source);
$lines = explode("\n", $content);

$current_grade = "";
$current_dba_id = null;
$area_id = 1; // Ciencias Naturales

// Obtener una competencia por defecto para el área
$stmt_comp = $db->prepare("SELECT id FROM ares_catalogo_competencias WHERE area_id = ? LIMIT 1");
$stmt_comp->execute([$area_id]);
$default_comp_id = $stmt_comp->fetchColumn();

$db->beginTransaction();

try {
    // Limpiar datos previos de esta área para evitar duplicados en pruebas
    $db->prepare("DELETE FROM ares_catalogo_aprendizajes WHERE area_id = ?")->execute([$area_id]);
    $db->prepare("DELETE FROM ares_catalogo_evidencias WHERE aprendizaje_id NOT IN (SELECT id FROM ares_catalogo_aprendizajes)")->execute();

    foreach ($lines as $line) {
        $line = trim($line);
        
        // Detectar Grado
        if (preg_match('/## 🎓 (.*)/', $line, $m)) {
            $current_grade = trim($m[1]);
            continue;
        }

        // Detectar DBA
        if (preg_match('/### 📌 DBA #(\d+)/', $line, $m)) {
            $num_dba = $m[1];
            continue;
        }

        // Detectar Enunciado
        if (preg_match('/\*\*Enunciado:\*\* (.*)/', $line, $m)) {
            $enunciado = trim($m[1]);
            $ins_dba = $db->prepare("INSERT INTO ares_catalogo_aprendizajes (competencia_id, area_id, grado, num_dba, enunciado) VALUES (?, ?, ?, ?, ?)");
            $ins_dba->execute([$default_comp_id, $area_id, $current_grade, $num_dba, $enunciado]);
            $current_dba_id = $db->lastInsertId();
            continue;
        }

        // Detectar Evidencia
        if (preg_match('/^- (.*)/', $line, $m)) {
            $evidencia = trim($m[1]);
            if ($current_dba_id) {
                $ins_ev = $db->prepare("INSERT INTO ares_catalogo_evidencias (aprendizaje_id, texto) VALUES (?, ?)");
                $ins_ev->execute([$current_dba_id, $evidencia]);
            }
        }
    }

    $db->commit();
    echo "✅ Inyección completada exitosamente.\n";
    
    // Conteo final
    $count_dba = $db->query("SELECT COUNT(*) FROM ares_catalogo_aprendizajes WHERE area_id = 1")->fetchColumn();
    $count_ev = $db->query("SELECT COUNT(*) FROM ares_catalogo_evidencias")->fetchColumn();
    echo "📊 Resumen: $count_dba Aprendizajes (DBA) y $count_ev Evidencias cargadas.\n";

} catch (Exception $e) {
    $db->rollBack();
    die("❌ Error durante la inyección: " . $e->getMessage());
}
