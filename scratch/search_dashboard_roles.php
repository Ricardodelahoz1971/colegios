<?php
declare(strict_types=1);

$lines = file(__DIR__ . '/../php/dashboard.php');
foreach ($lines as $i => $line) {
    if (stripos($line, 'rol_id') !== false || stripos($line, 'mi_rol') !== false) {
        echo ($i + 1) . ": " . trim($line) . "\n";
    }
}
