<?php
// scratch/inyector_estandares_huerfanas.php
include_once __DIR__ . '/../php/db.php';

echo "🚀 Iniciando inyección de Estándares para Áreas Huérfanas (Tecnología y Ética)...\n";

$db->beginTransaction();

try {
    // 1. TECNOLOGÍA E INFORMÁTICA (Área 9)
    $area_tech_id = 9;
    $comp_tech_name = "[Estándar] Competencia Tecnológica (Guía 30)";
    
    $ins_c = $db->prepare("INSERT INTO ares_catalogo_competencias (area_id, nombre, descripcion) VALUES (?, ?, ?)");
    $ins_c->execute([$area_tech_id, $comp_tech_name, 'Desarrollo de la cultura tecnológica según Guía 30 MEN.']);
    $comp_tech_id = $db->lastInsertId();

    $tech_data = [
        'Grado 1º-3º' => [
            1 => 'Identifica y describe la función de algunos artefactos de su entorno inmediato.',
            2 => 'Identifica y utiliza herramientas sencillas de manera segura.',
            3 => 'Reconoce productos tecnológicos cotidianos y su impacto.'
        ],
        'Grado 4º-5º' => [
            1 => 'Describe la relación entre los recursos naturales y los procesos de producción tecnológica.',
            2 => 'Utiliza herramientas con mayor precisión para la solución de problemas.',
            3 => 'Diferencia entre artefactos, procesos y sistemas tecnológicos.'
        ],
        'Grado 6º-7º' => [
            1 => 'Comprende los principios de hardware y software en sistemas informáticos.',
            2 => 'Maneja funciones básicas de sistemas operativos y herramientas de oficina.',
            3 => 'Explica cómo la tecnología ha evolucionado en diferentes contextos históricos.'
        ],
        'Grado 8º-9º' => [
            1 => 'Utiliza el pensamiento computacional para resolver problemas lógicos sencillos.',
            2 => 'Diseña y construye circuitos eléctricos y sistemas mecánicos simples.',
            3 => 'Analiza críticamente las redes sociales y la seguridad digital.'
        ],
        'Grado 10º-11º' => [
            1 => 'Desarrolla proyectos de robótica o automatización básica.',
            2 => 'Gestiona y analiza información mediante bases de datos y herramientas avanzadas.',
            3 => 'Valora el impacto de la Inteligencia Artificial y la biotecnología en la sociedad.'
        ]
    ];

    $ins_ap = $db->prepare("INSERT INTO ares_catalogo_aprendizajes (competencia_id, area_id, grado, num_dba, enunciado) VALUES (?, ?, ?, ?, ?)");
    foreach ($tech_data as $grado => $items) {
        foreach ($items as $num => $enunciado) {
            $ins_ap->execute([$comp_tech_id, $area_tech_id, $grado, $num, $enunciado]);
        }
    }

    // 2. EDUCACIÓN ÉTICA Y VALORES (Área 5)
    $area_etica_id = 5;
    $comp_etica_name = "[Estándar] Competencias Ciudadanas (Guía 6)";
    
    $ins_c->execute([$area_etica_id, $comp_etica_name, 'Estándares de convivencia, participación y pluralidad.']);
    $comp_etica_id = $db->lastInsertId();

    $etica_data = [
        'Grado 1º-3º' => [
            1 => 'Reconoce sus emociones y las de los demás en situaciones de convivencia.',
            2 => 'Conoce y respeta las normas básicas de comportamiento en el aula.',
            3 => 'Identifica que todas las personas son iguales en derechos y dignidad.'
        ],
        'Grado 4º-5º' => [
            1 => 'Reconoce y rechaza situaciones de exclusión o discriminación.',
            2 => 'Participa activamente en la construcción de acuerdos colectivos.',
            3 => 'Conoce sus derechos fundamentales y sabe ante quién acudir para su protección.'
        ],
        'Grado 6º-7º' => [
            1 => 'Utiliza el diálogo y la mediación para resolver conflictos escolares.',
            2 => 'Analiza críticamente el Manual de Convivencia institucional.',
            3 => 'Valora la diversidad étnica y cultural de su país.'
        ],
        'Grado 8º-9º' => [
            1 => 'Analiza dilemas morales complejos y propone soluciones reconciliadoras.',
            2 => 'Identifica prejuicios y estereotipos en los medios de comunicación.',
            3 => 'Propone cambios en las normas escolares basados en el bien común.'
        ],
        'Grado 10º-11º' => [
            1 => 'Analiza el impacto del conflicto armado y la construcción de paz en Colombia.',
            2 => 'Usa los mecanismos de participación ciudadana (Tutela, Derecho de Petición).',
            3 => 'Defiende la pluralidad y los derechos de las minorías según la Constitución.'
        ]
    ];

    foreach ($etica_data as $grado => $items) {
        foreach ($items as $num => $enunciado) {
            $ins_ap->execute([$comp_etica_id, $area_etica_id, $grado, $num, $enunciado]);
        }
    }

    $db->commit();
    echo "✅ Estándares de Tecnología y Ética inyectados exitosamente.\n";

} catch (Exception $e) {
    $db->rollBack();
    die("❌ Error: " . $e->getMessage());
}
