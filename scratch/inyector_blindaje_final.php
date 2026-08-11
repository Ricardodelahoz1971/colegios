<?php
// scratch/inyector_blindaje_final.php
include_once __DIR__ . '/../php/db.php';

echo "🚀 Iniciando Blindaje Final del Currículo ARES...\n";

$db->beginTransaction();

try {
    $ins_c = $db->prepare("INSERT INTO ares_catalogo_competencias (area_id, nombre, descripcion) VALUES (?, ?, ?)");
    $ins_ap = $db->prepare("INSERT INTO ares_catalogo_aprendizajes (competencia_id, area_id, grado, num_dba, enunciado) VALUES (?, ?, ?, ?, ?)");

    // 1. EDUCACIÓN FÍSICA (Área 7)
    $area_id = 7;
    $ins_c->execute([$area_id, '[Estándar] Desarrollo Motriz y Corporal', 'Orientaciones Doc. 15 MEN.']);
    $cid = $db->lastInsertId();
    $data = [
        'Primaria' => 'Desarrolla habilidades motrices básicas (correr, saltar, lanzar) y reconoce la importancia del ejercicio.',
        'Básica' => 'Aplica técnicas deportivas básicas y comprende la relación entre actividad física y salud.',
        'Media' => 'Diseña planes de acondicionamiento físico y lidera procesos de integración deportiva.'
    ];
    $n = 1; foreach($data as $g => $txt) $ins_ap->execute([$cid, $area_id, $g, $n++, $txt]);

    // 2. EDUCACIÓN ARTÍSTICA (Área 8)
    $area_id = 8;
    $ins_c->execute([$area_id, '[Estándar] Expresión Estética y Creativa', 'Orientaciones Doc. 16 MEN.']);
    $cid = $db->lastInsertId();
    $data = [
        'Primaria' => 'Explora diversos lenguajes artísticos (dibujo, música, danza) para expresar su mundo interior.',
        'Básica' => 'Utiliza técnicas artísticas para comunicar ideas, sentimientos y visiones del entorno.',
        'Media' => 'Analiza obras de arte universales y produce propuestas estéticas con identidad propia.'
    ];
    $n = 1; foreach($data as $g => $txt) $ins_ap->execute([$cid, $area_id, $g, $n++, $txt]);

    // 3. FILOSOFÍA (Área 10)
    $area_id = 10;
    $ins_c->execute([$area_id, '[Estándar] Pensamiento Crítico y Filosófico', 'Lineamientos de Media MEN.']);
    $cid = $db->lastInsertId();
    $data = [
        'Grado 10º' => 'Analiza los problemas fundamentales de la filosofía antigua y medieval.',
        'Grado 11º' => 'Construye argumentos críticos sobre la modernidad y los problemas éticos contemporáneos.'
    ];
    $n = 1; foreach($data as $g => $txt) $ins_ap->execute([$cid, $area_id, $g, $n++, $txt]);

    // 4. CIENCIAS POLÍTICAS Y ECONÓMICAS (Área 11)
    $area_id = 11;
    $ins_c->execute([$area_id, '[Estándar] Análisis Socioeconómico y Político', 'Lineamientos de Media MEN.']);
    $cid = $db->lastInsertId();
    $data = [
        'Media' => 'Comprende el funcionamiento del mercado, el estado y las relaciones de poder en Colombia.'
    ];
    $n = 1; foreach($data as $g => $txt) $ins_ap->execute([$cid, $area_id, $g, $n++, $txt]);

    // 5. EMPRENDIMIENTO (Área 12)
    $area_id = 12;
    $ins_c->execute([$area_id, '[Estándar] Cultura del Emprendimiento', 'Ley 1014 de 2006.']);
    $cid = $db->lastInsertId();
    $data = [
        'General' => 'Desarrolla actitudes de liderazgo, creatividad y planeación para la generación de proyectos productivos.'
    ];
    $n = 1; foreach($data as $g => $txt) $ins_ap->execute([$cid, $area_id, $g, $n++, $txt]);

    // 6. RELIGIÓN (Área 6)
    $area_id = 6;
    $ins_c->execute([$area_id, '[Estándar] Dimensión Trascendente', 'Autonomía Institucional (Libre Albedrío).']);
    $cid = $db->lastInsertId();
    $data = [
        'General' => 'Reconoce el hecho religioso y respeta la diversidad de creencias en la sociedad pluralista.'
    ];
    $n = 1; foreach($data as $g => $txt) $ins_ap->execute([$cid, $area_id, $g, $n++, $txt]);

    $db->commit();
    echo "✅ Blindaje Curricular Total completado.\n";

} catch (Exception $e) {
    $db->rollBack();
    die("❌ Error: " . $e->getMessage());
}
