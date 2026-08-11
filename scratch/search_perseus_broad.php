<?php
declare(strict_types=1);

$filepath = __DIR__ . '/../js/modules/perseus_engine.js';
$lines = file($filepath);
foreach ($lines as $i => $line) {
    if (stripos($line, 'guardar') !== false || stripos($line, 'calificar') !== false || stripos($line, 'modal') !== false) {
        if (strpos($line, 'function') !== false || strpos($line, '=') !== false) {
            echo sprintf("Line %d: %s\n", $i + 1, trim($line));
        }
    }
}
