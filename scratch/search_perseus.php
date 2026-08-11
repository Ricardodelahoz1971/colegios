<?php
declare(strict_types=1);

$dir = __DIR__ . '/../php';
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
foreach ($it as $file) {
    if ($file->isDir()) continue;
    $content = file_get_contents($file->getPathname());
    if (stripos($content, 'perseus') !== false || stripos($content, 'cobertura') !== false) {
        echo "File: " . $file->getPathname() . "\n";
    }
}
