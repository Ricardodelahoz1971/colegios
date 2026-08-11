<?php
include_once __DIR__ . '/../php/db.php';

$evidencias_10 = [
    49 => [ // Química 10 - Inorgánica
        "Relaciona distribución electrónica con comportamiento químico y formación de compuestos.",
        "Balancea ecuaciones químicas considerando conservación de masa y carga.",
        "Aplica nomenclatura IUPAC a óxidos, ácidos, hidróxidos y sales.",
        "Explica cuantitativamente reacciones de óxido-reducción, neutralización y precipitación."
    ],
    47 => [ // Física 10 - Dinámica
        "Predice equilibrio de cuerpos usando la Primera Ley de Newton.",
        "Calcula aceleraciones mediante la relación fuerza/masa (Segunda Ley de Newton).",
        "Identifica pares de acción-reacción (Tercera Ley de Newton) en interacciones a distancia."
    ],
    48 => [ // Física 10 - Energía
        "Aplica el principio de conservación de energía mecánica en péndulos y caída libre.",
        "Analiza transformaciones de energía en sistemas no conservativos (fricción y choques)."
    ]
];

try {
    $db->beginTransaction();
    foreach($evidencias_10 as $id => $evs) {
        $extra = "\n\n**EVIDENCIAS DE APRENDIZAJE:**\n- " . implode("\n- ", $evs);
        $db->prepare("UPDATE ares_catalogo_aprendizajes SET enunciado = enunciado || ? WHERE id = ?")->execute([$extra, $id]);
    }
    $db->commit();
    echo "✅ Músculo Curricular inyectado con éxito en Grado 10º.\n";
} catch (Exception $e) {
    $db->rollBack();
    echo "❌ Error: " . $e->getMessage();
}
