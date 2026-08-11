<?php
declare(strict_types=1);

function search_in_file($filepath, $search) {
    echo "--- Search in $filepath for '$search' ---\n";
    $lines = file($filepath);
    foreach ($lines as $i => $line) {
        if (stripos($line, $search) !== false) {
            echo ($i + 1) . ": " . trim($line) . "\n";
        }
    }
}

search_in_file(__DIR__ . '/../php/dashboard.php', 'perseus');
search_in_file(__DIR__ . '/../php/dashboard.php', 'cobertura');
search_in_file(__DIR__ . '/../php/vistas/inicio.php', 'perseus');
search_in_file(__DIR__ . '/../php/vistas/inicio.php', 'cobertura');
