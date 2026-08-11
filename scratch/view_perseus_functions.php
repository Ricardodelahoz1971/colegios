<?php
declare(strict_types=1);

$filepath = __DIR__ . '/../js/modules/perseus_engine.js';
$lines = file($filepath);
$found = false;
$braces = 0;

foreach ($lines as $i => $line) {
    if (strpos($line, 'guardarCalificacionFinal') !== false || strpos($line, 'abrirModalCalificar') !== false) {
        echo "--- Found in Line " . ($i + 1) . ": " . trim($line) . " ---\n";
        // print next 40 lines
        for ($j = $i; $j < $i + 60; $j++) {
            if (isset($lines[$j])) {
                echo ($j + 1) . ": " . $lines[$j];
            }
        }
        echo "\n\n";
    }
}
