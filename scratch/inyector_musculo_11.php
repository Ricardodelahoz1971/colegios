<?php
include_once __DIR__ . '/../php/db.php';

$evidencias_11 = [
    54 => [ // Química 11
        "Representa reacciones químicas entre compuestos orgánicos usando nomenclatura IUPAC.",
        "Clasifica compuestos orgánicos y biomoléculas (alcoholes, proteínas, lípidos, carbohidratos).",
        "Explica comportamientos exotérmicos/endotérmicos y el efecto de catalizadores en grupos orgánicos."
    ],
    51 => [ // Física 11 - Ondas
        "Clasifica ondas de luz y sonido según medio y dirección (longitudinal/transversal).",
        "Aplica leyes de reflexión, refracción y principio de Huygens para predecir comportamientos.",
        "Explica fenómenos de interferencia, difracción y polarización en casos prácticos.",
        "Relaciona tono, intensidad y color con longitud de onda y frecuencia."
    ],
    52 => [ // Física 11 - Electromagnetismo
        "Identifica cargas positivas/negativas por fricción o contacto.",
        "Diferencia fuerzas eléctricas/magnéticas de atracción y repulsión.",
        "Explica el funcionamiento técnico de un electroimán."
    ],
    53 => [ // Física 11 - Circuitos
        "Determina corriente y voltaje en circuitos resistivos usando Ley de Ohm.",
        "Identifica configuraciones serie, paralelo y mixtas en esquemas técnicos.",
        "Predice cambios de iluminación al alterar componentes de un circuito."
    ]
];

try {
    $db->beginTransaction();
    foreach($evidencias_11 as $id => $evs) {
        $extra = "\n\n**EVIDENCIAS DE APRENDIZAJE:**\n- " . implode("\n- ", $evs);
        $db->prepare("UPDATE ares_catalogo_aprendizajes SET enunciado = enunciado || ? WHERE id = ?")->execute([$extra, $id]);
    }
    $db->commit();
    echo "✅ Músculo Curricular inyectado con éxito en Grado 11º.\n";
} catch (Exception $e) {
    $db->rollBack();
    echo "❌ Error: " . $e->getMessage();
}
