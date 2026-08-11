<?php
// scratch/inyector_dba_ingles.php - MOTOR DE CARGA CURRICULAR (INGLÉS 6-11)
include_once __DIR__ . '/../php/db.php';

$source = "c:\\xampp\\htdocs\\sistema_escolar\\lineamientos\\DBA_Ingles_6-11_Linear.md";
if (!file_exists($source)) die("❌ Error: No se encuentra el archivo lineal.");

echo "🚀 Iniciando inyección de Inglés (Área ID 4 - Bachillerato)...\n";

$content = file_get_contents($source);
$lines = explode("\n", $content);

$current_grade = "";
$current_dba_id = null;
$area_id = 4; // Humanidades, Lengua Castellana e Idiomas Extranjeros

// Crear competencias específicas de Inglés para el Área 4
$comps = [
    [4, '[Inglés] Comprensión Oral y Escrita', 'Habilidades de Listening y Reading según el MCER.'],
    [4, '[Inglés] Producción Oral y Escrita', 'Habilidades de Writing y Speaking (Monólogos).'],
    [4, '[Inglés] Interacción Comunicativa', 'Habilidad de Conversación y debate espontáneo.']
];

$ins_c = $db->prepare("INSERT INTO ares_catalogo_competencias (area_id, nombre, descripcion) VALUES (?, ?, ?)");
$comp_ids = [];
foreach ($comps as $c) {
    $ins_c->execute($c);
    $comp_ids[] = $db->lastInsertId();
}

$default_comp_id = $comp_ids[0]; // Por defecto usa Comprensión

$db->beginTransaction();

try {
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Detección de Grado
        if (preg_match('/Grado (\d+)./i', $line, $m)) {
            $current_grade = "Grado " . $m[1] . "º";
            continue;
        }

        // Detección de número de DBA
        if (preg_match('/^(\d+)$/', $line, $m)) {
            $num_dba = $m[1];
            // El siguiente bloque de texto suele ser el enunciado en Español
            continue;
        }

        // Si tenemos un número y el grado, y la línea empieza por verbo (común en DBA)
        // O simplemente capturamos la primera línea larga después del número
        if (isset($num_dba) && $current_grade && strlen($line) > 30 && !strpos($line, ':')) {
            // Evitar capturar créditos o metadatos
            if (strpos($line, 'Ministerio') !== false || strpos($line, 'Derechos') !== false) continue;

            $enunciado = $line;
            $ins_dba = $db->prepare("INSERT INTO ares_catalogo_aprendizajes (competencia_id, area_id, grado, num_dba, enunciado) VALUES (?, ?, ?, ?, ?)");
            $ins_dba->execute([$default_comp_id, $area_id, $current_grade, $num_dba, $enunciado]);
            $current_dba_id = $db->lastInsertId();
            
            unset($num_dba); // Limpiar para el siguiente
            continue;
        }

        // Las evidencias en Inglés 6-11 no vienen con viñetas claras en el PDF lineal, 
        // pero podemos usar los "Por ejemplo:" como disparadores de evidencias o ejemplos.
        if (preg_match('/Por ejemplo:(.*)/i', $line, $m)) {
            $ev_texto = trim($m[1]);
            if (empty($ev_texto)) continue; // Si está en la siguiente línea, el parser lineal lo complica
            
            if ($current_dba_id) {
                $ins_ev = $db->prepare("INSERT INTO ares_catalogo_evidencias (aprendizaje_id, texto) VALUES (?, ?)");
                $ins_ev->execute([$current_dba_id, $ev_texto]);
            }
        }
    }

    $db->commit();
    echo "✅ Inyección de Inglés (Bachillerato) completada.\n";
    
} catch (Exception $e) {
    $db->rollBack();
    die("❌ Error: " . $e->getMessage());
}
