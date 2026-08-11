<?php
declare(strict_types=1);

$filepath = __DIR__ . '/../js/modules/academic_manager.js';
$lines = file($filepath);
foreach ($lines as $i => $line) {
    if (stripos($line, 'calificar') !== false || stripos($line, 'nota') !== false || stripos($line, 'examen') !== false || stripos($line, 'score') !== false) {
        if (strpos($line, '=') !== false || strpos($line, 'function') !== false || strpos($line, 'fetch') !== false) {
            echo sprintf("Line %d: %s\n", $i + 1, trim($line));
        }
    }
}
