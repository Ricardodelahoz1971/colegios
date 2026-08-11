<?php
declare(strict_types=1);

$dir = __DIR__ . '/../php';
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
foreach ($it as $file) {
    if ($file->isDir()) continue;
    $content = file_get_contents($file->getPathname());
    if (strpos($content, '17') !== false) {
        echo "Found 17 in: " . $file->getPathname() . "\n";
        $lines = file($file->getPathname());
        foreach ($lines as $i => $line) {
            if (strpos($line, '17') !== false) {
                echo "  " . ($i + 1) . ": " . trim($line) . "\n";
            }
        }
    }
}
