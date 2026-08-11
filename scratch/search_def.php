<?php
declare(strict_types=1);

$dir = __DIR__ . '/../php';
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
foreach ($it as $file) {
    if ($file->isDir()) continue;
    $content = file_get_contents($file->getPathname());
    if (stripos($content, 'function moduloAutorizadoMovil') !== false) {
        echo "Found definition in: " . $file->getPathname() . "\n";
    }
}
