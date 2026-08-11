<?php
declare(strict_types=1);

$lines = file(__DIR__ . '/../styles/modules/dashboard_elite.css');
foreach ($lines as $i => $line) {
    if (strpos($line, 'perseus') !== false) {
        echo ($i + 1) . ": " . trim($line) . "\n";
    }
}
