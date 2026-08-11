<?php
declare(strict_types=1);

$dir = __DIR__ . '/../styles';
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
foreach ($it as $file) {
    if ($file->isDir()) continue;
    $content = file_get_contents($file->getPathname());
    if (strpos($content, 'perseus-select-width') !== false) {
        echo "Found in: " . $file->getPathname() . "\n";
    }
}
