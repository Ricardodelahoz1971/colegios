<?php
declare(strict_types=1);

$filepath = __DIR__ . '/../js/modules/perseus_engine.js';
$lines = file($filepath);
for ($j = 330; $j < 450; $j++) {
    if (isset($lines[$j])) {
        echo ($j + 1) . ": " . $lines[$j];
    }
}
