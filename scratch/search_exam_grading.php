<?php
declare(strict_types=1);

$files = [
    __DIR__ . '/../php/logica/api_pruebas.php',
    __DIR__ . '/../php/vistas/calificar_pruebas.php'
];

foreach ($files as $f) {
    echo "=== FILE: " . basename($f) . " ===\n";
    $content = file_get_contents($f);
    $lines = file($f);
    foreach ($lines as $i => $line) {
        if (stripos($line, 'calific') !== false || stripos($line, 'nota') !== false || stripos($line, 'score') !== false || stripos($line, 'escala') !== false) {
            // Print line if it has assignment or calculation
            if (strpos($line, '=') !== false || strpos($line, '*') !== false || strpos($line, '/') !== false) {
                echo sprintf("L%d: %s\n", $i + 1, trim($line));
            }
        }
    }
    echo "\n";
}
