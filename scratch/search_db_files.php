<?php
declare(strict_types=1);

$dir = __DIR__ . '/../';
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
foreach ($it as $file) {
    if ($file->isDir()) continue;
    if (pathinfo($file->getPathname(), PATHINFO_EXTENSION) === 'db') {
        echo "DB File: " . $file->getPathname() . " (Size: " . $file->getSize() . " bytes)\n";
    }
}
