<?php
declare(strict_types=1);

$dir = __DIR__ . '/../js';
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
$matches = [];

foreach ($it as $file) {
    if ($file->isDir()) continue;
    $content = file_get_contents($file->getPathname());
    if (stripos($content, 'guardarCalificacionFinal') !== false || stripos($content, 'calificacion_manual') !== false) {
        $matches[] = $file->getPathname();
    }
}

echo "=== JS FILES DEFINING SAVE FUNCTION ===\n";
foreach ($matches as $m) {
    echo "- " . realpath($m) . "\n";
}
