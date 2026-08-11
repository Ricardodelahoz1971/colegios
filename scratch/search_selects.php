<?php
declare(strict_types=1);

$dir = __DIR__ . '/../php/vistas';
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
foreach ($it as $file) {
    if ($file->isDir()) continue;
    $content = file_get_contents($file->getPathname());
    if (stripos($content, '<select') !== false) {
        echo "File: " . $file->getPathname() . "\n";
        $lines = file($file->getPathname());
        foreach ($lines as $i => $line) {
            if (stripos($line, '<select') !== false || stripos($line, 'perseus-selector') !== false) {
                echo "  " . ($i + 1) . ": " . trim($line) . "\n";
            }
        }
    }
}
