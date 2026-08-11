<?php
require_once __DIR__ . '/../php/helpers_elite.php';

$casos = [
    '1A',          // Caso numérico estándar primaria
    '5B',          // Caso numérico estándar primaria
    'Once B',      // Caso textual secundaria (11º)
    'Décimo A',    // Caso textual secundaria con tilde (10º)
    'Transición',  // Caso preescolar (Textual)
    'Jardín B',    // Caso preescolar (Textual)
    'Curso Desconocido' // Fallback
];

echo "🧪 VERIFICACIÓN DEL NORMALIZADOR DE GRADOS (HELPER CENTRAL):\n";
foreach ($casos as $c) {
    $resultado = el_normalize_grade($c);
    echo "Curso: " . str_pad("'$c'", 20, " ") . " ➔ Normalizado MEN: '$resultado'\n";
}
