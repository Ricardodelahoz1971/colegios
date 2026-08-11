<?php
declare(strict_types=1);

$lines = file(__DIR__ . '/../php/dashboard.php');
foreach ($lines as $i => $line) {
    if (stripos($line, 'perseus') !== false || stripos($line, 'cobertura') !== false) {
        echo ($i + 1) . ": " . trim($line) . "\n";
    }
}
