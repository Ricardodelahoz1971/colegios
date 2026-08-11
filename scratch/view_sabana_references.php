<?php
declare(strict_types=1);

function inspect_file(string $filepath, array $keywords) {
    echo "=== INSPECTING: " . basename($filepath) . " ===\n";
    $lines = file($filepath);
    foreach ($lines as $i => $line) {
        foreach ($keywords as $kw) {
            if (stripos($line, $kw) !== false) {
                echo sprintf("Line %d: %s", $i + 1, trim($line)) . "\n";
                break;
            }
        }
    }
    echo "\n";
}

inspect_file(__DIR__ . '/../php/logica/api_sabana.php', ['escala', 'nota_minima', 'nota_maxima']);
inspect_file(__DIR__ . '/../php/vistas/sabana_calificaciones.php', ['escala', 'nota_minima', 'nota_maxima']);
