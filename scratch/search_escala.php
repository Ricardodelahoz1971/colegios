<?php
declare(strict_types=1);

$dir = __DIR__ . '/../php';
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
$matches = [];

foreach ($it as $file) {
    if ($file->isDir()) continue;
    $content = file_get_contents($file->getPathname());
    if (stripos($content, 'eval_config_escala') !== false || stripos($content, 'nota_minima') !== false || stripos($content, 'nota_maxima') !== false) {
        $matches[] = $file->getPathname();
    }
}

echo "=== FILES REFERENCING ESCALA CONFIG ===\n";
foreach ($matches as $m) {
    echo "- " . realpath($m) . "\n";
}
