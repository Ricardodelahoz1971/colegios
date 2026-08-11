<?php
// scratch/inyector_dba_ingles_primaria.php
include_once __DIR__ . '/../php/db.php';

$area_id = 4; // Humanidades e Idiomas
$comp_id = 14; // [Inglés] Comprensión (asumiendo IDs creados antes)

// Mapeo manual refinado del PDF de Primaria
$data = [
    'Transición' => [
        1 => 'Reconoce instrucciones sencillas relacionadas con su entorno inmediato y responde a ellas de manera no verbal.',
        2 => 'Asocia imágenes con sonidos de palabras relacionadas con su casa y salón de clases.',
        3 => 'Identifica, repite y utiliza palabras asociadas con su entorno inmediato (casa y salón de clase).',
        4 => 'Comprende y responde preguntas muy sencillas sobre sus datos personales (nombre, edad, familia).'
    ],
    'Grado 1º' => [
        1 => 'Comprende y responde a instrucciones sobre tareas escolares básicas, de manera verbal y no verbal.',
        2 => 'Comprende y realiza declaraciones sencillas usando expresiones ensayadas sobre su entorno inmediato.',
        3 => 'Organiza la secuencia de eventos principales en una historia corta y sencilla sobre temas familiares.',
        4 => 'Responde preguntas sencillas sobre información personal básica (nombre, edad, familia).'
    ],
    'Grado 2º' => [
        1 => 'Expresa ideas sencillas sobre temas estudiados usando palabras y frases cortas.',
        2 => 'Comprende la secuencia de una historia corta y sencilla y la cuenta nuevamente usando ilustraciones.',
        3 => 'Intercambia información personal (procedencia, edad) siguiendo modelos provistos.',
        4 => 'Menciona aspectos culturales propios de su entorno usando vocabulario conocido.'
    ],
    'Grado 3º' => [
        1 => 'Comprende y describe algunos detalles en textos cortos y sencillos sobre temas familiares.',
        2 => 'Responde preguntas sencillas sobre textos descriptivos cortos y alusivos a temas conocidos.',
        3 => 'Intercambia ideas y opiniones sencillas con compañeros y profesores siguiendo modelos.',
        4 => 'Describe de manera oral y escrita objetos, lugares, personas y comunidades usando oraciones simples.'
    ],
    'Grado 4º' => [
        1 => 'Comprende la idea general y algunos detalles en un texto informativo corto y sencillo.',
        2 => 'Pregunta y responde interrogantes relacionados con el "quién, cuándo y dónde" después de leer.',
        3 => 'Intercambia opiniones sencillas sobre un tema de interés a través de oraciones simples.',
        4 => 'Compara características básicas de personas, objetos y lugares de su escuela y comunidad.'
    ],
    'Grado 5º' => [
        1 => 'Comprende información general y específica en un texto narrativo corto sobre temas conocidos.',
        2 => 'Produce un texto narrativo oral y/o escrito corto que responde al "qué, quién, cuándo y dónde".',
        3 => 'Intercambia información sobre hábitos, gustos y preferencias siguiendo modelos.',
        4 => 'Explica causas y consecuencias de una situación a través de oraciones simples.'
    ]
];

echo "🚀 Iniciando inyección de Inglés Primaria...\n";

$db->beginTransaction();
try {
    // Buscar competencia de Inglés si no sabemos el ID exacto
    $stmt_c = $db->query("SELECT id FROM ares_catalogo_competencias WHERE area_id = 4 AND nombre LIKE '%[Inglés]%' LIMIT 1");
    $comp_id = $stmt_c->fetchColumn();

    foreach ($data as $grado => $dbas) {
        foreach ($dbas as $num => $enunciado) {
            $ins = $db->prepare("INSERT INTO ares_catalogo_aprendizajes (competencia_id, area_id, grado, num_dba, enunciado) VALUES (?, ?, ?, ?, ?)");
            $ins->execute([$comp_id, $area_id, $grado, $num, $enunciado]);
        }
    }
    $db->commit();
    echo "✅ Inglés Primaria inyectado con éxito.\n";
} catch (Exception $e) {
    $db->rollBack();
    die("❌ Error: " . $e->getMessage());
}
